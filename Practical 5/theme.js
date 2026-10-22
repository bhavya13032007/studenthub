(() => {
    const storageKey = 'wdf-dark-theme';
    const darkThemeStyles = `
        html.dark-theme, html.dark-theme body {
            background-color: rgb(10, 25, 47) !important;
            color: rgb(224, 238, 255) !important;
        }
        html.dark-theme body * {
            color: rgb(224, 238, 255) !important;
            border-color: rgb(112, 168, 224) !important;
        }
        html.dark-theme h1,
        html.dark-theme .table {
            background-color: rgb(20, 54, 92) !important;
        }
        html.dark-theme th {
            background-color: rgb(31, 78, 121) !important;
        }
        html.dark-theme input,
        html.dark-theme textarea,
        html.dark-theme select {
            background-color: rgb(20, 54, 92) !important;
        }
        #theme-toggle {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 1000;
            padding: 10px 14px;
            border: 1px solid rgb(13, 47, 87);
            border-radius: 4px;
            background-color: rgb(224, 238, 255);
            color: rgb(13, 47, 87);
            cursor: pointer;
            font: inherit;
        }
        html.dark-theme #theme-toggle {
            border-color: rgb(112, 168, 224);
            background-color: rgb(31, 78, 121);
            color: rgb(224, 238, 255);
        }
    `;

    const style = document.createElement('style');
    style.textContent = darkThemeStyles;
    document.head.appendChild(style);

    const toggle = document.createElement('button');
    toggle.id = 'theme-toggle';
    toggle.type = 'button';
    document.body.appendChild(toggle);

    const updateTheme = (isDark) => {
        document.documentElement.classList.toggle('dark-theme', isDark);
        toggle.textContent = isDark ? 'Light theme' : 'Dark theme';
        toggle.setAttribute('aria-pressed', String(isDark));
    };

    const savedTheme = localStorage.getItem(storageKey) === 'true';
    updateTheme(savedTheme);

    toggle.addEventListener('click', () => {
        const isDark = !document.documentElement.classList.contains('dark-theme');
        localStorage.setItem(storageKey, String(isDark));
        updateTheme(isDark);
    });
})();
