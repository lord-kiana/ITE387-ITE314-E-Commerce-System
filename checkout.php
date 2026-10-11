
<?php
require_once __DIR__ . '/db/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/product-image.php';

require_customer();

$error = '';
$orderId = null;
$selectedPaymentMethod = in_array($_POST['payment_method'] ?? 'cod', ['card', 'cod'], true)
    ? $_POST['payment_method']
    : 'cod';
$paymentConfirmation = '';

function checkout_card_number_is_valid(string $number): bool
{
    if (!preg_match('/^\d{13,19}$/', $number)) {
        return false;
    }

    $sum = 0;
    $doubleDigit = false;
    for ($index = strlen($number) - 1; $index >= 0; $index--) {
        $digit = (int) $number[$index];
        if ($doubleDigit) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        $sum += $digit;
        $doubleDigit = !$doubleDigit;
    }

    return $sum % 10 === 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $couponCode = strtoupper(trim($_POST['coupon_code'] ?? ''));
    $paymentMethod = (string) ($_POST['payment_method'] ?? '');

    try {
        if (!in_array($paymentMethod, ['card', 'cod'], true)) {
            throw new RuntimeException('Choose a payment method to continue.');
        }

        if ($paymentMethod === 'card') {
            $cardholder = trim((string) ($_POST['cardholder'] ?? ''));
            $cardNumber = preg_replace('/\D+/', '', (string) ($_POST['card_number'] ?? ''));
            $cardExpiry = trim((string) ($_POST['card_expiry'] ?? ''));
            $cardCvc = trim((string) ($_POST['card_cvc'] ?? ''));

            if ($cardholder === '' || !checkout_card_number_is_valid($cardNumber)) {
                throw new RuntimeException('Enter a cardholder name and a valid test card number.');
            }
            if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $cardExpiry, $expiryParts)) {
                throw new RuntimeException('Enter the card expiry as MM/YY.');
            }

            $expiryMonth = (int) $expiryParts[1];
            $expiryYear = 2000 + (int) $expiryParts[2];
            $currentYear = (int) date('Y');
            $currentMonth = (int) date('n');
            if ($expiryYear < $currentYear || ($expiryYear === $currentYear && $expiryMonth < $currentMonth)) {
                throw new RuntimeException('This test card has expired.');
            }
            if (!preg_match('/^\d{3,4}$/', $cardCvc)) {
                throw new RuntimeException('Enter a 3 or 4 digit security code.');
            }
        }

        $pdo->beginTransaction();

        // Lock the customer's cart so two checkouts cannot use it at once.
        $stmt = $pdo->prepare(
            'SELECT id FROM public.carts
             WHERE user_id = :uid FOR UPDATE'
        );
        $stmt->execute([':uid' => $_SESSION['user_id']]);
        $cartId = $stmt->fetchColumn();

        if (!$cartId) {
            throw new RuntimeException('Your cart is empty.');
        }

        // Lock product rows and read their latest stock and prices.
        $stmt = $pdo->prepare(
            'SELECT ci.product_id, ci.quantity,
                    p.name, p.price, p.stock
             FROM public.cart_items ci
             JOIN public.products p ON p.id = ci.product_id
             WHERE ci.cart_id = :cart
             ORDER BY p.id
             FOR UPDATE OF p'
        );
        $stmt->execute([':cart' => $cartId]);
        $items = $stmt->fetchAll();

        if (!$items) {
            throw new RuntimeException('Your cart is empty.');
        }

        $subtotal = 0.0;

        foreach ($items as $item) {
            if (
                (int) $item['quantity'] < 1 ||
                (int) $item['quantity'] > (int) $item['stock']
            ) {
                throw new RuntimeException(
                    'Insufficient stock for ' . $item['name'] .
                    '. Please update your cart.'
                );
            }

            $subtotal +=
                (float) $item['price'] * (int) $item['quantity'];
        }

        $subtotal = round($subtotal, 2);
        $coupon = null;
        $discount = 0.0;

        // Lock the coupon to serialize usage-limit checks.
        if ($couponCode !== '') {
            $stmt = $pdo->prepare(
                'SELECT * FROM public.coupons
                 WHERE code = :code
                 FOR UPDATE'
            );
            $stmt->execute([':code' => $couponCode]);
            $coupon = $stmt->fetch();

            if (
                !$coupon ||
                !$coupon['active'] ||
                strtotime($coupon['starts_at']) > time() ||
                (
                    $coupon['expires_at'] &&
                    strtotime($coupon['expires_at']) <= time()
                ) ||
                (
                    $coupon['usage_limit'] !== null &&
                    (int) $coupon['times_used'] >=
                    (int) $coupon['usage_limit']
                )
            ) {
                throw new RuntimeException(
                    'Coupon is invalid, expired or fully used.'
                );
            }

            $stmt = $pdo->prepare(
                'SELECT COUNT(*)
                 FROM public.coupon_redemptions
                 WHERE coupon_id = :cid AND user_id = :uid'
            );
            $stmt->execute([
                ':cid' => $coupon['id'],
                ':uid' => $_SESSION['user_id'],
            ]);

            if (
                (int) $stmt->fetchColumn() >=
                (int) $coupon['per_user_limit']
            ) {
                throw new RuntimeException(
                    'You have reached this coupon’s usage limit.'
                );
            }

            if ($subtotal < (float) $coupon['min_order_amount']) {
                throw new RuntimeException(
                    'Your order does not meet the coupon minimum.'
                );
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
        }

        $total = max(0, round($subtotal - $discount, 2));

        // Create the order.
        $stmt = $pdo->prepare(
            'INSERT INTO public.orders
                (user_id, status, subtotal, discount_amount,
                 total, coupon_id)
             VALUES
                (:uid, :status, :subtotal, :discount, :total, :coupon)
             RETURNING id'
        );

        $stmt->execute([
            ':uid' => $_SESSION['user_id'],
            ':status' => 'placed',
            ':subtotal' => $subtotal,
            ':discount' => $discount,
            ':total' => $total,
            ':coupon' => $coupon['id'] ?? null,
        ]);

        $orderId = (int) $stmt->fetchColumn();

        foreach ($items as $item) {
            // PostgreSQL stored function performs a conditional deduction.
            $stmt = $pdo->prepare(
                'SELECT public.deduct_inventory(
                    CAST(:pid AS BIGINT),
                    CAST(:qty AS INTEGER)
                )'
            );

            $stmt->execute([
                ':pid' => $item['product_id'],
                ':qty' => $item['quantity'],
            ]);

            $deducted = $stmt->fetchColumn();

            if (!in_array($deducted, [true, 1, '1', 't'], true)) {
                throw new RuntimeException(
                    'Stock deduction failed for ' . $item['name'] . '.'
                );
            }

            // Preserve the product name and price at purchase time.
            $stmt = $pdo->prepare(
                'INSERT INTO public.order_items
                    (order_id, product_id, product_name,
                     unit_price, quantity, line_total)
                 VALUES
                    (:oid, :pid, :name, :price, :qty, :line)'
            );

            $stmt->execute([
                ':oid' => $orderId,
                ':pid' => $item['product_id'],
                ':name' => $item['name'],
                ':price' => $item['price'],
                ':qty' => $item['quantity'],
                ':line' => round(
                    (float) $item['price'] * (int) $item['quantity'],
                    2
                ),
            ]);
        }

        if ($coupon) {
            $stmt = $pdo->prepare(
                'UPDATE public.coupons
                 SET times_used = times_used + 1
                 WHERE id = :id'
            );
            $stmt->execute([':id' => $coupon['id']]);

            $stmt = $pdo->prepare(
                'INSERT INTO public.coupon_redemptions
                    (coupon_id, user_id, order_id, discount_amount)
                 VALUES (:cid, :uid, :oid, :discount)'
            );
            $stmt->execute([
                ':cid' => $coupon['id'],
                ':uid' => $_SESSION['user_id'],
                ':oid' => $orderId,
                ':discount' => $discount,
            ]);
        }

        // Clear the cart only after all order operations succeed.
        $stmt = $pdo->prepare(
            'DELETE FROM public.cart_items WHERE cart_id = :cart'
        );
        $stmt->execute([':cart' => $cartId]);

        $pdo->commit();
        $paymentConfirmation = $paymentMethod === 'card'
            ? 'Demo card checkout completed. No card details were stored and no payment was collected.'
            : 'Cash on delivery selected. Payment is due when your order arrives.';

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $orderId = null;

        $error = $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Checkout failed. Please try again.';

        if (!($e instanceof RuntimeException)) {
            error_log('Checkout error: ' . $e->getMessage());
        }
    }
}

