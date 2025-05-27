document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    if (!form) return;

    const messagesContainer = document.createElement('div');
    messagesContainer.className = 'form-messages';
    form.insertBefore(messagesContainer, form.querySelector('.form-actions'));

    function validateForm(form) {
        const errors = {};
        let isValid = true;

        const fullName = form.querySelector('[name="full_name"]').value.trim();
        if (!fullName) {
            errors.full_name = 'Заполните ФИО';
            isValid = false;
        } else if (fullName.length > 128) {
            errors.full_name = 'ФИО не должно превышать 128 символов';
            isValid = false;
        }

        const phone = form.querySelector('[name="phone"]').value.trim();
        if (!phone) {
            errors.phone = 'Введите номер телефона';
            isValid = false;
        } else if (!/^\+7\d{10}$/.test(phone)) {
            errors.phone = 'Телефон должен быть в формате +7XXXXXXXXXX';
            isValid = false;
        }

        const email = form.querySelector('[name="email"]').value.trim();
        if (!email) {
            errors.email = 'Введите email';
            isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            errors.email = 'Введите корректный email';
            isValid = false;
        }

        const day = parseInt(form.querySelector('[name="birth_day"]').value);
        const month = parseInt(form.querySelector('[name="birth_month"]').value);
        const year = parseInt(form.querySelector('[name="birth_year"]').value);
        
        if (isNaN(day) || isNaN(month) || isNaN(year)) {
            errors.birth_date = 'Укажите полную дату рождения';
            isValid = false;
        } else {
            const birthDate = new Date(year, month - 1, day);
            const currentDate = new Date();
            const minDate = new Date();
            minDate.setFullYear(currentDate.getFullYear() - 120);
            
            if (birthDate > currentDate) {
                errors.birth_date = 'Дата рождения не может быть в будущем';
                isValid = false;
            } else if (birthDate < minDate) {
                errors.birth_date = 'Возраст не может быть больше 120 лет';
                isValid = false;
            }
        }

        const gender = form.querySelector('[name="gender"]:checked');
        if (!gender) {
            errors.gender = 'Укажите пол';
            isValid = false;
        }

        const languages = Array.from(form.querySelectorAll('[name="languages[]"]:checked'));
        if (languages.length === 0) {
            errors.languages = 'Выберите хотя бы один язык';
            isValid = false;
        }

        const biography = form.querySelector('[name="biography"]').value.trim();
        if (!biography) {
            errors.biography = 'Заполните биографию';
            isValid = false;
        } else if (biography.length > 512) {
            errors.biography = 'Биография не должна превышать 512 символов';
            isValid = false;
        }

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
        
        // Очищаем предыдущие ошибки
        document.querySelectorAll('.error-message').forEach(el => el.remove());
        document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));

        // Показываем новые ошибки
        for (const [field, message] of Object.entries(errors)) {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('error');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = message;
                input.parentNode.insertBefore(errorDiv, input.nextSibling);
            }
        }
    }

    // Обработчик отправки формы
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = form.querySelector('[type="submit"]');
        const originalBtnText = submitBtn.value;
        
        // Блокируем кнопку
        submitBtn.disabled = true;
        submitBtn.value = 'Отправка...';

        // Валидация
        const { errors, isValid } = validateForm(form);
        if (!isValid) {
            showErrors(errors);
            submitBtn.disabled = false;
            submitBtn.value = originalBtnText;
            return;
        }

        try {
            // Отправляем данные на текущий URL (index.php)
            const response = await fetch('index.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: new FormData(form)
            });

            if (!response.ok) throw new Error('Ошибка сервера');
            
            const result = await response.json();

            if (result.success) {
                // Успешная отправка
                messagesContainer.innerHTML = `
                    <div class="success-message">
                        ${result.login ? `Данные сохранены! Логин: ${result.login}, Пароль: ${result.password}` : 'Данные обновлены'}
                    </div>
                `;
                
                if (result.login) {
                    form.reset();
                }
            } else {
                // Ошибки сервера
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
            // Разблокируем кнопку
            submitBtn.disabled = false;
            submitBtn.value = originalBtnText;
        }
    });
