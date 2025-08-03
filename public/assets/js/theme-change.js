document.addEventListener('DOMContentLoaded', () => {
    const toggleSwitch = document.querySelector('.theme-switch input[type="checkbox"]');
    const metaThemeColor = document.querySelector('meta[name="theme-color"]');

    /**
     * Применяет тему к сайту, обновляет meta theme-color и чекбокс
     * @param {string} theme - 'light' или 'dark'
     */
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem('theme', theme);

        if (metaThemeColor) {
            metaThemeColor.setAttribute('content', theme === 'dark' ? '#000' : '#d4e5ea');
        }

        if (toggleSwitch) {
            toggleSwitch.checked = theme === 'dark';
        }

        // Если используешь UIkit классы для темы, раскомментируй:
        // document.body.classList.toggle('uk-dark', theme === 'dark');
        // document.body.classList.toggle('uk-light', theme !== 'dark');
    }

    // Получаем тему из data-bs-theme (установлен preload-скриптом) или fallback на 'light'
    const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';

    // Применяем тему
    applyTheme(currentTheme);

    // Слушаем изменения чекбокса
    if (toggleSwitch) {
        toggleSwitch.addEventListener('change', (e) => {
            const theme = e.target.checked ? 'dark' : 'light';
            applyTheme(theme);
        });
    }
});
