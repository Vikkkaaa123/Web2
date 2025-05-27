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

    function showErrors(errors) {
        messagesContainer.innerHTML = '';
        form.querySelectorAll('.error').forEach(el => {
            el.classList.remove('error');
        });

        for (const [field, message] of Object.entries(errors)) {
            if (field === 'birth_date') {
                const dateContainer = form.querySelector('.date-fields');
                dateContainer.classList.add('error');
                const errorElement = document.createElement('div');
                errorElement.className = 'error-message';
                errorElement.textContent = message;
                dateContainer.appendChild(errorElement);
                continue;
            }

            let fieldElement;
            
            if (field === 'gender') {
                fieldElement = form.querySelector('.gender-options');
            } else if (field === 'agreement') {
                fieldElement = form.querySelector('.agreement-field');
            } else if (field === 'languages') {
                fieldElement = form.querySelector('select[name="languages[]"]').parentElement;
            } else {
                fieldElement = form.querySelector(`[name="${field}"]`);
            }

            if (fieldElement) {
                fieldElement.classList.add('error');
                const errorElement = document.createElement('div');
                errorElement.className = 'error-message';
                errorElement.textContent = message;
                fieldElement.parentNode.insertBefore(errorElement, fieldElement.nextSibling);
            }
        }
    }

    function showSuccess(result) {
        messagesContainer.innerHTML = '';
        
        const successDiv = document.createElement('div');
        successDiv.className = 'success-message';
        
        if (result.login && result.password) {
            successDiv.innerHTML = `
                <p>Данные успешно сохранены!</p>
                <p>Ваши учетные данные:</p>
                <p><strong>Логин:</strong> ${result.login}</p>
                <p><strong>Пароль:</strong> ${result.password}</p>
            `;
            form.reset();
        } else {
            successDiv.textContent = result.message || 'Данные успешно обновлены';
        }
        
        messagesContainer.appendChild(successDiv);
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = form.querySelector('[type="submit"]');
        const originalBtnText = submitBtn.value;
        
        submitBtn.disabled = true;
        submitBtn.value = 'Отправка...';

        const { errors, isValid } = validateForm(form);
        if (!isValid) {
            showErrors(errors);
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
            } else {
                showErrors(result.errors || {});
            }
        } catch (error) {
            messagesContainer.innerHTML = `<div class="error-message">Ошибка при отправке формы: ${error.message || 'Неизвестная ошибка'}</div>`;
            console.error('Ошибка:', error);
        } finally {
            submitBtn.disabled = false;
            submitBtn.value = originalBtnText;
        }
    });
});
