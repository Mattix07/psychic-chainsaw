/**
 * EventsMaster - JavaScript
 * Netflix-style interactions with cart and theme management
 */

document.addEventListener('DOMContentLoaded', function() {

    // ==========================================
    // THEME MANAGEMENT (with auto detection)
    // ==========================================
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');

    // Get system theme preference
    function getSystemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    // Load saved theme or auto-detect
    function loadTheme() {
        const savedTheme = localStorage.getItem('em_theme') || 'auto';
        applyTheme(savedTheme);
    }

    // Apply theme based on setting
    function applyTheme(setting) {
        let actualTheme;
        if (setting === 'auto') {
            actualTheme = getSystemTheme();
        } else {
            actualTheme = setting;
        }
        document.documentElement.setAttribute('data-theme', actualTheme);
        updateThemeIcon(setting);
        updateThemeButtons(setting);
    }

    function updateThemeIcon(theme) {
        if (themeIcon) {
            if (theme === 'auto') {
                themeIcon.className = 'fas fa-circle-half-stroke';
            } else {
                themeIcon.className = theme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
            }
        }
    }

    function updateThemeButtons(setting) {
        document.querySelectorAll('.theme-opt').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.theme === setting);
        });
    }

    function toggleTheme() {
        const currentSetting = localStorage.getItem('em_theme') || 'auto';
        // Cycle: auto -> light -> dark -> auto
        let newSetting;
        if (currentSetting === 'auto') {
            newSetting = 'light';
        } else if (currentSetting === 'light') {
            newSetting = 'dark';
        } else {
            newSetting = 'auto';
        }
        localStorage.setItem('em_theme', newSetting);
        applyTheme(newSetting);
    }

    function setTheme(theme) {
        localStorage.setItem('em_theme', theme);
        applyTheme(theme);
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }

    // Listen for system theme changes (when set to auto)
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        const setting = localStorage.getItem('em_theme') || 'auto';
        if (setting === 'auto') {
            applyTheme('auto');
        }
    });

    // Theme option buttons in dropdown
    document.querySelectorAll('.theme-opt').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            setTheme(btn.dataset.theme);
        });
    });

    loadTheme();

    // ==========================================
    // USER DROPDOWN MENU
    // ==========================================
    const userDropdown = document.querySelector('.user-dropdown');
    const userDropdownToggle = document.getElementById('userDropdownToggle');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    if (userDropdownToggle && userDropdownMenu) {
        userDropdownToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('open');
        });

        // Close on click outside
        document.addEventListener('click', (e) => {
            if (!userDropdown.contains(e.target)) {
                userDropdown.classList.remove('open');
            }
        });

        // Close on ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                userDropdown.classList.remove('open');
            }
        });
    }

    // ==========================================
    // HEADER SCROLL EFFECT
    // ==========================================
    const header = document.getElementById('header');

    if (header) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }

    // ==========================================
    // SEARCH BAR TOGGLE
    // ==========================================
    const searchBar = document.getElementById('searchBar');
    const searchToggle = document.getElementById('searchToggle');

    if (searchBar && searchToggle) {
        searchToggle.addEventListener('click', function() {
            searchBar.classList.toggle('active');
            const input = searchBar.querySelector('input[type="text"]');
            if (searchBar.classList.contains('active') && input) {
                input.focus();
            }
        });

        // Close on click outside
        document.addEventListener('click', function(e) {
            if (!searchBar.contains(e.target) && searchBar.classList.contains('active')) {
                searchBar.classList.remove('active');
            }
        });

        // Submit on Enter
        const searchInput = searchBar.querySelector('input[type="text"]');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchBar.querySelector('form').submit();
                }
            });
        }
    }

    // ==========================================
    // CAROUSEL NAVIGATION
    // ==========================================
    const carouselNavButtons = document.querySelectorAll('.carousel-nav');

    carouselNavButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const carouselId = this.getAttribute('data-carousel');
            const carousel = document.getElementById(carouselId);

            if (!carousel) return;

            const scrollAmount = carousel.offsetWidth * 0.8;

            if (this.classList.contains('prev')) {
                carousel.scrollBy({
                    left: -scrollAmount,
                    behavior: 'smooth'
                });
            } else {
                carousel.scrollBy({
                    left: scrollAmount,
                    behavior: 'smooth'
                });
            }
        });
    });

    // ==========================================
    // CAROUSEL TOUCH SCROLL (Mobile)
    // ==========================================
    const carousels = document.querySelectorAll('.carousel');

    carousels.forEach(function(carousel) {
        let isDown = false;
        let startX;
        let scrollLeft;

        carousel.addEventListener('mousedown', function(e) {
            isDown = true;
            carousel.style.cursor = 'grabbing';
            startX = e.pageX - carousel.offsetLeft;
            scrollLeft = carousel.scrollLeft;
        });

        carousel.addEventListener('mouseleave', function() {
            isDown = false;
            carousel.style.cursor = 'grab';
        });

        carousel.addEventListener('mouseup', function() {
            isDown = false;
            carousel.style.cursor = 'grab';
        });

        carousel.addEventListener('mousemove', function(e) {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - carousel.offsetLeft;
            const walk = (x - startX) * 2;
            carousel.scrollLeft = scrollLeft - walk;
        });
    });

    // ==========================================
    // CATEGORY SHORTCUTS
    // ==========================================
    const categoryButtons = document.querySelectorAll('.category-btn');

    categoryButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            categoryButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            // TODO: Filter events by category
        });
    });

    // ==========================================
    // AUTO-HIDE ALERTS
    // ==========================================
    const alerts = document.querySelectorAll('.alert');

    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            alert.style.transition = 'all 0.5s ease';
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 5000);
    });

    // ==========================================
    // CARD HOVER EFFECTS
    // ==========================================
    const eventCards = document.querySelectorAll('.event-card');

    eventCards.forEach(function(card) {
        // Prevent navigation when clicking action buttons
        const actionButtons = card.querySelectorAll('.card-action-btn');
        actionButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                // TODO: Handle action (add to favorites, etc.)
                this.classList.toggle('active');
            });
        });
    });

    // ==========================================
    // FORM VALIDATION
    // ==========================================
    const forms = document.querySelectorAll('form');

    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let valid = true;

            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = '#e50914';
                    field.classList.add('error');
                } else {
                    field.style.borderColor = '';
                    field.classList.remove('error');
                }
            });

            if (!valid) {
                e.preventDefault();
            }
        });
    });

    // ==========================================
    // PASSWORD CONFIRMATION
    // ==========================================
    const passwordConfirm = document.getElementById('password_confirm');

    if (passwordConfirm) {
        passwordConfirm.addEventListener('input', function() {
            const password = document.getElementById('password');
            if (password && password.value !== passwordConfirm.value) {
                passwordConfirm.setCustomValidity('Le password non coincidono');
                passwordConfirm.style.borderColor = '#e50914';
            } else {
                passwordConfirm.setCustomValidity('');
                passwordConfirm.style.borderColor = '';
            }
        });
    }

    // ==========================================
    // LAZY LOADING IMAGES
    // ==========================================
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    observer.unobserve(img);
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(function(img) {
            imageObserver.observe(img);
        });
    }

    // ==========================================
    // SMOOTH SCROLL FOR ANCHOR LINKS
    // ==========================================
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });

    // ==========================================
    // KEYBOARD NAVIGATION
    // ==========================================
    document.addEventListener('keydown', function(e) {
        // ESC to close search
        if (e.key === 'Escape' && searchBar && searchBar.classList.contains('active')) {
            searchBar.classList.remove('active');
        }

        // "/" to focus search
        if (e.key === '/' && searchBar && !e.target.matches('input, textarea')) {
            e.preventDefault();
            searchBar.classList.add('active');
            const input = searchBar.querySelector('input[type="text"]');
            if (input) input.focus();
        }
    });

    // ==========================================
    // PRELOAD CRITICAL IMAGES
    // ==========================================
    const heroImage = document.querySelector('.hero-bg');
    if (heroImage) {
        const bgImage = heroImage.style.backgroundImage;
        const urlMatch = bgImage.match(/url\(['"]?([^'"]+)['"]?\)/);
        if (urlMatch && urlMatch[1]) {
            const img = new Image();
            img.src = urlMatch[1];
        }
    }

});

