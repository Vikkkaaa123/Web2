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
        e.preventDefault(); // Важно: предотвращаем стандартную отправку
        
        const submitBtn = form.querySelector('#submit-btn');
        const originalText = submitBtn.value;
        
        submitBtn.disabled = true;
        submitBtn.value = 'Отправка...';

        // Очищаем предыдущие ошибки
        document.querySelectorAll('.error-text').forEach(el => el.remove());
        document.querySelectorAll('.error-field').forEach(el => el.classList.remove('error-field'));

        try {
            const formData = new FormData(form);
            formData.append('is_ajax', '1'); // Добавляем флаг AJAX

            const response = await fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (result.success) {
                alert('Данные успешно сохранены!');
            } else {
                // Показываем ошибки для каждого поля
                if (result.errors) {
                    for (const [field, message] of Object.entries(result.errors)) {
                        const input = form.querySelector(`[name="${field}"]`) || 
                                     form.querySelector(`[name="${field}[]"]`);
                        if (input) {
                            input.classList.add('error-field');
                            const errorSpan = document.createElement('span');
                            errorSpan.className = 'error-text';
                            errorSpan.textContent = message;
                            input.parentNode.appendChild(errorSpan);
                        }
                    }
                }
            }
        } catch (error) {
            console.error('Ошибка:', error);
            alert('Произошла ошибка при отправке формы');
        } finally {
            submitBtn.disabled = false;
            submitBtn.value = originalText;
        }
    });
});
