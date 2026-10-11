
<?php
require_once __DIR__ . '/db/database.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

$stmt = $pdo->prepare(
    'SELECT
        ci.id AS cart_item_id,
        ci.quantity,
        p.id AS product_id,
        p.name,
        p.price,
        p.stock,
        p.image,
        p.price * ci.quantity AS line_total
     FROM public.carts c
     JOIN public.cart_items ci ON ci.cart_id = c.id
     JOIN public.products p ON p.id = ci.product_id
     WHERE c.user_id = :uid
     ORDER BY ci.id DESC'
);

$stmt->execute([':uid' => $_SESSION['user_id']]);
$items = $stmt->fetchAll();

$subtotal = 0;

foreach ($items as $item) {
    $subtotal += (float) $item['line_total'];
}

$message = $_SESSION['cart_error'] ?? '';
unset($_SESSION['cart_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Cart | ShopFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
</head>
<body>
<main class="container py-5">
    <div class="d-flex justify-content-between mb-4">
        <h1>Your Cart</h1>
        <a href="index.php">Continue Shopping</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-warning"><?= h($message) ?></div>
    <?php endif; ?>

    <?php if (!$items): ?>
        <p>Your cart is empty.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <?= h($item['name']) ?>
                            <div class="small text-muted">
                                Available stock: <?= (int) $item['stock'] ?>
                            </div>
                        </td>
                        <td>₱<?= number_format((float) $item['price'], 2) ?></td>
                        <td>
                            <form method="post" action="cart_update.php"
                                  class="d-flex gap-2">
                                <input type="hidden" name="csrf_token"
                                       value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="cart_item_id"
                                       value="<?= (int) $item['cart_item_id'] ?>">
                                <input class="form-control" style="width: 90px"
                                       type="number" name="quantity"
                                       min="0" max="<?= (int) $item['stock'] ?>"
                                       value="<?= (int) $item['quantity'] ?>"
                                       required>
                                <button class="btn btn-outline-primary"
                                        name="action" value="update">
                                    Update
                                </button>
                            </form>
                        </td>
                        <td>
                            ₱<?= number_format((float) $item['line_total'], 2) ?>
                        </td>
                        <td>
                            <form method="post" action="cart_update.php">
                                <input type="hidden" name="csrf_token"
                                       value="<?= h(csrf_token()) ?>">
                                <input type="hidden" name="cart_item_id"
                                       value="<?= (int) $item['cart_item_id'] ?>">
                                <button class="btn btn-outline-danger btn-sm"
                                        name="action" value="remove">
                                    Remove
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="text-end">
            <h3>Subtotal: ₱<?= number_format($subtotal, 2) ?></h3>
            <a href="checkout.php" class="btn btn-primary">
                Proceed to Checkout
            </a>
        </div>
    <?php endif; ?>
</main>
</body>
</html>