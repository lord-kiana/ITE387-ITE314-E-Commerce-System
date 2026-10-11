(() => {
    const form = document.querySelector('.navbar-search');
    const input = document.querySelector('#shop-search');
    const products = Array.from(document.querySelectorAll('.product-result'));

    if (!form || !input || products.length === 0) return;

    const count = document.querySelector('#product-count');
    const countLabel = document.querySelector('#product-count-label');
    const emptyState = document.querySelector('#live-search-empty');
    const heading = document.querySelector('#product-heading');
    const subtitle = document.querySelector('#product-subtitle');

    const filterProducts = (value) => {
        const query = value.trim().toLocaleLowerCase();
        let visibleCount = 0;

        products.forEach((product) => {
            const matches = product.dataset.productSearch
                .toLocaleLowerCase()
                .includes(query);

            product.hidden = !matches;
            if (matches) visibleCount += 1;
        });

        if (count) count.textContent = String(visibleCount);
        if (countLabel) countLabel.textContent = visibleCount === 1 ? 'Product' : 'Products';
        if (emptyState) emptyState.hidden = visibleCount !== 0;
        if (heading) heading.textContent = query ? 'Search results' : 'Our Products';
        if (subtitle) {
            subtitle.textContent = query
                ? `Showing matches for “${value.trim()}”.`
                : 'Explore our latest collection.';
        }
    };

    input.addEventListener('input', () => filterProducts(input.value));
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        filterProducts(input.value);

        const url = new URL(window.location.href);
        const query = input.value.trim();
        if (query) url.searchParams.set('q', query);
        else url.searchParams.delete('q');
        window.history.replaceState({}, '', url);
    });

    filterProducts(input.value);
})();
