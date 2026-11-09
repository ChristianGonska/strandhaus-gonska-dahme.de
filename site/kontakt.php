<?php
// Empfängt das Kontaktformular von /kontakt-1/ und schickt es per SMTP (IONOS-Postfach) weiter.
// Wird von /assets/kontakt.js per POST (JSON) aufgerufen.
// Zugangsdaten stehen in kontakt-config.php; die Datei erzeugt der Upload-Ablauf aus dem
// GitHub-Secret SMTP_PASSWORD, sie liegt nicht im Repository.

const EMPFAENGER = 'kontakt@strandhaus-gonska-dahme.de';
const BETREFF    = 'Kontaktanfrage über strandhaus-gonska-dahme.de';
const SMTP_HOST  = 'ssl://smtp.ionos.de:465';

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');

function antwort(int $status, bool $ok, string $fehler = ''): void {
    http_response_code($status);
    echo json_encode(['ok' => $ok, 'fehler' => $fehler]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') antwort(405, false, 'Methode nicht erlaubt');

$daten = json_decode(file_get_contents('php://input'), true);
if (!is_array($daten)) antwort(400, false, 'Ungültige Anfrage');

// Spam-Schutz: verstecktes Feld muss leer bleiben, Formular nicht schneller als 3 s ausgefüllt
if (!empty($daten['website'])) antwort(200, true);
if (isset($daten['dauer']) && (int)$daten['dauer'] < 3000) antwort(200, true);

$feld = fn(string $k, int $max) => trim(mb_substr((string)($daten[$k] ?? ''), 0, $max));
$name       = $feld('name', 200);
$email      = $feld('email', 200);
$telefon    = $feld('telefon', 200);
$nachricht  = $feld('nachricht', 2500);
$zustimmung = !empty($daten['zustimmung']);

if ($name === '' || $telefon === '' || !$zustimmung) antwort(422, false, 'Pflichtfelder fehlen');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) antwort(422, false, 'E-Mail ungültig');

// Zeilenumbrüche in Kopfzeilen verhindern (Header-Injection)
$name  = str_replace(["\r", "\n"], ' ', $name);
$email = str_replace(["\r", "\n"], '', $email);

$config = @include __DIR__ . '/kontakt-config.php';
if (!is_array($config) || empty($config['user']) || empty($config['pass'])) {
    error_log('kontakt.php: kontakt-config.php fehlt oder unvollständig');
    antwort(500, false, 'Versand nicht eingerichtet');
}

$text = "Neue Nachricht über das Kontaktformular der Webseite:\n\n"
      . "Name:     $name\n"
      . "E-Mail:   $email\n"
      . "Mobil:    $telefon\n"
      . "Datum:    " . date('d.m.Y H:i') . "\n\n"
      . "Nachricht:\n" . ($nachricht !== '' ? $nachricht : '(keine)') . "\n\n"
      . "Einwilligung zur Datenverarbeitung: ja\n";

$absender = $config['user'];
$domain   = substr(strrchr($absender, '@'), 1);
$kopf = implode("\r\n", [
    'Date: ' . date('r'),
    'From: ' . mb_encode_mimeheader('Strandhaus Gonska Webseite') . " <$absender>",
    'To: <' . EMPFAENGER . '>',
    'Reply-To: ' . mb_encode_mimeheader($name) . " <$email>",
    'Subject: ' . mb_encode_mimeheader(BETREFF),
    'Message-ID: <' . bin2hex(random_bytes(12)) . "@$domain>",
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: base64',
]);
$inhalt = $kopf . "\r\n\r\n" . rtrim(chunk_split(base64_encode($text), 76, "\r\n"));

function smtp_senden(string $user, string $pass, string $von, string $an, string $daten): ?string {
    $s = @stream_socket_client(SMTP_HOST, $errno, $errstr, 15);
    if (!$s) return "Verbindung: $errstr";
    stream_set_timeout($s, 15);
    $lies = function () use ($s): string {
        $zeilen = '';
        while (($z = fgets($s, 1024)) !== false) { $zeilen .= $z; if (isset($z[3]) && $z[3] === ' ') break; }
        return $zeilen;
    };
    $schritt = function (?string $befehl, string $erwartet) use ($s, $lies): ?string {
        if ($befehl !== null) fwrite($s, $befehl . "\r\n");
        $r = $lies();
        // nur die Serverantwort zurückgeben, nie den Befehl (enthält ggf. Zugangsdaten)
        return strncmp($r, $erwartet, 3) === 0 ? null : "erwartet $erwartet, Antwort: " . trim($r);
    };
    $fehler = $schritt(null, '220')
        ?? $schritt('EHLO ' . (gethostname() ?: 'localhost'), '250')
        ?? $schritt('AUTH LOGIN', '334')
        ?? $schritt(base64_encode($user), '334')
        ?? $schritt(base64_encode($pass), '235')
        ?? $schritt("MAIL FROM:<$von>", '250')
        ?? $schritt("RCPT TO:<$an>", '250')
        ?? $schritt('DATA', '354')
        ?? $schritt(preg_replace('/^\./m', '..', $daten) . "\r\n.", '250');
    fwrite($s, "QUIT\r\n");
    fclose($s);
    return $fehler;
}

$fehler = smtp_senden($absender, $config['pass'], $absender, EMPFAENGER, $inhalt);
if ($fehler !== null) {
    error_log('kontakt.php: SMTP-Fehler: ' . $fehler);
    antwort(500, false, 'Versand fehlgeschlagen');
}
antwort(200, true);
