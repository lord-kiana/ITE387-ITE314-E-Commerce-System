<?php
require_once __DIR__ . '/DB/database.php';
require_once __DIR__ . '/includes/auth.php';

require_customer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

verify_csrf();

$itemId = filter_input(
    INPUT_POST,
    'cart_item_id',
    FILTER_VALIDATE_INT
);

$quantity = filter_input(
    INPUT_POST,
    'quantity',
    FILTER_VALIDATE_INT
);

$action = $_POST['action'] ?? 'update';

$stmt = $pdo->prepare(
    'SELECT ci.id, p.stock
     FROM public.cart_items ci
     JOIN public.carts c ON c.id = ci.cart_id
     JOIN public.products p ON p.id = ci.product_id
     WHERE ci.id = :id AND c.user_id = :uid'
);

$stmt->execute([
    ':id' => $itemId,
    ':uid' => $_SESSION['user_id'],
]);

$item = $stmt->fetch();

if (!$item) {
    header('Location: cart.php');
    exit;
}

if ($action === 'remove') {
    $stmt = $pdo->prepare(
        'DELETE FROM public.cart_items WHERE id = :id'
    );
    $stmt->execute([':id' => $itemId]);

} elseif (
    $quantity !== false &&
    $quantity !== null &&
    $quantity >= 0 &&
    $quantity <= 99 &&
    $quantity <= (int) $item['stock']
) {
    if ($quantity === 0) {
        $stmt = $pdo->prepare(
            'DELETE FROM public.cart_items WHERE id = :id'
        );
        $stmt->execute([':id' => $itemId]);
    } else {
        $stmt = $pdo->prepare(
            'UPDATE public.cart_items
             SET quantity = :quantity, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            ':quantity' => $quantity,
            ':id' => $itemId,
        ]);
    }
} else {
    $_SESSION['cart_error'] =
        'Quantity exceeds available stock or is invalid.';
}

header('Location: cart.php');
exit;
