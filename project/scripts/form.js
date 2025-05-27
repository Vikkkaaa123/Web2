document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (!form) return;

    // Удаляем стандартное поведение формы
    form.removeAttribute('action');

    const messagesContainer = document.createElement('div');
    messagesContainer.className = 'form-messages';
    form.insertBefore(messagesContainer, form.querySelector('.form-actions'));

    // Валидация формы
    function validateForm() {
        const errors = {};
        let isValid = true;

        // Проверка ФИО
        const fullName = form.querySelector('[name="full_name"]').value.trim();
        if (!fullName) {
            errors.full_name = 'Заполните ФИО';
            isValid = false;
        }

        // Проверка телефона
        const phone = form.querySelector('[name="phone"]').value.trim();
        if (!phone) {
            errors.phone = 'Введите номер телефона';
            isValid = false;
        }

        // Проверка email
        const email = form.querySelector('[name="email"]').value.trim();
        if (!email) {
            errors.email = 'Введите email';
            isValid = false;
        }

        // Проверка даты рождения
        const day = form.querySelector('[name="birth_day"]').value;
        const month = form.querySelector('[name="birth_month"]').value;
        const year = form.querySelector('[name="birth_year"]').value;
        if (!day || !month || !year) {
            errors.birth_date = 'Укажите полную дату рождения';
            isValid = false;
        }

        // Проверка пола
        const gender = form.querySelector('[name="gender"]:checked');
        if (!gender) {
            errors.gender = 'Укажите пол';
            isValid = false;
        }

        // Проверка языков программирования
        const languages = Array.from(form.querySelectorAll('[name="languages[]"]:checked'));
        if (languages.length === 0) {
            errors.languages = 'Выберите хотя бы один язык';
            isValid = false;
        }

        // Проверка биографии
        const biography = form.querySelector('[name="biography"]').value.trim();
        if (!biography) {
            errors.biography = 'Заполните биографию';
            isValid = false;
        }

        // Проверка согласия
        const agreement = form.querySelector('[name="agreement"]').checked;
        if (!agreement) {
            errors.agreement = 'Необходимо дать согласие';
            isValid = false;
        }

        return { errors, isValid };
    }

    // Показ ошибок
    function showErrors(errors) {
        messagesContainer.innerHTML = '';
        document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
        document.querySelectorAll('.error-message').forEach(el => el.remove());

        for (const [field, message] of Object.entries(errors)) {
            let element;
            
            if (field === 'birth_date') {
                element = form.querySelector('.date-fields');
            } else if (field === 'gender') {
                element = form.querySelector('.gender-options');
            } else if (field === 'agreement') {
                element = form.querySelector('.agreement-field');
            } else if (field === 'languages') {
                element = form.querySelector('[name="languages[]"]').parentElement;
            } else {
                element = form.querySelector(`[name="${field}"]`);
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

    // Обработчик отправки формы
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();

        const submitBtn = form.querySelector('[type="submit"]');
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
            const formData = new FormData(form);
            
            // Явно добавляем languages[]
            const languages = Array.from(form.querySelectorAll('[name="languages[]"]:checked'));
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
                    form.reset();
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
