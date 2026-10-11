<?php
function product_image_url(array $product, string $assetPrefix = ''): string
{
    $name = (string) ($product['name'] ?? $product['product_name'] ?? '');
    $description = (string) ($product['description'] ?? '');
    $searchable = strtolower($name . ' ' . $description);

    if (preg_match('/\b(sneakers?|shoes?|footwear|jordans?|nike|adidas)\b/i', $searchable)) {
        return $assetPrefix . 'assets/carousel-sneakers.jpg';
    }

    if (preg_match('/\b(phones?|mobiles?|smartphones?|iphones?|android|samsung|galaxies|galaxy|pixels?|tablets?|earbuds?|airpods|chargers?)\b/i', $searchable)) {
        return $assetPrefix . 'assets/carousel-phone-tech.jpg';
    }

    if (preg_match('/\b(bags?|handbags?|backpacks?|purses?|satchels?|totes?)\b/i', $searchable)) {
        return $assetPrefix . 'assets/carousel-bag.jpg';
    }

    $image = trim((string) ($product['image'] ?? ''));
    if ($image !== '') {
        if (preg_match('#^(https?://|/)#i', $image)) {
            return $image;
        }

        return $assetPrefix . ltrim($image, './');
    }

    return $assetPrefix . 'assets/product-placeholder.svg';
}
