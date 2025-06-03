// Variables globales
let cart = JSON.parse(localStorage.getItem('yohualli_cart')) || [];
let selectedColor = '';
let estimatedWeight = 50; // gramos base
let hasLoadedBefore = localStorage.getItem('yohualli_loaded') === 'true';

// Productos disponibles
const products = [
    { id: 1, name: 'Figura Personalizada', price: 299, category: 'figuras', image: 'fas fa-cube', description: 'Figuras personalizadas de alta calidad en diferentes materiales y colores.' },
    { id: 2, name: 'Miniatura Arquitectónica', price: 450, category: 'decorativos', image: 'fas fa-home', description: 'Modelos arquitectónicos precisos para presentaciones y exhibiciones.' },
    { id: 3, name: 'Prototipo Funcional', price: 650, category: 'prototipos', image: 'fas fa-tools', description: 'Prototipos funcionales para pruebas y desarrollo de productos.' },
    { id: 4, name: 'Joyería Personalizada', price: 200, category: 'decorativos', image: 'fas fa-gem', description: 'Joyería única y personalizada con diseños exclusivos.' },
    { id: 5, name: 'Repuesto Mecánico', price: 180, category: 'funcionales', image: 'fas fa-cog', description: 'Repuestos mecánicos precisos para maquinaria y equipos.' },
    { id: 6, name: 'Modelo Anatómico', price: 380, category: 'figuras', image: 'fas fa-user-md', description: 'Modelos anatómicos detallados para educación y medicina.' },
    { id: 7, name: 'Herramienta Custom', price: 320, category: 'funcionales', image: 'fas fa-wrench', description: 'Herramientas personalizadas para aplicaciones específicas.' },
    { id: 8, name: 'Arte Decorativo', price: 250, category: 'decorativos', image: 'fas fa-palette', description: 'Piezas artísticas decorativas únicas y personalizadas.' },
    { id: 9, name: 'Prototipo Electrónico', price: 720, category: 'prototipos', image: 'fas fa-microchip', description: 'Carcasas y componentes para dispositivos electrónicos.' },
    { id: 10, name: 'Miniatura de Vehículo', price: 350, category: 'figuras', image: 'fas fa-car', description: 'Miniaturas detalladas de vehículos y transportes.' }
];

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    // Manejar pantalla de carga solo la primera vez
    handleLoadingScreen();
    
    // Cargar carrito
    loadCart();
    
    // Configurar navegación activa
    setActiveNavigation();
    
    // Configurar eventos de formularios si existen
    setupFormEvents();
    
    // Configurar filtros si existen
    setupFilters();
    
    // Cargar productos si es la página de productos
    if (document.getElementById('allProductsGrid')) {
        loadAllProducts();
    }
    
    // Configurar personalización si es la página personalizada
    if (document.getElementById('material')) {
        setupCustomization();
    }
    
    // Configurar menú móvil
    setupMobileMenu();
    
    // Crear partículas si es la página de inicio
    if (document.getElementById('particles')) {
        createParticles();
    }
});

// Manejar pantalla de carga
function handleLoadingScreen() {
    const loadingScreen = document.querySelector('.loading-screen');
    const loadingPercentage = document.querySelector('.loading-percentage');
    
    if (hasLoadedBefore) {
        // Si ya se cargó antes, ocultar inmediatamente
        if (loadingScreen) {
            loadingScreen.classList.add('hide');
        }
    } else {
        // Primera vez, mostrar animación completa con porcentaje
        if (loadingPercentage) {
            animatePercentage(loadingPercentage);
        }
        
        setTimeout(() => {
            if (loadingScreen) {
                loadingScreen.style.display = 'none';
            }
            localStorage.setItem('yohualli_loaded', 'true');
        }, 2000); // Duración reducida
    }
}

// Animar el porcentaje de carga
function animatePercentage(element) {
    let current = 0;
    const target = 100;
    const increment = 2;
    const duration = 1400; // Mismo tiempo que la barra
    const stepTime = duration / (target / increment);
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        element.textContent = current + '%';
    }, stepTime);
}

// Configurar menú móvil
function setupMobileMenu() {
    const mobileBtn = document.querySelector('.mobile-menu-btn');
    const body = document.body;
    
    if (mobileBtn) {
        mobileBtn.addEventListener('click', toggleMobileMenu);
    }
    
    // Crear overlay del menú móvil si no existe
    if (!document.querySelector('.mobile-menu-overlay')) {
        createMobileMenuOverlay();
    }
}

function createMobileMenuOverlay() {
    const overlay = document.createElement('div');
    overlay.className = 'mobile-menu-overlay';
    
    const navLinks = document.createElement('div');
    navLinks.className = 'mobile-nav-links';
    
    // Obtener enlaces de navegación
    const desktopLinks = document.querySelectorAll('.nav-links a');
    desktopLinks.forEach(link => {
        const mobileLink = document.createElement('a');
        mobileLink.href = link.href;
        mobileLink.textContent = link.textContent;
        mobileLink.className = link.className;
        mobileLink.addEventListener('click', () => {
            toggleMobileMenu();
        });
        navLinks.appendChild(mobileLink);
    });
    
    overlay.appendChild(navLinks);
    document.body.appendChild(overlay);
    
    // Cerrar menú al hacer clic en el overlay
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            toggleMobileMenu();
        }
    });
}

function toggleMobileMenu() {
    const mobileBtn = document.querySelector('.mobile-menu-btn');
    const overlay = document.querySelector('.mobile-menu-overlay');
    const body = document.body;
    
    if (overlay && mobileBtn) {
        const isOpen = overlay.classList.contains('active');
        
        if (isOpen) {
            overlay.classList.remove('active');
            mobileBtn.classList.remove('active');
            body.style.overflow = 'auto';
        } else {
            overlay.classList.add('active');
            mobileBtn.classList.add('active');
            body.style.overflow = 'hidden';
        }
    }
}

// Gestión del carrito
function addToCart(id, name, price) {
    const existingItem = cart.find(item => item.id === id);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: id,
            name: name,
            price: price,
            quantity: 1,
            image: products.find(p => p.id === id)?.image || 'fas fa-cube'
        });
    }
    
    updateCartUI();
    showNotification('¡Producto agregado al carrito!', 'success');
    saveCart();
}

function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    updateCartUI();
    showNotification('Producto eliminado del carrito', 'info');
    saveCart();
}

function updateQuantity(id, change) {
    const item = cart.find(item => item.id === id);
    if (item) {
        item.quantity += change;
        if (item.quantity <= 0) {
            removeFromCart(id);
        } else {
            updateCartUI();
            saveCart();
        }
    }
}

