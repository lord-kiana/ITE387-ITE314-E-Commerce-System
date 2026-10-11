
<?php
require_once __DIR__ . '/db/database.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'valid' => false,
        'message' => 'Please log in first.'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'valid' => false,
        'message' => 'Method not allowed.'
    ]);
    exit;
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(419);
    echo json_encode([
        'valid' => false,
        'message' => 'Invalid form token.'
    ]);
    exit;
}

$code = strtoupper(trim($_POST['code'] ?? ''));

$stmt = $pdo->prepare(
    "SELECT c.*,
        (
            SELECT COUNT(*)
            FROM public.coupon_redemptions r
            WHERE r.coupon_id = c.id
              AND r.user_id = :uid
        ) AS user_uses
     FROM public.coupons c
     WHERE c.code = :code
       AND c.active = TRUE
       AND c.starts_at <= NOW()
       AND (c.expires_at IS NULL OR c.expires_at > NOW())
       AND (c.usage_limit IS NULL OR c.times_used < c.usage_limit)"
);

$stmt->execute([
    ':uid' => $_SESSION['user_id'],
    ':code' => $code
]);

$coupon = $stmt->fetch();

if (!$coupon) {
    echo json_encode([
        'valid' => false,
        'message' => 'Invalid, expired or fully used coupon.'
    ]);
    exit;
}

if ((int) $coupon['user_uses'] >= (int) $coupon['per_user_limit']) {
    echo json_encode([
        'valid' => false,
        'message' => 'You have reached this coupon’s usage limit.'
    ]);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT COALESCE(SUM(p.price * ci.quantity), 0)
     FROM public.carts c
     JOIN public.cart_items ci ON ci.cart_id = c.id
     JOIN public.products p ON p.id = ci.product_id
     WHERE c.user_id = :uid'
);

$stmt->execute([':uid' => $_SESSION['user_id']]);
$subtotal = (float) $stmt->fetchColumn();

if ($subtotal < (float) $coupon['min_order_amount']) {
    echo json_encode([
        'valid' => false,
        'message' => 'Your order does not meet the minimum amount.'
    ]);
    exit;
}

if ($coupon['discount_type'] === 'percent') {
    $discount = $subtotal *
        ((float) $coupon['discount_value'] / 100);
} else {
    $discount = (float) $coupon['discount_value'];
}

if ($coupon['max_discount'] !== null) {
    $discount = min(
        $discount,
        (float) $coupon['max_discount']
    );
}

$discount = min($subtotal, round($discount, 2));

echo json_encode([
    'valid' => true,
    'message' => 'Coupon applied successfully.',
    'discount' => number_format($discount, 2, '.', ''),
    'total' => number_format(
        max(0, $subtotal - $discount),
        2,
        '.',
        ''
    )
]);