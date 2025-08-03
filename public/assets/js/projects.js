// /public/assets/js/projects.js
UIkit.util.ready(function() {
    // Форматирует дату в формат Y-m-d H:i
    function formatDate(timestamp) {
        return new Date(timestamp).toLocaleString('ru-RU', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        }).replace(',', '');
    }

    // Форматирует оставшееся время
    function formatRemainingTime(diff) {
        if (diff <= 0) {
            return ' | Срок истёк';
        }

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

        let result = ' | Для решения осталось: ';
        if (days > 0) result += `${days} дн.`;
        if (hours > 0) result += (result.endsWith(': ') ? '' : ' ') + `${hours} ч.`;
        if (minutes > 0) result += (result.endsWith(': ') ? '' : ' ') + `${minutes} мин.`;
        return result;
    }

    // Обновляет дедлайны для видимых элементов
    function updateDeadlines() {
        UIkit.util.$$('.deadline-timer').forEach(function(element) {
            if (!UIkit.util.isInView(element)) return; // Пропускаем невидимые элементы

            const deadline = element.getAttribute('data-deadline');
            const dateElement = element.querySelector('.deadline-date');
            const remainingElement = element.querySelector('.deadline-remaining');

            if (!deadline || isNaN(parseInt(deadline))) {
                dateElement.textContent = 'не установлен';
                dateElement.className = 'deadline-date';
                remainingElement.textContent = '';
                return;
            }

            const timestamp = parseInt(deadline) * 1000; // Секунды в миллисекунды
            const now = Date.now();
            const diff = timestamp - now;

            dateElement.textContent = formatDate(timestamp);
            dateElement.className = 'deadline-date ' + (diff <= 0 ? 'overdue' : 'overok');
            remainingElement.textContent = formatRemainingTime(diff);
        });
    }

    // Инициализация: обновляем дедлайны и настраиваем кнопки удаления
    function init() {
        // Обновляем дедлайны сразу
        updateDeadlines();
        setInterval(updateDeadlines, 60000);

        // Обработчик кнопок удаления
        UIkit.util.on('.project-delete-btn', 'click', function(e) {
            const projectTitle = e.currentTarget.dataset.projectTitle;
            const modal = UIkit.util.$(e.currentTarget.getAttribute('uk-toggle').replace('target: ', ''));

            // Обновляем заголовок модального окна, если нужно
            const titleElement = modal.querySelector('.uk-modal-title + p strong');
            if (titleElement) {
                titleElement.textContent = projectTitle;
            }
        });
    }

    // Запускаем инициализацию
    init();

    // Поддержка динамически загружаемых элементов (например, AJAX-пагинация)
    UIkit.util.on(document, 'ajaxComplete', function() {
        updateDeadlines();
    });
});
