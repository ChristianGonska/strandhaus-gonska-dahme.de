// Ersetzt den alten WordPress-Versand des Kontaktformulars: schickt die Eingaben an /kontakt.php
// und nutzt die vorhandenen Fehler-, Erfolgs- und Ladeanzeigen der Seite.
(function () {
  var form = document.querySelector('form.form-form');
  if (!form) return;
  var id = form.getAttribute('data-id');
  var geladen = Date.now();

  // verstecktes Feld gegen Spam-Bots
  var falle = document.createElement('input');
  falle.type = 'text'; falle.name = 'website'; falle.tabIndex = -1; falle.autocomplete = 'off';
  falle.setAttribute('aria-hidden', 'true');
  falle.style.cssText = 'position:absolute;left:-9999px;width:1px;height:1px;opacity:0';
  form.appendChild(falle);

  function feld(n) { return form.querySelector('[data-id="' + id + n + '"]'); }
  function zeige(el, an) { if (el) el.classList.toggle('form-display-none', !an); }
  function fehlerVon(input) {
    var combo = input.closest('.form-input-combo');
    return combo ? combo.querySelectorAll('.form-label-error') : [];
  }

  function pruefe() {
    var ok = true;
    ['input0', 'input1', 'input2', 'gdpr'].forEach(function (n) {
      var el = feld(n), meldungen = fehlerVon(el);
      var leer = el.type === 'checkbox' ? !el.checked : el.value.trim() === '';
      var ungueltig = n === 'input1' && !leer && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(el.value.trim());
      zeige(meldungen[0], leer);
      if (meldungen[1]) zeige(meldungen[1], ungueltig);
      if (leer || ungueltig) ok = false;
    });
    return ok;
  }

  // auf window in der Capture-Phase, damit der alte Handler nie zum Zug kommt
  window.addEventListener('submit', function (e) {
    if (e.target !== form) return;
    e.preventDefault();
    e.stopImmediatePropagation();
    var fehler = document.getElementById(id + 'error');
    var erfolg = document.getElementById(id + 'success');
    var laden = document.getElementById(id + 'loading');
    zeige(fehler, false); zeige(erfolg, false);
    if (!pruefe()) return;

    zeige(laden, true);
    fetch('/kontakt.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name: feld('input0').value,
        email: feld('input1').value,
        telefon: feld('input2').value,
        nachricht: feld('input3').value,
        zustimmung: feld('gdpr').checked,
        website: falle.value,
        dauer: Date.now() - geladen
      })
    })
      .then(function (r) { return r.json().catch(function () { return { ok: false }; }); })
      .then(function (res) {
        zeige(laden, false);
        if (res.ok) { form.reset(); zeige(erfolg, true); } else { zeige(fehler, true); }
      })
      .catch(function () { zeige(laden, false); zeige(fehler, true); });
  }, true);
})();
