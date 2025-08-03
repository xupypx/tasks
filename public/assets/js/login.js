/**
 * Управление функционалом форм логина и сброса пароля: показ пароля, сила пароля, Caps Lock, генерация, копирование
 */
const initPasswordForm = () => {
    const passwordInput = document.getElementById('password');
    if (!passwordInput) {
        console.error('Поле пароля (id="password") не найдено');
        return;
    }

    const elements = {
        passwordInput,
        togglePassword: document.querySelector('.toggle-password'),
        generatePassword: document.querySelector('.generate-password'),
        copyPassword: document.querySelector('.copy-password'),
        strengthIndicator: document.getElementById('password-strength'),
        errorIndicator: document.getElementById('password-error'),
        confirmInput: document.getElementById('password-confirm'),
        toggleConfirm: document.querySelector('.toggle-password-confirm'),
        copyConfirm: document.querySelector('.copy-password-confirm'),
        confirmError: document.getElementById('password-confirm-error')
    };

    if (!elements.copyPassword) console.error('Кнопка копирования (.copy-password) не найдена');
    if (!elements.copyConfirm) console.error('Кнопка копирования (.copy-password-confirm) не найдена');
    if (!elements.togglePassword) console.error('Кнопка переключения видимости (.toggle-password) не найдена');
    if (!elements.toggleConfirm) console.error('Кнопка переключения видимости (.toggle-password-confirm) не найдена');
    if (!elements.generatePassword) console.error('Кнопка генерации (.generate-password) не найдена');

    const MIN_PASSWORD_LENGTH = 12;
    const ANIMATION_DURATION = 300;
    const NOTIFICATION_DURATION = 2000;
    const HIDE_PASSWORD_DELAY = 5000;
    const ICON_CHANGE_DURATION = 1000;
    const STRENGTH_LEVELS = {
        weak: { text: 'Слабый', class: 'password-strength-weak' },
        medium: { text: 'Средний', class: 'password-strength-medium' },
        strong: { text: 'Сильный', class: 'password-strength-strong' },
        empty: { text: '', class: '' }
    };
    const PASSWORD_CHARS = {
        lowercase: 'abcdefghijklmnopqrstuvwxyz',
        uppercase: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        numbers: '0123456789',
        symbols: '!@#$%^&*()_+-=[]{}|;:,.<>?'
    };

    const checkPasswordStrength = (password) => {
        let strength = 0;
        if (password.length >= MIN_PASSWORD_LENGTH) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;

        return strength < 2 ? STRENGTH_LEVELS.weak :
               strength === 2 ? STRENGTH_LEVELS.medium :
               STRENGTH_LEVELS.strong;
    };

    const generateRandomPassword = () => {
        const allChars = PASSWORD_CHARS.lowercase + PASSWORD_CHARS.uppercase + PASSWORD_CHARS.numbers + PASSWORD_CHARS.symbols;
        let password = '';
        password += PASSWORD_CHARS.lowercase[Math.floor(Math.random() * PASSWORD_CHARS.lowercase.length)];
        password += PASSWORD_CHARS.uppercase[Math.floor(Math.random() * PASSWORD_CHARS.uppercase.length)];
        password += PASSWORD_CHARS.numbers[Math.floor(Math.random() * PASSWORD_CHARS.numbers.length)];
        password += PASSWORD_CHARS.symbols[Math.floor(Math.random() * PASSWORD_CHARS.symbols.length)];
        for (let i = password.length; i < MIN_PASSWORD_LENGTH; i++) {
            password += allChars[Math.floor(Math.random() * allChars.length)];
        }
        return password.split('').sort(() => Math.random() - 0.5).join('');
    };

    const showNotification = (message, status = 'success') => {
        const notification = document.createElement('div');
        notification.className = `uk-alert-${status} uk-position-top-center uk-position-fixed`;
        notification.setAttribute('uk-alert', '');
        notification.innerHTML = `<p>${message}</p>`;
        document.body.appendChild(notification);
        setTimeout(() => UIkit.alert(notification).close(), NOTIFICATION_DURATION);
    };

    const updatePasswordUI = () => {
        const isEmpty = elements.passwordInput.value.length === 0;
        const passwordValue = elements.passwordInput.value;
        const confirmValue = elements.confirmInput?.value || '';

        if (elements.togglePassword) {
            UIkit.util.toggleClass(elements.togglePassword, 'uk-hidden', isEmpty);
        }
        if (elements.copyPassword) {
            UIkit.util.toggleClass(elements.copyPassword, 'uk-hidden', isEmpty);
        }
        if (elements.generatePassword) {
            UIkit.util.toggleClass(elements.generatePassword, 'uk-hidden', isEmpty);
        }
        if (elements.toggleConfirm && elements.confirmInput) {
            UIkit.util.toggleClass(elements.toggleConfirm, 'uk-hidden', confirmValue.length === 0);
        }
        if (elements.copyConfirm && elements.confirmInput) {
            UIkit.util.toggleClass(elements.copyConfirm, 'uk-hidden', confirmValue.length === 0);
        }

        if (elements.strengthIndicator) {
            const strength = isEmpty ? STRENGTH_LEVELS.empty : checkPasswordStrength(passwordValue);
            elements.strengthIndicator.textContent = isEmpty ? '' : `Сила пароля: ${strength.text}`;
            elements.strengthIndicator.className = `uk-text-small uk-margin-small-top ${strength.class}`;
        }

        if (elements.errorIndicator) {
            elements.errorIndicator.textContent = !isEmpty && passwordValue.length < MIN_PASSWORD_LENGTH
                ? `Пароль должен содержать минимум ${MIN_PASSWORD_LENGTH} символов`
                : '';
        }

        if (elements.confirmError && elements.confirmInput) {
            elements.confirmError.textContent = confirmValue && passwordValue !== confirmValue
                ? 'Пароли не совпадают'
                : '';
        }
    };

    const handleCapsLock = (event) => {
        const warningId = 'caps-lock-warning-' + event.target.id;
        let warning = document.getElementById(warningId);

        if (event.getModifierState('CapsLock')) {
            if (!warning) {
                warning = document.createElement('div');
                warning.id = warningId;
                warning.className = 'uk-text-warning uk-text-small uk-margin-small-top';
                warning.textContent = 'Включен Caps Lock';
                event.target.parentElement.appendChild(warning);
            }
        } else if (warning) {
            warning.remove();
        }
    };

    const togglePasswordVisibility = (event, input, toggle) => {
        event.preventDefault();
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        toggle.setAttribute('data-uk-icon', isPassword ? 'icon: eye-slash' : 'icon: eye');
        UIkit.util.addClass(input, 'uk-animation-fade');
        setTimeout(() => UIkit.util.removeClass(input, 'uk-animation-fade'), ANIMATION_DURATION);
    };

    const copyToClipboard = async (event, input) => {
        event.preventDefault();
        if (!input.value) {
            showNotification('Поле пустое', 'warning');
            return;
        }
        const copyIcon = input.id === 'password' ? elements.copyPassword : elements.copyConfirm;
        try {
            await navigator.clipboard.writeText(input.value);
            showNotification('Пароль скопирован!', 'success');
            if (copyIcon) {
                copyIcon.setAttribute('data-uk-icon', 'icon: check');
                setTimeout(() => copyIcon.setAttribute('data-uk-icon', 'icon: copy'), ICON_CHANGE_DURATION);
            }
            setTimeout(() => {
                input.type = 'password';
                const toggle = input.id === 'password' ? elements.togglePassword : elements.toggleConfirm;
                if (toggle) toggle.setAttribute('data-uk-icon', 'icon: eye');
            }, HIDE_PASSWORD_DELAY);
        } catch (err) {
            try {
                const textarea = document.createElement('textarea');
                textarea.value = input.value;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                showNotification('Пароль скопирован!', 'success');
                if (copyIcon) {
                    copyIcon.setAttribute('data-uk-icon', 'icon: check');
                    setTimeout(() => copyIcon.setAttribute('data-uk-icon', 'icon: copy'), ICON_CHANGE_DURATION);
                }
                setTimeout(() => {
                    input.type = 'password';
                    const toggle = input.id === 'password' ? elements.togglePassword : elements.toggleConfirm;
                    if (toggle) toggle.setAttribute('data-uk-icon', 'icon: eye');
                }, HIDE_PASSWORD_DELAY);
            } catch (fallbackErr) {
                showNotification('Ошибка при копировании', 'danger');
                console.error('Ошибка копирования:', fallbackErr);
            }
        }
    };

    const handleGeneratePassword = (event) => {
        event.preventDefault();
        const newPassword = generateRandomPassword();
        elements.passwordInput.value = newPassword;
        if (elements.confirmInput) {
            elements.confirmInput.value = newPassword;
        }
        updatePasswordUI();
        elements.passwordInput.focus();
        UIkit.util.addClass(elements.passwordInput, 'uk-animation-shake');
        setTimeout(() => UIkit.util.removeClass(elements.passwordInput, 'uk-animation-shake'), ANIMATION_DURATION);
    };

    updatePasswordUI();
    elements.passwordInput.addEventListener('input', updatePasswordUI);
    elements.passwordInput.addEventListener('keydown', handleCapsLock);
    if (elements.togglePassword) {
        elements.togglePassword.addEventListener('click', (e) => togglePasswordVisibility(e, elements.passwordInput, elements.togglePassword));
    }
    if (elements.copyPassword) {
        elements.copyPassword.addEventListener('click', (e) => copyToClipboard(e, elements.passwordInput));
    }
    if (elements.generatePassword) {
        elements.generatePassword.addEventListener('click', handleGeneratePassword);
    }
    if (elements.confirmInput) {
        elements.confirmInput.addEventListener('input', updatePasswordUI);
        elements.confirmInput.addEventListener('keydown', handleCapsLock);
    }
    if (elements.toggleConfirm) {
        elements.toggleConfirm.addEventListener('click', (e) => togglePasswordVisibility(e, elements.confirmInput, elements.toggleConfirm));
    }
    if (elements.copyConfirm) {
        elements.copyConfirm.addEventListener('click', (e) => copyToClipboard(e, elements.confirmInput));
    }
};

document.addEventListener('DOMContentLoaded', initPasswordForm);
