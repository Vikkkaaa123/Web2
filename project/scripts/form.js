document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('myform');
    if (!form) return;
    
    const messagesContainer = document.createElement('div');
    messagesContainer.className = 'form-messages';
    form.parentNode.insertBefore(messagesContainer, form.nextSibling);

    function validateForm(form) {
        const errors = {};
        let isValid = true;

        const fullName = form.querySelector('[name="full_name"]').value.trim();
        if (!fullName) {
            errors.full_name = 'Укажите ФИО';
            isValid = false;
        } else if (fullName.length > 128) {
            errors.full_name = 'ФИО не должно превышать 128 символов';
            isValid = false;
        }

        const phone = form.querySelector('[name="phone"]').value.trim();
        if (!phone) {
            errors.phone = 'Укажите телефон';
            isValid = false;
        } else if (!/^\+7\d{10}$/.test(phone)) {
            errors.phone = 'Формат: +7XXXXXXXXXX';
            isValid = false;
        }

        const email = form.querySelector('[name="email"]').value.trim();
        if (!email) {
            errors.email = 'Укажите email';
            isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            errors.email = 'Некорректный email';
            isValid = false;
        }

        const day = form.querySelector('[name="birth_day"]').value;
        const month = form.querySelector('[name="birth_month"]').value;
        const year = form.querySelector('[name="birth_year"]').value;
        if (!day || !month || !year) {
            errors.birth_date = 'Укажите полную дату';
            isValid = false;
        } else {
            const birthDate = new Date(`${year}-${month}-${day}`);
            const minDate = new Date();
            minDate.setFullYear(minDate.getFullYear() - 120);
            if (birthDate > new Date()) {
                errors.birth_date = 'Дата не может быть в будущем';
                isValid = false;
            } else if (birthDate < minDate) {
                errors.birth_date = 'Проверьте дату рождения';
                isValid = false;
            }
        }

        const gender = form.querySelector('[name="gender"]:checked');
        if (!gender) {
            errors.gender = 'Укажите пол';
            isValid = false;
        }

        const langs = form.querySelectorAll('[name="languages[]"]:checked');
        if (langs.length === 0) {
            errors.languages = 'Выберите хотя бы один язык';
            isValid = false;
        }

        const bio = form.querySelector('[name="biography"]').value.trim();
        if (!bio) {
            errors.biography = 'Заполните биографию';
            isValid = false;
        } else if (bio.length > 512) {
            errors.biography = 'Не более 512 символов';
            isValid = false;
        }

        const agreement = form.querySelector('[name="agreement"]').checked;
        if (!agreement) {
            errors.agreement = 'Необходимо согласие';
            isValid = false;
        }

        return { isValid, errors };
    }

    function showErrors(errors) {
        messagesContainer.innerHTML = '';
        messagesContainer.className = 'form-messages errors';
        
        form.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });

        for (const [field, message] of Object.entries(errors)) {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('is-invalid');
                const errorElement = document.createElement('div');
                errorElement.className = 'error-message';
                errorElement.textContent = message;
                messagesContainer.appendChild(errorElement);
            }
        }
    }

    function showSuccess(data) {
        messagesContainer.innerHTML = '';
        messagesContainer.className = 'form-messages success';
        
        if (data.login && data.password) {
            const successMsg = document.createElement('div');
            successMsg.innerHTML = `
                <p>Данные сохранены!</p>
                <p>Ваши учетные данные:</p>
                <p><strong>Логин:</strong> ${data.login}</p>
                <p><strong>Пароль:</strong> ${data.password}</p>
                <p><a href="/login">Войти в систему</a></p>
            `;
            messagesContainer.appendChild(successMsg);
        } else {
            messagesContainer.textContent = 'Данные успешно обновлены!';
        }
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = form.querySelector('[type="submit"]');
        const originalText = submitBtn.value;
        submitBtn.disabled = true;
        submitBtn.value = 'Отправка...';

        const { isValid, errors } = validateForm(form);
        if (!isValid) {
            showErrors(errors);
            submitBtn.disabled = false;
            submitBtn.value = originalText;
            return;
        }

        try {
            const formData = new FormData(form);
            const langs = Array.from(form.querySelectorAll('[name="languages[]"]:checked'))
                .map(el => el.value);
            formData.set('languages', langs.join(','));

            const response = await fetch('/api/form', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const result = await response.json();

            if (response.ok) {
                showSuccess(result);
                if (result.login) {
                    form.reset();
                }
            } else {
                showErrors(result.errors || {});
            }
        } catch (error) {
            messagesContainer.innerHTML = `
                <div class="error-message">Ошибка сети: ${error.message}</div>
            `;
            messagesContainer.className = 'form-messages errors';
        } finally {
            submitBtn.disabled = false;
            submitBtn.value = originalText;
        }
    });

    form.setAttribute('novalidate', 'true');
});
