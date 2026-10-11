(() => {
    const form = document.querySelector('.checkout-card form');
    const methods = Array.from(document.querySelectorAll('input[name="payment_method"]'));
    const panel = document.querySelector('#card-entry-panel');
    const cardFields = panel ? Array.from(panel.querySelectorAll('input')) : [];
    const submitLabel = document.querySelector('.checkout-submit-label');

    if (!form || methods.length === 0 || !panel) return;

    const updateMethod = () => {
        const cardSelected = methods.some((method) => method.checked && method.value === 'card');
        methods.forEach((method) => method.closest('.payment-option')?.classList.toggle('is-selected', method.checked));
        panel.hidden = !cardSelected;
        cardFields.forEach((field) => {
            field.disabled = !cardSelected;
            field.required = cardSelected;
        });
        if (submitLabel) submitLabel.textContent = cardSelected ? 'Pay securely' : 'Place order';
    };

    methods.forEach((method) => method.addEventListener('change', updateMethod));
    updateMethod();

    const cardNumber = document.querySelector('#card_number');
    cardNumber?.addEventListener('input', () => {
        const digits = cardNumber.value.replace(/\D/g, '').slice(0, 19);
        cardNumber.value = digits.replace(/(.{4})/g, '$1 ').trim();
    });

    const expiry = document.querySelector('#card_expiry');
    expiry?.addEventListener('input', () => {
        const digits = expiry.value.replace(/\D/g, '').slice(0, 4);
        expiry.value = digits.length > 2 ? `${digits.slice(0, 2)}/${digits.slice(2)}` : digits;
    });

    const cvc = document.querySelector('#card_cvc');
    cvc?.addEventListener('input', () => {
        cvc.value = cvc.value.replace(/\D/g, '').slice(0, 4);
    });

    form.addEventListener('submit', () => {
        const button = form.querySelector('.checkout-submit');
        if (!button) return;
        button.disabled = true;
        if (submitLabel) submitLabel.textContent = 'Processing order...';
    });
})();
