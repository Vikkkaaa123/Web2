document.addEventListener('DOMContentLoaded', function() {
    // 1. Находим форму и заменяем её на div
    const oldForm = document.querySelector('form');
    if (!oldForm) return;
    
    const formContainer = document.createElement('div');
    formContainer.className = 'form-container';
    formContainer.innerHTML = oldForm.innerHTML;
    oldForm.parentNode.replaceChild(formContainer, oldForm);

    // 2. Создаем контейнер для сообщений
    const messagesContainer = document.createElement('div');
    messagesContainer.className = 'form-messages';
    formContainer.insertBefore(messagesContainer, formContainer.querySelector('.form-actions'));

    // 3. Валидация формы
    function validateForm() {
        const errors = {};
        let isValid = true;

        // Проверка ФИО
        const fullName = formContainer.querySelector('[name="full_name"]').value.trim();
        if (!fullName) {
            errors.full_name = 'Заполните ФИО';
            isValid = false;
        }

        // Проверка телефона
        const phone = formContainer.querySelector('[name="phone"]').value.trim();
        if (!phone) {
            errors.phone = 'Введите номер телефона';
            isValid = false;
        }

        // Проверка email
        const email = formContainer.querySelector('[name="email"]').value.trim();
        if (!email) {
            errors.email = 'Введите email';
            isValid = false;
        }

        // Проверка даты рождения
        const day = formContainer.querySelector('[name="birth_day"]').value;
        const month = formContainer.querySelector('[name="birth_month"]').value;
        const year = formContainer.querySelector('[name="birth_year"]').value;
        if (!day || !month || !year) {
            errors.birth_date = 'Укажите полную дату рождения';
            isValid = false;
        }

        // Проверка пола
        const gender = formContainer.querySelector('[name="gender"]:checked');
        if (!gender) {
            errors.gender = 'Укажите пол';
            isValid = false;
        }

        // Проверка языков программирования
        const languages = Array.from(formContainer.querySelectorAll('[name="languages[]"]:checked'));
        if (languages.length === 0) {
            errors.languages = 'Выберите хотя бы один язык';
            isValid = false;
        }

        // Проверка биографии
        const biography = formContainer.querySelector('[name="biography"]').value.trim();
        if (!biography) {
            errors.biography = 'Заполните биографию';
            isValid = false;
        }

        // Проверка согласия
        const agreement = formContainer.querySelector('[name="agreement"]').checked;
        if (!agreement) {
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
            
            if (field === 'birth_date') {
                element = formContainer.querySelector('.date-fields');
            } else if (field === 'gender') {
                element = formContainer.querySelector('.gender-options');
            } else if (field === 'agreement') {
                element = formContainer.querySelector('.agreement-field');
            } else if (field === 'languages') {
                element = formContainer.querySelector('[name="languages[]"]').parentElement;
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
                    // Сброс формы
                    formContainer.querySelectorAll('input, textarea, select').forEach(el => {
                        if (el.type !== 'submit') {
                            if (el.type === 'checkbox' || el.type === 'radio') {
                                el.checked = false;
                            } else {
                                el.value = '';
                            }
                        }
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
