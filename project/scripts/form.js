document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('myform');
    if (!form) return;

    const messagesContainer = document.querySelector('.form-messages');
    const submitBtn = form.querySelector('#submit-btn');

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Блокировка кнопки
        const originalText = submitBtn.value;
        submitBtn.disabled = true;
        submitBtn.value = 'Отправка...';

        try {
            // Валидация ВСЕХ полей
            const errors = validateForm(form);
            if (Object.keys(errors).length > 0) {
                showErrors(errors);
                return;
            }

            // Подготовка данных
            const formData = new FormData(form);
            formData.append('is_ajax', '1');

            // Отправка
            const response = await fetch(form.action || window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (result.success) {
                showSuccess(result);
                if (result.redirect) {
                    window.location.href = result.redirect;
                }
            } else {
                showErrors(result.errors || {});
            }
        } catch (error) {
            console.error('Ошибка:', error);
            messagesContainer.innerHTML = `
                <div class="error-message">
                    Ошибка при отправке формы. Пожалуйста, попробуйте еще раз.
                </div>
            `;
        } finally {
            submitBtn.disabled = false;
            submitBtn.value = originalText;
        }
    });

    // Полная валидация формы
    function validateForm(form) {
        const errors = {};
        const fields = {
            'fio': 'Укажите ФИО',
            'phone': 'Укажите телефон',
            'email': 'Укажите email',
            'birth_day': 'Укажите день рождения',
            'birth_month': 'Укажите месяц рождения',
            'birth_year': 'Укажите год рождения',
            'gender': 'Укажите пол',
            'biography': 'Напишите биографию',
            'languages[]': 'Выберите хотя бы один язык',
            'agreement': 'Необходимо ваше согласие'
        };

        // Проверка каждого поля
        for (const [name, message] of Object.entries(fields)) {
            const element = form.querySelector(`[name="${name}"]`);
            if (!element) continue;

            let isValid = true;

            if (element.type === 'checkbox') {
                isValid = element.checked;
            } else if (element.type === 'radio') {
                isValid = form.querySelector(`[name="${name}"]:checked`) !== null;
            } else if (element.type === 'select-multiple') {
                isValid = element.selectedOptions.length > 0;
            } else {
                isValid = element.value.trim() !== '';
            }

            if (!isValid) {
                errors[name.replace('[]', '')] = message;
            }
        }

        // Дополнительная проверка даты
        if (!errors.birth_day && !errors.birth_month && !errors.birth_year) {
            const day = parseInt(form.querySelector('[name="birth_day"]').value);
            const month = parseInt(form.querySelector('[name="birth_month"]').value);
            const year = parseInt(form.querySelector('[name="birth_year"]').value);
            
            if (!checkDate(day, month, year)) {
                errors.birth_day = 'Некорректная дата';
                errors.birth_month = 'Некорректная дата';
                errors.birth_year = 'Некорректная дата';
            }
        }

        return errors;
    }

    function checkDate(day, month, year) {
        if (isNaN(day) || isNaN(month) || isNaN(year)) return false;
        const date = new Date(year, month - 1, day);
        return date.getFullYear() === year && 
               date.getMonth() === month - 1 && 
               date.getDate() === day;
    }

    function showErrors(errors) {
        // Очистка предыдущих ошибок
        document.querySelectorAll('.error-field').forEach(el => {
            el.classList.remove('error-field');
        });
        document.querySelectorAll('.error-text').forEach(el => {
            el.remove();
        });

        // Добавление новых ошибок
        for (const [field, message] of Object.entries(errors)) {
            let element;
            
            if (field === 'languages') {
                element = document.querySelector('[name="languages[]"]');
            } else {
                element = document.querySelector(`[name="${field}"]`);
            }

            if (element) {
                const container = element.closest('label') || element.parentElement;
                container.classList.add('error-field');
                
                const errorElement = document.createElement('span');
                errorElement.className = 'error-text';
                errorElement.textContent = message;
                container.appendChild(errorElement);
            }
        }

        // Прокрутка к первой ошибке
        const firstError = document.querySelector('.error-field');
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function showSuccess(result) {
        messagesContainer.innerHTML = `
            <div class="success-message">
                ${result.message || 'Форма успешно отправлена!'}
            </div>
        `;
        
        if (result.login && result.password) {
            messagesContainer.innerHTML += `
                <div class="credentials">
                    Логин: <strong>${result.login}</strong><br>
                    Пароль: <strong>${result.password}</strong>
                </div>
            `;
        }
    }
});
