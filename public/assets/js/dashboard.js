document.addEventListener('DOMContentLoaded', () => {
    const side = document.querySelector('[data-sidebar]');
    const back = document.querySelector('[data-sidebar-backdrop]');
    const open = () => { side?.classList.add('open'); back?.classList.add('show'); };
    const close = () => { side?.classList.remove('open'); back?.classList.remove('show'); };

    document.querySelector('[data-sidebar-open]')?.addEventListener('click', open);
    document.querySelector('[data-sidebar-close]')?.addEventListener('click', close);
    back?.addEventListener('click', close);

    document.querySelectorAll('[data-review-open]').forEach(b => b.addEventListener('click', () => document.getElementById(b.dataset.reviewOpen)?.showModal()));
    document.querySelectorAll('[data-review-close]').forEach(b => b.addEventListener('click', () => document.getElementById(b.dataset.reviewClose)?.close()));

    /* --- Custom Dropdown Menu for Dashboard --- */
    function initLudesCustomDropdowns() {
        const selects = document.querySelectorAll('select');

        selects.forEach(select => {
            if (select.closest('.ludes-custom-select') || select.classList.contains('ludes-initialized')) return;

            select.classList.add('ludes-initialized');
            
            const wrapper = document.createElement('div');
            wrapper.className = 'ludes-custom-select';
            
            select.parentNode.insertBefore(wrapper, select);
            wrapper.appendChild(select);
            select.classList.add('native-hidden-select');

            const trigger = document.createElement('div');
            trigger.className = 'ludes-select-trigger';
            trigger.tabIndex = 0;
            
            const selectedOption = select.options[select.selectedIndex] || select.options[0];
            const triggerText = document.createElement('span');
            triggerText.className = 'trigger-text';
            triggerText.textContent = selectedOption ? selectedOption.text : '';

            const triggerArrow = document.createElement('span');
            triggerArrow.className = 'trigger-arrow';
            triggerArrow.innerHTML = '<i class="bi bi-chevron-down"></i>';

            trigger.appendChild(triggerText);
            trigger.appendChild(triggerArrow);
            wrapper.appendChild(trigger);

            const menu = document.createElement('div');
            menu.className = 'ludes-select-menu';

            function buildMenuOptions() {
                menu.innerHTML = '';
                Array.from(select.options).forEach((opt, idx) => {
                    const optItem = document.createElement('div');
                    optItem.className = 'ludes-select-option' + (idx === select.selectedIndex ? ' is-selected' : '');
                    optItem.dataset.value = opt.value;
                    
                    const optText = document.createElement('span');
                    optText.textContent = opt.text;
                    optItem.appendChild(optText);

                    if (idx === select.selectedIndex) {
                        const check = document.createElement('i');
                        check.className = 'bi bi-check2 check-icon';
                        optItem.appendChild(check);
                    }

                    optItem.addEventListener('click', (e) => {
                        e.stopPropagation();
                        select.selectedIndex = idx;
                        triggerText.textContent = opt.text;
                        
                        select.dispatchEvent(new Event('change', { bubbles: true }));

                        buildMenuOptions();
                        closeMenu();
                    });

                    menu.appendChild(optItem);
                });
            }

            function positionMenu() {
                const rect = trigger.getBoundingClientRect();
                menu.style.position = 'fixed';
                menu.style.left = rect.left + 'px';
                menu.style.width = Math.max(rect.width, 120) + 'px';

                const estimatedMenuHeight = Math.min(select.options.length * 34 + 10, 200);
                if (rect.bottom + estimatedMenuHeight > window.innerHeight && rect.top > estimatedMenuHeight) {
                    menu.style.top = 'auto';
                    menu.style.bottom = (window.innerHeight - rect.top + 4) + 'px';
                } else {
                    menu.style.top = (rect.bottom + 4) + 'px';
                    menu.style.bottom = 'auto';
                }
            }

            function openMenu() {
                document.querySelectorAll('.ludes-select-menu.is-active').forEach(m => m.classList.remove('is-active'));
                document.querySelectorAll('.ludes-custom-select.is-open').forEach(w => w.classList.remove('is-open'));

                buildMenuOptions();
                if (!document.body.contains(menu)) {
                    document.body.appendChild(menu);
                }
                positionMenu();
                wrapper.classList.add('is-open');
                requestAnimationFrame(() => {
                    menu.classList.add('is-active');
                });
            }

            function closeMenu() {
                wrapper.classList.remove('is-open');
                menu.classList.remove('is-active');
            }

            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                if (wrapper.classList.contains('is-open')) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });

            select.addEventListener('change', () => {
                const current = select.options[select.selectedIndex];
                if (current) triggerText.textContent = current.text;
                buildMenuOptions();
            });

            window.addEventListener('scroll', closeMenu, { passive: true });
            window.addEventListener('resize', closeMenu, { passive: true });
        });
    }

    initLudesCustomDropdowns();

    document.addEventListener('click', () => {
        document.querySelectorAll('.ludes-select-menu.is-active').forEach(m => m.classList.remove('is-active'));
        document.querySelectorAll('.ludes-custom-select.is-open').forEach(w => w.classList.remove('is-open'));
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.ludes-select-menu.is-active').forEach(m => m.classList.remove('is-active'));
            document.querySelectorAll('.ludes-custom-select.is-open').forEach(w => w.classList.remove('is-open'));
        }
    });
});
