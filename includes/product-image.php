<?php
function product_category(array $product): string
{
    $searchable = strtolower(
        (string) ($product['name'] ?? $product['product_name'] ?? '') . ' ' .
        (string) ($product['description'] ?? '')
    );

    if (preg_match('/\b(sneakers?|shoes?|footwear|jordans?|nike|adidas)\b/i', $searchable)) {
        return 'Shoes';
    }

    if (preg_match('/\b(bags?|handbags?|backpacks?|purses?|satchels?|totes?)\b/i', $searchable)) {
        return 'Bags';
    }

    return 'Other';
}

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

    if (preg_match('/\bwhite\b/i', $searchable) && preg_match('/\b(sneakers?|shoes?|footwear)\b/i', $searchable)) {
        return $assetPrefix . 'assets/product-white-sneakers.jpg';
    }

    if (preg_match('/\b(running shoes?|athletic shoes?|trainers?)\b/i', $searchable)) {
        return $assetPrefix . 'assets/product-running-shoes.jpg';
    }

    if (preg_match('/\b(high[- ]?tops?|high[- ]?top sneakers?)\b/i', $searchable)) {
        return $assetPrefix . 'assets/product-high-top-sneakers.jpg';
    }

    if (preg_match('/\b(formal shoes?|dress shoes?|leather shoes?|oxfords?)\b/i', $searchable)) {
        return $assetPrefix . 'assets/product-leather-formal-shoes.jpg';
    }

    if (preg_match('/\b(casual shoes?|black casual shoes?)\b/i', $searchable)) {
        return $assetPrefix . 'assets/product-black-casual-shoes.jpg';
    }

    if (preg_match('/\b(vans|slip[- ]?ons?)\b/i', $searchable)) {
        return $assetPrefix . 'assets/product-vans-slipons.jpg';
    }

    if (preg_match('/\b(sneakers?|shoes?|footwear|jordans?|nike|adidas)\b/i', $searchable)) {
        return $assetPrefix . 'assets/product-black-sneakers.jpg';
    }

    if (preg_match('/\b(phones?|mobiles?|smartphones?|iphones?|android|samsung|galaxies|galaxy|pixels?|tablets?|earbuds?|airpods|chargers?)\b/i', $searchable)) {
        return $assetPrefix . 'assets/carousel-phone-tech.jpg';
    }

    if (preg_match('/\b(backpacks?|rucksacks?)\b/i', $searchable)) {
        return $assetPrefix . 'assets/product-backpack.jpg';
    }

    if (preg_match('/\b(tote bags?|totes?|shopping bags?)\b/i', $searchable)) {
        return $assetPrefix . 'assets/product-tote-bag.jpg';
    }

    if (preg_match('/\b(duffel|duffle|gym|travel|weekender) bags?\b/i', $searchable)) {
        return $assetPrefix . 'assets/product-duffel-bag.jpg';
    }

    if (preg_match('/\b(white|cream|ivory)\b/i', $searchable) && preg_match('/\b(bags?|handbags?|purses?|satchels?|clutches?)\b/i', $searchable)) {
        return $assetPrefix . 'assets/product-white-handbag.jpg';
    }

    if (preg_match('/\b(bags?|handbags?|purses?|satchels?|shoulder bags?|crossbody bags?|clutches?)\b/i', $searchable)) {
        return $assetPrefix . 'assets/product-leather-handbag.jpg';
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
