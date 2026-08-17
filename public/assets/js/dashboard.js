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

    /* --- Collapsible Sidebar Sections --- */
    document.querySelectorAll('[data-toggle-section]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const section = btn.closest('.sidebar-section');
            if (!section) return;
            const isCollapsed = section.classList.toggle('is-collapsed');
            const sectionId = section.dataset.sectionId;
            if (sectionId) {
                try {
                    localStorage.setItem('ludes_sidebar_' + sectionId, isCollapsed ? 'collapsed' : 'expanded');
                } catch (err) {}
            }
        });
    });

    document.querySelectorAll('.sidebar-section[data-section-id]').forEach(section => {
        const sectionId = section.dataset.sectionId;
        const hasActiveChild = section.querySelector('a.active') !== null;

        if (hasActiveChild) {
            section.classList.remove('is-collapsed');
        } else if (sectionId) {
            try {
                const savedState = localStorage.getItem('ludes_sidebar_' + sectionId);
                if (savedState === 'collapsed') {
                    section.classList.add('is-collapsed');
                }
            } catch (err) {}
        }
    });

    /* --- Custom Dropdown Menu for Dashboard --- */
    function initLudesCustomDropdowns() {
        const selects = document.querySelectorAll('select');

        selects.forEach(select => {
            if (select.closest('.ludes-custom-select') || select.closest('.swal2-container') || select.classList.contains('swal2-select') || select.classList.contains('ludes-initialized')) return;

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
                const container = select.closest('dialog') || document.body;
                if (!container.contains(menu)) {
                    container.appendChild(menu);
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

    /* ==========================================================================
       DASHBOARD INTERACTIVE MOTION & ANIMATION ENGINE
       ========================================================================== */

    /* --- 1. Tactile Click Ripple Effect --- */
    function createRipple(e) {
        const btn = e.currentTarget;
        if (btn.classList.contains('disabled') || btn.disabled) return;

        const rect = btn.getBoundingClientRect();
        const ripple = document.createElement('span');
        ripple.className = 'ludes-ripple-effect';

        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;

        ripple.style.width = ripple.style.height = `${size}px`;
        ripple.style.left = `${x}px`;
        ripple.style.top = `${y}px`;

        btn.appendChild(ripple);
        setTimeout(() => ripple.remove(), 1600);
    }

    document.querySelectorAll('.button, .button-outline, .btn, .profile-tab-btn, button[type="submit"]').forEach(btn => {
        btn.addEventListener('click', createRipple);
    });

    /* --- 2. Metric Grid Count-Up Animation (Linear & Constant Velocity) --- */
    function animateValue(obj, start, end, duration, prefix = '', suffix = '') {
        let startTimestamp = null;
        const step = (timestamp) => {
            if (!startTimestamp) startTimestamp = timestamp;
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const current = Math.floor(progress * (end - start) + start);
            obj.textContent = prefix + current.toLocaleString('id-ID') + suffix;
            if (progress < 1) {
                window.requestAnimationFrame(step);
            } else {
                obj.textContent = prefix + end.toLocaleString('id-ID') + suffix;
            }
        };
        window.requestAnimationFrame(step);
    }

    const metricStrong = document.querySelectorAll('.metric-grid article strong');
    if (metricStrong.length > 0 && 'IntersectionObserver' in window) {
        const metricObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const text = el.textContent.trim();
                    let prefix = '';
                    let suffix = '';
                    let cleanText = text;

                    if (text.startsWith('Rp')) {
                        prefix = 'Rp';
                        cleanText = text.replace('Rp', '').trim();
                    }
                    if (text.endsWith('%')) {
                        suffix = '%';
                        cleanText = cleanText.replace('%', '').trim();
                    }

                    const num = parseInt(cleanText.replace(/\./g, '').replace(/,/g, ''), 10);
                    if (!isNaN(num) && num > 0) {
                        animateValue(el, 0, num, 1600, prefix, suffix);
                    }
                    observer.unobserve(el);
                }
            });
        }, { threshold: 0.1 });

        metricStrong.forEach(el => metricObserver.observe(el));
    }

    /* --- 3. Interactive Profile Tabs with Smooth Transition --- */
    const tabButtons = document.querySelectorAll('.profile-tab-btn[data-tab-target]');
    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.tabTarget;
            const targetContent = document.getElementById(targetId);
            if (!targetContent) return;

            tabButtons.forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.profile-tab-content').forEach(c => {
                c.classList.remove('active');
            });

            btn.classList.add('active');
            targetContent.classList.add('active');
        });
    });
});

