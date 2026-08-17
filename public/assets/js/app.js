document.addEventListener('DOMContentLoaded', () => {
    /* --- Mobile menu toggle --- */
    const toggle = document.querySelector('[data-menu-toggle]');
    const menu = document.querySelector('[data-mobile-menu]');
    
    toggle?.addEventListener('click', () => menu?.classList.toggle('open'));
    
    document.querySelectorAll('[data-mobile-menu] a').forEach(a => {
        a.addEventListener('click', () => menu?.classList.remove('open'));
    });

    /* --- Sticky Header Scroll Effect --- */
    const siteHeader = document.getElementById('siteHeader');
    if (siteHeader) {
        const scrollThreshold = 30;
        function handleHeaderScroll() {
            if (window.scrollY > scrollThreshold) {
                siteHeader.classList.add('scrolled');
            } else {
                siteHeader.classList.remove('scrolled');
            }
        }
        window.addEventListener('scroll', handleHeaderScroll, { passive: true });
        handleHeaderScroll(); // run on load in case page is already scrolled
    }

    /* --- ScrollSpy for Landing Page Navbar (Beranda, Tentang, Mitra, Kontak) --- */
    const navItems = document.querySelectorAll('.desktop-nav a[data-nav], .mobile-menu a[data-nav]');
    if (navItems.length > 0 && document.body.classList.contains('home-page')) {
        const sections = [
            { id: 'beranda', el: document.getElementById('beranda') },
            { id: 'tentang', el: document.getElementById('tentang') },
            { id: 'mitra', el: document.getElementById('mitra') },
            { id: 'kontak', el: document.getElementById('kontak') }
        ];

        function onScrollSpy() {
            const scrollPos = window.scrollY + 130;
            const windowHeight = window.innerHeight;
            const docHeight = document.documentElement.scrollHeight;

            let activeNav = 'beranda';

            if (window.scrollY + windowHeight >= docHeight - 80) {
                activeNav = 'kontak';
            } else {
                for (let i = sections.length - 1; i >= 0; i--) {
                    const sec = sections[i];
                    if (sec.el && scrollPos >= sec.el.offsetTop) {
                        activeNav = sec.id;
                        break;
                    }
                }
            }

            navItems.forEach(link => {
                const navKey = link.getAttribute('data-nav');
                if (navKey === activeNav) {
                    link.classList.add('active');
                } else if (['beranda', 'tentang', 'mitra', 'kontak'].includes(navKey)) {
                    link.classList.remove('active');
                }
            });
        }

        window.addEventListener('scroll', onScrollSpy, { passive: true });
        window.addEventListener('resize', onScrollSpy, { passive: true });
        onScrollSpy();
    }

    /* --- Add to Cart Modal --- */
    const modal = document.getElementById('addToCartModal');
    if (modal) {
        const modalPhoto = document.getElementById('modalPhoto');
        const modalName = document.getElementById('modalName');
        const modalUmkm = document.getElementById('modalUmkm');
        const modalPrice = document.getElementById('modalPrice');
        const modalStock = document.getElementById('modalStock');
        const modalSubtotal = document.getElementById('modalSubtotal');
        const modalForm = document.getElementById('modalForm');
        const modalSubmit = document.getElementById('modalSubmit');
        const modalUnavailable = document.getElementById('modalUnavailable');
        const qtyInput = document.getElementById('qtyInput');
        const qtyMinus = document.getElementById('qtyMinus');
        const qtyPlus = document.getElementById('qtyPlus');
        const modalClose = document.getElementById('modalClose');

        let currentHarga = 0;
        let currentStok = 0;

        function formatRupiah(n) {
            return 'Rp' + n.toLocaleString('id-ID');
        }

        function updateSubtotal() {
            const qty = parseInt(qtyInput.value) || 1;
            modalSubtotal.textContent = formatRupiah(currentHarga * qty);
            qtyMinus.disabled = qty <= 1;
            qtyPlus.disabled = qty >= currentStok;
        }

        function openModal(btn) {
            const id = btn.dataset.productId;
            const name = btn.dataset.productName;
            const umkm = btn.dataset.productUmkm;
            const foto = btn.dataset.productFoto;
            const harga = parseFloat(btn.dataset.productHarga);
            const stok = parseInt(btn.dataset.productStok);
            const available = btn.dataset.productAvailable === '1';
            const url = btn.dataset.productUrl;
            const initial = btn.dataset.productInitial;

            currentHarga = harga;
            currentStok = stok;

            modalName.textContent = name;
            modalUmkm.textContent = umkm;
            modalPrice.textContent = formatRupiah(harga) + ' / unit';
            modalStock.textContent = 'Sisa stok: ' + stok;

            if (foto) {
                modalPhoto.innerHTML = `<img src="${foto}" alt="${name}">`;
            } else {
                modalPhoto.innerHTML = `<div class="product-placeholder"><span>${initial}</span><small>Produk Lokal</small></div>`;
            }

            if (available && stok > 0) {
                modalForm.style.display = '';
                modalUnavailable.style.display = 'none';
                modalForm.action = url;
                qtyInput.value = 1;
                qtyInput.max = stok;
                updateSubtotal();
            } else {
                modalForm.style.display = 'none';
                modalUnavailable.style.display = '';
            }

            modal.classList.add('modal-open');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.remove('modal-open');
            document.body.style.overflow = '';
        }

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.add-cart-btn');
            if (btn) {
                e.preventDefault();
                openModal(btn);
            }
        });

        modalClose.addEventListener('click', closeModal);
        
        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('modal-open')) closeModal();
        });

        qtyMinus.addEventListener('click', function () {
            let v = parseInt(qtyInput.value) || 1;
            if (v > 1) {
                qtyInput.value = v - 1;
                updateSubtotal();
            }
        });

        qtyPlus.addEventListener('click', function () {
            let v = parseInt(qtyInput.value) || 1;
            if (v < currentStok) {
                qtyInput.value = v + 1;
                updateSubtotal();
            }
        });

        qtyInput.addEventListener('change', function () {
            let v = parseInt(qtyInput.value) || 1;
            if (v < 1) v = 1;
            if (v > currentStok) v = currentStok;
            qtyInput.value = v;
            updateSubtotal();
        });
    }

    /* --- Password Visibility Toggle --- */
    document.addEventListener('click', function (e) {
        const toggle = e.target.closest('[data-toggle-password]');
        if (toggle) {
            const field = toggle.closest('.password-field');
            const input = field ? field.querySelector('input') : null;
            if (input) {
                if (input.type === 'password') {
                    input.type = 'text';
                    toggle.classList.remove('bi-eye-slash', 'bi-lock');
                    toggle.classList.add('bi-eye');
                    toggle.setAttribute('title', 'Sembunyikan password');
                } else {
                    input.type = 'password';
                    toggle.classList.remove('bi-eye');
                    toggle.classList.add('bi-eye-slash');
                    toggle.setAttribute('title', 'Lihat password');
                }
            }
        }
    });

    /* --- Custom Dropdown Popup Menu --- */
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
                menu.style.width = Math.max(rect.width, 130) + 'px';

                const estimatedMenuHeight = Math.min(select.options.length * 36 + 12, 220);
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
       LUDES-MARKET INTERACTIVE MOTION & ANIMATION ENGINE
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

    document.querySelectorAll('.button, .button-light, .button-outline, .btn, .round-link, .add-cart-btn, .profile-tab-btn').forEach(btn => {
        btn.addEventListener('click', createRipple);
    });

    /* --- 2. Dynamic Scroll Reveal with Staggering --- */
    const revealSelector = '.product-card, .metric-grid article, .intro-grid, .keroyokan-cta-section, .location-card, .category-card, .seller-card, .card, .form-card, .section-heading, .feature-card, .hero-facts > div';
    const revealElements = document.querySelectorAll(revealSelector);

    if (revealElements.length > 0 && 'IntersectionObserver' in window) {
        revealElements.forEach((el, index) => {
            el.classList.add('ludes-reveal');
            const staggerIndex = (index % 6) + 1;
            el.classList.add(`stagger-${staggerIndex}`);
        });

        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-revealed');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            root: null,
            threshold: 0.08,
            rootMargin: '0px 0px -40px 0px'
        });

        revealElements.forEach(el => revealObserver.observe(el));
    } else {
        revealElements.forEach(el => el.classList.add('is-revealed'));
    }

    /* --- 3. Dynamic Number Count-Up Animation (Linear & Constant Velocity) --- */
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

    const countElements = document.querySelectorAll('.hero-facts strong, .metric-grid strong');
    if (countElements.length > 0 && 'IntersectionObserver' in window) {
        const countObserver = new IntersectionObserver((entries, observer) => {
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
        }, { threshold: 0.2 });

        countElements.forEach(el => countObserver.observe(el));
    }

    /* --- 4. Interactive 3D Card Hover Tilt (Smooth & Constant) --- */
    const tiltCards = document.querySelectorAll('.product-card, .seller-card');
    tiltCards.forEach(card => {
        card.setAttribute('data-tilt-card', 'true');
        card.style.transition = 'transform 0.4s linear, box-shadow 0.4s linear';

        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;

            const rotateX = ((y - centerY) / centerY) * -2.5;
            const rotateY = ((x - centerX) / centerX) * 2.5;

            card.style.transform = `perspective(800px) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg) translateY(-4px)`;
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = '';
        });
    });

    /* --- 5. Cart Badge Bounce Feedback --- */
    document.querySelectorAll('.add-cart-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const cartLink = document.querySelector('.cart-link');
            if (cartLink) {
                cartLink.classList.remove('badge-pop');
                void cartLink.offsetWidth; // Trigger reflow
                cartLink.classList.add('badge-pop');
                setTimeout(() => cartLink.classList.remove('badge-pop'), 600);
            }
        });
    });

    /* --- 6. Interactive Floating Back-To-Top Button --- */
    let backToTop = document.getElementById('ludesBackToTop');
    if (!backToTop) {
        backToTop = document.createElement('button');
        backToTop.id = 'ludesBackToTop';
        backToTop.setAttribute('aria-label', 'Kembali ke atas halaman');
        backToTop.innerHTML = '<i class="bi bi-chevron-up"></i>';
        document.body.appendChild(backToTop);
    }

    window.addEventListener('scroll', () => {
        if (window.scrollY > 350) {
            backToTop.classList.add('is-visible');
        } else {
            backToTop.classList.remove('is-visible');
        }
    }, { passive: true });

    backToTop.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
});

