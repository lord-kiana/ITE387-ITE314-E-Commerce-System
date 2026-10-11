
<?php
require_once __DIR__ . '/db/database.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

verify_csrf();

$productId = filter_input(
    INPUT_POST,
    'product_id',
    FILTER_VALIDATE_INT
);

$quantity = filter_input(
    INPUT_POST,
    'quantity',
    FILTER_VALIDATE_INT
);

$quantity = $quantity ?: 1;

if (!$productId || $quantity < 1 || $quantity > 99) {
    $_SESSION['cart_error'] = 'Invalid product or quantity.';
    header('Location: index.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT id, stock
         FROM public.products
         WHERE id = :id
         FOR UPDATE'
    );
    $stmt->execute([':id' => $productId]);
    $product = $stmt->fetch();

    if (!$product || (int) $product['stock'] < $quantity) {
        throw new RuntimeException('Not enough stock available.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO public.carts (user_id)
         VALUES (:uid)
         ON CONFLICT (user_id) DO NOTHING'
    );
    $stmt->execute([':uid' => $_SESSION['user_id']]);

    $stmt = $pdo->prepare(
        'SELECT id FROM public.carts WHERE user_id = :uid'
    );
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $cartId = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        'SELECT quantity
         FROM public.cart_items
         WHERE cart_id = :cart AND product_id = :product'
    );
    $stmt->execute([
        ':cart' => $cartId,
        ':product' => $productId,
    ]);

    $existingQuantity = (int) ($stmt->fetchColumn() ?: 0);

    if ($existingQuantity + $quantity > (int) $product['stock']) {
        throw new RuntimeException(
            'Your cart quantity would exceed available stock.'
        );
    }

    $stmt = $pdo->prepare(
        'INSERT INTO public.cart_items
            (cart_id, product_id, quantity)
         VALUES (:cart, :product, :quantity)
         ON CONFLICT (cart_id, product_id)
         DO UPDATE SET
            quantity = public.cart_items.quantity + EXCLUDED.quantity,
            updated_at = NOW()'
    );

    $stmt->execute([
        ':cart' => $cartId,
        ':product' => $productId,
        ':quantity' => $quantity,
    ]);

    $pdo->commit();

    header('Location: cart.php');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['cart_error'] = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'Could not add this item to your cart.';

    if (!($e instanceof RuntimeException)) {
        error_log($e->getMessage());
    }

    header('Location: index.php');
    exit;
}