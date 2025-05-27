<?php

define('DISPLAY_ERRORS', 1);

define('INCLUDE_PATH', './scripts' . PATH_SEPARATOR . './modules');

$conf = array(
  'sitename' => 'Demo Framework',
  'theme' => './theme',
  'charset' => 'UTF-8',
  'clean_urls' => TRUE,
  'display_errors' => 1,
  'date_format' => 'Y.m.d',
  'date_format_2' => 'Y.m.d H:i',
  'date_format_3' => 'd.m.Y',
  'basedir' => '/Web2/',
  'login' => 'admin',
  'password' => '123',
);

$urlconf = array(
  '' => array('module' => 'front'),
  '/^form$/' => array('module' => 'front'), 
  '/^admin$/' => array('module' => 'admin', 'auth' => 'auth_basic'),
  '/^admin\/(\d+)$/' => array('module' => 'admin', 'auth' => 'auth_basic'),
  '/^login$/' => array('module' => 'login'), 
  '/^logout$/' => array('module' => 'logout', 'method' => 'post') 
);

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
header('Content-Type: text/html; charset=' . $conf['charset']);
