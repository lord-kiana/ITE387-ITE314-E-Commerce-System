<?php
require_once __DIR__ . '/db/database.php';
require_once __DIR__ . '/includes/auth.php';

$searchQuery = trim((string) ($_GET['q'] ?? ''));

try {
    if ($searchQuery !== '') {
        $stmt = $pdo->prepare(
            "SELECT id, name, description, price, image, stock
             FROM public.products
             WHERE name ILIKE :name_query
                OR COALESCE(description, '') ILIKE :description_query
             ORDER BY created_at DESC"
        );
        $searchTerm = '%' . $searchQuery . '%';
        $stmt->execute([
            ':name_query' => $searchTerm,
            ':description_query' => $searchTerm,
        ]);
    } else {
        $stmt = $pdo->query(
            "SELECT id, name, description, price, image, stock
             FROM public.products
             ORDER BY created_at DESC"
        );
    }

    $products = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('ShopFlow products error: ' . $e->getMessage());
    $products = [];
    $productError = true;
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$isLoggedIn = !empty($_SESSION['user_id']);
$cartMessage = $_SESSION['cart_error'] ?? '';
unset($_SESSION['cart_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>ShopFlow | Online Store</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link rel="stylesheet" href="CSS/styles.css">
</head>
<body>

<?php require __DIR__ . '/includes/navbar.php'; ?>

<header class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <p class="text-primary fw-bold mb-2">
                    WELCOME TO SHOPFLOW
                </p>

                <h1 class="display-4 fw-bold">
                    Find your next favorite product.
                </h1>

                <p class="lead text-secondary">
                    Discover quality products at prices you'll love.
                    Browse freely and sign in when you're ready to buy.
                </p>

                <a href="#products" class="btn btn-primary btn-lg mt-2">
                    Shop Now
                </a>
            </div>
        </div>
    </div>
</header>

<main class="container py-5" id="products">

    <?php if (isset($productError)): ?>
        <div class="alert alert-warning">
            We couldn't load the products right now. Please try again later.
        </div>
    <?php endif; ?>

    <?php if ($cartMessage !== ''): ?>
        <div class="alert alert-warning alert-dismissible fade show">
            <?= e($cartMessage) ?>
            <button type="button" class="btn-close"
                    data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['registered'])): ?>
        <div class="alert alert-success">
            Your account has been created. You can now log in.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['login'])): ?>
        <div class="alert alert-info">
            Please log in to add products to your shopping cart.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['added'])): ?>
        <div class="alert alert-success">
            Product added to your cart successfully.
            <a href="cart.php" class="alert-link">View Cart</a>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1"><?= $searchQuery !== '' ? 'Search results' : 'Our Products' ?></h2>
            <p class="text-secondary mb-0">
                <?= $searchQuery !== '' ? 'Showing matches for “' . e($searchQuery) . '”.' : 'Explore our latest collection.' ?>
            </p>
        </div>

        <span class="badge bg-primary rounded-pill">
            <?= count($products) ?> <?= count($products) === 1 ? 'Product' : 'Products' ?>
        </span>
    </div>

    <div class="row g-4">

        <?php if (empty($products) && !isset($productError)): ?>
            <div class="col-12">
                <div class="alert alert-info">
                    <?= $searchQuery !== '' ? 'No products matched your search. Try another name or keyword.' : 'No products are available yet.' ?>
                    <?php if ($searchQuery !== ''): ?> <a href="index.php" class="alert-link">Clear search</a><?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php foreach ($products as $product): ?>
            <?php
                $productId = (int) $product['id'];
                $stock = (int) $product['stock'];
            ?>

            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <div class="card product-card h-100">

                    <?php if (!empty($product['image'])): ?>
                        <img
                            src="<?= e($product['image']) ?>"
                            class="card-img-top product-image"
                            alt="<?= e($product['name']) ?>"
                            loading="lazy"
                            onerror="this.onerror=null;this.src='assets/product-placeholder.svg'">
                    <?php else: ?>
                        <div class="product-placeholder" role="img" aria-label="ShopFlow product illustration"></div>
                    <?php endif; ?>

                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title fw-bold">
                            <?= e($product['name']) ?>
                        </h5>

                        <p class="card-text text-secondary small">
                            <?= e($product['description'] ?? '') ?>
                        </p>

                        <div class="mt-auto">
                            <h4 class="text-primary fw-bold">
                                â‚±<?= number_format((float) $product['price'], 2) ?>
                            </h4>

                            <?php if ($stock > 0): ?>
                                <p class="small text-success">
                                    In stock:
                                    <span data-product-stock="<?= $productId ?>">
                                        <?= $stock ?>
                                    </span>
                                </p>

                                <?php if ($isLoggedIn): ?>
                                    <form action="cart_add.php" method="post">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= e(csrf_token()) ?>">

                                        <input type="hidden" name="product_id"
                                               value="<?= $productId ?>">

                                        <label class="form-label small"
                                               for="quantity-<?= $productId ?>">
                                            Quantity
                                        </label>

                                        <input
                                            class="form-control mb-2"
                                            type="number"
                                            id="quantity-<?= $productId ?>"
                                            name="quantity"
                                            value="1"
                                            min="1"
                                            max="<?= min(99, $stock) ?>"
                                            required>

                                        <button type="submit"
                                                class="btn btn-primary w-100">
                                            Add to Cart
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="auth/login.php?buy=<?= $productId ?>"
                                       class="btn btn-primary w-100">
                                        Buy Now
                                    </a>
                                <?php endif; ?>

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

<script type="module" src="assets/firebase-inventory.js"></script>

</body>
</html>
