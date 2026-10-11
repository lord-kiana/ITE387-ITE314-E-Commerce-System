(() => {
    const form = document.querySelector('.navbar-search');
    const input = document.querySelector('#shop-search');
    const products = Array.from(document.querySelectorAll('.product-result'));
    const categoryButtons = Array.from(document.querySelectorAll('[data-category-filter]'));

    if (!form || !input || products.length === 0) return;

    const count = document.querySelector('#product-count');
    const countLabel = document.querySelector('#product-count-label');
    const emptyState = document.querySelector('#live-search-empty');
    const heading = document.querySelector('#product-heading');
    const subtitle = document.querySelector('#product-subtitle');
    let activeCategory = 'all';

    const filterProducts = (value) => {
        const query = value.trim().toLocaleLowerCase();
        let visibleCount = 0;

        products.forEach((product) => {
            const matchesSearch = product.dataset.productSearch
                .toLocaleLowerCase()
                .includes(query);
            const matchesCategory = activeCategory === 'all'
                || product.dataset.productCategory === activeCategory;
            const matches = matchesSearch && matchesCategory;

            product.hidden = !matches;
            if (matches) visibleCount += 1;
        });

        if (count) count.textContent = String(visibleCount);
        if (countLabel) countLabel.textContent = visibleCount === 1 ? 'Product' : 'Products';
        if (emptyState) emptyState.hidden = visibleCount !== 0;
        if (heading) {
            heading.textContent = query
                ? 'Search results'
                : activeCategory === 'all' ? 'Our Products' : `${activeCategory[0].toUpperCase()}${activeCategory.slice(1)}`;
        }
        if (subtitle) {
            const filters = [];
            if (query) filters.push(`matches for “${value.trim()}”`);
            if (activeCategory !== 'all') filters.push(`in ${activeCategory}`);
            subtitle.textContent = filters.length ? `Showing ${filters.join(' ')}.` : 'Explore our latest collection.';
        }
    };

    categoryButtons.forEach((button) => {
        button.addEventListener('click', () => {
            activeCategory = button.dataset.categoryFilter;
            categoryButtons.forEach((item) => {
                const isActive = item === button;
                item.classList.toggle('is-active', isActive);
                item.setAttribute('aria-pressed', String(isActive));
            });
            filterProducts(input.value);
        });
    });

    document.querySelectorAll('[data-footer-category]').forEach((link) => {
        link.addEventListener('click', () => {
            const categoryButton = categoryButtons.find(
                (button) => button.dataset.categoryFilter === link.dataset.footerCategory
            );
            categoryButton?.click();
        });
    });

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
