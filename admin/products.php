<?php
require_once __DIR__ . '/../db/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/product-image.php';

require_admin();

$errors = [];
$notice = '';
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            try {
                $stmt = $pdo->prepare('DELETE FROM public.products WHERE id = :id');
                $stmt->execute([':id' => $id]);
                $_SESSION['admin_notice'] = $stmt->rowCount()
                    ? 'Product removed.'
                    : 'That product could not be found.';
            } catch (PDOException $e) {
                error_log('ShopFlow product delete error: ' . $e->getMessage());
                $_SESSION['admin_notice'] = 'This product is attached to an order or cart and cannot be deleted. Set its stock to 0 to hide it from shoppers.';
            }
        }
        header('Location: products.php');
        exit;
    }

    if ($action === 'save') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: null;
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $image = trim($_POST['image'] ?? '');
        $priceInput = trim($_POST['price'] ?? '');
        $stockInput = trim($_POST['stock'] ?? '');

        if ($name === '' || strlen($name) > 180) {
            $errors[] = 'Enter a product name up to 180 characters.';
        }
        if (strlen($description) > 5000) {
            $errors[] = 'Description must be 5,000 characters or fewer.';
        }
        if ($priceInput === '' || !is_numeric($priceInput) || (float) $priceInput < 0) {
            $errors[] = 'Enter a valid non-negative price.';
        }
        if ($stockInput === '' || filter_var($stockInput, FILTER_VALIDATE_INT) === false || (int) $stockInput < 0) {
            $errors[] = 'Stock must be a whole number of 0 or more.';
        }
        if ($image !== '' && strlen($image) > 1000) {
            $errors[] = 'Image address must be 1,000 characters or fewer.';
        }

        if (!$errors) {
            try {
                if ($id) {
                    $stmt = $pdo->prepare(
                        'UPDATE public.products
                         SET name = :name, description = :description,
                             price = :price, image = :image, stock = :stock
                         WHERE id = :id'
                    );
                    $stmt->execute([
                        ':name' => $name,
                        ':description' => $description,
                        ':price' => round((float) $priceInput, 2),
                        ':image' => $image !== '' ? $image : null,
                        ':stock' => (int) $stockInput,
                        ':id' => $id,
                    ]);
                    $_SESSION['admin_notice'] = 'Product changes saved.';
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO public.products (name, description, price, image, stock)
                         VALUES (:name, :description, :price, :image, :stock)'
                    );
                    $stmt->execute([
                        ':name' => $name,
                        ':description' => $description,
                        ':price' => round((float) $priceInput, 2),
                        ':image' => $image !== '' ? $image : null,
                        ':stock' => (int) $stockInput,
                    ]);
                    $_SESSION['admin_notice'] = 'Product added to the catalog.';
                }
                header('Location: products.php');
                exit;
            } catch (PDOException $e) {
                error_log('ShopFlow product save error: ' . $e->getMessage());
                $errors[] = 'The product could not be saved. Please check the values and try again.';
            }
        }
        $editing = [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'price' => $priceInput,
            'image' => $image,
            'stock' => $stockInput,
        ];
    }
}

if (!$editing && isset($_GET['edit'])) {
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($editId) {
        $stmt = $pdo->prepare('SELECT id, name, description, price, image, stock FROM public.products WHERE id = :id');
        $stmt->execute([':id' => $editId]);
        $editing = $stmt->fetch() ?: null;
    }
}

