document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('myform');
    if (!form) return;
    
    const messagesContainer = document.querySelector('.form-messages');
    
    // Функция валидации формы
    function validateForm(form) {
        const errors = {};
        const langSelect = form.querySelector('[name="languages[]"]');
        const selectedLanguages = langSelect ? 
            Array.from(langSelect.selectedOptions).map(opt => opt.value) : 
            [];

        const values = {
            'fio': form.querySelector('[name="fio"]')?.value.trim(),
            'phone': form.querySelector('[name="phone"]')?.value.trim(),
            'email': form.querySelector('[name="email"]')?.value.trim(),
            'birth_day': form.querySelector('[name="birth_day"]')?.value,
            'birth_month': form.querySelector('[name="birth_month"]')?.value,
            'birth_year': form.querySelector('[name="birth_year"]')?.value,
            'gender': form.querySelector('[name="gender"]:checked')?.value,
            'languages': selectedLanguages,
            'biography': form.querySelector('[name="biography"]')?.value.trim(),
            'agreement': form.querySelector('[name="agreement"]')?.checked
        };

        errors.isValid = true;

        // Проверка ФИО
        if (!values.fio) {
            errors.fio = 'Укажите ФИО';
            errors.isValid = false;
        } else if (values.fio.length > 128) {
            errors.fio = 'ФИО не должно превышать 128 символов';
            errors.isValid = false;
        }

        // Проверка телефона
        if (!values.phone) {
            errors.phone = 'Укажите телефон';
            errors.isValid = false;
        } else if (!/^\+7\d{10}$/.test(values.phone)) {
            errors.phone = 'Телефон должен быть в формате +7XXXXXXXXXX';
            errors.isValid = false;
        }

        // Проверка email
        if (!values.email) {
            errors.email = 'Укажите email';
            errors.isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(values.email)) {
            errors.email = 'Введите корректный email';
            errors.isValid = false;
        }

        // Проверка даты рождения
        if (!values.birth_day || !values.birth_month || !values.birth_year) {
            errors.birth_date = 'Укажите дату рождения';
            errors.isValid = false;
        } else if (!checkDate(values.birth_month, values.birth_day, values.birth_year)) {
            errors.birth_date = 'Некорректная дата рождения';
            errors.isValid = false;
        }

        // Проверка пола
        if (!values.gender) {
            errors.gender = 'Укажите пол';
            errors.isValid = false;
        }

        // Проверка языков программирования
        if (values.languages.length === 0) {
            errors.lang = 'Выберите хотя бы один язык';
            errors.isValid = false;
        }

        // Проверка биографии
        if (!values.biography) {
            errors.biography = 'Напишите биографию';
            errors.isValid = false;
        } else if (values.biography.length > 512) {
            errors.biography = 'Биография не должна превышать 512 символов';
            errors.isValid = false;
        }

        // Проверка согласия
        if (!values.agreement) {
            errors.agreement = 'Необходимо согласие';
            errors.isValid = false;
        }

        return {errors, values};
    }

    function checkDate(month, day, year) {
        month = parseInt(month);
        day = parseInt(day);
        year = parseInt(year);
        return !isNaN(month) && !isNaN(day) && !isNaN(year) && 
               month >= 1 && month <= 12 && 
               day >= 1 && day <= 31 && 
               year >= 1900 && year <= new Date().getFullYear();
    }

    // Функция отображения ошибок
    function showErrors(errors, form) {
        // Очистка предыдущих ошибок
        form.querySelectorAll('.error-field').forEach(el => {
            el.classList.remove('error-field');
        });
        form.querySelectorAll('.error-text').forEach(el => {
            el.remove();
        });

        // Добавление новых ошибок
        for (const [field, message] of Object.entries(errors)) {
            let input;
            let container;
            
            if (field === 'birth_date') {
                input = form.querySelector('[name="birth_day"]');
                container = input?.closest('.date-fields') || input?.parentElement;
            } else if (field === 'lang') {
                input = form.querySelector('[name="languages[]"]');
                container = input?.closest('label') || input?.parentElement;
            } else if (field === 'gender') {
                container = form.querySelector('[name="gender"]')?.closest('.gender-options');
            } else if (field === 'agreement') {
                container = form.querySelector('[name="agreement"]')?.closest('.checkbox-block');
            } else {
                input = form.querySelector(`[name="${field}"]`);
                container = input?.closest('label') || input?.parentElement;
            }

            if (container) {
                container.classList.add('error-field');
                
                const errorElement = document.createElement('span');
                errorElement.className = 'error-text';
                errorElement.textContent = message;
                
                if (input?.nextSibling) {
                    input.parentNode.insertBefore(errorElement, input.nextSibling);
                } else {
                    container.appendChild(errorElement);
                }
            }
        }
    }

    // Функция отображения успеха
    function showSuccess(result) {
        messagesContainer.innerHTML = '';
        
        if (result.login && result.password) {
            const successElement = document.createElement('div');
            successElement.className = 'success-message';
            successElement.innerHTML = `
                <p>Спасибо, данные сохранены!</p>
                <p>Ваш логин: <strong>${result.login}</strong></p>
                <p>Ваш пароль: <strong>${result.password}</strong></p>
                <p>Вы можете <a href="/Web2/project/login">войти</a> для изменения данных</p>
            `;
            messagesContainer.appendChild(successElement);
        } else {
            const successElement = document.createElement('div');
            successElement.className = 'success-message';
            successElement.textContent = 'Данные успешно обновлены!';
            messagesContainer.appendChild(successElement);
        }
    }

    // Обработчик отправки формы
  form.addEventListener('submit', async function(e) {
    e.preventDefault();
    console.log('Начало обработки формы');
    
    const submitBtn = form.querySelector('#submit-btn');
    if (!submitBtn) {
        console.error('Кнопка submit не найдена');
        return;
    }
    
    const originalText = submitBtn.value;
    submitBtn.disabled = true;
    submitBtn.value = 'Отправка...';
    
    try {
        console.log('Валидация формы');
        const {errors, values} = validateForm(form);
        
        if (!errors.isValid) {
            console.log('Ошибки валидации:', errors);
            showErrors(errors, form);
            return;
        }

        console.log('Подготовка FormData');
        const formData = new FormData(form);
        formData.append('is_ajax', '1');
        
        // Явно добавляем языки
        const langSelect = form.querySelector('[name="languages[]"]');
        if (langSelect) {
            Array.from(langSelect.selectedOptions).forEach(option => {
                formData.append('languages[]', option.value);
                console.log('Добавлен язык:', option.value);
            });
        }

        console.log('Отправка запроса');
        const response = await fetch('', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        console.log('Ответ получен, статус:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        console.log('Результат:', result);

        if (result.success) {
            showSuccess(result);
            if (result.login && result.password) {
                form.reset();
            }
        } else {
            showErrors(result.errors || {}, form);
        }
    } catch (error) {
        console.error('Ошибка при отправке:', error);
        messagesContainer.innerHTML = `
            <div class="error-message">
                Ошибка: ${error.message}
            </div>
        `;
    } finally {
        submitBtn.disabled = false;
        submitBtn.value = originalText;
        console.log('Обработка формы завершена');
    }
});
