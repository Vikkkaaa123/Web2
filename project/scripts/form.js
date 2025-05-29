document.addEventListener('DOMContentLoaded', function() {
    // Инициализация формы
    const form = document.getElementById('myform');
    if (!form) return;
    
    const messagesContainer = document.querySelector('.form-messages');
    if (!messagesContainer) {
        console.error('Контейнер для сообщений не найден');
        return;
    }

    // Функция валидации формы
    function validateForm(form) {
        const errors = {};
        const values = {};
        let isValid = true;

        // Получаем выбранные языки
        const langSelect = form.querySelector('[name="languages[]"]');
        const selectedLangs = langSelect ? 
            Array.from(langSelect.selectedOptions).map(opt => opt.value) : 
            [];

        // Валидация полей
        const fields = [
            {name: 'fio', required: true, maxLength: 128},
            {name: 'phone', required: true, pattern: /^\+7\d{10}$/},
            {name: 'email', required: true, pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/},
            {name: 'birth_day', required: true},
            {name: 'birth_month', required: true},
            {name: 'birth_year', required: true},
            {name: 'gender', required: true},
            {name: 'biography', required: true, maxLength: 512},
            {name: 'agreement', required: true, type: 'checkbox'}
        ];

        fields.forEach(field => {
            const element = form.querySelector(`[name="${field.name}"]`);
            if (!element) return;

            let value;
            if (field.type === 'checkbox') {
                value = element.checked;
            } else {
                value = element.value.trim();
            }

            values[field.name] = value;

            if (field.required && !value) {
                errors[field.name] = 'Это поле обязательно';
                isValid = false;
            } else if (field.maxLength && value.length > field.maxLength) {
                errors[field.name] = `Максимум ${field.maxLength} символов`;
                isValid = false;
            } else if (field.pattern && !field.pattern.test(value)) {
                errors[field.name] = 'Неверный формат';
                isValid = false;
            }
        });

        // Валидация даты
        if (values.birth_day && values.birth_month && values.birth_year) {
            if (!checkDate(values.birth_month, values.birth_day, values.birth_year)) {
                errors.birth_date = 'Некорректная дата';
                isValid = false;
            }
        }

        // Валидация языков
        if (selectedLangs.length === 0) {
            errors.lang = 'Выберите хотя бы один язык';
            isValid = false;
        }
        values.languages = selectedLangs;

        return {errors, values, isValid};
    }

    function checkDate(month, day, year) {
        month = parseInt(month);
        day = parseInt(day);
        year = parseInt(year);
        return !isNaN(month) && !isNaN(day) && !isNaN(year) && 
               month >= 1 && month <= 12 && 
               day >= 1 && day <= 31 && 
               year >= 1900 && year <= new Date().getFullYear();
    }

    // Показ ошибок
    function showErrors(errors, form) {
        // Очистка предыдущих ошибок
        form.querySelectorAll('.error-field').forEach(el => {
            el.classList.remove('error-field');
        });
        form.querySelectorAll('.error-text').forEach(el => {
            el.remove();
        });

        // Добавление новых ошибок
        Object.entries(errors).forEach(([field, message]) => {
            let element;
            
            if (field === 'birth_date') {
                element = form.querySelector('[name="birth_day"]');
            } else if (field === 'lang') {
                element = form.querySelector('[name="languages[]"]');
            } else {
                element = form.querySelector(`[name="${field}"]`);
            }

            if (!element) return;

            const container = element.closest('.form-group') || 
                             element.closest('label') || 
                             element.parentElement;
            
            if (container) {
                container.classList.add('error-field');
                const errorElement = document.createElement('span');
                errorElement.className = 'error-text';
                errorElement.textContent = message;
                container.appendChild(errorElement);
            }
        });
    }

    // Показ успешной отправки
    function showSuccess(result) {
        messagesContainer.innerHTML = '';
        const successElement = document.createElement('div');
        successElement.className = 'success-message';
        
        if (result.login && result.password) {
            successElement.innerHTML = `
                <p>Данные сохранены!</p>
                <p>Логин: <strong>${result.login}</strong></p>
                <p>Пароль: <strong>${result.password}</strong></p>
                <p><a href="/Web2/project/login">Войти в систему</a></p>
            `;
        } else {
            successElement.textContent = 'Данные успешно обновлены!';
        }
        
        messagesContainer.appendChild(successElement);
    }

    // Обработчик отправки формы
    async function handleSubmit(e) {
        e.preventDefault();
        
        const submitBtn = form.querySelector('#submit-btn');
        if (!submitBtn) return;
        
        const originalText = submitBtn.value;
        submitBtn.disabled = true;
        submitBtn.value = 'Отправка...';

        // Валидация
        const {errors, values, isValid} = validateForm(form);
        if (!isValid) {
            showErrors(errors, form);
            submitBtn.disabled = false;
            submitBtn.value = originalText;
            return;
        }

        // Подготовка данных
        const formData = new FormData(form);
        formData.append('is_ajax', '1');

        // Явно добавляем языки
        values.languages.forEach(lang => {
            formData.append('languages[]', lang);
        });

        try {
            // Отправка
            const response = await fetch('', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`Ошибка сервера: ${response.status}`);
            }

            const result = await response.json();
            
            if (result.success) {
                showSuccess(result);
                if (result.login && result.password) {
                    form.reset();
                }
            } else {
                showErrors(result.errors || {}, form);
            }
        } catch (error) {
            console.error('Ошибка:', error);
            messagesContainer.innerHTML = `
                <div class="error-message">
                    Ошибка при отправке: ${error.message}
                </div>
            `;
        } finally {
            submitBtn.disabled = false;
            submitBtn.value = originalText;
        }
    }

    // Назначение обработчика
    form.addEventListener('submit', handleSubmit);
});
