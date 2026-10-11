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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/styles.css">
</head>
<body>
<?php require __DIR__ . '/includes/navbar.php'; ?>
<main class="container cart-page">
    <div class="cart-heading">
        <div>
            <p class="cart-eyebrow">YOUR SHOPPING BAG</p>
            <h1>Your cart <span>(<?= count($items) ?> <?= count($items) === 1 ? 'item' : 'items' ?>)</span></h1>
            <p class="text-secondary mb-0">Review your picks before you check out.</p>
        </div>
        <a href="index.php" class="cart-continue">← Continue shopping</a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-warning"><?= h($message) ?></div>
    <?php endif; ?>

    <?php if (!$items): ?>
        <section class="cart-empty">
            <img src="assets/product-placeholder.svg" alt="" width="190" height="135">
            <h2>Your cart is waiting for something good</h2>
            <p class="text-secondary">Take a look around and add a few favorites.</p>
            <a href="index.php" class="btn btn-primary">Explore products</a>
        </section>
    <?php else: ?>
        <div class="row g-4 align-items-start">
            <section class="col-lg-8" aria-label="Cart items">
                <div class="cart-items">
                    <?php foreach ($items as $item): ?>
                        <article class="cart-item">
                            <img class="cart-item-image"
                                 src="<?= !empty($item['image']) ? h($item['image']) : 'assets/product-placeholder.svg' ?>"
                                 alt="<?= h($item['name']) ?>"
                                 onerror="this.onerror=null;this.src='assets/product-placeholder.svg'">
                            <div class="cart-item-info">
                                <div class="cart-item-title-row">
                                    <div>
                                        <h2><?= h($item['name']) ?></h2>
                                        <p class="cart-stock"><span></span><?= (int) $item['stock'] ?> available</p>
                                    </div>
                                    <strong class="cart-line-total">₱<?= number_format((float) $item['line_total'], 2) ?></strong>
                                </div>
                                <p class="cart-unit-price">₱<?= number_format((float) $item['price'], 2) ?> <span>per item</span></p>
                                <div class="cart-item-actions">
                                    <form method="post" action="cart_update.php" class="cart-quantity-form">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                        <input type="hidden" name="cart_item_id" value="<?= (int) $item['cart_item_id'] ?>">
                                        <label class="visually-hidden" for="quantity-<?= (int) $item['cart_item_id'] ?>">Quantity for <?= h($item['name']) ?></label>
                                        <input class="form-control" id="quantity-<?= (int) $item['cart_item_id'] ?>" type="number" name="quantity"
                                               min="0" max="<?= (int) $item['stock'] ?>" value="<?= (int) $item['quantity'] ?>" required>
                                        <button class="btn btn-outline-primary" name="action" value="update">Update</button>
                                    </form>
                                    <form method="post" action="cart_update.php">
                                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                                        <input type="hidden" name="cart_item_id" value="<?= (int) $item['cart_item_id'] ?>">
                                        <button class="cart-remove" name="action" value="remove">Remove</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <a href="index.php" class="cart-back-link">← Keep exploring the shop</a>
            </section>

            <aside class="col-lg-4">
                <div class="cart-summary">
                    <h2>Order summary</h2>
                    <div class="cart-summary-line"><span>Subtotal</span><strong>₱<?= number_format($subtotal, 2) ?></strong></div>
                    <div class="cart-summary-line cart-shipping"><span>Shipping</span><span>Calculated at checkout</span></div>
                    <div class="cart-summary-total"><span>Estimated total</span><strong>₱<?= number_format($subtotal, 2) ?></strong></div>
                    <p class="cart-summary-note">Any available discounts will be applied at checkout.</p>
                    <a href="checkout.php" class="btn btn-primary w-100 cart-checkout">Continue to checkout <span>→</span></a>
                    <div class="cart-secure-note"><span aria-hidden="true">✓</span> Secure checkout</div>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