function updateCartUI() {
    const cartCount = document.getElementById('cartCount');
    const cartItems = document.getElementById('cartItems');
    const totalAmount = document.getElementById('totalAmount');
    
    if (!cartCount || !cartItems || !totalAmount) return;
    
    // Actualizar contador
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    cartCount.textContent = totalItems;
    
    // Actualizar items del carrito
    if (cart.length === 0) {
        cartItems.innerHTML = `
            <div style="text-align: center; color: var(--text-gray); padding: 2rem;">
                <i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <p>Tu carrito está vacío</p>
            </div>
        `;
    } else {
        cartItems.innerHTML = '';
        let total = 0;
        
        cart.forEach(item => {
            const itemTotal = item.price * item.quantity;
            total += itemTotal;
            
            const cartItem = document.createElement('div');
            cartItem.className = 'cart-item';
            cartItem.innerHTML = `
                <div class="cart-item-image">
                    <i class="${item.image}"></i>
                </div>
                <div class="cart-item-details">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-price">$${item.price}</div>
                    <div class="quantity-controls">
                        <button class="quantity-btn" onclick="updateQuantity(${item.id}, -1)">
                            <i class="fas fa-minus"></i>
                        </button>
                        <span style="margin: 0 0.5rem; color: var(--white); font-weight: bold;">${item.quantity}</span>
                        <button class="quantity-btn" onclick="updateQuantity(${item.id}, 1)">
                            <i class="fas fa-plus"></i>
                        </button>
                        <button class="quantity-btn" onclick="removeFromCart(${item.id})" style="margin-left: 0.5rem; background: var(--accent-purple);">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            cartItems.appendChild(cartItem);
        });
        
        totalAmount.textContent = total;
    }
}

function toggleCart() {
    const cartModal = document.getElementById('cartModal');
    const cartOverlay = document.querySelector('.cart-overlay');
    
    if (cartModal && cartOverlay) {
        cartModal.classList.toggle('open');
        cartOverlay.classList.toggle('active');
        
        // Prevenir scroll del body cuando el carrito está abierto
        if (cartModal.classList.contains('open')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = 'auto';
        }
    }
}

function checkout() {
    if (cart.length === 0) {
        showNotification('Tu carrito está vacío', 'warning');
        return;
    }
    
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const message = `Proceso de pago iniciado por un total de $${total}.\n\nEsta funcionalidad estará disponible próximamente.\n\n¿Deseas ser contactado para proceder con el pago?`;
    
    if (confirm(message)) {
        window.location.href = 'contacto.html';
    }
}

// Gestión de navegación
function setActiveNavigation() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const navLinks = document.querySelectorAll('.nav-links a');
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === currentPage || 
           (currentPage === '' && link.getAttribute('href') === 'index.html')) {
            link.classList.add('active');
        }
    });
}

function toggleMobileMenu() {
    const navLinks = document.querySelector('.nav-links');
    const mobileBtn = document.querySelector('.mobile-menu-btn i');
    
    if (navLinks) {
        navLinks.classList.toggle('mobile-open');
        
        if (mobileBtn) {
            mobileBtn.classList.toggle('fa-bars');
            mobileBtn.classList.toggle('fa-times');
        }
    }
}

// Cargar todos los productos (página productos)
function loadAllProducts() {
    const grid = document.getElementById('allProductsGrid');
    if (!grid) return;
    
    grid.innerHTML = '';
    
    products.forEach((product, index) => {
        const productCard = document.createElement('div');
        productCard.className = 'product-card fade-in';
        productCard.setAttribute('data-category', product.category);
        productCard.style.animationDelay = `${index * 0.1}s`;
        
        productCard.innerHTML = `
            <div class="product-image">
                <i class="${product.image}"></i>
            </div>
            <div class="product-info">
                <h3 class="product-name">${product.name}</h3>
                <p class="product-description">${product.description}</p>
                <p class="product-price">${product.price}</p>
                <button class="add-to-cart" onclick="addToCart(${product.id}, '${product.name}', ${product.price})">
                    <i class="fas fa-cart-plus"></i>
                    <span>Agregar al Carrito</span>
                </button>
            </div>
        `;
        
        grid.appendChild(productCard);
    });
}

// Configurar filtros
function setupFilters() {
    const filterBtns = document.querySelectorAll('.filter-btn');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remover clase activa de todos los botones
            filterBtns.forEach(b => b.classList.remove('active'));
            // Agregar clase activa al botón clickeado
            this.classList.add('active');
            
            const filter = this.getAttribute('data-filter');
            filterProducts(filter);
        });
    });
}

// Filtrar productos
function filterProducts(category) {
    const productCards = document.querySelectorAll('#allProductsGrid .product-card');
    
    productCards.forEach(card => {
        if (category === 'all' || card.getAttribute('data-category') === category) {
            card.style.display = 'block';
            card.style.animation = 'fadeIn 0.5s ease-in-out';
        } else {
            card.style.display = 'none';
        }
    });
}

// Configuración de personalización
function setupCustomization() {
    // Inicializar precio
    updatePrice();
    
    // Configurar eventos de cambio
    const selects = document.querySelectorAll('#material, #size, #quality');
    selects.forEach(select => {
        select.addEventListener('change', updatePrice);
    });
}

function handleFileUpload(input) {
    const file = input.files[0];
    const fileName = document.getElementById('fileName');
    const fileUploadArea = document.querySelector('.file-upload');
    
    if (file) {
        fileName.textContent = file.name;
        fileUploadArea.style.background = 'rgba(0, 212, 255, 0.2)';
        fileUploadArea.style.borderColor = 'var(--neon-blue)';
        
        // Estimar peso basado en el tamaño del archivo (aproximación)
        const fileSizeKB = file.size / 1024;
        estimatedWeight = Math.max(20, Math.min(200, fileSizeKB / 10));
        
        updatePrice();
        showNotification('Archivo cargado correctamente', 'success');
    }
}

function selectColor(element) {
    // Remover selección previa
    document.querySelectorAll('.color-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Seleccionar nuevo color
    element.classList.add('selected');
    selectedColor = element.getAttribute('data-color');
    document.getElementById('selectedColor').textContent = selectedColor;
    updatePrice();
}

function updatePrice() {
    const material = document.getElementById('material')?.value || 'pla';
    const size = document.getElementById('size')?.value || 'small';
    const quality = document.getElementById('quality')?.value || 'draft';
    
    // Precios base por material (por gramo)
    const materialPrices = {
        'pla': 5,
        'abs': 7,
        'petg': 8,
        'tpu': 12,
        'resina': 15
    };
    
    // Multiplicadores por tamaño
    const sizeMultipliers = {
        'small': 1,
        'medium': 2,
        'large': 3,
        'xlarge': 4
    };
    
    // Multiplicadores por calidad
    const qualityMultipliers = {
        'draft': 1,
        'normal': 1.5,
        'high': 2,
        'ultra': 3
    };
    
    const baseWeight = estimatedWeight * sizeMultipliers[size];
    const materialCost = baseWeight * materialPrices[material];
    const finalPrice = Math.round(materialCost * qualityMultipliers[quality]);
    
    // Actualizar interfaz
    const elements = {
        selectedMaterial: document.getElementById('selectedMaterial'),
        selectedSize: document.getElementById('selectedSize'),
        selectedQuality: document.getElementById('selectedQuality'),
        estimatedPrice: document.getElementById('estimatedPrice')
    };
    
    if (elements.selectedMaterial) elements.selectedMaterial.textContent = material.toUpperCase();
    if (elements.selectedSize) {
        const sizeSelect = document.getElementById('size');
        elements.selectedSize.textContent = sizeSelect?.selectedOptions[0]?.text.split(' - ')[0] || 'Pequeño';
    }
    if (elements.selectedQuality) {
        const qualitySelect = document.getElementById('quality');
        elements.selectedQuality.textContent = qualitySelect?.selectedOptions[0]?.text.split(' - ')[0] || 'Borrador';
    }
    if (elements.estimatedPrice) elements.estimatedPrice.textContent = finalPrice;
}

function submitCustomOrder() {
    const fileName = document.getElementById('fileName')?.textContent;
    const material = document.getElementById('selectedMaterial')?.textContent;
    const color = document.getElementById('selectedColor')?.textContent;
    const size = document.getElementById('selectedSize')?.textContent;
    const quality = document.getElementById('selectedQuality')?.textContent;
    const price = document.getElementById('estimatedPrice')?.textContent;
    const notes = document.getElementById('customNotes')?.value || '';
    
    // Validaciones
    if (!fileName || fileName === 'No seleccionado') {
        showNotification('Por favor, selecciona un archivo para continuar.', 'warning');
        return;
    }
    
    if (!selectedColor) {
        showNotification('Por favor, selecciona un color.', 'warning');
        return;
    }
    
    // Crear objeto de pedido personalizado
    const customOrder = {
        id: Date.now(),
        type: 'custom',
        fileName: fileName,
        material: material,
        color: color,
        size: size,
        quality: quality,
        price: parseInt(price),
        notes: notes,
        timestamp: new Date().toLocaleString()
    };
    
    // Guardar en localStorage para referencia
    const customOrders = JSON.parse(localStorage.getItem('yohualli_custom_orders')) || [];
    customOrders.push(customOrder);
    localStorage.setItem('yohualli_custom_orders', JSON.stringify(customOrders));
    
    // Mostrar confirmación
    const message = `¡Cotización enviada exitosamente!\n\nResumen:\nArchivo: ${fileName}\nMaterial: ${material}\nColor: ${color}\nTamaño: ${size}\nCalidad: ${quality}\nPrecio estimado: $${price}\n\nNos contactaremos contigo en las próximas 24 horas.`;
    
    alert(message);
    
    // Limpiar formulario
    resetCustomForm();
}

function resetCustomForm() {
    const form = document.querySelector('.custom-form');
    if (form) {
        // Resetear selects
        document.getElementById('material').selectedIndex = 0;
        document.getElementById('size').selectedIndex = 0;
        document.getElementById('quality').selectedIndex = 0;
        
        // Limpiar archivo
        document.getElementById('fileInput').value = '';
        document.getElementById('fileName').textContent = 'No seleccionado';
        
        // Limpiar color
        document.querySelectorAll('.color-option').forEach(option => {
            option.classList.remove('selected');
        });
        selectedColor = '';
        document.getElementById('selectedColor').textContent = 'No seleccionado';
        
        // Limpiar notas
        document.getElementById('customNotes').value = '';
        
        // Resetear área de subida
        const fileUploadArea = document.querySelector('.file-upload');
        fileUploadArea.style.background = 'rgba(0, 212, 255, 0.05)';
        fileUploadArea.style.borderColor = 'var(--neon-blue)';
        
        // Actualizar precio
        estimatedWeight = 50;
        updatePrice();
    }
}

// Configurar eventos de formularios
function setupFormEvents() {
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', submitContact);
    }
}

function submitContact(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    // Validación básica
    if (!data.name || !data.email || !data.subject || !data.message) {
        showNotification('Por favor, completa todos los campos obligatorios.', 'warning');
        return;
    }
    
    // Guardar contacto en localStorage para referencia
    const contacts = JSON.parse(localStorage.getItem('yohualli_contacts')) || [];
    contacts.push({
        ...data,
        timestamp: new Date().toLocaleString(),
        id: Date.now()
    });
    localStorage.setItem('yohualli_contacts', JSON.stringify(contacts));
    
    // Simular envío
    showNotification('¡Mensaje enviado exitosamente!', 'success');
    
    setTimeout(() => {
        alert(`Gracias ${data.name}, hemos recibido tu mensaje sobre "${data.subject}".\n\nNos pondremos en contacto contigo pronto a ${data.email}.`);
        event.target.reset();
    }, 1000);
}

// Sistema de notificaciones
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = 'notification';
    
    // Diferentes estilos por tipo
    const styles = {
        success: 'background: var(--gradient-1); border-color: #00ff00;',
        warning: 'background: var(--gradient-2); border-color: #ffaa00;',
        error: 'background: linear-gradient(45deg, #ff4444, #cc0000); border-color: #ff0000;',
        info: 'background: var(--gradient-3); border-color: var(--neon-blue);'
    };
    
    notification.style.cssText = styles[type] || styles.info;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : type === 'warning' ? 'exclamation' : type === 'error' ? 'times' : 'info'}"></i>
        ${message}
    `;
    
    document.body.appendChild(notification);
    
    // Animación de entrada
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
        notification.style.opacity = '1';
    }, 100);
    
    // Remover después de 4 segundos
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 4000);
}

