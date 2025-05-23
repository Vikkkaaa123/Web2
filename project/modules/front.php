<?php

// Обработчик запросов методом GET.
function front_get($request) {
  // Пример ответа веб-сервиса.
  return array('headers' => array('Content-Type' => 'application/xml'), 'entity' => '<document />');
  // Пример возврата контента.
  return '123';
  // Пример запрета доступа.
  return access_denied();
  // Пример возврата ресурс не найден.
  return not_found();
}

// Обработчик запросов методом POST.
function front_post($request) {
  // Пример возврата редиректа.
  return redirect('new-location');
}