$notice = $_SESSION['admin_notice'] ?? '';
unset($_SESSION['admin_notice']);
$stmt = $pdo->query('SELECT id, name, description, price, image, stock FROM public.products ORDER BY created_at DESC, id DESC');
$products = $stmt->fetchAll();
$totalStock = array_sum(array_map(static fn ($product) => (int) $product['stock'], $products));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Products | ShopFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/styles.css">
</head>
<body>
<?php require __DIR__ . '/../includes/navbar.php'; ?>
<main class="container admin-page">
    <div class="admin-heading">
        <div>
            <p class="cart-eyebrow">SHOPFLOW ADMIN</p>
            <h1>Product catalog</h1>
            <p class="text-secondary mb-0">Add products, update inventory, and keep your storefront current.</p>
        </div>
        <a class="btn btn-primary" href="#product-form">+ Add a product</a>
    </div>

    <?php if ($notice): ?><div class="alert alert-info"><?= h($notice) ?></div><?php endif; ?>
    <?php if ($errors): ?>
        <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="row g-3 admin-stats">
        <div class="col-sm-6 col-xl-4"><div class="admin-stat"><span>Catalog products</span><strong><?= count($products) ?></strong><small>Items listed in the store</small></div></div>
        <div class="col-sm-6 col-xl-4"><div class="admin-stat"><span>Units in stock</span><strong><?= number_format($totalStock) ?></strong><small>Across all products</small></div></div>
        <div class="col-sm-6 col-xl-4"><div class="admin-stat"><span>Needs attention</span><strong><?= count(array_filter($products, static fn ($product) => (int) $product['stock'] === 0)) ?></strong><small>Out of stock products</small></div></div>
    </div>

    <div class="admin-section-heading">
        <div><h2>All products</h2><p><?= count($products) ?> products in your catalog</p></div>
    </div>
    <section class="admin-table-card">
        <?php if (!$products): ?>
            <div class="admin-empty"><img src="../assets/product-placeholder.svg" alt=""><h3>Your catalog is ready to grow</h3><p>Add your first product to get started.</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table admin-table align-middle mb-0">
                    <thead><tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($products as $product): ?>
                        <?php $adminProductImage = product_image_url($product, '../'); ?>
                        <tr>
                            <td><div class="admin-product-cell"><img src="<?= h($adminProductImage) ?>" alt="" onerror="this.onerror=null;this.src='../assets/product-placeholder.svg'"><div><strong><?= h($product['name']) ?></strong><small><?= h($product['description'] ?? '') ?></small></div></div></td>
                            <td><span class="product-category-badge"><?= h(product_category($product)) ?></span></td>
                            <td class="admin-price">PHP <?= number_format((float) $product['price'], 2) ?></td>
                            <td><?= (int) $product['stock'] ?></td>
                            <td><span class="stock-badge <?= (int) $product['stock'] > 0 ? 'is-available' : 'is-empty' ?>"><?= (int) $product['stock'] > 0 ? 'In stock' : 'Out of stock' ?></span></td>
                            <td><div class="admin-actions"><a class="btn btn-outline-primary btn-sm" href="?edit=<?= (int) $product['id'] ?>#product-form">Edit</a><form method="post" onsubmit="return confirm('Remove this product from the catalog?')"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $product['id'] ?>"><button class="btn btn-outline-danger btn-sm" type="submit">Delete</button></form></div></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-form-card" id="product-form">
        <div class="admin-section-heading"><div><h2><?= $editing ? 'Edit product' : 'Add a product' ?></h2><p>Product details appear on your storefront.</p></div><?php if ($editing): ?><a class="admin-cancel" href="products.php">Cancel edit</a><?php endif; ?></div>
        <form method="post" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= h($editing['id'] ?? '') ?>">
            <div class="col-md-6"><label class="form-label" for="name">Product name</label><input class="form-control" id="name" name="name" maxlength="180" required value="<?= h($editing['name'] ?? '') ?>"></div>
            <div class="col-md-3"><label class="form-label" for="price">Price (PHP)</label><input class="form-control" id="price" name="price" type="number" min="0" step="0.01" required value="<?= h($editing['price'] ?? '') ?>"></div>
            <div class="col-md-3"><label class="form-label" for="stock">Stock</label><input class="form-control" id="stock" name="stock" type="number" min="0" step="1" required value="<?= h($editing['stock'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label" for="image">Image URL <span class="text-secondary fw-normal">(optional)</span></label><input class="form-control" id="image" name="image" type="url" maxlength="1000" placeholder="https://example.com/product.jpg" value="<?= h($editing['image'] ?? '') ?>"></div>
            <div class="col-12"><label class="form-label" for="description">Description</label><textarea class="form-control" id="description" name="description" rows="4" maxlength="5000" placeholder="What makes this product special?"><?= h($editing['description'] ?? '') ?></textarea></div>
            <div class="col-12"><button class="btn btn-primary" type="submit"><?= $editing ? 'Save changes' : 'Add product' ?></button></div>
        </form>
    </section>
</main>
</body>
</html>
