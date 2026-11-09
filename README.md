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

## Offene Punkte

- Kontaktformular (`site/kontakt-1/`) schickt bisher an WordPress (admin-ajax) und
  funktioniert statisch nicht. Braucht einen Ersatz (PHP-Mailskript oder Formulardienst).
