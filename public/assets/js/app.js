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
});
