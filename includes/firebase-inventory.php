<?php

use Kreait\Firebase\Factory;
use Kreait\Firebase\Database;

function firebase_inventory_database(): ?Database
{
    static $database = null;
    static $initialized = false;

    if ($initialized) {
        return $database;
    }

    $initialized = true;
    $databaseUrl = getenv('FIREBASE_DATABASE_URL');
    $serviceAccountJson = getenv('FIREBASE_SERVICE_ACCOUNT_JSON');
    $autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';

    if (!$databaseUrl || !$serviceAccountJson || !is_file($autoloadPath)) {
        return null;
    }

    $credentials = json_decode($serviceAccountJson, true);
    if (!is_array($credentials)) {
        error_log('Firebase inventory error: service account JSON is invalid.');
        return null;
    }

    $credentialsPath = tempnam(sys_get_temp_dir(), 'firebase-credentials-');
    if ($credentialsPath === false) {
        error_log('Firebase inventory error: could not create credentials file.');
        return null;
    }

    try {
        if (file_put_contents($credentialsPath, $serviceAccountJson) === false) {
            throw new RuntimeException('Could not write service account credentials.');
        }
        chmod($credentialsPath, 0600);

        require_once $autoloadPath;
        $database = (new Factory())
            ->withServiceAccount($credentialsPath)
            ->withDatabaseUri($databaseUrl)
            ->createDatabase();
    } catch (Throwable $e) {
        error_log('Firebase inventory initialization error: ' . $e->getMessage());
    }

    if ($database) {
        register_shutdown_function(static function () use ($credentialsPath): void {
            @unlink($credentialsPath);
        });
    } else {
        @unlink($credentialsPath);
    }

    return $database;
}

function firebase_sync_inventory(PDO $pdo, array $productIds): void
{
    $database = firebase_inventory_database();
    if (!$database) {
        return;
    }

    foreach (array_unique(array_map('intval', $productIds)) as $productId) {
        if ($productId < 1) {
            continue;
        }

        $reference = $database->getReference('inventory/products/' . $productId);

        try {
            $stmt = $pdo->prepare(
                'SELECT id, name, stock
                 FROM public.products
                 WHERE id = :id'
            );
            $stmt->execute([':id' => $productId]);
            $product = $stmt->fetch();

            if (!$product) {
                $reference->remove();
                continue;
            }

            $reference->set([
                'id' => (int) $product['id'],
                'name' => $product['name'],
                'stock' => (int) $product['stock'],
                'updatedAt' => ['.sv' => 'timestamp'],
            ]);
        } catch (Throwable $e) {
            error_log('Firebase inventory sync error: ' . $e->getMessage());
        }
    }
}

function firebase_seed_inventory_if_empty(PDO $pdo, array $products): void
{
    if (!$products) {
        return;
    }

    $database = firebase_inventory_database();
    if (!$database) {
        return;
    }

    try {
        $reference = $database->getReference('inventory/products');
        if ($reference->getSnapshot()->exists()) {
            return;
        }

        $inventory = [];
        foreach ($products as $product) {
            $productId = (int) $product['id'];
            $inventory[$productId] = [
                'id' => $productId,
                'name' => $product['name'],
                'stock' => (int) $product['stock'],
                'updatedAt' => ['.sv' => 'timestamp'],
            ];
        }

        $reference->set($inventory);
    } catch (Throwable $e) {
        error_log('Firebase inventory seed error: ' . $e->getMessage());
    }
}