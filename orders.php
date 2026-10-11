
<?php
require_once __DIR__ . '/db/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/product-image.php';

require_customer();

$stmt = $pdo->prepare(
    'SELECT id, status, subtotal, discount_amount, total, created_at
     FROM public.orders
     WHERE user_id = :uid
     ORDER BY created_at DESC'
);

$stmt->execute([':uid' => $_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order History | ShopFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link rel="stylesheet" href="CSS/styles.css">
</head>
<body>
<?php require __DIR__ . '/includes/navbar.php'; ?>
<main class="container orders-page">
    <div class="page-heading">
        <div><p class="cart-eyebrow">YOUR ACCOUNT</p><h1>Order history</h1><p class="text-secondary mb-0">A look back at everything you’ve ordered.</p></div>
        <a href="index.php" class="btn btn-outline-primary">Continue shopping</a>
    </div>

    <?php if (!$orders): ?>
        <section class="orders-empty"><img src="assets/product-placeholder.svg" alt=""><h2>No orders yet</h2><p>Your orders will show up here after checkout.</p><a href="index.php" class="btn btn-primary">Browse products</a></section>
    <?php endif; ?>

    <?php foreach ($orders as $order): ?>
        <section class="card order-card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <h2 class="h5 order-number">
                        Order #<?= (int) $order['id'] ?>
                    </h2>
                    <span class="badge text-bg-secondary">
                        <?= h(ucfirst($order['status'])) ?>
                    </span>
                </div>

                <p class="text-muted order-date">
                    <?= h($order['created_at']) ?>
                </p>

                <?php
                $stmt = $pdo->prepare(
                    'SELECT oi.product_name, oi.unit_price,
                            oi.quantity, oi.line_total, p.image
                     FROM public.order_items oi
                     LEFT JOIN public.products p ON p.id = oi.product_id
                     WHERE oi.order_id = :oid
                     ORDER BY oi.id'
                );

                $stmt->execute([':oid' => $order['id']]);
                $orderItems = $stmt->fetchAll();
                ?>

                <ul>
                    <?php foreach ($orderItems as $item): ?>
                        <?php $orderImage = product_image_url($item); ?>
                        <li class="order-item-row">
                            <img src="<?= h($orderImage) ?>" alt="" onerror="this.onerror=null;this.src='assets/product-placeholder.svg'">
                            <span><?= h($item['product_name']) ?><small>Qty <?= (int) $item['quantity'] ?></small></span>
                            <strong>&#8369;<?= number_format((float) $item['line_total'], 2) ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <hr>

                <div class="text-end">
                    <p>
                        Subtotal: &#8369;<?= number_format(
                            (float) $order['subtotal'],
                            2
                        ) ?>
                    </p>
                    <p>
                        Discount: -&#8369;<?= number_format(
                            (float) $order['discount_amount'],
                            2
                        ) ?>
                    </p>
                    <h3 class="h5">
                        Total: &#8369;<?= number_format(
                            (float) $order['total'],
                            2
                        ) ?>
                    </h3>
                </div>
            </div>
        </section>
    <?php endforeach; ?>
</main>
</body>
</html>
