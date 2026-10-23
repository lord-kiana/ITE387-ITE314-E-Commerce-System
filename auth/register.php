<?php
require_once __DIR__ . '/../DB/database.php';
require_once __DIR__ . '/../includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (
        $name === '' ||
        strlen($name) > 100 ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 150 ||
        strlen($password) < 8
    ) {
        $error = 'Enter a valid name and email. Password must be at least 8 characters.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO public.users
                    (name, email, password, role)
                 VALUES (:name, :email, :password, :role)'
            );

            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':password' => password_hash(
                    $password,
                    PASSWORD_DEFAULT
                ),
                ':role' => 'customer',
            ]);

            header('Location: login.php?registered=1');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                $error = 'That email is already registered.';
            } else {
                error_log($e->getMessage());
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | ShopFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link rel="stylesheet" href="../CSS/styles.css">
</head>
<body class="bg-light">
<?php require __DIR__ . '/../includes/navbar.php'; ?>
<main class="container py-5 auth-page" style="max-width: 520px">
    <div class="auth-visual"><img src="../assets/shopflow-hero.svg" alt="A green ShopFlow shopping bag illustration"></div>
    <p class="cart-eyebrow">JOIN SHOPFLOW</p>
    <h1 class="mb-4">Create Account</h1>
    <p class="auth-intro">Create an account to save your cart and track orders.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token"
               value="<?= h(csrf_token()) ?>">

        <div class="mb-3">
            <label class="form-label" for="name">Full Name</label>
            <input class="form-control" id="name" name="name"
                   maxlength="100" required
                   value="<?= h($_POST['name'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input class="form-control" id="email" name="email"
                   type="email" maxlength="150" required
                   value="<?= h($_POST['email'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">
                Password (minimum 8 characters)
            </label>
            <input class="form-control" id="password" name="password"
                   type="password" minlength="8" required>
        </div>

        <button class="btn btn-primary w-100">Register</button>
    </form>

    <p class="mt-3">
        Already registered? <a href="/auth/login.php">Log in</a>
    </p>
</main>
</body>
</html>
