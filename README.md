# Sturmfrei – Hamburger Impro Shows

Website für **Sturmfrei**, eine Improvisationstheater-Gruppe aus Hamburg.
Statische Seite ohne Build-Schritt oder Abhängigkeiten.

🔗 Instagram: [@impro_sturmfrei](https://www.instagram.com/impro_sturmfrei/)

## Aufbau

```
index.html            Die komplette Seite (Nav, Hero, Showplan, Über uns, Shows, Kontakt)
style.css             Styles; Farben als CSS-Variablen in :root
script.js             Cross-Fade der Gruppenfotos im "Wer sind wir?"-Abschnitt
images/logo/          Logo (auch als Favicon)
images/group/         Gruppenfotos für den Cross-Fade
images/shows/<datum>/  Fotos je Show, Ordner nach Datum (YYYY-MM-DD)
```

## Lokal ansehen

Da alles statisch ist, reicht ein einfacher Webserver:

```bash
python3 -m http.server 8000
```

Dann [http://localhost:8000](http://localhost:8000) öffnen.
`index.html` direkt im Browser zu öffnen funktioniert auch.

## Neue Show hinzufügen

1. Ordner `images/shows/YYYY-MM-DD/` anlegen und die Fotos als `1.jpg`, `2.jpg`, … ablegen.
2. In `index.html` im Abschnitt `<section id="shows">` eine neue `<div class="show">`-Karte
   nach dem Muster der bestehenden anlegen — mit Datum, Ort, Spielern und einem
   `<img>` pro Foto.
3. Jedes `<img>` braucht ein `alt` und `loading="lazy"`.

**Bilder vorher verkleinern.** Die Slider zeigen sie mit 340×425 px an, deshalb sind
ca. 1000 px Breite mehr als genug. Zurzeit sind einige Dateien fast 1 MB groß.
