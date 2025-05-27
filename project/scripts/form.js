document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (!form) return;

    // 1. Полностью заменяем форму на div, но сохраняем все поля
    const formContainer = document.createElement('div');
    formContainer.innerHTML = form.innerHTML;
    form.parentNode.replaceChild(formContainer, form);

    // 2. Создаем контейнер для сообщений
    const messagesContainer = document.createElement('div');
    messagesContainer.className = 'form-messages';
    formContainer.insertBefore(messagesContainer, formContainer.querySelector('.form-actions'));

    // 3. Валидация формы
    function validateForm() {
        const errors = {};
        let isValid = true;

        // Проверка всех обязательных полей
        const fieldsToValidate = {
            'full_name': 'ФИО',
            'phone': 'Телефон',
            'email': 'Email',
            'birth_day': 'День рождения',
            'birth_month': 'Месяц рождения',
            'birth_year': 'Год рождения',
            'gender': 'Пол',
            'biography': 'Биография'
        };

        for (const [field, name] of Object.entries(fieldsToValidate)) {
            const value = field === 'gender' 
                ? formContainer.querySelector(`[name="${field}"]:checked`)
                : formContainer.querySelector(`[name="${field}"]`)?.value.trim();

            if (!value) {
                errors[field] = `Заполните поле "${name}"`;
                isValid = false;
            }
        }

        // Проверка языков программирования
        const languages = Array.from(formContainer.querySelectorAll('[name="languages[]"]:checked'));
        if (languages.length === 0) {
            errors.languages = 'Выберите хотя бы один язык программирования';
            isValid = false;
        }

        // Проверка согласия
        if (!formContainer.querySelector('[name="agreement"]').checked) {
            errors.agreement = 'Необходимо дать согласие';
            isValid = false;
        }

        return { errors, isValid };
    }

    // 4. Показ ошибок
    function showErrors(errors) {
        messagesContainer.innerHTML = '';
        formContainer.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
        formContainer.querySelectorAll('.error-message').forEach(el => el.remove());

        for (const [field, message] of Object.entries(errors)) {
            let element;
            
            if (field === 'birth_day' || field === 'birth_month' || field === 'birth_year') {
                element = formContainer.querySelector('.date-fields');
            } else if (field === 'gender') {
                element = formContainer.querySelector('.gender-options');
            } else if (field === 'agreement') {
                element = formContainer.querySelector('.agreement-field');
            } else if (field === 'languages') {
                element = formContainer.querySelector('[name="languages[]"]').closest('.form-group');
            } else {
                element = formContainer.querySelector(`[name="${field}"]`);
            }

            if (element) {
                element.classList.add('error');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = message;
                element.parentNode.insertBefore(errorDiv, element.nextSibling);
            }
        }
    }

    // 5. Обработчик кнопки
    formContainer.querySelector('[type="submit"]').addEventListener('click', async function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        const submitBtn = this;
        const originalText = submitBtn.value;
        
        submitBtn.disabled = true;
        submitBtn.value = 'Отправка...';

        // Валидация
        const { errors, isValid } = validateForm();
        if (!isValid) {
            showErrors(errors);
            submitBtn.disabled = false;
            submitBtn.value = originalText;
            return;
        }

        try {
            // Подготовка данных
            const formData = new FormData();
            
            // Собираем все данные
            const fields = [
                'full_name', 'phone', 'email', 
                'birth_day', 'birth_month', 'birth_year',
                'gender', 'biography', 'agreement'
            ];
            
            fields.forEach(field => {
                const element = formContainer.querySelector(`[name="${field}"]`);
                if (element.type === 'checkbox') {
                    formData.append(field, element.checked ? '1' : '0');
                } else if (element.type === 'radio') {
                    const checked = formContainer.querySelector(`[name="${field}"]:checked`);
                    if (checked) formData.append(field, checked.value);
                } else {
                    formData.append(field, element.value);
                }
            });

            // Добавляем языки программирования
            const languages = Array.from(formContainer.querySelectorAll('[name="languages[]"]:checked'));
            languages.forEach(lang => {
                formData.append('languages[]', lang.value);
            });

            // Отправка на сервер
            const response = await fetch('index.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            // Обработка ответа
            const result = await response.json();
            
            if (result.success) {
                messagesContainer.innerHTML = `
                    <div class="success-message">
                        ${result.login ? `Данные сохранены! Логин: ${result.login}, Пароль: ${result.password}` : 'Данные обновлены'}
                    </div>
                `;
                
                if (result.login) {
                    formContainer.querySelectorAll('input, textarea, select').forEach(el => {
                        if (el.type !== 'submit') el.value = '';
                        if (el.type === 'checkbox') el.checked = false;
                        if (el.type === 'radio') el.checked = false;
                    });
                }
            } else {
                showErrors(result.errors || {});
            }
        } catch (error) {
            messagesContainer.innerHTML = `
                <div class="error-message">
                    Ошибка при отправке: ${error.message}
                </div>
            `;
            console.error('Ошибка:', error);
        } finally {
            submitBtn.disabled = false;
            submitBtn.value = originalText;
        }
    });
});
