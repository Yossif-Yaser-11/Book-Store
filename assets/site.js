document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.menu-toggle');
    const navigation = document.querySelector('.site-nav');

    if (toggle && navigation) {
        toggle.addEventListener('click', () => {
            const isOpen = navigation.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(isOpen));
        });
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrfToken) {
        document.querySelectorAll('form[method="post"], form:not([method])').forEach((form) => {
            if (!form.querySelector('input[name="csrf_token"]')) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'csrf_token';
                input.value = csrfToken;
                form.prepend(input);
            }
        });
    }
});