// Funciones de almacenamiento
function saveCart() {
    localStorage.setItem('yohualli_cart', JSON.stringify(cart));
}

function loadCart() {
    const savedCart = localStorage.getItem('yohualli_cart');
    if (savedCart) {
        cart = JSON.parse(savedCart);
        updateCartUI();
    }
}

// Efectos visuales adicionales
function addRippleEffect(element, event) {
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;
    
    const ripple = document.createElement('div');
    ripple.style.cssText = `
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: scale(0);
        animation: ripple 0.6s linear;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        pointer-events: none;
    `;
    
    element.style.position = 'relative';
    element.style.overflow = 'hidden';
    element.appendChild(ripple);
    
    setTimeout(() => {
        ripple.remove();
    }, 600);
}

// Añadir efectos ripple a botones
document.addEventListener('click', function(e) {
    if (e.target.matches('button, .cta-button, .add-to-cart')) {
        addRippleEffect(e.target, e);
    }
});

// Smooth scroll para enlaces internos
document.addEventListener('click', function(e) {
    if (e.target.matches('a[href^="#"]')) {
        e.preventDefault();
        const target = document.querySelector(e.target.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    }
});

// Animación CSS para ripple
const rippleStyle = document.createElement('style');
rippleStyle.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(rippleStyle);

// JAVASCRIPT CORREGIDO PARA LOS FILTROS DE GALERÍA

// Función corregida para filtrar galería sin problemas de tamaño
function filterGalleryFixed(category) {
    const filterBtns = document.querySelectorAll('.gallery-filter .filter-btn');
    const galleryItems = document.querySelectorAll('.gallery-item-fixed');
    
    // Actualizar botones activos
    filterBtns.forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent.toLowerCase() === category.toLowerCase() || 
           (category === 'all' && btn.textContent.toLowerCase() === 'todos')) {
            btn.classList.add('active');
        }
    });
    
    // Filtrar items con animación suave SIN cambiar tamaños
    galleryItems.forEach((item, index) => {
        const itemCategory = item.getAttribute('data-category');
        const shouldShow = category === 'all' || itemCategory === category;
        
        if (shouldShow) {
            // Mostrar elemento
            item.style.display = 'block';
            item.style.opacity = '0';
            item.style.transform = 'scale(0.8)';
            
            setTimeout(() => {
                item.style.opacity = '1';
                item.style.transform = 'scale(1)';
            }, index * 100);
        } else {
            // Ocultar elemento
            item.style.opacity = '0';
            item.style.transform = 'scale(0.8)';
            
            setTimeout(() => {
                item.style.display = 'none';
            }, 300);
        }
    });
    
    // Tracking del evento
    if (typeof trackEvent === 'function') {
        trackEvent('gallery_filtered', { category });
    }
}

// Inicialización de filtros corregida
function setupGalleryFiltersFixed() {
    const filterBtns = document.querySelectorAll('.gallery-filter .filter-btn');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const btnText = this.textContent.toLowerCase();
            let category = 'all';
            
            switch(btnText) {
                case 'todos':
                    category = 'all';
                    break;
                case 'prototipos':
                    category = 'prototipos';
                    break;
                case 'figuras':
                    category = 'figuras';
                    break;
                case 'funcional':
                    category = 'funcional';
                    break;
                case 'decorativo':
                    category = 'decorativo';
                    break;
            }
            
            filterGalleryFixed(category);
        });
    });
}

// Función para animar proceso sin problemas
function animateProcessStepsFixed() {
    const steps = document.querySelectorAll('.process-step');
    
    if (steps.length === 0) return;
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                const allSteps = Array.from(steps);
                const stepIndex = allSteps.indexOf(entry.target);
                
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, stepIndex * 150);
                
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.2,
        rootMargin: '0px 0px -30px 0px'
    });
    
    // Configurar estado inicial solo si no están ya visibles
    steps.forEach(step => {
        if (step.style.opacity !== '1') {
            step.style.opacity = '0';
            step.style.transform = 'translateY(20px)';
            step.style.transition = 'all 0.6s ease';
        }
        observer.observe(step);
    });
}

// Prevenir conflictos con el código existente
function initializeFixedSections() {
    // Esperar a que el DOM esté completamente cargado
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                setupGalleryFiltersFixed();
                animateProcessStepsFixed();
            }, 100);
        });
    } else {
        // DOM ya está cargado
        setTimeout(() => {
            setupGalleryFiltersFixed();
            animateProcessStepsFixed();
        }, 100);
    }
}

// Función para limpiar efectos hover problemáticos
function fixHoverEffects() {
    const processImages = document.querySelectorAll('.step-image');
    
    processImages.forEach(image => {
        // Remover cualquier animación problemática existente
        image.style.transition = 'all 0.3s ease';
        
        image.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.1)';
            this.style.borderColor = 'var(--neon-blue)';
            this.style.boxShadow = '0 15px 40px rgba(0, 212, 255, 0.5)';
            
            const icon = this.querySelector('i');
            if (icon) {
                icon.style.transform = 'scale(1.2)';
                icon.style.color = 'var(--neon-blue)';
                icon.style.textShadow = '0 0 15px var(--neon-blue)';
            }
        });
        
        image.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
            this.style.borderColor = 'transparent';
            this.style.boxShadow = '0 10px 30px rgba(0, 212, 255, 0.3)';
            
            const icon = this.querySelector('i');
            if (icon) {
                icon.style.transform = 'scale(1)';
                icon.style.color = 'var(--white)';
                icon.style.textShadow = '0 0 20px rgba(0, 0, 0, 0.3)';
            }
        });
    });
}

