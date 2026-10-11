
<?php
require_once __DIR__ . '/db/database.php';

$error = '';
$buyId = filter_input(INPUT_GET, 'buy', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $buyId = filter_input(INPUT_POST, 'buy', FILTER_VALIDATE_INT);
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Please enter a valid email and password.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT id, name, email, password, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            $destination = 'index.php';

            if ($buyId !== false && $buyId !== null && $buyId > 0) {
                $destination .= '?buy=' . $buyId;
            }

            header('Location: ' . $destination);
            exit;
        } else {
            $error = 'Incorrect email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | ShopFlow</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">

<div class="container min-vh-100 d-flex align-items-center justify-content-center py-5">

    <div class="row login-wrapper shadow-lg overflow-hidden">

        <div class="col-md-6 login-banner d-flex flex-column justify-content-center p-5">
            <a href="index.php" class="text-white text-decoration-none fw-bold fs-4 mb-4">
                ShopFlow
            </a>

            <h1 class="fw-bold display-5">Shopping starts here.</h1>

            <p class="mt-3">
                Sign in to continue shopping, place orders, and keep track
                of your purchases.
            </p>

            <div class="login-illustration">🛍️</div>

            <a href="index.php" class="text-white mt-4">
                ← Continue browsing products
            </a>
        </div>

        <div class="col-md-6 bg-white p-4 p-lg-5 d-flex flex-column justify-content-center">

            <h2 class="fw-bold mb-2">Welcome back!</h2>

            <p class="text-secondary mb-4">
                Enter your account details to sign in.
            </p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['buy'])): ?>
                <div class="alert alert-info">
                    Please log in to continue with your purchase.
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <input
                    type="hidden"
                    name="buy"
                    value="<?= $buyId && $buyId > 0 ? (int)$buyId : '' ?>">

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">
                        Email Address
                    </label>

                    <input
                        type="email"
                        class="form-control form-control-lg"
                        id="email"
                        name="email"
                        placeholder="you@example.com"
                        autocomplete="email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        required>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">
                        Password
                    </label>

                    <input
                        type="password"
                        class="form-control form-control-lg"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100">
                    Sign In
                </button>
            </form>

            <p class="text-center mt-4 mb-0">
                Don't have an account?
                <a href="register.php" class="fw-semibold text-decoration-none">
                    Create one
                </a>
            </p>

        </div>
    </div>
</div>

</body>
</html>