// ==========================================
// UTILITY FUNCTIONS
// ==========================================

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Format price
 */
function formatPrice(price) {
    return new Intl.NumberFormat('it-IT', {
        style: 'currency',
        currency: 'EUR'
    }).format(price);
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i> ${message}`;
    toast.style.position = 'fixed';
    toast.style.bottom = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '9999';

    document.body.appendChild(toast);

    setTimeout(function() {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100px)';
        toast.style.transition = 'all 0.5s ease';
        setTimeout(() => toast.remove(), 500);
    }, 3000);
}

// ==========================================
// CART MANAGEMENT (Server API + localStorage fallback)
// ==========================================
const Cart = {
    STORAGE_KEY: 'em_cart',
    _cache: null, // Cache per carrello server

    // Check if user is logged in
    isLoggedIn() {
        return window.EventsMaster?.isLoggedIn === true;
    },

    // Get CSRF token
    getCsrfToken() {
        return window.EventsMaster?.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
    },

    // Get cart (from server if logged in, localStorage otherwise)
    get() {
        if (this.isLoggedIn() && this._cache !== null) {
            return this._cache;
        }

        try {
            const cart = localStorage.getItem(this.STORAGE_KEY);
            if (!cart) return [];

            const parsed = JSON.parse(cart);
            return parsed.filter(item => item && (item.eventoId || item.idEvento));
        } catch (e) {
            console.error('Error reading cart:', e);
            return [];
        }
    },

    // Save cart to localStorage (for guest)
    save(cart) {
        try {
            localStorage.setItem(this.STORAGE_KEY, JSON.stringify(cart));
            this.updateUI();
        } catch (e) {
            console.error('Error saving cart:', e);
        }
    },

    // Add item to cart
    async add(item) {
        if (this.isLoggedIn()) {
            return await this.addToServer(item);
        }

        // Guest: use localStorage
        const cart = this.get();
        const existingIndex = cart.findIndex(i =>
            (i.eventoId || i.idEvento) === (item.eventoId || item.idEvento) &&
            (i.tipoId || i.idClasse) === (item.tipoId || item.idClasse)
        );

        if (existingIndex >= 0) {
            cart[existingIndex].quantity = (cart[existingIndex].quantity || 1) + (item.quantity || 1);
        } else {
            cart.push({
                ...item,
                quantity: item.quantity || 1,
                addedAt: Date.now()
            });
        }

        this.save(cart);
        showToast('Biglietto aggiunto al carrello', 'success');
        return cart;
    },

    // Add to server cart (for logged in users)
    async addToServer(item) {
        try {
            const formData = new FormData();
            formData.append('idEvento', item.eventoId || item.idEvento);
            formData.append('idClasse', item.tipoId || item.idClasse || 'Standard');
            formData.append('quantita', item.quantity || 1);
            formData.append('csrf_token', this.getCsrfToken());

            const response = await fetch('index.php?action=cart_add', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this._cache = result.cart;
                showToast(result.message, 'success');
                this.updateUI();
                return result.cart;
            } else {
                showToast(result.error || 'Errore nell\'aggiunta al carrello', 'error');
                return null;
            }
        } catch (e) {
            console.error('Error adding to server cart:', e);
            showToast('Errore di connessione', 'error');
            return null;
        }
    },

    // Remove item from cart
    async remove(indexOrId) {
        if (this.isLoggedIn()) {
            return await this.removeFromServer(indexOrId);
        }

        const cart = this.get();
        if (indexOrId >= 0 && indexOrId < cart.length) {
            cart.splice(indexOrId, 1);
            this.save(cart);
        }
        return cart;
    },

    // Remove from server cart
    async removeFromServer(idBiglietto) {
        try {
            const formData = new FormData();
            formData.append('idBiglietto', idBiglietto);
            formData.append('csrf_token', this.getCsrfToken());

            const response = await fetch('index.php?action=cart_remove', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this._cache = result.cart;
                this.updateUI();
                showToast(result.message, 'success');
            }
            return result.cart;
        } catch (e) {
            console.error('Error removing from server cart:', e);
            return null;
        }
    },

    // Update item (for server cart: update ticket data)
    async updateItem(idBiglietto, data) {
        if (!this.isLoggedIn()) return;

        try {
            const formData = new FormData();
            formData.append('idBiglietto', idBiglietto);
            formData.append('csrf_token', this.getCsrfToken());

            if (data.nome !== undefined) formData.append('nome', data.nome);
            if (data.cognome !== undefined) formData.append('cognome', data.cognome);
            if (data.sesso !== undefined) formData.append('sesso', data.sesso);
            if (data.idClasse !== undefined) formData.append('idClasse', data.idClasse);

            const response = await fetch('index.php?action=cart_update', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this._cache = result.cart;
            }
            return result;
        } catch (e) {
            console.error('Error updating cart item:', e);
            return null;
        }
    },

    // Update item quantity (for localStorage cart)
    updateQuantity(index, quantity) {
        const cart = this.get();
        if (index >= 0 && index < cart.length) {
            if (quantity <= 0) {
                return this.remove(index);
            }
            cart[index].quantity = quantity;
            this.save(cart);
        }
        return cart;
    },

    // Clear cart
    async clear() {
        if (this.isLoggedIn()) {
            try {
                const formData = new FormData();
                formData.append('csrf_token', this.getCsrfToken());

                await fetch('index.php?action=cart_clear', {
                    method: 'POST',
                    body: formData
                });
                this._cache = [];
            } catch (e) {
                console.error('Error clearing server cart:', e);
            }
        }

        localStorage.removeItem(this.STORAGE_KEY);
        this.updateUI();
    },

    // Get total price
    getTotal() {
        const cart = this.get();
        return cart.reduce((total, item) => {
            const price = item.price || item.prezzo || 0;
            const qty = item.quantity || 1;
            return total + (price * qty);
        }, 0);
    },

    // Get total items count
    getCount() {
        const cart = this.get();
        return cart.reduce((count, item) => count + (item.quantity || 1), 0);
    },

    // Load cart from server
    async loadFromServer() {
        if (!this.isLoggedIn()) return;

        try {
            const response = await fetch('index.php?action=cart_get');
            const result = await response.json();

            this._cache = result.cart || [];
            this.updateUI();
        } catch (e) {
            console.error('Error loading cart from server:', e);
        }
    },

    // Check availability before adding
    async checkAvailability(idEvento, quantita = 1) {
        try {
            const response = await fetch(`index.php?action=check_availability&idEvento=${idEvento}&quantita=${quantita}`);
            return await response.json();
        } catch (e) {
            console.error('Error checking availability:', e);
            return { disponibile: true, illimitati: true };
        }
    },

    // Update UI elements
    updateUI() {
        const badge = document.getElementById('cartBadge');
        const cartItems = document.getElementById('cartItems');
        const cartEmpty = document.getElementById('cartEmpty');
        const cartFooter = document.getElementById('cartFooter');
        const cartTotal = document.getElementById('cartTotal');

        const cart = this.get();
        const count = this.getCount();

        // Update badge
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }

        // Update cart sidebar content
        if (cartItems && cartEmpty && cartFooter) {
            if (cart.length === 0) {
                cartEmpty.style.display = 'flex';
                cartItems.innerHTML = '';
                cartFooter.style.display = 'none';
            } else {
                cartEmpty.style.display = 'none';
                cartFooter.style.display = 'block';

                cartItems.innerHTML = cart.map((item, index) => {
                    const eventName = item.eventName || item.eventoNome || 'Evento';
                    const ticketType = item.ticketType || item.idClasse || 'Standard';
                    const eventDate = item.eventDate || item.eventoData || '';
                    const price = item.price || item.prezzo || 0;
                    const qty = item.quantity || 1;
                    const itemId = item.id || index;

                    return `
                    <div class="cart-item" data-index="${index}" data-id="${itemId}">
                        <div class="cart-item-image">
                            <img src="${item.image || 'public/img/placeholder-event.jpg'}" alt="${eventName}">
                        </div>
                        <div class="cart-item-info">
                            <h4>${eventName}</h4>
                            <p class="cart-item-type">${ticketType}</p>
                            <p class="cart-item-date">${eventDate}</p>
                            <div class="cart-item-price">${formatPrice(price)}</div>
                        </div>
                        <div class="cart-item-actions">
                            ${this.isLoggedIn() ? `
                            <button class="cart-item-remove" data-id="${itemId}">
                                <i class="fas fa-trash"></i>
                            </button>
                            ` : `
                            <div class="quantity-control">
                                <button class="qty-btn minus" data-index="${index}">-</button>
                                <span class="qty-value">${qty}</span>
                                <button class="qty-btn plus" data-index="${index}">+</button>
                            </div>
                            <button class="cart-item-remove" data-index="${index}">
                                <i class="fas fa-trash"></i>
                            </button>
                            `}
                        </div>
                    </div>
                `}).join('');

                // Bind events
                this.bindCartItemEvents();
            }

            // Update total
            if (cartTotal) {
                cartTotal.textContent = formatPrice(this.getTotal());
            }
        }
    },

    // Bind cart item events
    bindCartItemEvents() {
        // Remove buttons
        document.querySelectorAll('.cart-item-remove').forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (this.isLoggedIn()) {
                    const id = parseInt(e.currentTarget.dataset.id);
                    this.remove(id);
                } else {
                    const index = parseInt(e.currentTarget.dataset.index);
                    this.remove(index);
                }
            });
        });

        // Quantity buttons (solo per guest)
        if (!this.isLoggedIn()) {
            document.querySelectorAll('.qty-btn.minus').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const index = parseInt(e.currentTarget.dataset.index);
                    const cart = this.get();
                    this.updateQuantity(index, (cart[index].quantity || 1) - 1);
                });
            });

            document.querySelectorAll('.qty-btn.plus').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const index = parseInt(e.currentTarget.dataset.index);
                    const cart = this.get();
                    this.updateQuantity(index, (cart[index].quantity || 1) + 1);
                });
            });
        }
    },

    // Merge local cart with server cart (after login)
    async mergeWithServer() {
        const localCart = this.get();
        if (localCart.length === 0 || !this.isLoggedIn()) return;

        // Add each local item to server cart
        for (const item of localCart) {
            await this.addToServer(item);
        }

        // Clear local cart
        localStorage.removeItem(this.STORAGE_KEY);

        // Reload from server
        await this.loadFromServer();
    }
};

// ==========================================
// CART SIDEBAR TOGGLE
// ==========================================
(function() {
    const cartToggle = document.getElementById('cartToggle');
    const cartSidebar = document.getElementById('cartSidebar');
    const cartOverlay = document.getElementById('cartOverlay');
    const cartClose = document.getElementById('cartClose');
    const modalClose = document.getElementById('modalClose');
    const cartMergeModal = document.getElementById('cartMergeModal');

    function openCart() {
        if (cartSidebar) {
            cartSidebar.classList.add('active');
            cartOverlay?.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeCart() {
        if (cartSidebar) {
            cartSidebar.classList.remove('active');
            cartOverlay?.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    if (cartToggle) {
        cartToggle.addEventListener('click', openCart);
    }

    if (cartClose) {
        cartClose.addEventListener('click', closeCart);
    }

    if (cartOverlay) {
        cartOverlay.addEventListener('click', closeCart);
    }

    if (modalClose) {
        modalClose.addEventListener('click', () => {
            cartMergeModal?.classList.remove('active');
        });
    }

    // ESC to close cart
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeCart();
            cartMergeModal?.classList.remove('active');
        }
    });

    // Initialize cart
    if (Cart.isLoggedIn()) {
        // Carica carrello dal server
        Cart.loadFromServer().then(() => {
            // Se c'è un carrello locale, uniscilo
            const localCart = JSON.parse(localStorage.getItem(Cart.STORAGE_KEY) || '[]');
            if (localCart.length > 0) {
                Cart.mergeWithServer();
            }
        });
    } else {
        Cart.updateUI();
    }
})();

// ==========================================
// ADD TO CART FUNCTION (Global)
// ==========================================
// Supporta sia chiamata con parametri separati che con oggetto singolo
window.addToCart = async function(eventoIdOrItem, tipoId, eventName, ticketType, price, eventDate, image, quantity = 1) {
    let item;

    // Se il primo parametro è un oggetto, usalo direttamente
    if (typeof eventoIdOrItem === 'object') {
        item = eventoIdOrItem;
    } else {
        // Altrimenti costruisci l'oggetto dai parametri
        item = {
            eventoId: eventoIdOrItem,
            idEvento: eventoIdOrItem,
            tipoId: tipoId,
            idClasse: tipoId,
            eventName: eventName,
            ticketType: ticketType,
            price: parseFloat(price),
            eventDate: eventDate,
            image: image,
            quantity: quantity
        };
    }

    // Verifica disponibilità se loggato
    if (Cart.isLoggedIn()) {
        const availability = await Cart.checkAvailability(
            item.eventoId || item.idEvento,
            item.quantity || 1
        );

        if (!availability.disponibile) {
            const msg = availability.illimitati
                ? 'Errore nel controllo disponibilità'
                : `Solo ${availability.rimanenti} biglietti disponibili`;
            showToast(msg, 'error');
            return null;
        }
    }

    return await Cart.add(item);
};

// Update cart count badge
window.updateCartCount = function() {
    Cart.updateUI();
};

// ==========================================
// CAROUSEL SCROLL FUNCTION (Global)
// ==========================================
function scrollCarousel(button, direction) {
    const wrapper = button.closest('.carousel-wrapper');
    const carousel = wrapper.querySelector('.carousel-row');

    if (!carousel) return;

    const scrollAmount = carousel.offsetWidth * 0.8;

    carousel.scrollBy({
        left: scrollAmount * direction,
        behavior: 'smooth'
    });
}