// Función para ajustar el espaciado entre secciones
function fixSectionSpacing() {
    const gallerySection = document.querySelector('.gallery-section-fixed');
    const ctaBanner = document.querySelector('.cta-banner-fixed');
    
    if (gallerySection && ctaBanner) {
        // Asegurar que no se empalmen
        gallerySection.style.marginBottom = '6rem';
        ctaBanner.style.marginTop = '2rem';
        ctaBanner.style.clear = 'both';
        ctaBanner.style.position = 'relative';
        ctaBanner.style.zIndex = '1';
    }
}

// Función para optimizar rendimiento en móviles
function optimizeForMobile() {
    if (window.innerWidth <= 768) {
        const galleryItems = document.querySelectorAll('.gallery-item-fixed');
        
        galleryItems.forEach(item => {
            // Reducir efectos pesados en móvil
            item.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            
            item.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            });
        });
    }
}

// Inicializar todo
initializeFixedSections();

// Ejecutar al cargar la página
window.addEventListener('load', function() {
    fixHoverEffects();
    fixSectionSpacing();
    optimizeForMobile();
});

// Ajustar en redimensionamiento
window.addEventListener('resize', function() {
    fixSectionSpacing();
    optimizeForMobile();
});

// Función de utilidad para debugging
function debugGallery() {
    console.log('Gallery items:', document.querySelectorAll('.gallery-item-fixed').length);
    console.log('Filter buttons:', document.querySelectorAll('.gallery-filter .filter-btn').length);
}

// Exportar funciones para uso externo
window.YohualliFixed = {
    filterGallery: filterGalleryFixed,
    setupFilters: setupGalleryFiltersFixed,
    animateProcess: animateProcessStepsFixed,
    debug: debugGallery
};

// JAVASCRIPT PARA ANIMACIÓN DE IMPRESORA 3D

// Variables globales para la animación
let hasSeenAnimation = sessionStorage.getItem('yohualli_animation_seen') === 'true';
let animationProgress = 0;
let percentageInterval;

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    // Si ya vio la animación en esta sesión, omitirla
    if (hasSeenAnimation) {
        skipAnimation();
    } else {
        initializePrinterAnimation();
    }
});

// Inicializar la animación de la impresora
function initializePrinterAnimation() {
    const printerScreen = document.getElementById('printerIntroScreen');
    const mainContent = document.getElementById('mainContent');
    
    if (!printerScreen || !mainContent) return;
    
    // Ocultar contenido principal inicialmente
    mainContent.style.opacity = '0';
    mainContent.style.transform = 'translateY(50px)';
    
    // Iniciar contador de porcentaje
    startPercentageCounter();
    
    // Configurar eventos de teclado para saltar animación
    document.addEventListener('keydown', handleKeyPress);
    
    // Agregar botón de saltar (opcional)
    addSkipButton();
    
    // Iniciar secuencia de animación
    startAnimationSequence();
}

// Iniciar contador de porcentaje
function startPercentageCounter() {
    const percentageElement = document.querySelector('.intro-percentage');
    if (!percentageElement) return;
    
    let currentPercentage = 0;
    const targetPercentage = 100;
    const duration = 6000; // 6 segundos
    const increment = 2;
    const stepTime = duration / (targetPercentage / increment);
    
    percentageInterval = setInterval(() => {
        currentPercentage += increment;
        if (currentPercentage >= targetPercentage) {
            currentPercentage = targetPercentage;
            clearInterval(percentageInterval);
        }
        
        percentageElement.textContent = currentPercentage + '%';
        animationProgress = currentPercentage;
        
        // Cambiar color del porcentaje según el progreso
        if (currentPercentage < 30) {
            percentageElement.style.color = '#ffaa00';
        } else if (currentPercentage < 70) {
            percentageElement.style.color = 'var(--neon-blue)';
        } else {
            percentageElement.style.color = '#00ff00';
        }
        
    }, stepTime);
}

// Secuencia completa de animación
function startAnimationSequence() {
    // Fase 1: Aparición de la impresora (0-2s)
    setTimeout(() => {
        addPrinterSounds();
    }, 500);
    
    // Fase 2: Inicio de impresión (3s)
    setTimeout(() => {
        startPrintingEffects();
    }, 3000);
    
    // Fase 3: Construcción del objeto (3-5s)
    setTimeout(() => {
        highlightObjectConstruction();
    }, 3500);
    
    // Fase 4: Finalización (6s)
    setTimeout(() => {
        finalizePrinting();
    }, 6000);
    
    // Fase 5: Zoom al producto (6.5s)
    setTimeout(() => {
        zoomToProduct();
    }, 6500);
    
    // Fase 6: Mostrar contenido principal (8.5s)
    setTimeout(() => {
        showMainContent();
    }, 8500);
}

// Efectos sonoros simulados (visuales)
function addPrinterSounds() {
    const soundIndicators = document.createElement('div');
    soundIndicators.className = 'sound-indicators';
    soundIndicators.innerHTML = `
        <div class="sound-wave wave-1"></div>
        <div class="sound-wave wave-2"></div>
        <div class="sound-wave wave-3"></div>
    `;
    
    const printerBase = document.querySelector('.printer-base');
    if (printerBase) {
        printerBase.appendChild(soundIndicators);
        
        // Remover después de 3 segundos
        setTimeout(() => {
            if (soundIndicators.parentNode) {
                soundIndicators.parentNode.removeChild(soundIndicators);
            }
        }, 3000);
    }
}

// Iniciar efectos de impresión
function startPrintingEffects() {
    const extruder = document.querySelector('.printer-extruder');
    const nozzle = document.querySelector('.printer-nozzle');
    
    if (extruder) {
        extruder.classList.add('printing-active');
    }
    
    if (nozzle) {
        nozzle.classList.add('heating-active');
    }
    
    // Agregar partículas de material
    addMaterialParticles();
}

// Agregar partículas de material caliente
function addMaterialParticles() {
    const particlesContainer = document.querySelector('.print-particles');
    if (!particlesContainer) return;
    
    for (let i = 0; i < 10; i++) {
        setTimeout(() => {
            const particle = document.createElement('div');
            particle.className = 'material-particle';
            particle.style.cssText = `
                position: absolute;
                width: 2px;
                height: 2px;
                background: #ff6b35;
                border-radius: 50%;
                left: ${Math.random() * 100}%;
                top: 50%;
                animation: materialDrop 1s ease-out forwards;
                box-shadow: 0 0 6px #ff6b35;
            `;
            
            particlesContainer.appendChild(particle);
            
            setTimeout(() => {
                if (particle.parentNode) {
                    particle.parentNode.removeChild(particle);
                }
            }, 1000);
        }, i * 200);
    }
}

// Resaltar construcción del objeto
function highlightObjectConstruction() {
    const layers = document.querySelectorAll('.object-layer');
    
    layers.forEach((layer, index) => {
        setTimeout(() => {
            layer.style.boxShadow = '0 0 20px var(--neon-blue)';
            layer.style.transform = 'translateX(-50%) scale(1.05)';
            
            setTimeout(() => {
                layer.style.boxShadow = '0 2px 10px rgba(0, 212, 255, 0.3)';
                layer.style.transform = 'translateX(-50%) scale(1)';
            }, 300);
        }, index * 200);
    });
}

// Finalizar proceso de impresión
function finalizePrinting() {
    const extruder = document.querySelector('.printer-extruder');
    const nozzle = document.querySelector('.printer-nozzle');
    const completedObject = document.querySelector('.printing-object');

    // Detener efectos de impresión
    if (extruder) {
        extruder.classList.remove('printing-active');
    }

    if (nozzle) {
        nozzle.classList.remove('heating-active');
        nozzle.style.boxShadow = '0 0 10px rgba(255, 69, 0, 0.4)';
    }

    // Resaltar objeto completado
    if (completedObject) {
        completedObject.style.animation = 'objectComplete 1s ease-out forwards';
    }

    // 🔥 Asegurarse de que el mensaje no exista
    const oldMsg = document.querySelector('.completion-message');
    if (oldMsg) oldMsg.remove();

// Función anulada para evitar que se muestre el mensaje
function showCompletionMessage() {
    // Intencionalmente vacía
}

}

