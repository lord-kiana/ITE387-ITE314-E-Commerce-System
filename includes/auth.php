
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /auth/login.php');
        exit;
    }
}

function require_customer(): void
{
    require_login();

    if (($_SESSION['user_role'] ?? 'customer') === 'admin') {
        header('Location: /admin/products.php');
        exit;
    }
}

function require_admin(): void
{
    require_login();

    if (($_SESSION['user_role'] ?? 'customer') !== 'admin') {
        http_response_code(403);
        exit('Access denied.');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';

    if (
        !is_string($submitted) ||
        !is_string($expected) ||
        $expected === '' ||
        !hash_equals($expected, $submitted)
    ) {
        http_response_code(419);
        exit('Invalid form token. Refresh the page and try again.');
    }
}
