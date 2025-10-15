/**
 * Dark Mode Manager para Cooperativa Agrícola
 * Maneja el cambio entre modo claro y oscuro
 */
class DarkModeManager {
    constructor() {
        this.theme = localStorage.getItem('theme') || 'light';
        this.init();
    }

    init() {
        this.setTheme(this.theme);
        this.createToggle();
        this.bindEvents();
    }

    setTheme(theme) {
        this.theme = theme;
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        this.updateToggleIcon();
    }

    createToggle() {
        // No crear botón flotante - Solo usar el toggle del perfil de usuario
        this.toggle = null;
    }

    bindEvents() {
        // Solo escuchar cambios desde el perfil de usuario
        document.addEventListener('darkModeChanged', (e) => {
            this.setTheme(e.detail.enabled ? 'dark' : 'light');
        });
    }

    updateToggleIcon() {
        // No hay icono flotante que actualizar
    }

    // Método para notificar cambios desde el perfil
    notifyProfileChange(enabled) {
        const event = new CustomEvent('darkModeChanged', {
            detail: { enabled }
        });
        document.dispatchEvent(event);
    }
}

// Inicializar el modo oscuro
const darkModeManager = new DarkModeManager();

// Función para inicializar el toggle del perfil
function initProfileDarkMode() {
    const darkModeToggle = document.getElementById('darkMode');
    if (darkModeToggle) {
        // Establecer el estado inicial
        const currentTheme = localStorage.getItem('theme') || 'light';
        darkModeToggle.checked = currentTheme === 'dark';
        
        // Event listener para cambios
        darkModeToggle.addEventListener('change', function() {
            darkModeManager.notifyProfileChange(this.checked);
        });
    }
}

// Función para inicializar el toggle en login y register
function initLoginRegisterDarkMode() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        // Establecer el estado inicial
        const currentTheme = localStorage.getItem('theme') || 'light';
        updateToggleIcon(darkModeToggle, currentTheme);
        
        // Event listener para cambios
        darkModeToggle.addEventListener('click', function() {
            const currentTheme = localStorage.getItem('theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            darkModeManager.setTheme(newTheme);
            updateToggleIcon(this, newTheme);
        });
    }
}

// Función para actualizar el icono del toggle
function updateToggleIcon(toggle, theme) {
    const icon = toggle.querySelector('i');
    if (icon) {
        if (theme === 'dark') {
            icon.className = 'fas fa-sun';
            toggle.title = 'Cambiar a modo claro';
        } else {
            icon.className = 'fas fa-moon';
            toggle.title = 'Cambiar a modo oscuro';
        }
    }
}

// Aplicar modo oscuro a elementos dinámicos
function applyDarkModeToDynamicElements() {
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        // Aplicar estilos de modo oscuro a elementos nuevos
                        if (node.classList && node.classList.contains('card')) {
                            if (document.documentElement.getAttribute('data-theme') === 'dark') {
                                node.style.background = 'var(--bg-secondary)';
                                node.style.borderColor = 'var(--border-color)';
                                node.style.color = 'var(--text-primary)';
                            }
                        }
                    }
                });
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    initProfileDarkMode();
    initLoginRegisterDarkMode();
    applyDarkModeToDynamicElements();
});

// Exportar para uso global
window.darkModeManager = darkModeManager;