// Mostrar mensaje de completado
function showCompletionMessage() {
    const completionMsg = document.createElement('div');
    completionMsg.className = 'completion-message';
    completionMsg.innerHTML = `
        <div class="completion-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="completion-text">¡Impresión Completada!</div>
    `;
    
    completionMsg.style.cssText = `
        position: absolute;
        top: 50%;
        right: -200px;
        transform: translateY(-50%);
        background: var(--gradient-1);
        color: var(--white);
        padding: 1rem 2rem;
        border-radius: 25px;
        font-weight: bold;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        animation: slideInRight 0.8s ease-out forwards;
        z-index: 5;
    `;
    
    const printerContainer = document.querySelector('.printer-animation-container');
    if (printerContainer) {
        printerContainer.appendChild(completionMsg);
        
        setTimeout(() => {
            if (completionMsg.parentNode) {
                completionMsg.style.animation = 'slideOutRight 0.8s ease-out forwards';
                setTimeout(() => {
                    if (completionMsg.parentNode) {
                        completionMsg.parentNode.removeChild(completionMsg);
                    }
                }, 800);
            }
        }, 1500);
    }
}

// Zoom al producto final
function zoomToProduct() {
    const finalProduct = document.getElementById('finalProduct');
    if (finalProduct) {
        finalProduct.style.display = 'block';
    }
    
    // Agregar efectos de partículas para la transición
    createTransitionParticles();
}

// Crear partículas para la transición
function createTransitionParticles() {
    const particleCount = 20;
    const body = document.body;
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.style.cssText = `
            position: fixed;
            width: 6px;
            height: 6px;
            background: var(--neon-blue);
            border-radius: 50%;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            animation: explodeParticle 2s ease-out forwards;
            animation-delay: ${i * 0.05}s;
            z-index: 9999;
            box-shadow: 0 0 10px var(--neon-blue);
        `;
        
        // Dirección aleatoria para cada partícula
        const angle = (i / particleCount) * 360;
        const distance = 200 + Math.random() * 100;
        
        particle.style.setProperty('--end-x', Math.cos(angle * Math.PI / 180) * distance + 'px');
        particle.style.setProperty('--end-y', Math.sin(angle * Math.PI / 180) * distance + 'px');
        
        body.appendChild(particle);
        
        setTimeout(() => {
            if (particle.parentNode) {
                particle.parentNode.removeChild(particle);
            }
        }, 2000);
    }
}

// Mostrar contenido principal
function showMainContent() {
    const printerScreen = document.getElementById('printerIntroScreen');
    const mainContent = document.getElementById('mainContent');
    
    // Marcar animación como vista
    sessionStorage.setItem('yohualli_animation_seen', 'true');
    
    // Ocultar pantalla de animación
    if (printerScreen) {
        printerScreen.style.animation = 'fadeOutIntro 1s ease-in-out forwards';
        setTimeout(() => {
            printerScreen.style.display = 'none';
        }, 1000);
    }
    
    // Mostrar contenido principal
    if (mainContent) {
        setTimeout(() => {
            mainContent.style.opacity = '1';
            mainContent.style.transform = 'translateY(0)';
            mainContent.style.transition = 'all 1s ease-out';
        }, 500);
    }
    
    // Limpiar event listeners
    document.removeEventListener('keydown', handleKeyPress);
    
    // Inicializar funciones normales de la página
    initializeMainPage();
}

// Saltar animación
function skipAnimation() {
    const printerScreen = document.getElementById('printerIntroScreen');
    const mainContent = document.getElementById('mainContent');
    
    // Limpiar intervalos
    if (percentageInterval) {
        clearInterval(percentageInterval);
    }
    
    // Ocultar pantalla de animación inmediatamente
    if (printerScreen) {
        printerScreen.style.display = 'none';
    }
    
    // Mostrar contenido principal inmediatamente
    if (mainContent) {
        mainContent.style.opacity = '1';
        mainContent.style.transform = 'translateY(0)';
    }
    
    // Marcar como vista para esta sesión
    sessionStorage.setItem('yohualli_animation_seen', 'true');
    
    // Inicializar página normal
    initializeMainPage();
}

// Manejar teclas para saltar animación
function handleKeyPress(event) {
    // ESC, SPACE o ENTER para saltar
    if (event.key === 'Escape' || event.key === ' ' || event.key === 'Enter') {
        event.preventDefault();
        skipAnimation();
    }
}

// Agregar botón de saltar
function addSkipButton() {
    const skipButton = document.createElement('button');
    skipButton.innerHTML = `
        <i class="fas fa-forward"></i>
        <span>Saltar</span>
    `;
    skipButton.className = 'skip-animation-btn';
    skipButton.style.cssText = `
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(0, 0, 0, 0.7);
        color: var(--white);
        border: 2px solid var(--accent-gray);
        padding: 10px 20px;
        border-radius: 25px;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: bold;
        transition: all 0.3s ease;
        z-index: 10001;
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    `;
    
    // Efectos hover
    skipButton.addEventListener('mouseenter', () => {
        skipButton.style.background = 'var(--gradient-1)';
        skipButton.style.borderColor = 'var(--neon-blue)';
        skipButton.style.transform = 'translateY(-2px)';
        skipButton.style.boxShadow = '0 5px 15px rgba(0, 212, 255, 0.3)';
    });
    
    skipButton.addEventListener('mouseleave', () => {
        skipButton.style.background = 'rgba(0, 0, 0, 0.7)';
        skipButton.style.borderColor = 'var(--accent-gray)';
        skipButton.style.transform = 'translateY(0)';
        skipButton.style.boxShadow = 'none';
    });
    
    skipButton.addEventListener('click', skipAnimation);
    
    const printerScreen = document.getElementById('printerIntroScreen');
    if (printerScreen) {
        printerScreen.appendChild(skipButton);
    }
}

