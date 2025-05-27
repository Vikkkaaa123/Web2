document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('myform');
    if (!form) return;
    
    const messagesContainer = document.querySelector('.error_messages');
    
      // Функция валидации формы
  function validateForm(form) {
      const errors = {};
      const del={};
        let isValid = true;

        // Проверка ФИО
        const fullName = form.querySelector('[name="full_name"]').value.trim();
        if (!fullName) {
            errors.full_name = 'Заполните ФИО';
            isValid = false;
        } else if (fullName.length > 128) {
            errors.full_name = 'ФИО не должно превышать 128 символов';
            isValid = false;
        }

        // Проверка телефона
        const phone = form.querySelector('[name="phone"]').value.trim();
        if (!phone) {
            errors.phone = 'Введите номер телефона';
            isValid = false;
        } else if (!/^\+7\d{10}$/.test(phone)) {
            errors.phone = 'Номер должен быть в формате +7XXXXXXXXXX';
            isValid = false;
        }

        // Проверка email
        const email = form.querySelector('[name="email"]').value.trim();
        if (!email) {
            errors.email = 'Введите email';
            isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            errors.email = 'Введите корректный email';
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
        const languages = form.querySelector('[name="languages[]"]');
        const selectedOptions = Array.from(languages.selectedOptions);
        if (selectedOptions.length === 0) {
            errors.languages = 'Выберите хотя бы один язык';
            isValid = false;
        }

        // Проверка биографии
        const biography = form.querySelector('[name="biography"]').value.trim();
        if (!biography) {
            errors.biography = 'Заполните биографию';
            isValid = false;
        } else if (biography.length > 512) {
            errors.biography = 'Биография не должна превышать 512 символов';
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
        // Очищаем предыдущие ошибки
        messagesContainer.innerHTML = '';
        document.querySelectorAll('.error-message').forEach(el => el.remove());
        document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));

        // Показываем новые ошибки
        for (const [field, message] of Object.entries(errors)) {
            let element;
            
            // Особые случаи для разных типов полей
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
            // Подготовка данных формы
            const formData = new FormData(form);
            
            // Добавляем languages[] для корректной обработки на сервере
            const languages = form.querySelector('[name="languages[]"]');
            const selectedOptions = Array.from(languages.selectedOptions);
            selectedOptions.forEach(option => {
                formData.append('languages[]', option.value);
            });

            // Отправка на сервер
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            // Обработка ответа
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
});
