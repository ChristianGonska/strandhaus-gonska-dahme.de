<?php
header('Content-Type: application/json');
$to = 'kontakt@strandhaus-gonska-dahme.de';
$res = [];
foreach (['webseite@strandhaus-gonska-dahme.de', 'webseite@k357.de', null] as $i => $from) {
  $h = "Content-Type: text/plain; charset=UTF-8\r\n" . ($from ? "From: $from" : '');
  $ok = @mail($to, "Test $i Kontaktformular (bitte ignorieren)", "Absender-Test $i: " . ($from ?? 'Server-Standard'), $h);
  $res[] = ['from' => $from ?? 'Server-Standard', 'ok' => $ok, 'err' => $ok ? null : (error_get_last()['message'] ?? null)];
  if ($ok) break;
}
echo json_encode($res, JSON_PRETTY_PRINT);
