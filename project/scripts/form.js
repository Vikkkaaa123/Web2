document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('myform');
    if (!form) {
        console.error('Форма не найдена');
        return;
    }

    const messagesContainer = document.querySelector('.form-messages');
    if (!messagesContainer) {
        console.error('Контейнер сообщений не найден');
        return;
    }

    // Отключаем стандартную отправку формы
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('Начата обработка формы');

        const submitBtn = form.querySelector('#submit-btn');
        if (!submitBtn) {
            console.error('Кнопка отправки не найдена');
            return;
        }

        // Блокируем кнопку
        const originalText = submitBtn.value;
        submitBtn.disabled = true;
        submitBtn.value = 'Отправка...';

        try {
            // Валидация
            const errors = validateForm();
            if (Object.keys(errors).length > 0) {
                showErrors(errors);
                return;
            }

            // Подготовка данных
            const formData = new FormData(form);
            formData.append('is_ajax', '1');

            // Определяем правильный URL для отправки
            const formAction = form.getAttribute('action') || window.location.pathname;
            console.log('Отправка на URL:', formAction);

            // Отправка запроса
            const response = await fetch(formAction, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            console.log('Статус ответа:', response.status);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            console.log('Результат:', result);

            if (result.success) {
                showSuccess(result);
            } else {
                showErrors(result.errors || {});
            }
        } catch (error) {
            console.error('Ошибка:', error);
            messagesContainer.innerHTML = `
                <div class="error-message">
                    Ошибка при отправке формы: ${error.message}
                </div>
            `;
        } finally {
            submitBtn.disabled = false;
            submitBtn.value = originalText;
        }
    });

    // Функция валидации
    function validateForm() {
        const errors = {};
        const langSelect = document.querySelector('[name="languages[]"]');
        const selectedLangs = langSelect ? 
            Array.from(langSelect.selectedOptions).map(opt => opt.value) : 
            [];

        // Проверка обязательных полей
        if (!form.querySelector('[name="fio"]').value.trim()) {
            errors.fio = 'Укажите ФИО';
        }

        if (!form.querySelector('[name="phone"]').value.trim()) {
            errors.phone = 'Укажите телефон';
        }

        if (selectedLangs.length === 0) {
            errors.lang = 'Выберите хотя бы один язык';
        }

        return errors;
    }

    // Показ ошибок
    function showErrors(errors) {
        messagesContainer.innerHTML = '';
        
        // Очистка предыдущих ошибок
        document.querySelectorAll('.error-field').forEach(el => {
            el.classList.remove('error-field');
        });
        document.querySelectorAll('.error-text').forEach(el => {
            el.remove();
        });

        // Добавление новых ошибок
        for (const [field, message] of Object.entries(errors)) {
            let element;
            
            if (field === 'lang') {
                element = document.querySelector('[name="languages[]"]');
            } else {
                element = document.querySelector(`[name="${field}"]`);
            }

            if (element) {
                const container = element.closest('.form-group') || element.parentElement;
                container.classList.add('error-field');
                
                const errorElement = document.createElement('div');
                errorElement.className = 'error-text';
                errorElement.textContent = message;
                container.appendChild(errorElement);
            }
        }
    }

    // Показ успешной отправки
    function showSuccess(result) {
        messagesContainer.innerHTML = `
            <div class="success-message">
                ${result.login && result.password ? 
                    `Данные сохранены!<br>
                    Логин: ${result.login}<br>
                    Пароль: ${result.password}` : 
                    'Данные успешно обновлены!'}
            </div>
        `;
    }
});
