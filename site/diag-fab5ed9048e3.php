<?php
header('Content-Type: application/json');
echo json_encode([
  'php' => PHP_VERSION,
  'sendmail_path' => ini_get('sendmail_path'),
  'disable_functions' => ini_get('disable_functions'),
  'mail_exists' => function_exists('mail'),
  'mail_force_extra_parameters' => ini_get('mail.force_extra_parameters'),
  'smtp' => ini_get('SMTP'),
], JSON_PRETTY_PRINT);
