(() => {
    const toggle = document.querySelector('.site-nav-toggle');
    const links = document.getElementById('site-nav-links');

    if (!toggle || !links) {
        return;
    }

    const mobile = window.matchMedia('(max-width: 1000px)');

    const setOpen = (open) => {
        toggle.setAttribute('aria-expanded', String(open));
        links.hidden = mobile.matches && !open;
    };

    const updateLayout = () => {
        const focusWillBeHidden = mobile.matches && links.contains(document.activeElement);
        const toggleHadFocus = document.activeElement === toggle;
        toggle.hidden = !mobile.matches;
        setOpen(false);

        if (focusWillBeHidden) {
            toggle.focus();
        } else if (!mobile.matches && toggleHadFocus) {
            links.querySelector('a').focus();
        }
    };

    toggle.addEventListener('click', () => {
        setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });

    toggle.closest('nav').addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && mobile.matches && toggle.getAttribute('aria-expanded') === 'true') {
            setOpen(false);
            toggle.focus();
        }
    });

    mobile.addEventListener('change', updateLayout);
    updateLayout();
})();
