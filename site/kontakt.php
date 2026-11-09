<?php
// Empfängt das Kontaktformular von /kontakt-1/ und schickt es per E-Mail weiter.
// Wird von /assets/kontakt.js per POST (JSON) aufgerufen.

const EMPFAENGER = 'kontakt@strandhaus-gonska-dahme.de';
const ABSENDER   = 'webseite@strandhaus-gonska-dahme.de';
const BETREFF    = 'Kontaktanfrage über strandhaus-gonska-dahme.de';

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
$name      = $feld('name', 200);
$email     = $feld('email', 200);
$telefon   = $feld('telefon', 200);
$nachricht = $feld('nachricht', 2500);
$zustimmung = !empty($daten['zustimmung']);

if ($name === '' || $telefon === '' || !$zustimmung) antwort(422, false, 'Pflichtfelder fehlen');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) antwort(422, false, 'E-Mail ungültig');

// Zeilenumbrüche in Kopfzeilen verhindern (Header-Injection)
$name  = str_replace(["\r", "\n"], ' ', $name);
$email = str_replace(["\r", "\n"], '', $email);

$text = "Neue Nachricht über das Kontaktformular der Webseite:\n\n"
      . "Name:     $name\n"
      . "E-Mail:   $email\n"
      . "Mobil:    $telefon\n"
      . "Datum:    " . date('d.m.Y H:i') . "\n\n"
      . "Nachricht:\n" . ($nachricht !== '' ? $nachricht : '(keine)') . "\n\n"
      . "Einwilligung zur Datenverarbeitung: ja\n";

$kopf = implode("\r\n", [
    'From: Strandhaus Gonska Webseite <' . ABSENDER . '>',
    'Reply-To: ' . mb_encode_mimeheader($name) . " <$email>",
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
]);

$gesendet = mail(EMPFAENGER, mb_encode_mimeheader(BETREFF), $text, $kopf, '-f' . ABSENDER);
antwort($gesendet ? 200 : 500, $gesendet, $gesendet ? '' : 'Versand fehlgeschlagen');
