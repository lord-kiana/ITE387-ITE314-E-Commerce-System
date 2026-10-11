
<?php
require_once __DIR__ . '/db/database.php';
require_once __DIR__ . '/includes/auth.php';

require_login();

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
</head>
<body>
<main class="container py-5">
    <div class="d-flex justify-content-between mb-4">
        <h1>Order History</h1>
        <a href="index.php">ShopFlow</a>
    </div>

    <?php if (!$orders): ?>
        <p>You have not placed any orders yet.</p>
    <?php endif; ?>

    <?php foreach ($orders as $order): ?>
        <section class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <h2 class="h5">
                        Order #<?= (int) $order['id'] ?>
                    </h2>
                    <span class="badge text-bg-secondary">
                        <?= h(ucfirst($order['status'])) ?>
                    </span>
                </div>

                <p class="text-muted">
                    <?= h($order['created_at']) ?>
                </p>

                <?php
                $stmt = $pdo->prepare(
                    'SELECT product_name, unit_price,
                            quantity, line_total
                     FROM public.order_items
                     WHERE order_id = :oid
                     ORDER BY id'
                );

                $stmt->execute([':oid' => $order['id']]);
                $orderItems = $stmt->fetchAll();
                ?>

                <ul>
                    <?php foreach ($orderItems as $item): ?>
                        <li>
                            <?= h($item['product_name']) ?>
                            — <?= (int) $item['quantity'] ?> ×
                            ₱<?= number_format(
                                (float) $item['unit_price'],
                                2
                            ) ?>
                            = ₱<?= number_format(
                                (float) $item['line_total'],
                                2
                            ) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <hr>

                <div class="text-end">
                    <p>
                        Subtotal: ₱<?= number_format(
                            (float) $order['subtotal'],
                            2
                        ) ?>
                    </p>
                    <p>
                        Discount: −₱<?= number_format(
                            (float) $order['discount_amount'],
                            2
                        ) ?>
                    </p>
                    <h3 class="h5">
                        Total: ₱<?= number_format(
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