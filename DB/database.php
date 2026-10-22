
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '5432';
$dbname = getenv('DB_NAME') ?: 'postgres';
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$sslmode = getenv('DB_SSLMODE') ?: 'require';

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=$sslmode",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Verify the connection and products table
    $stmt = $pdo->query(
        'SELECT id, name, price, stock
         FROM public.products
         ORDER BY id'
    );

    $products = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('ShopFlow database error: ' . $e->getMessage());

    http_response_code(500);
    exit('Unable to connect to the database or retrieve products.');
}
?>