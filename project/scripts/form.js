document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('myform');
    if (!form) return;
    
    form.removeAttribute('action');
    const messagesContainer = document.querySelector('.error_messages');
    
    function validateForm(form) {
        const errors = {};
        let isValid = true;

        // ФИО
        const fio = form.querySelector('[name="fio"]').value.trim();
        if (!fio) {
            errors.fio = 'Заполните имя, пожалуйста';
            isValid = false;
        } else if (fio.length > 128) {
            errors.fio = 'Имя не должно превышать 128 символов';
            isValid = false;
        }

        // Телефон
        const phone = form.querySelector('[name="phone"]').value.trim();
        if (!phone) {
            errors.phone = 'Введите номер телефона';
            isValid = false;
        } else if (!/^\+7\d{10}$/.test(phone)) {
            errors.phone = 'Телефон должен быть в формате +7XXXXXXXXXX';
            isValid = false;
        }

        // Email
        const email = form.querySelector('[name="email"]').value.trim();
        if (!email) {
            errors.email = 'Введите email';
            isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            errors.email = 'Email должен быть в формате example@domain.com';
            isValid = false;
        }

        // Дата рождения
        const day = form.querySelector('[name="birth_day"]').value;
        const month = form.querySelector('[name="birth_month"]').value;
        const year = form.querySelector('[name="birth_year"]').value;
        if (!day || !month || !year) {
            errors.birth_date = 'Укажите полную дату рождения';
            isValid = false;
        } else {
            const birthDate = new Date(`${year}-${month}-${day}`);
            if (isNaN(birthDate.getTime())) {
                errors.birth_date = 'Некорректная дата рождения';
                isValid = false;
            }
        }

        // Пол
        const gender = form.querySelector('[name="gender"]:checked');
        if (!gender) {
            errors.gender = 'Укажите пол';
            isValid = false;
        }

        // Языки программирования
        const languages = Array.from(form.querySelectorAll('[name="languages[]"]:checked'));
        if (languages.length === 0) {
            errors.lang = 'Укажите хотя бы один язык';
            isValid = false;
        }

        // Биография
        const biography = form.querySelector('[name="biography"]').value.trim();
        if (!biography) {
            errors.biography = 'Заполните биографию';
            isValid = false;
        } else if (biography.length > 512) {
            errors.biography = 'Биография не должна превышать 512 символов';
            isValid = false;
        }

        // Согласие
        const agreement = form.querySelector('[name="agreement"]').checked;
        if (!agreement) {
            errors.agreement = 'Необходимо согласиться с условиями';
            isValid = false;
        }

        return { errors, isValid };
    }
    
    function showErrors(errors, form, container) {
        container.innerHTML = '';
        form.querySelectorAll('.error-field').forEach(el => {
            el.classList.remove('error-field');
        });

        for (const [field, message] of Object.entries(errors)) {
            let fieldElement;
            
            if (field === 'gender') {
                fieldElement = form.querySelector(`[name="${field}"]`).closest('.gender-options');
            } else if (field === 'agreement') {
                fieldElement = form.querySelector(`[name="${field}"]`).closest('.checkbox-block');
            } else if (field === 'lang') {
                fieldElement = form.querySelector(`[name="languages[]"]`).closest('label');
            } else if (field === 'birth_date') {
                fieldElement = form.querySelector(`[name="birth_day"]`).closest('label');
            } else {
                fieldElement = form.querySelector(`[name="${field}"]`);
            }

            if (fieldElement) {
                fieldElement.classList.add('error-field');
                const errorElement = document.createElement('div');
                errorElement.className = 'error';
                errorElement.textContent = message;
                container.appendChild(errorElement);
            }
        }
    }

    function showSuccess(result, container, form) {
        container.innerHTML = '';
        container.style.display = 'block';
        
        if (result.login && result.password) {
            const loginMsg = document.createElement('div');
            loginMsg.className = 'success';
            loginMsg.innerHTML = `Вы можете войти с логином: ${result.login} и паролем: ${result.password}`;
            container.appendChild(loginMsg);
        }
    }
  
   form.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = form.querySelector('#submit-btn');
    const originalBtnText = submitBtn.value;
    submitBtn.disabled = true;
    submitBtn.value = 'Отправка...';
    
    try {
        const { errors, isValid } = validateForm(form);
        if (!isValid) {
            showErrors(errors, form, messagesContainer);
            submitBtn.disabled = false;
            submitBtn.value = originalBtnText;
            return;
        }

        const formData = new FormData(form); // Используем форму напрямую

        const response = await fetch(form.action || 'index.php', { // Используем action формы или index.php
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();

        if (result.success) {
            if (result.credentials) {
                /
                showSuccess(result, messagesContainer, form);
            } else {
                
                window.location.reload();
            }
        } else {
            showErrors(result.errors || {}, form, messagesContainer);
        }
    } catch (error) {
        console.error('Ошибка:', error);
        messagesContainer.innerHTML = `<div class="error">Ошибка при отправке формы: ${error.message}</div>`;
    } finally {
        submitBtn.disabled = false;
        submitBtn.value = originalBtnText;
    }
});