// Inicializar funciones principales de la página
function initializeMainPage() {
    // Crear partículas del hero si existe
    if (document.getElementById('particles')) {
        createParticles();
    }
    
    // Cargar carrito
    loadCart();
    
    // Animaciones de aparición progresiva para las tarjetas
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.2}s`;
    });
    
    // Configurar otros eventos de la página
    setupPageEvents();
}

// Configurar eventos adicionales de la página
function setupPageEvents() {
    // Efectos de scroll suave
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Efectos de intersección para animaciones
    setupIntersectionObserver();
    
    // Efectos de parallax ligero
    setupParallaxEffects();
}

// Configurar observer para animaciones al hacer scroll
function setupIntersectionObserver() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                
                // Animación especial para tarjetas de productos
                if (entry.target.classList.contains('product-card')) {
                    const delay = Array.from(entry.target.parentNode.children).indexOf(entry.target) * 100;
                    setTimeout(() => {
                        entry.target.style.transform = 'translateY(0)';
                        entry.target.style.opacity = '1';
                    }, delay);
                }
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    // Observar elementos que necesitan animación
    document.querySelectorAll('.product-card, .section-title').forEach(el => {
        observer.observe(el);
        // Estado inicial para animaciones
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.6s ease-out';
    });
}

// Efectos de parallax ligero
function setupParallaxEffects() {
    let ticking = false;
    
    function updateParallax() {
        const scrolled = window.pageYOffset;
        const parallaxElements = document.querySelectorAll('.particles');
        
        parallaxElements.forEach(el => {
            const speed = el.dataset.speed || 0.5;
            el.style.transform = `translateY(${scrolled * speed}px)`;
        });
        
        ticking = false;
    }
    
    function requestTick() {
        if (!ticking) {
            requestAnimationFrame(updateParallax);
            ticking = true;
        }
    }
    
    window.addEventListener('scroll', requestTick);
}

// Crear partículas para el hero
function createParticles() {
    const particlesContainer = document.getElementById('particles');
    if (!particlesContainer) return;
    
    const particleCount = 60;
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 20 + 's';
        particle.style.animationDuration = (Math.random() * 15 + 15) + 's';
        
        // Variación en el tamaño de las partículas
        const size = Math.random() * 3 + 1;
        particle.style.width = size + 'px';
        particle.style.height = size + 'px';
        
        particlesContainer.appendChild(particle);
    }
}

// Funciones de utilidad para la animación
function addCustomStyles() {
    const style = document.createElement('style');
    style.textContent = `
        /* Estilos adicionales para la animación */
        .sound-indicators {
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 5px;
        }
        
        .sound-wave {
            width: 4px;
            height: 15px;
            background: var(--neon-blue);
            border-radius: 2px;
            animation: soundBounce 0.8s ease-in-out infinite;
        }
        
        .wave-1 { animation-delay: 0s; }
        .wave-2 { animation-delay: 0.2s; }
        .wave-3 { animation-delay: 0.4s; }
        
        @keyframes soundBounce {
            0%, 100% { transform: scaleY(1); opacity: 0.7; }
            50% { transform: scaleY(1.5); opacity: 1; }
        }
        
        .printing-active {
            animation: printerWorking 2s ease-in-out infinite;
        }
        
        @keyframes printerWorking {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            25% { transform: translateX(-45%) translateY(-2px); }
            75% { transform: translateX(-55%) translateY(-2px); }
        }
        
        .heating-active {
            animation: nozzleActiveHeat 1s ease-in-out infinite;
        }
        
        @keyframes nozzleActiveHeat {
            0%, 100% { 
                box-shadow: 0 0 20px rgba(255, 69, 0, 0.8);
                background: linear-gradient(180deg, #ff6b35, #ff4500);
            }
            50% { 
                box-shadow: 0 0 40px rgba(255, 69, 0, 1);
                background: linear-gradient(180deg, #ff9500, #ff6b35);
            }
        }
        
        @keyframes materialDrop {
            0% { 
                opacity: 1; 
                transform: translateY(0) scale(1); 
            }
            100% { 
                opacity: 0; 
                transform: translateY(30px) scale(0.5); 
            }
        }
        
        @keyframes objectComplete {
            0% { 
                transform: translateX(-50%) scale(1); 
                box-shadow: none;
            }
            50% { 
                transform: translateX(-50%) scale(1.1); 
                box-shadow: 0 0 30px var(--neon-blue);
            }
            100% { 
                transform: translateX(-50%) scale(1); 
                box-shadow: 0 5px 20px rgba(0, 212, 255, 0.3);
            }
        }
        
        @keyframes slideInRight {
            0% { 
                right: -200px; 
                opacity: 0; 
            }
            100% { 
                right: 20px; 
                opacity: 1; 
            }
        }
        
        @keyframes slideOutRight {
            0% { 
                right: 20px; 
                opacity: 1; 
            }
            100% { 
                right: -200px; 
                opacity: 0; 
            }
        }
        
        @keyframes explodeParticle {
            0% { 
                transform: translate(-50%, -50%) scale(1);
                opacity: 1;
            }
            100% { 
                transform: translate(
                    calc(-50% + var(--end-x)), 
                    calc(-50% + var(--end-y))
                ) scale(0);
                opacity: 0;
            }
        }
        
        .completion-icon {
            font-size: 1.5rem;
            color: #00ff00;
            margin-bottom: 0.5rem;
            text-align: center;
        }
        
        .completion-text {
            font-size: 0.9rem;
            text-align: center;
            white-space: nowrap;
        }
        
        /* Animación para aparecer elementos */
        .animate-in {
            opacity: 1 !important;
            transform: translateY(0) !important;
        }
        
        /* Responsive para botón de saltar */
        @media (max-width: 768px) {
            .skip-animation-btn {
                top: 10px !important;
                right: 10px !important;
                padding: 8px 16px !important;
                font-size: 0.8rem !important;
            }
            
            .skip-animation-btn span {
                display: none;
            }
        }
    `;
    
    document.head.appendChild(style);
}

// Detección de preferencias de movimiento reducido
function respectsReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

// Versión reducida de la animación para usuarios que prefieren menos movimiento
function showReducedAnimation() {
    const printerScreen = document.getElementById('printerIntroScreen');
    const mainContent = document.getElementById('mainContent');
    
    if (printerScreen && mainContent) {
        // Mostrar solo el logo y progreso por 3 segundos
        printerScreen.style.animation = 'fadeOutIntro 1s ease-in-out 3s forwards';
        
        // Simplificar animaciones
        const allAnimatedElements = printerScreen.querySelectorAll('*');
        allAnimatedElements.forEach(el => {
            el.style.animation = 'none';
        });
        
        // Solo mostrar texto principal
        const introText = document.querySelector('.intro-text');
        if (introText) {
            introText.style.opacity = '1';
        }
        
        setTimeout(() => {
            showMainContent();
        }, 3500);
    }
}

// Funciones de debug y utilidad
function debugAnimation() {
    console.log('Animation Progress:', animationProgress + '%');
    console.log('Has Seen Animation:', hasSeenAnimation);
    console.log('Reduced Motion:', respectsReducedMotion());
}

// Función para reiniciar la animación (útil para desarrollo)
function resetAnimation() {
    sessionStorage.removeItem('yohualli_animation_seen');
    location.reload();
}

// Función para forzar saltar la animación en futuras visitas
function skipAnimationPermanently() {
    localStorage.setItem('yohualli_skip_animation', 'true');
    sessionStorage.setItem('yohualli_animation_seen', 'true');
    skipAnimation();
}

// Verificar si debe saltar permanentemente
function shouldSkipPermanently() {
    return localStorage.getItem('yohualli_skip_animation') === 'true';
}

// Inicialización mejorada que considera todas las preferencias
function improvedInitialization() {
    // Verificar preferencias del usuario
    if (shouldSkipPermanently() || respectsReducedMotion()) {
        skipAnimation();
        return;
    }
    
    // Si ya vió la animación en esta sesión
    if (hasSeenAnimation) {
        skipAnimation();
        return;
    }
    
    // Agregar estilos personalizados
    addCustomStyles();
    
    // Decidir qué tipo de animación mostrar
    if (respectsReducedMotion()) {
        showReducedAnimation();
    } else {
        initializePrinterAnimation();
    }
}

// Event listeners adicionales
window.addEventListener('load', function() {
    // Precargar recursos críticos
    const criticalImages = document.querySelectorAll('img[data-critical]');
    criticalImages.forEach(img => {
        if (img.dataset.src) {
            img.src = img.dataset.src;
        }
    });
});

// Manejo de visibilidad de la página
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // Pausar animaciones cuando la página no es visible
        const animatedElements = document.querySelectorAll('[style*="animation"]');
        animatedElements.forEach(el => {
            el.style.animationPlayState = 'paused';
        });
    } else {
        // Reanudar animaciones
        const animatedElements = document.querySelectorAll('[style*="animation"]');
        animatedElements.forEach(el => {
            el.style.animationPlayState = 'running';
        });
    }
});

// Exportar funciones para uso global
window.YohualliAnimation = {
    skip: skipAnimation,
    reset: resetAnimation,
    debug: debugAnimation,
    skipPermanently: skipAnimationPermanently
};

// Inicialización mejorada al cargar el DOM
document.addEventListener('DOMContentLoaded', improvedInitialization);

// Función para asegurar que los gradientes se muestren correctamente
function fixGradientTitles() {
    const gradientElements = document.querySelectorAll('.section-title, .hero h1, .intro-title, .logo');
    
    gradientElements.forEach(element => {
        // Forzar re-render
        element.style.opacity = '0.99';
        setTimeout(() => {
            element.style.opacity = '1';
        }, 50);
    });
}

// Ejecutar cuando la página esté completamente cargada
window.addEventListener('load', function() {
    setTimeout(fixGradientTitles, 100);
});

// También ejecutar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(fixGradientTitles, 100);
});
// FUNCIÓN CORREGIDA PARA EL MENÚ MÓVIL
// Agregar este código al final de script.js

// Variables globales para el menú móvil
let mobileMenuOpen = false;

// Función principal para toggle del menú móvil
function toggleMobileMenu() {
    const mobileBtn = document.querySelector('.mobile-menu-btn');
    const overlay = document.querySelector('.mobile-menu-overlay');
    const body = document.body;
    
    console.log('Toggle móvil activado'); // Para debug
    
    if (!overlay) {
        console.log('Creando overlay móvil');
        createMobileMenuOverlay();
        // Llamar de nuevo después de crear el overlay
        setTimeout(() => toggleMobileMenu(), 100);
        return;
    }
    
    mobileMenuOpen = !mobileMenuOpen;
    
    if (mobileMenuOpen) {
        // Abrir menú
        overlay.classList.add('active');
        mobileBtn.classList.add('active');
        body.style.overflow = 'hidden';
        
        // Cambiar icono
        const icon = mobileBtn.querySelector('i');
        if (icon) {
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-times');
        }
        
        console.log('Menú móvil abierto');
    } else {
        // Cerrar menú
        overlay.classList.remove('active');
        mobileBtn.classList.remove('active');
        body.style.overflow = 'auto';
        
        // Cambiar icono
        const icon = mobileBtn.querySelector('i');
        if (icon) {
            icon.classList.remove('fa-times');
            icon.classList.add('fa-bars');
        }
        
        console.log('Menú móvil cerrado');
    }
}

// Función para crear el overlay del menú móvil
function createMobileMenuOverlay() {
    // Verificar si ya existe
    if (document.querySelector('.mobile-menu-overlay')) {
        return;
    }
    
    const overlay = document.createElement('div');
    overlay.className = 'mobile-menu-overlay';
    
    const navLinks = document.createElement('div');
    navLinks.className = 'mobile-nav-links';
    
    // Obtener enlaces de navegación del menú desktop
    const desktopLinks = document.querySelectorAll('.nav-links a');
    
    desktopLinks.forEach(link => {
        const mobileLink = document.createElement('a');
        mobileLink.href = link.href;
        mobileLink.textContent = link.textContent;
        mobileLink.className = link.className;
        
        // Cerrar menú al hacer clic en un enlace
        mobileLink.addEventListener('click', () => {
            toggleMobileMenu();
        });
        
        navLinks.appendChild(mobileLink);
    });
    
    overlay.appendChild(navLinks);
    document.body.appendChild(overlay);
    
    // Cerrar menú al hacer clic en el overlay (fondo)
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            toggleMobileMenu();
        }
    });
    
    console.log('Overlay móvil creado');
}

// Función mejorada para configurar el menú móvil
function setupMobileMenuImproved() {
    const mobileBtn = document.querySelector('.mobile-menu-btn');
    
    if (mobileBtn) {
        // Limpiar eventos anteriores
        mobileBtn.removeEventListener('click', toggleMobileMenu);
        
        // Agregar nuevo evento
        mobileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleMobileMenu();
        });
        
        console.log('Menú móvil configurado correctamente');
    } else {
        console.error('Botón móvil no encontrado');
    }
    
    // Crear overlay si no existe
    if (!document.querySelector('.mobile-menu-overlay')) {
        createMobileMenuOverlay();
    }
}

// Cerrar menú móvil con tecla ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && mobileMenuOpen) {
        toggleMobileMenu();
    }
});

// Cerrar menú al redimensionar la ventana (si se agranda)
window.addEventListener('resize', function() {
    if (window.innerWidth > 968 && mobileMenuOpen) {
        toggleMobileMenu();
    }
});

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando menú móvil mejorado');
    setupMobileMenuImproved();
});

// También inicializar cuando la página esté completamente cargada
window.addEventListener('load', function() {
    setupMobileMenuImproved();
});

// Función de debug para verificar elementos
function debugMobileMenu() {
    console.log('=== DEBUG MENÚ MÓVIL ===');
    console.log('Botón móvil:', document.querySelector('.mobile-menu-btn'));
    console.log('Overlay móvil:', document.querySelector('.mobile-menu-overlay'));
    console.log('Enlaces desktop:', document.querySelectorAll('.nav-links a').length);
    console.log('Estado del menú:', mobileMenuOpen);
    console.log('Ancho de ventana:', window.innerWidth);
}
// ===== JAVASCRIPT PARA AUTENTICACIÓN - AGREGAR A script.js =====

// Variables globales para autenticación
let isUserLoggedIn = false;
let currentUser = null;

// Inicialización de autenticación al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    checkAuthStatus();
    setupAuthEventListeners();
});

// Verificar estado de autenticación
function checkAuthStatus() {
    // Simular verificación de sesión (en producción esto vendría del servidor)
    const userData = localStorage.getItem('yohualli_user');
    const sessionToken = localStorage.getItem('yohualli_session');
    
    if (userData && sessionToken) {
        try {
            currentUser = JSON.parse(userData);
            isUserLoggedIn = true;
            showUserMenu();
            hideAuthButtons();
        } catch (e) {
            console.error('Error parsing user data:', e);
            logout();
        }
    } else {
        showAuthButtons();
        hideUserMenu();
    }
}

// Mostrar menú de usuario logueado
function showUserMenu() {
    const userMenu = document.getElementById('userMenu');
    const authButtons = document.getElementById('authButtons');
    
    if (userMenu && currentUser) {
        userMenu.classList.add('active');
        userMenu.style.display = 'block';
        
        // Actualizar información del usuario
        const userInitial = document.getElementById('userInitial');
        const userName = document.getElementById('userName');
        const userEmail = document.getElementById('userEmail');
        
        if (userInitial) {
            userInitial.textContent = currentUser.nombre ? currentUser.nombre.charAt(0).toUpperCase() : 'U';
        }
        
        if (userName) {
            userName.textContent = currentUser.nombre + ' ' + (currentUser.apellidos || '');
        }
        
        if (userEmail) {
            userEmail.textContent = currentUser.email;
        }
    }
    
    if (authButtons) {
        authButtons.style.display = 'none';
    }
}

// Ocultar menú de usuario
function hideUserMenu() {
    const userMenu = document.getElementById('userMenu');
    if (userMenu) {
        userMenu.classList.remove('active');
        userMenu.style.display = 'none';
    }
}

// Mostrar botones de autenticación
function showAuthButtons() {
    const authButtons = document.getElementById('authButtons');
    if (authButtons) {
        authButtons.style.display = 'flex';
    }
}

// Ocultar botones de autenticación
function hideAuthButtons() {
    const authButtons = document.getElementById('authButtons');
    if (authButtons) {
        authButtons.style.display = 'none';
    }
}

// Toggle del dropdown de usuario
function toggleUserDropdown() {
    const dropdown = document.getElementById('userDropdown');
    const overlay = document.querySelector('.dropdown-overlay');
    
    if (!dropdown) return;
    
    const isOpen = dropdown.classList.contains('show');
    
    if (isOpen) {
        closeUserDropdown();
    } else {
        openUserDropdown();
    }
}

// Abrir dropdown de usuario
function openUserDropdown() {
    const dropdown = document.getElementById('userDropdown');
    let overlay = document.querySelector('.dropdown-overlay');
    
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'dropdown-overlay';
        document.body.appendChild(overlay);
        
        // Cerrar dropdown al hacer clic en el overlay
        overlay.addEventListener('click', closeUserDropdown);
    }
    
    if (dropdown) {
        dropdown.classList.add('show');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

// Cerrar dropdown de usuario
function closeUserDropdown() {
    const dropdown = document.getElementById('userDropdown');
    const overlay = document.querySelector('.dropdown-overlay');
    
    if (dropdown) {
        dropdown.classList.remove('show');
    }
    
    if (overlay) {
        overlay.classList.remove('active');
    }
    
    document.body.style.overflow = 'auto';
}

// Configurar event listeners para autenticación
function setupAuthEventListeners() {
    // Cerrar dropdown con tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeUserDropdown();
        }
    });
    
    // Prevenir que el dropdown se cierre al hacer clic dentro de él
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        dropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
    
    // Event listeners para botones de autenticación
    const loginBtn = document.querySelector('.login-btn');
    const registerBtn = document.querySelector('.register-btn');
    
    if (loginBtn) {
        loginBtn.addEventListener('click', function(e) {
            // Si estás usando enlaces, no necesitas prevenir default
            // Si usas JavaScript para manejar el login, descomenta la siguiente línea:
            // e.preventDefault();
            // handleLogin();
        });
    }
    
    if (registerBtn) {
        registerBtn.addEventListener('click', function(e) {
            // Si estás usando enlaces, no necesitas prevenir default
            // Si usas JavaScript para manejar el registro, descomenta la siguiente línea:
            // e.preventDefault();
            // handleRegister();
        });
    }
}

// Función para simular login (para pruebas - eliminar en producción)
function simulateLogin(userData = null) {
    const testUser = userData || {
        id: 1,
        nombre: 'Juan',
        apellidos: 'Pérez',
        email: 'juan.perez@example.com',
        rol: 'usuario'
    };
    
    // Simular datos de sesión
    localStorage.setItem('yohualli_user', JSON.stringify(testUser));
    localStorage.setItem('yohualli_session', 'fake_session_token_' + Date.now());
    
    currentUser = testUser;
    isUserLoggedIn = true;
    
    showUserMenu();
    hideAuthButtons();
    
    showNotification('Bienvenido de vuelta, ' + testUser.nombre + '!', 'success');
    
    // Redirigir según el rol del usuario
    if (testUser.rol === 'admin') {
        // Opcional: redirigir a dashboard de admin
        console.log('Usuario admin logueado');
    }
}

// Función de logout
function logout() {
    // Limpiar datos de sesión
    localStorage.removeItem('yohualli_user');
    localStorage.removeItem('yohualli_session');
    
    currentUser = null;
    isUserLoggedIn = false;
    
    hideUserMenu();
    showAuthButtons();
    closeUserDropdown();
    
    showNotification('Sesión cerrada correctamente', 'info');
    
    // Redirigir a página principal si está en página protegida
    if (window.location.pathname.includes('dashboard') || 
        window.location.pathname.includes('profile') || 
        window.location.pathname.includes('admin')) {
        window.location.href = 'index.html';
    }
}

// Función para manejar login desde formulario
function handleLoginSubmit(formData) {
    const loginBtn = document.querySelector('.login-btn');
    
    if (loginBtn) {
        loginBtn.classList.add('loading');
        loginBtn.textContent = 'Iniciando...';
    }
    
    // Simular petición de login (reemplazar con llamada real a la API)
    setTimeout(() => {
        // Simular respuesta exitosa
        const userData = {
            id: 1,
            nombre: formData.get('nombre') || 'Usuario',
            apellidos: formData.get('apellidos') || '',
            email: formData.get('email'),
            rol: 'usuario'
        };
        
        simulateLogin(userData);
        
        if (loginBtn) {
            loginBtn.classList.remove('loading');
            loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> <span>Iniciar Sesión</span>';
        }
    }, 1500);
}

// Función para verificar si el usuario está logueado (para páginas protegidas)
function requireAuth() {
    if (!isUserLoggedIn) {
        showNotification('Debes iniciar sesión para acceder a esta página', 'warning');
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 2000);
        return false;
    }
    return true;
}

// Función para verificar si el usuario es admin (para páginas de admin)
function requireAdmin() {
    if (!isUserLoggedIn || currentUser?.rol !== 'admin') {
        showNotification('No tienes permisos para acceder a esta página', 'error');
        setTimeout(() => {
            window.location.href = 'index.html';
        }, 2000);
        return false;
    }
    return true;
}

// Función para actualizar el carrito cuando el usuario se loguea
function syncCartWithUser() {
    if (isUserLoggedIn && currentUser) {
        // Aquí puedes sincronizar el carrito local con el carrito del usuario en el servidor
        console.log('Sincronizando carrito para usuario:', currentUser.email);
        
        // Ejemplo: enviar carrito local al servidor
        const localCart = JSON.parse(localStorage.getItem('yohualli_cart')) || [];
        if (localCart.length > 0) {
            // syncCartToServer(localCart);
        }
    }
}

// Función para mostrar notificaciones de autenticación
function showAuthNotification(message, type = 'info', duration = 4000) {
    // Usar la función showNotification existente o crear una específica para auth
    if (typeof showNotification === 'function') {
        showNotification(message, type);
    } else {
        // Fallback si no existe la función showNotification
        alert(message);
    }
}

// Función para manejar errores de autenticación
function handleAuthError(error) {
    console.error('Error de autenticación:', error);
    
    let message = 'Error de autenticación';
    
    switch (error.code) {
        case 'INVALID_CREDENTIALS':
            message = 'Email o contraseña incorrectos';
            break;
        case 'USER_NOT_FOUND':
            message = 'Usuario no encontrado';
            break;
        case 'ACCOUNT_DISABLED':
            message = 'Cuenta deshabilitada. Contacta al administrador';
            break;
        case 'SESSION_EXPIRED':
            message = 'Sesión expirada. Por favor, inicia sesión nuevamente';
            logout();
            break;
        default:
            message = error.message || 'Error desconocido';
    }
    
    showAuthNotification(message, 'error');
}

// Función para refrescar token de sesión
function refreshAuthToken() {
    const sessionToken = localStorage.getItem('yohualli_session');
    
    if (!sessionToken || !isUserLoggedIn) {
        return Promise.reject('No hay sesión activa');
    }
    
    // En producción, hacer petición al servidor para refrescar el token
    return new Promise((resolve, reject) => {
        // Simular petición
        setTimeout(() => {
            const newToken = 'refreshed_token_' + Date.now();
            localStorage.setItem('yohualli_session', newToken);
            resolve(newToken);
        }, 1000);
    });
}

// Verificar sesión periódicamente
function startSessionCheck() {
    // Verificar sesión cada 15 minutos
    setInterval(() => {
        if (isUserLoggedIn) {
            refreshAuthToken()
                .catch(() => {
                    handleAuthError({ code: 'SESSION_EXPIRED' });
                });
        }
    }, 15 * 60 * 1000); // 15 minutos
}

// Integración con menú móvil para mostrar opciones de auth
function updateMobileMenuAuth() {
    const mobileOverlay = document.querySelector('.mobile-menu-overlay');
    const mobileNavLinks = document.querySelector('.mobile-nav-links');
    
    if (!mobileNavLinks) return;
    
    // Remover auth móvil existente
    const existingAuth = mobileNavLinks.querySelector('.auth-mobile');
    if (existingAuth) {
        existingAuth.remove();
    }
    
    // Crear nueva sección de auth para móvil
    const authMobile = document.createElement('div');
    authMobile.className = 'auth-mobile';
    
    if (isUserLoggedIn && currentUser) {
        // Usuario logueado - mostrar opciones de usuario
        authMobile.innerHTML = `
            <div style="text-align: center; padding: 1rem; background: var(--gradient-1); border-radius: 15px; margin-bottom: 1rem;">
                <div style="color: var(--white); font-weight: bold; margin-bottom: 0.5rem;">
                    ${currentUser.nombre} ${currentUser.apellidos || ''}
                </div>
                <div style="color: var(--white); opacity: 0.9; font-size: 0.9rem;">
                    ${currentUser.email}
                </div>
            </div>
            <a href="dashboard.php" class="auth-btn login-btn">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="profile.php" class="auth-btn register-btn">
                <i class="fas fa-user"></i>
                <span>Mi Perfil</span>
            </a>
            <button onclick="logout()" class="auth-btn" style="background: #ff6b6b; border-color: #ff6b6b;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar Sesión</span>
            </button>
        `;
    } else {
        // Usuario no logueado - mostrar botones de login/register
        authMobile.innerHTML = `
            <a href="login.php" class="auth-btn login-btn">
                <i class="fas fa-sign-in-alt"></i>
                <span>Iniciar Sesión</span>
            </a>
            <a href="register.php" class="auth-btn register-btn">
                <i class="fas fa-user-plus"></i>
                <span>Registrarse</span>
            </a>
        `;
    }
    
    authMobile.classList.add('show');
    mobileNavLinks.appendChild(authMobile);
}

// Función para testing - remover en producción
function testAuth() {
    console.log('=== TESTING AUTH ===');
    console.log('Estado login:', isUserLoggedIn);
    console.log('Usuario actual:', currentUser);
    
    if (!isUserLoggedIn) {
        console.log('Simulando login...');
        simulateLogin();
    } else {
        console.log('Simulando logout...');
        logout();
    }
}

// Iniciar verificación de sesión
startSessionCheck();

// Actualizar menú móvil cuando cambie el estado de auth
function updateAuthState() {
    updateMobileMenuAuth();
    syncCartWithUser();
}

// Llamar updateAuthState cuando cambie el estado de autenticación
document.addEventListener('authStateChanged', updateAuthState);

// Event personalizado para cambios de estado de auth
function triggerAuthStateChange() {
    const event = new CustomEvent('authStateChanged', {
        detail: {
            isLoggedIn: isUserLoggedIn,
            user: currentUser
        }
    });
    document.dispatchEvent(event);
}

// Modificar las funciones de login y logout para disparar el evento
const originalSimulateLogin = simulateLogin;
simulateLogin = function(userData) {
    originalSimulateLogin(userData);
    triggerAuthStateChange();
};

const originalLogout = logout;
logout = function() {
    originalLogout();
    triggerAuthStateChange();
};

// Exponer funciones globales para debugging (remover en producción)
window.YohualliAuth = {
    login: simulateLogin,
    logout: logout,
    test: testAuth,
    getUser: () => currentUser,
    isLoggedIn: () => isUserLoggedIn
};