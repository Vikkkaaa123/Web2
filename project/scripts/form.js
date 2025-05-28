document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('myform');
    if (!form) return;
    
    // Удаляем стандартный action формы
    form.removeAttribute('action');
    
    const messagesContainer = document.querySelector('.error_messages');
    
    // Функция валидации формы
    function validateForm(form) {
        const errors = {};
        let isValid = true;

        // Проверка ФИО
        const fio = form.querySelector('[name="fio"]').value.trim();
        if (!fio) {
            errors.fio = 'Заполните имя, пожалуйста';
            isValid = false;
        }

        // Проверка телефона
        const phone = form.querySelector('[name="number"]').value.trim();
        if (!phone) {
            errors.number = 'Введите номер телефона';
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
            errors.birthdate = 'Укажите полную дату рождения';
            isValid = false;
        }

        // Проверка пола
        const gender = form.querySelector('[name="radio-group-1"]:checked');
        if (!gender) {
            errors['radio-group-1'] = 'Укажите пол';
            isValid = false;
        }

        // Проверка языков
        const languages = Array.from(form.querySelectorAll('[name="languages[]"]:checked'));
        if (languages.length === 0) {
            errors.languages = 'Укажите хотя бы один язык';
            isValid = false;
        }

        // Проверка биографии
        const biography = form.querySelector('[name="biography"]').value.trim();
        if (!biography) {
            errors.biography = 'Заполните биографию';
            isValid = false;
        }

        // Проверка согласия
        const contract = form.querySelector('[name="checkbox"]').checked;
        if (!contract) {
            errors.checkbox = 'Необходимо согласиться с условиями';
            isValid = false;
        }

        return { errors, isValid };
    }
    
    // Функция отображения ошибок
    function showErrors(errors, form, container) {
        container.innerHTML = '';
        form.querySelectorAll('.error-field').forEach(el => {
            el.classList.remove('error-field');
        });

        for (const [field, message] of Object.entries(errors)) {
            let fieldElement;
            
            if (field === 'radio-group-1') {
                fieldElement = form.querySelector(`[name="${field}"]`).closest('label');
            } else if (field === 'checkbox') {
                fieldElement = form.querySelector(`[name="${field}"]`).closest('label');
            } else if (field === 'languages') {
                fieldElement = form.querySelector(`[name="languages[]"]`).closest('label');
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

    // Функция отображения успеха
    function showSuccess(result, container, form) {
        container.innerHTML = '';
        container.style.display = 'block';
        
        if (result.login && result.password) {
            const loginMsg = document.createElement('div');
            loginMsg.className = 'success';
            loginMsg.innerHTML = `Вы можете войти с логином: ${result.login} и паролем: ${result.password}`;
            container.appendChild(loginMsg);
        }
        
        if (!form.querySelector('[name="uid"]')) {
            form.reset();
        }
    }
  
    // Обработчик отправки формы
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        e.stopImmediatePropagation(); // Важно: предотвращаем стандартное поведение
        
        const submitBtn = form.querySelector('#submit-btn');
        const originalBtnText = submitBtn.value;
        submitBtn.disabled = true;
        submitBtn.value = 'Отправка...';
        
        try {
            // Валидация
            const { errors, isValid } = validateForm(form);
            if (!isValid) {
                showErrors(errors, form, messagesContainer);
                submitBtn.disabled = false;
                submitBtn.value = originalBtnText;
                return;
            }

            // Подготовка данных
            const formData = new FormData();
            
            // Собираем все данные
            const fields = [
                'fio', 'number', 'email', 
                'birth_day', 'birth_month', 'birth_year',
                'radio-group-1', 'biography', 'checkbox'
            ];
            
            fields.forEach(field => {
                const element = form.querySelector(`[name="${field}"]`);
                if (element.type === 'checkbox') {
                    formData.append(field, element.checked ? '1' : '0');
                } else if (element.type === 'radio') {
                    const checked = form.querySelector(`[name="${field}"]:checked`);
                    if (checked) formData.append(field, checked.value);
                } else {
                    formData.append(field, element.value);
                }
            });

            // Добавляем языки программирования
            const languages = Array.from(form.querySelectorAll('[name="languages[]"]:checked'));
            languages.forEach(lang => {
                formData.append('languages[]', lang.value);
            });

            // Отправка на сервер (явно указываем index.php)
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
                showSuccess(result, messagesContainer, form);
            } else {
                showErrors(result.errors || {}, form, messagesContainer);
            }
        } catch (error) {
            messagesContainer.innerHTML = `<div class="error">Ошибка при отправке формы: ${error.message}</div>`;
            messagesContainer.style.display = 'block';
        } finally {
            submitBtn.disabled = false;
            submitBtn.value = originalBtnText;
        }
    });
});
