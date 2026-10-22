import { initializeApp } from 'https://www.gstatic.com/firebasejs/11.6.0/firebase-app.js';
import { getDatabase, onValue, ref } from 'https://www.gstatic.com/firebasejs/11.6.0/firebase-database.js';

const config = window.shopFlowFirebaseConfig;
if (!config?.databaseURL) {
    console.warn('Firebase inventory is not configured.');
} else {
    const app = initializeApp(config);
    const database = getDatabase(app);

    onValue(ref(database, 'inventory/products'), (snapshot) => {
        const inventory = snapshot.val() ?? {};

        document.querySelectorAll('[data-product-id]').forEach((card) => {
            const product = inventory[card.dataset.productId];
            if (!product) return;

            const stock = Math.max(0, Number(product.stock) || 0);
            const stockLabel = card.querySelector('[data-product-stock]');
            const availableState = card.querySelector('[data-stock-state="available"]');
            const emptyState = card.querySelector('[data-stock-state="empty"]');

            if (stockLabel) stockLabel.textContent = String(stock);
            if (availableState) availableState.hidden = stock === 0;
            if (emptyState) emptyState.hidden = stock > 0;

            const quantity = card.querySelector('input[name="quantity"]');
            if (quantity && stock > 0) {
                const maximum = Math.min(99, stock);
                quantity.max = String(maximum);
                quantity.value = String(Math.min(Number(quantity.value) || 1, maximum));
            }
        });
    }, (error) => {
        console.error('Firebase inventory listener failed:', error);
    });
}