// Get the current cart for display.
$stmt = $pdo->prepare(
    'SELECT p.name, p.price, p.image, ci.quantity
     FROM public.carts c
     JOIN public.cart_items ci ON ci.cart_id = c.id
     JOIN public.products p ON p.id = ci.product_id
     WHERE c.user_id = :uid'
);
$stmt->execute([':uid' => $_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

$displaySubtotal = 0.0;

foreach ($cartItems as $item) {
    $displaySubtotal +=
        (float) $item['price'] * (int) $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout | ShopFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link rel="stylesheet" href="CSS/styles.css">
</head>
<body>
<?php require __DIR__ . '/includes/navbar.php'; ?>
<main class="container checkout-page" style="max-width: 900px">
    <div class="page-heading">
        <div><p class="cart-eyebrow">ALMOST THERE</p><h1>Checkout</h1><p class="text-secondary mb-0">Confirm your order details below.</p></div>
        <a href="cart.php" class="btn btn-outline-primary">Back to cart</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger mt-3">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($orderId): ?>
        <div class="alert alert-success mt-4">
            <h2 class="h5">Order placed successfully!</h2>
            Your order number is
            <strong>#<?= (int) $orderId ?></strong>.
            <p class="mb-2 mt-2"><?= h($paymentConfirmation) ?></p>
            <a href="orders.php">View Order History</a>
        </div>

    <?php elseif ($cartItems): ?>
        <div class="card checkout-card mt-4">
            <div class="card-body">
                <h2 class="h5">Order Summary</h2>

                <?php foreach ($cartItems as $item): ?>
                    <?php $checkoutImage = product_image_url($item); ?>
                    <div class="d-flex justify-content-between align-items-center py-2 checkout-item-row">
                        <span class="checkout-item-description">
                            <img src="<?= h($checkoutImage) ?>" alt="" onerror="this.onerror=null;this.src='assets/product-placeholder.svg'">
                            <?= h($item['name']) ?>
                            x <?= (int) $item['quantity'] ?>
                        </span>
                        <span>
                            &#8369;<?= number_format(
                                (float) $item['price'] *
                                (int) $item['quantity'],
                                2
                            ) ?>
                        </span>
                    </div>
                <?php endforeach; ?>

                <hr>

                <p class="d-flex justify-content-between">
                    <strong>Subtotal</strong>
                    <strong>
                        &#8369;<?= number_format($displaySubtotal, 2) ?>
                    </strong>
                </p>

                <form method="post">
                    <input type="hidden" name="csrf_token"
                           value="<?= h(csrf_token()) ?>">

                    <div class="mb-3">
                        <label class="form-label" for="coupon_code">
                            Coupon Code (Optional)
                        </label>
                        <input class="form-control" id="coupon_code"
                               name="coupon_code" maxlength="50"
                               placeholder="WELCOME10">
                    </div>

                    <p class="small text-muted">
                        The coupon will be checked again when the order
                        is submitted.
                    </p>

                    <fieldset class="payment-methods mb-4">
                        <legend class="form-label">Payment method</legend>
                        <label class="payment-option" for="payment-card">
                            <input type="radio" name="payment_method" id="payment-card" value="card" <?= $selectedPaymentMethod === 'card' ? 'checked' : '' ?>>
                            <span class="payment-option-icon" aria-hidden="true">CC</span>
                            <span class="payment-option-copy"><strong>Credit or debit card</strong><small>Visa, Mastercard, or other major cards</small></span>
                            <span class="payment-card-brands" aria-label="Visa and Mastercard accepted"><b>VISA</b><b>MC</b></span>
                        </label>
                        <label class="payment-option" for="payment-cod">
                            <input type="radio" name="payment_method" id="payment-cod" value="cod" <?= $selectedPaymentMethod === 'cod' ? 'checked' : '' ?>>
                            <span class="payment-option-icon" aria-hidden="true">&#8369;</span>
                            <span class="payment-option-copy"><strong>Cash on delivery</strong><small>Pay when your order arrives</small></span>
                        </label>
                        <div class="payment-demo-note" role="note">Demo checkout: card payments are simulated. Do not enter real card details; nothing is charged or stored.</div>
                        <div class="card-entry-panel" id="card-entry-panel" <?= $selectedPaymentMethod === 'card' ? '' : 'hidden' ?>>
                            <div class="mb-3"><label class="form-label" for="cardholder">Name on card</label><input class="form-control" id="cardholder" name="cardholder" autocomplete="off" placeholder="Alex Morgan" disabled></div>
                            <div class="mb-3"><label class="form-label" for="card_number">Card number</label><input class="form-control" id="card_number" name="card_number" inputmode="numeric" autocomplete="off" placeholder="4242 4242 4242 4242" maxlength="23" disabled></div>
                            <div class="row g-3">
                                <div class="col-6"><label class="form-label" for="card_expiry">Expiry date</label><input class="form-control" id="card_expiry" name="card_expiry" inputmode="numeric" autocomplete="off" placeholder="MM/YY" maxlength="5" disabled></div>
                                <div class="col-6"><label class="form-label" for="card_cvc">Security code</label><input class="form-control" id="card_cvc" name="card_cvc" type="password" inputmode="numeric" autocomplete="off" placeholder="CVC" maxlength="4" disabled></div>
                            </div>
                            <p class="payment-test-hint">Use demo card 4242 4242 4242 4242, any future expiry, and a 3 digit code.</p>
                        </div>
                    </fieldset>

                    <button class="btn btn-primary w-100 checkout-submit" type="submit">
                        <span class="checkout-submit-label"><?= $selectedPaymentMethod === 'card' ? 'Pay securely' : 'Place order' ?></span>
                    </button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <p class="mt-4">
            Your cart is empty. <a href="index.php">Browse products</a>
        </p>
    <?php endif; ?>
</main>
<script src="JS/checkout-payment.js"></script>
</body>
</html>
