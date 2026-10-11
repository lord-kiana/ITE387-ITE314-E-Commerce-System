<?php
function product_image_url(array $product, string $assetPrefix = ''): string
{
    $name = (string) ($product['name'] ?? $product['product_name'] ?? '');
    $description = (string) ($product['description'] ?? '');
    $searchable = strtolower($name . ' ' . $description);

    if (preg_match('/\b(mice|mouses?)\b/i', $searchable)) {
        return $assetPrefix . 'assets/product-wireless-mouse.jpg';
    }

    if (preg_match('/\b(gaming\s+headphones?|headsets?)\b/i', $searchable)) {
        return $assetPrefix . 'assets/product-gaming-headset.jpg';
    }

    if (preg_match('/\bheadphones?\b/i', $searchable)) {
        return $assetPrefix . 'assets/product-sony-headphones.jpg';
    }

    if (preg_match('/\b(keyboards?|keycaps?)\b/i', $searchable)) {
        return $assetPrefix . 'assets/product-keyboard.jpg';
    }

    if (preg_match('/\b((?:usb[- ]c )?hub|adapters?|dongles?|docks?|docking station)\b/i', $searchable)) {
        return $assetPrefix . 'assets/product-usb-hub.jpg';
    }

    if (preg_match('/\b(laptops?|notebooks?|computers?)\b/i', $searchable)) {
        return $assetPrefix . 'assets/product-laptop.jpg';
    }

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
