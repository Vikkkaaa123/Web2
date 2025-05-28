document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('myform');
    if (!form) return;
    
    const messagesContainer = document.createElement('div');
    messagesContainer.className = 'form-messages';
    form.parentNode.insertBefore(messagesContainer, form.nextSibling);

    // Функция отображения ошибок
    function showErrors(errors) {
        // Очищаем предыдущие ошибки
        document.querySelectorAll('.error-field').forEach(el => {
            el.classList.remove('error-field');
        });
        document.querySelectorAll('.error-text').forEach(el => {
            el.remove();
        });

        // Добавляем новые ошибки
        for (const [field, message] of Object.entries(errors)) {
            let input;
            
            if (field === 'birth_date') {
                input = form.querySelector('[name="birth_day"]');
            } else if (field === 'lang') {
                input = form.querySelector('[name="languages[]"]');
            } else {
                input = form.querySelector(`[name="${field}"]`);
            }

            if (input) {
                const fieldContainer = input.closest('label') || input.parentElement;
                fieldContainer.classList.add('error-field');
                
                const errorElement = document.createElement('span');
                errorElement.className = 'error-text';
                errorElement.textContent = message;
                
                if (input.nextSibling) {
                    input.parentNode.insertBefore(errorElement, input.nextSibling);
                } else {
                    fieldContainer.appendChild(errorElement);
                }
            }
        }
    }

    // Функция отображения успешного сообщения
    function showSuccess(message) {
        messagesContainer.innerHTML = '';
        const successElement = document.createElement('div');
        successElement.className = 'success-message';
        successElement.textContent = message;
        messagesContainer.appendChild(successElement);
    }

    
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('myform');
    if (!form) return;



       
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        e.stopPropagation();

        const submitBtn = form.querySelector('#submit-btn');
        const originalBtnText = submitBtn.value;
        
        submitBtn.disabled = true;
        submitBtn.value = 'Отправка...';

        // Удаляем предыдущие ошибки
        document.querySelectorAll('.error-text').forEach(el => el.remove());
        document.querySelectorAll('.input-field').forEach(el => el.classList.remove('error'));

        try {
            const formData = new FormData(form);
            
            // Добавляем флаг AJAX
            formData.append('is_ajax', '1');

            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            // Проверяем, что ответ - JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Сервер вернул не JSON ответ');
            }

            const result = await response.json();

            if (result.success) {
                // Успешная отправка
                alert('Данные успешно сохранены!');
            } else {
                // Показываем ошибки
                if (result.errors) {
                    Object.entries(result.errors).forEach(([field, message]) => {
                        let inputName = field;
                        if (field === 'birth_date') inputName = 'birth_day';
                        if (field === 'lang') inputName = 'languages[]';
                        
                        const input = form.querySelector(`[name="${inputName}"]`);
                        if (input) {
                            input.classList.add('error');
                            
                            const errorElement = document.createElement('span');
                            errorElement.className = 'error-text';
                            errorText.textContent = message;
                            
                            input.insertAdjacentElement('afterend', errorElement);
                        }
                    });
                }
            }
        } catch (error) {
            console.error('Ошибка:', error);
            alert('Произошла ошибка при отправке формы');
        } finally {
            submitBtn.disabled = false;
            submitBtn.value = originalBtnText;
        }
    });
});
