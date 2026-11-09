# Strandhaus Gonska Dahme

Statische Kopie von https://www.strandhaus-gonska-dahme.de (bisher IONOS „MyWebsite Now“),
übernommen am 09.11.2026. Aussehen und Inhalte sind 1:1 die bestehende Seite.

## Aufbau

- `site/` – die veröffentlichte Webseite (jede Seite ist `site/<seite>/index.html`)
  - `site/index.html` – Startseite
  - `site/zimmer-reservieren-am-meer/`, `site/fruehstueck/`, `site/kontakt-1/`, `site/gemeinschaftsraum/`,
    `site/terasse/`, `site/die-region-1/`, `site/faq-haeufig-gestellte-fragen/`,
    `site/impressum-and-datenschutzerklaerung/`
  - `site/wp-content/uploads/go-x/u/<id>/` – Bilder
- `tools/mirror.mjs` – lädt die Live-Seite erneut herunter (`node tools/mirror.mjs site`)
- `tools/clean.mjs` – entfernt WordPress-API-Links (`node tools/clean.mjs site`)

## Lokal ansehen

```bash
npx http-server@14.1.1 site -p 8080
```

## Veröffentlichung

- Jeder Push auf `main`, der `site/` ändert, lädt per GitHub Actions (`.github/workflows/deploy.yml`)
  per SFTP auf IONOS Webhosting Pro (Vertrag 56726412), Ordner `/strandhausgonska`.
- SFTP-Benutzer `acc440118376`, Passwort als Repository-Secret `SFTP_PASSWORD`.
- Vorschau: http://strandhaus.k357.de (Subdomain auf `/strandhausgonska`).

## Kontaktformular

- `site/assets/kontakt.js` fängt das Formular auf `/kontakt-1/` ab und schickt es an `site/kontakt.php`.
- `kontakt.php` sendet per PHP `mail()` an kontakt@strandhaus-gonska-dahme.de (Absender
  webseite@strandhaus-gonska-dahme.de, Antwort-an = Besucher). Spam-Schutz: verstecktes Feld + Mindestzeit.
