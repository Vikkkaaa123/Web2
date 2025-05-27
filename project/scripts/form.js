document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('myform');
    if (!form) return;
    
    const messagesContainer = document.querySelector('.error_messages');
    
  function validateForm(form) {
      const errors = {};
      const del={};
      const fio = form.querySelector('[name="fio"]')?.value.trim();
      const phone = form.querySelector('[name="number"]')?.value.trim();
      const email = form.querySelector('[name="email"]')?.value.trim();
      const date = form.querySelector('[name="birthdate"]')?.value;

      const gender = form.querySelector('[name="radio-group-1"]:checked');

      const languages = form.querySelector('[name="languages[]"]');
      
      const biography = form.querySelector('[name="biography"]')?.value.trim();
      const contract = form.querySelector('[name="checkbox"]')?.checked;
  
      errors.proverka = true;
  
      del.fio = 'check';
      if (!fio) {
        errors.fio = 'Заполните имя, пожалуйста';
        errors.proverka = false;
      } else if (fio.length > 150) {
        errors.fio = 'Имя не должно превышать 150 символов';
        errors.proverka = false;
      } else if (!/^[a-zA-Zа-яА-ЯёЁ\s]+$/u.test(fio)) {
        errors.fio = 'Имя должно содержать только буквы и пробелы';
        errors.proverka = false;
      }
      
      del.number = 'check';
      if (!phone) {
        errors.number = 'Введите номер телефона';
        errors.proverka = false;
      } else if (!/^\+7\d{10}$/.test(phone)) {
        errors.number = 'Номер должен быть в формате +7XXXXXXXXXX';
        errors.proverka = false;
      }
      
      del.email = 'check';
      if (!email) {
        errors.email = 'Введите email';
        errors.proverka = false;
      } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errors.email = 'Введите корректный email';
        errors.proverka = false;
      }
      
     const day = form.querySelector('[name="birth_day"]').value;
const month = form.querySelector('[name="birth_month"]').value;
const year = form.querySelector('[name="birth_year"]').value;

      
// Проверка даты рождения
del.birthdate = 'check';
if (!day || !month || !year) {
    errors.birthdate = 'Укажите полную дату рождения';
    errors.proverka = false;
} else {
    // Создаем дату из отдельных компонентов
    const birthDate = new Date(`${year}-${month}-${day}`);
    
    // Проверяем, что дата валидна (например, не 31 февраля)
    if (isNaN(birthDate.getTime())) {
        errors.birthdate = 'Укажите корректную дату рождения';
        errors.proverka = false;
    } else {
        const minDate = new Date();
        minDate.setFullYear(minDate.getFullYear() - 120);
        
        const maxDate = new Date();
        maxDate.setFullYear(maxDate.getFullYear() - 0);
        
        if (birthDate < minDate) {
            errors.birthdate = 'Дата рождения не может быть раньше ' + minDate.toLocaleDateString();
            errors.proverka = false;
        } else if (birthDate > maxDate) {
            errors.birthdate = 'Вам должно быть больше 0 лет';
            errors.proverka = false;
        }
    }
}
      
      del['radio-group-1'] = 'check';
       if (!gender) {
         errors['radio-group-1'] = 'Укажите пол';
         errors.proverka = false;
      }

       del.languages = 'check';
      if (!languages.value) { 
        errors.languages = 'Укажите хотя бы один язык';
        errors.proverka = false;
      }
      
       del.biography = 'check';
      if (!biography) {
        errors.biography = 'Заполните биографию';
        errors.proverka = false;
      } else if (biography.length > 512) {
        errors.biography = 'Биография не должна превышать 512 символов';
        errors.proverka = false;
      } else if (/[<>{}[\]]|<\?php|<script/i.test(biography)) {
        errors.biography = 'Биография содержит запрещенные символы';
        errors.proverka = false;
      }
      
       del.checkbox = 'check';
      if (!contract) {
        errors.checkbox = 'Необходимо согласиться с условиями';
        errors.proverka = false;
      }
      
      return errors;
    }
    
    function showErrors(errors, form, container) {
      container.innerHTML = '';
      container.style.display = 'block';


      form.querySelectorAll('.error-field').forEach(el => {
        el.classList.remove('error-field');
      });
      const del = {};
        del.fio='c';
        del.number='c';
        del.email='c';
        del.birthdate='c';
        del.languages='c';
        del.biography='c';
        del.checkbox='c';
        del['radio-group-1'] = 'check';
      for(const [field, message] of Object.entries(del)){
        let fieldElement;
        

        if (field === 'radio-group-1') {
          fieldElement = form.querySelector(`[name="${field}"]`)?.closest('label');
            console.log( form.querySelector(`[name="${field}"]`)?.closest('label'));
        } else if (field === 'checkbox') {
          fieldElement = form.querySelector(`[name="${field}"]`)?.closest('label');
        } else if (field === 'languages') {
          fieldElement = form.querySelector(`[name="${field}[]"]`)?.closest('label');
        } else {
          fieldElement = form.querySelector(`[name="${field}"]`);
        }

        fieldElement.classList.remove('error-field');
      }


      for (const [field, message] of Object.entries(errors)) {

        console.log(field);

        let fieldElement;
        
        if (field === 'radio-group-1') {
          fieldElement = form.querySelector(`[name="${field}"]`)?.closest('label');
            console.log( form.querySelector(`[name="${field}"]`)?.closest('label'));
        } else if (field === 'checkbox') {
          fieldElement = form.querySelector(`[name="${field}"]`)?.closest('label');
        } else if (field === 'languages') {
          fieldElement = form.querySelector(`[name="${field}[]"]`)?.closest('label');
        } else {
          fieldElement = form.querySelector(`[name="${field}"]`);
        }
        
        console.log("fieldElement: ", fieldElement);

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
      
      if (result.message) {
          console.log(result.message);
          const loginMsg = document.createElement('div');
          loginMsg.className = 'succes-message';
          loginMsg.innerHTML = `${result.message}`;
          container.appendChild(loginMsg);
        }

        if (result.login && result.password) {
          console.log(result.login);
          console.log(result.password);
          const loginMsg = document.createElement('div');
          loginMsg.className = 'success';
          loginMsg.innerHTML = `Вы можете войти с логином: ${result.login} и паролем: ${result.password}`;
          container.appendChild(loginMsg);
        }
      
      if (!form.querySelector('[name="uid"]')) {
        form.reset();
      }
    }
      
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = form.querySelector('#submit-btn');
        const originalBtnText = submitBtn.value;
        submitBtn.disabled = true;
        submitBtn.value = 'Отправка...';
        
        
        try {
                          
          const errors = validateForm(form);
          if (errors.proverka == false) {
              console.log('валидация!');
            showErrors(errors, form, messagesContainer);
            submitBtn.disabled = false;
            submitBtn.value = originalBtnText;
            return;
          }
          console.log('НЕ валидация!');
            const formData = new FormData(form);
            console.log('Данные формы:', Object.fromEntries(formData.entries()));
          
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            
            console.log("check", response.headers.get('content-type'));
            //console.log(await response.text());
            
            const result = await response.json();

            console.log(result);

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
