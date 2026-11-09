<?php
header('Content-Type: application/json');
$c = @include __DIR__ . '/kontakt-config.php';
$out = ['config_vorhanden' => is_array($c), 'user' => $c['user'] ?? null, 'pass_laenge' => isset($c['pass']) ? strlen($c['pass']) : 0];
foreach (['ssl://smtp.ionos.de:465', 'tcp://smtp.ionos.de:587'] as $host) {
  $s = @stream_socket_client($host, $no, $str, 10);
  if (!$s) { $out[$host] = "keine Verbindung: $str"; continue; }
  stream_set_timeout($s, 10);
  $lies = function () use ($s) { $r=''; while (($z=fgets($s,1024))!==false) { $r.=$z; if (isset($z[3]) && $z[3]===' ') break; } return trim($r); };
  $log = ['begruessung' => substr($lies(), 0, 80)];
  if (str_starts_with($host, 'ssl://') && is_array($c)) {
    fwrite($s, "EHLO test\r\n"); $lies();
    fwrite($s, "AUTH LOGIN\r\n"); $log['auth'] = substr($lies(), 0, 3);
    fwrite($s, base64_encode($c['user'])."\r\n"); $log['user'] = substr($lies(), 0, 3);
    fwrite($s, base64_encode($c['pass'])."\r\n"); $log['pass'] = $lies();
  }
  fwrite($s, "QUIT\r\n"); fclose($s);
  $out[$host] = $log;
}
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
