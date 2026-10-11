
<?php
require_once 'database.php';

$stmt = $pdo->query(
    "SELECT id, name, description, price, image, stock
     FROM products
     ORDER BY created_at DESC"
);

$products = $stmt->fetchAll();

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ShopFlow | Online Store</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">ShopFlow</a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#navbarMenu"
                aria-controls="navbarMenu" aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMenu">
            <div class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <a class="nav-link active" href="index.php">Products</a>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <span class="navbar-text text-white">
                        Hello, <?= e($_SESSION['user_name']) ?>
                    </span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">
                        Logout
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-light btn-sm">
                        Login
                    </a>
                    <a href="register.php" class="btn btn-primary btn-sm">
                        Create Account
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<header class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <p class="text-primary fw-bold mb-2">WELCOME TO SHOPFLOW</p>
                <h1 class="display-4 fw-bold">Find your next favorite product.</h1>
                <p class="lead text-secondary">
                    Discover quality products at prices you'll love.
                    Browse freely and sign in when you're ready to buy.
                </p>
                <a href="#products" class="btn btn-primary btn-lg mt-2">
                    Shop Now
                </a>
            </div>
            <div class="col-lg-5 d-none d-lg-block text-center">
                <div class="hero-icon">🛍️</div>
            </div>
        </div>
    </div>
</header>

<main class="container py-5" id="products">

    <?php if (isset($_GET['buy']) && isset($_SESSION['user_id'])): ?>
        <div class="alert alert-success">
            You're logged in! You can now continue with your purchase.
            Checkout will be available once the checkout page is implemented.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['registered'])): ?>
        <div class="alert alert-success">
            Your account has been created. You can now log in.
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Our Products</h2>
            <p class="text-secondary mb-0">Explore our latest collection.</p>
        </div>
        <span class="badge bg-primary rounded-pill">
            <?= count($products) ?> Products
        </span>
    </div>

    <div class="row g-4">

        <?php foreach ($products as $product): ?>
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <div class="card product-card h-100">

                    <?php if (!empty($product['image'])): ?>
                        <img
                            src="<?= e($product['image']) ?>"
                            class="card-img-top product-image"
                            alt="<?= e($product['name']) ?>"
                            loading="lazy">
                    <?php else: ?>
                        <div class="product-placeholder">🛍️</div>
                    <?php endif; ?>

                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold">
                            <?= e($product['name']) ?>
                        </h5>

                        <p class="card-text text-secondary small">
                            <?= e($product['description']) ?>
                        </p>

                        <div class="mt-auto">
                            <h4 class="text-primary fw-bold">
                                ₱<?= number_format((float)$product['price'], 2) ?>
                            </h4>

                            <?php if ($product['stock'] > 0): ?>
                                <p class="small text-success">
                                    In stock: <?= (int)$product['stock'] ?>
                                </p>

                                <a
                                    href="login.php?buy=<?= (int)$product['id'] ?>"
                                    class="btn btn-primary w-100">
                                    Buy Now
                                </a>
                            <?php else: ?>
                                <p class="small text-danger">Out of stock</p>
                                <button class="btn btn-secondary w-100" disabled>
                                    Out of Stock
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    </div>
</main>

<footer class="bg-dark text-white text-center py-4">
    <div class="container">
        <p class="mb-1 fw-bold">ShopFlow</p>
        <p class="mb-0 small text-white-50">
            Your simple and convenient online shopping experience.
        </p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>