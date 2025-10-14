/**
 * Sistema de Modo Oscuro - Cooperativa Agrícola La Pintada
 * Maneja el cambio entre modo claro y oscuro con persistencia
 */

class DarkModeManager {
    constructor() {
        this.theme = localStorage.getItem('theme') || 'light';
        this.toggle = null;
        this.init();
    }

    init() {
        this.createToggle();
        this.applyTheme();
        this.bindEvents();
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

    toggleTheme() {
        const newTheme = this.theme === 'light' ? 'dark' : 'light';
        this.setTheme(newTheme);
        
        // Notificar cambio al perfil de usuario
        this.notifyProfileChange(newTheme === 'dark');
    }

    setTheme(theme) {
        this.theme = theme;
        localStorage.setItem('theme', theme);
        this.applyTheme();
        this.updateToggleIcon();
        this.updateProfileToggle();
    }

    applyTheme() {
        document.documentElement.setAttribute('data-theme', this.theme);
        
        // Aplicar transición suave
        document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
        
        // Actualizar meta theme-color para móviles
        this.updateMetaThemeColor();
        
        // Notificar a otros componentes
        window.dispatchEvent(new CustomEvent('themeChanged', {
            detail: { theme: this.theme }
        }));
    }

    updateToggleIcon() {
        // No hay icono flotante que actualizar
    }

    updateProfileToggle() {
        // Actualizar el toggle en el modal de perfil si está abierto
        const profileToggle = document.getElementById('darkMode');
        if (profileToggle) {
            profileToggle.checked = this.theme === 'dark';
        }
    }

    updateMetaThemeColor() {
        let metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (!metaThemeColor) {
            metaThemeColor = document.createElement('meta');
            metaThemeColor.name = 'theme-color';
            document.head.appendChild(metaThemeColor);
        }
        
        metaThemeColor.content = this.theme === 'dark' ? '#0f172a' : '#f5f5f5';
    }

    notifyProfileChange(isDark) {
        // Notificar al sistema de perfil
        const event = new CustomEvent('darkModeChanged', {
            detail: { enabled: isDark }
        });
        document.dispatchEvent(event);
    }

    // Método público para obtener el tema actual
    getCurrentTheme() {
        return this.theme;
    }

    // Método público para verificar si está en modo oscuro
    isDarkMode() {
        return this.theme === 'dark';
    }

    // Método para sincronizar con preferencias del sistema
    syncWithSystemPreference() {
        if (localStorage.getItem('theme') === null) {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            this.setTheme(prefersDark ? 'dark' : 'light');
        }
    }
}

// Inicializar el gestor de modo oscuro
const darkModeManager = new DarkModeManager();

// Sincronizar con preferencias del sistema al cargar
document.addEventListener('DOMContentLoaded', () => {
    darkModeManager.syncWithSystemPreference();
});

// Escuchar cambios en las preferencias del sistema
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    if (localStorage.getItem('theme') === null) {
        darkModeManager.setTheme(e.matches ? 'dark' : 'light');
    }
});

// Función para integrar con el modal de perfil
function initializeProfileDarkMode() {
    const profileToggle = document.getElementById('darkMode');
    if (profileToggle) {
        // Sincronizar el estado inicial
        profileToggle.checked = darkModeManager.isDarkMode();
        
        // Escuchar cambios en el toggle del perfil
        profileToggle.addEventListener('change', (e) => {
            darkModeManager.setTheme(e.target.checked ? 'dark' : 'light');
        });
    }
}

// Función para aplicar modo oscuro a elementos dinámicos
function applyDarkModeToDynamicElements() {
    // Aplicar a elementos que se crean dinámicamente
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    // Aplicar estilos de modo oscuro a nuevos elementos
                    if (node.classList && node.classList.contains('modal-content')) {
                        node.setAttribute('data-theme', darkModeManager.getCurrentTheme());
                    }
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

// Inicializar observador de elementos dinámicos
document.addEventListener('DOMContentLoaded', () => {
    applyDarkModeToDynamicElements();
});

// Exportar para uso global
window.DarkModeManager = darkModeManager;
window.initializeProfileDarkMode = initializeProfileDarkMode;
