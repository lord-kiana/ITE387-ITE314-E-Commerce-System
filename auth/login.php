
<?php
require_once __DIR__ . '/../db/database.php';
require_once __DIR__ . '/../includes/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        'SELECT id, name, email, password, role
         FROM public.users
         WHERE email = :email
         LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        $stmt = $pdo->prepare(
            'INSERT INTO public.carts (user_id)
             VALUES (:uid)
             ON CONFLICT (user_id) DO NOTHING'
        );
        $stmt->execute([':uid' => $user['id']]);

        header('Location: ../index.php');
        exit;
    }

    $error = 'Incorrect email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | ShopFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <link rel="stylesheet" href="../CSS/styles.css">
</head>
<body class="bg-light">
<?php require __DIR__ . '/../includes/navbar.php'; ?>
<main class="container py-5 auth-page" style="max-width: 520px">
    <div class="auth-visual"><img src="../assets/shopflow-hero.svg" alt="A green ShopFlow shopping bag illustration"></div>
    <p class="cart-eyebrow">WELCOME BACK</p>
    <h1 class="mb-4">Log In</h1>
    <p class="auth-intro">Sign in to pick up where you left off.</p>

    <?php if (isset($_GET['registered'])): ?>
        <div class="alert alert-success">
            Account created. You can now log in.
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token"
               value="<?= h(csrf_token()) ?>">

        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input class="form-control" id="email" name="email"
                   type="email" required>
        </div>

        <div class="mb-3">
            <label class="form-label" for="password">Password</label>
            <input class="form-control" id="password" name="password"
                   type="password" required>
        </div>

        <button class="btn btn-primary w-100">Log In</button>
    </form>

    <p class="mt-3">
        New here? <a href="/auth/register.php">Create an account</a>
    </p>
</main>
</body>
</html>
