document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('myform');
    if (!form) return;
    
    const messagesContainer = document.querySelector('.error_messages');
    const submitBtn = form.querySelector('#submit-btn');

    function validateForm(form) {
        const errors = {};
        let isValid = true;

        const full_name = form.querySelector('[name="full_name"]')?.value.trim();
        const phone = form.querySelector('[name="phone"]')?.value.trim();
        const email = form.querySelector('[name="email"]')?.value.trim();
        const birth_day = form.querySelector('[name="birth_day"]')?.value;
        const birth_month = form.querySelector('[name="birth_month"]')?.value;
        const birth_year = form.querySelector('[name="birth_year"]')?.value;
        const gender = form.querySelector('[name="gender"]:checked')?.value;
        const languages = Array.from(form.querySelectorAll('[name="languages[]"]:checked')).map(el => el.value);
        const biography = form.querySelector('[name="biography"]')?.value.trim();
        const agreement = form.querySelector('[name="agreement"]')?.checked;

        if (!full_name) {
            errors.full_name = 'Заполните ФИО';
            isValid = false;
        } else if (full_name.length > 128) {
            errors.full_name = 'ФИО не должно превышать 128 символов';
            isValid = false;
        }

        if (!phone) {
            errors.phone = 'Введите номер телефона';
            isValid = false;
        } else if (!/^\+7\d{10}$/.test(phone)) {
            errors.phone = 'Номер должен быть в формате +7XXXXXXXXXX';
            isValid = false;
        }

        if (!email) {
            errors.email = 'Введите email';
            isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            errors.email = 'Введите корректный email';
            isValid = false;
        }

        const day = parseInt(birth_day);
        const month = parseInt(birth_month);
        const year = parseInt(birth_year);
        
        if (!day || !month || !year) {
            errors.birth_date = 'Заполните дату рождения полностью';
            isValid = false;
        } else {
            const birthDate = new Date(year, month-1, day);
            const currentDate = new Date();
            const minDate = new Date();
            minDate.setFullYear(currentDate.getFullYear() - 120);
            
            if (birthDate > currentDate) {
                errors.birth_date = 'Дата рождения не может быть в будущем';
                isValid = false;
            } else if (birthDate < minDate) {
                errors.birth_date = 'Возраст не может быть более 120 лет';
                isValid = false;
            }
        }

        if (!gender) {
            errors.gender = 'Укажите пол';
            isValid = false;
        }

        if (languages.length === 0) {
            errors.languages = 'Выберите хотя бы один язык';
            isValid = false;
        }

        if (!biography) {
            errors.biography = 'Заполните биографию';
            isValid = false;
        } else if (biography.length > 500) {
            errors.biography = 'Биография не должна превышать 500 символов';
            isValid = false;
        }

        if (!agreement) {
            errors.agreement = 'Необходимо дать согласие';
            isValid = false;
        }

        return { errors, isValid };
    }

    function showErrors(errors, form) {
        form.querySelectorAll('.error-field').forEach(el => {
            el.classList.remove('error-field');
        });
        
        messagesContainer.innerHTML = '';
        messagesContainer.style.display = 'block';

        for (const [field, message] of Object.entries(errors)) {
            const fieldElement = form.querySelector(`[name="${field}"], [name="${field}[]"]`);
            if (fieldElement) {
                const parent = fieldElement.closest('.form-group') || fieldElement.closest('label') || fieldElement.parentElement;
                parent.classList.add('error-field');
                
                const errorElement = document.createElement('div');
                errorElement.className = 'error-message';
                errorElement.textContent = message;
                parent.appendChild(errorElement);
            } else {
                const errorElement = document.createElement('div');
                errorElement.className = 'error-message';
                errorElement.textContent = message;
                messagesContainer.appendChild(errorElement);
            }
        }
    }

    function showSuccess(response) {
        messagesContainer.innerHTML = '';
        messagesContainer.style.display = 'block';

        if (response.login && response.password) {
            const successMsg = document.createElement('div');
            successMsg.className = 'success-message';
            successMsg.innerHTML = `
                <p>Данные успешно сохранены!</p>
                <p>Ваши учетные данные:</p>
                <p><strong>Логин:</strong> ${response.login}</p>
                <p><strong>Пароль:</strong> ${response.password}</p>
                <p>Используйте их для входа в следующий раз.</p>
            `;
            messagesContainer.appendChild(successMsg);
        } else {
            const successMsg = document.createElement('div');
            successMsg.className = 'success-message';
            successMsg.textContent = 'Данные успешно обновлены!';
            messagesContainer.appendChild(successMsg);
        }
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const originalBtnText = submitBtn.value;
        submitBtn.disabled = true;
        submitBtn.value = 'Отправка...';

        const { errors, isValid } = validateForm(form);
        if (!isValid) {
            showErrors(errors, form);
            submitBtn.disabled = false;
            submitBtn.value = originalBtnText;
            return;
        }

        try {
            const formData = new FormData(form);
            
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showSuccess(result);
                if (result.redirect) {
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 3000);
                }
            } else {
                showErrors(result.errors || {}, form);
            }
        } catch (error) {
            messagesContainer.innerHTML = `
                <div class="error-message">
                    Ошибка соединения с сервером. Пожалуйста, попробуйте позже.
                </div>
            `;
            messagesContainer.style.display = 'block';
        } finally {
            submitBtn.disabled = false;
            submitBtn.value = originalBtnText;
        }
    });
});
