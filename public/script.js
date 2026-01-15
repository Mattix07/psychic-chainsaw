/**
 * EventsMaster - JavaScript
 * Netflix-style interactions with cart and theme management
 */

document.addEventListener('DOMContentLoaded', function() {

    // ==========================================
    // THEME MANAGEMENT
    // ==========================================
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');

    // Load saved theme or default to dark
    function loadTheme() {
        const savedTheme = localStorage.getItem('em_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
        updateThemeIcon(savedTheme);
    }

    function updateThemeIcon(theme) {
        if (themeIcon) {
            themeIcon.className = theme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
        }
    }

    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('em_theme', newTheme);
        updateThemeIcon(newTheme);
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }

    loadTheme();

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
// CART MANAGEMENT (localStorage)
// ==========================================
const Cart = {
    STORAGE_KEY: 'em_cart',

    // Get cart from localStorage
    get() {
        try {
            const cart = localStorage.getItem(this.STORAGE_KEY);
            return cart ? JSON.parse(cart) : [];
        } catch (e) {
            console.error('Error reading cart:', e);
            return [];
        }
    },

    // Save cart to localStorage
    save(cart) {
        try {
            localStorage.setItem(this.STORAGE_KEY, JSON.stringify(cart));
            this.updateUI();
        } catch (e) {
            console.error('Error saving cart:', e);
        }
    },

    // Add item to cart
    add(item) {
        const cart = this.get();
        // Check if same ticket type for same event already exists
        const existingIndex = cart.findIndex(i =>
            i.eventoId === item.eventoId && i.tipoId === item.tipoId
        );

        if (existingIndex >= 0) {
            cart[existingIndex].quantity += item.quantity || 1;
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

    // Remove item from cart
    remove(index) {
        const cart = this.get();
        if (index >= 0 && index < cart.length) {
            cart.splice(index, 1);
            this.save(cart);
        }
        return cart;
    },

    // Update item quantity
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
    clear() {
        localStorage.removeItem(this.STORAGE_KEY);
        this.updateUI();
    },

    // Get total price
    getTotal() {
        const cart = this.get();
        return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    },

    // Get total items count
    getCount() {
        const cart = this.get();
        return cart.reduce((count, item) => count + item.quantity, 0);
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

                cartItems.innerHTML = cart.map((item, index) => `
                    <div class="cart-item" data-index="${index}">
                        <div class="cart-item-image">
                            <img src="${item.image || 'public/img/placeholder-event.jpg'}" alt="${item.eventName}">
                        </div>
                        <div class="cart-item-info">
                            <h4>${item.eventName}</h4>
                            <p class="cart-item-type">${item.ticketType}</p>
                            <p class="cart-item-date">${item.eventDate}</p>
                            <div class="cart-item-price">${formatPrice(item.price)}</div>
                        </div>
                        <div class="cart-item-actions">
                            <div class="quantity-control">
                                <button class="qty-btn minus" data-index="${index}">-</button>
                                <span class="qty-value">${item.quantity}</span>
                                <button class="qty-btn plus" data-index="${index}">+</button>
                            </div>
                            <button class="cart-item-remove" data-index="${index}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `).join('');

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
                const index = parseInt(e.currentTarget.dataset.index);
                this.remove(index);
            });
        });

        // Quantity buttons
        document.querySelectorAll('.qty-btn.minus').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const index = parseInt(e.currentTarget.dataset.index);
                const cart = this.get();
                this.updateQuantity(index, cart[index].quantity - 1);
            });
        });

        document.querySelectorAll('.qty-btn.plus').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const index = parseInt(e.currentTarget.dataset.index);
                const cart = this.get();
                this.updateQuantity(index, cart[index].quantity + 1);
            });
        });
    },

    // Merge local cart with server cart (after login)
    async mergeWithServer() {
        const localCart = this.get();
        if (localCart.length === 0 || !window.EventsMaster?.isLoggedIn) return;

        try {
            const response = await fetch('index.php?action=get_server_cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.EventsMaster.csrfToken
                }
            });

            const serverCart = await response.json();

            if (serverCart.length === 0) {
                // No server cart, just save local to server
                await this.saveToServer(localCart);
                return;
            }

            // Check for duplicates
            const duplicates = localCart.filter(localItem =>
                serverCart.some(serverItem =>
                    serverItem.eventoId === localItem.eventoId &&
                    serverItem.tipoId === localItem.tipoId
                )
            );

            if (duplicates.length > 0) {
                // Show merge modal
                this.showMergeModal(localCart, serverCart, duplicates);
            } else {
                // No duplicates, merge directly
                const merged = [...serverCart, ...localCart];
                await this.saveToServer(merged);
                this.clear();
            }
        } catch (e) {
            console.error('Error merging cart:', e);
        }
    },

    // Show merge modal for duplicates
    showMergeModal(localCart, serverCart, duplicates) {
        const modal = document.getElementById('cartMergeModal');
        const duplicatesList = document.getElementById('duplicatesList');

        if (!modal || !duplicatesList) return;

        duplicatesList.innerHTML = duplicates.map(item => `
            <div class="duplicate-item">
                <strong>${item.eventName}</strong> - ${item.ticketType}
            </div>
        `).join('');

        modal.classList.add('active');

        // Keep local button
        document.getElementById('keepLocal')?.addEventListener('click', async () => {
            await this.saveToServer(localCart);
            this.clear();
            modal.classList.remove('active');
        }, { once: true });

        // Merge button
        document.getElementById('mergeCart')?.addEventListener('click', async () => {
            // Merge by summing quantities for duplicates
            const merged = [...serverCart];
            localCart.forEach(localItem => {
                const existingIndex = merged.findIndex(i =>
                    i.eventoId === localItem.eventoId && i.tipoId === localItem.tipoId
                );
                if (existingIndex >= 0) {
                    merged[existingIndex].quantity += localItem.quantity;
                } else {
                    merged.push(localItem);
                }
            });
            await this.saveToServer(merged);
            this.clear();
            modal.classList.remove('active');
        }, { once: true });
    },

    // Save cart to server
    async saveToServer(cart) {
        try {
            await fetch('index.php?action=save_cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.EventsMaster.csrfToken
                },
                body: JSON.stringify({ cart })
            });
        } catch (e) {
            console.error('Error saving cart to server:', e);
        }
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

    // Initialize cart UI
    Cart.updateUI();

    // Check for cart merge after login
    if (window.EventsMaster?.isLoggedIn && window.EventsMaster?.redirectAfterLogin) {
        Cart.mergeWithServer();
    }
})();

// ==========================================
// ADD TO CART FUNCTION (Global)
// ==========================================
function addToCart(eventoId, tipoId, eventName, ticketType, price, eventDate, image) {
    Cart.add({
        eventoId,
        tipoId,
        eventName,
        ticketType,
        price: parseFloat(price),
        eventDate,
        image
    });
}
