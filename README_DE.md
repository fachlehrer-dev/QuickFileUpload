# QuickFileUpload

**Dateien sammeln, hochladen und teilen.**

QuickFileUpload ist eine kleine, selbst gehostete PHP-Anwendung zum Erstellen temporärer Datei-Sammelbereiche. Jeder Uploadbereich kann eine eigene URL, einen Titel, einen Gültigkeitszeitraum, erlaubte Dateitypen und optional einen passwortgeschützten Downloadbereich erhalten.

Die Anwendung ist für einfache Szenarien gedacht, in denen Dateien schnell gesammelt werden sollen, ohne dass die hochladenden Personen ein Benutzerkonto benötigen. Administratoren können mehrere Upload-Jobs verwalten; normale Management-Benutzer sehen ausschließlich ihre eigenen Jobs.

## Funktionen

- Selbst gehostete PHP-Anwendung
- Datei-Upload per Drag & Drop
- Konfigurierbare Dateityp-Gruppen
- Optionaler Frontend-Downloadbereich pro Job
- Passwortgeschützte Frontend-Downloads
- Direkte Fotoaufnahme mit Smartphone/Kamera über HTTPS
- QR-Code zum Öffnen der Kamera-Seite auf dem Smartphone
- Bildvorschau vor dem Upload
- Mehrere Management-Benutzer
- Job-Besitz: normale Benutzer verwalten nur ihre eigenen Jobs
- Hauptadministrator kann optional alle Jobs anzeigen
- Gültigkeitszeitraum je Job
- Mehrsprachiges Frontend
- Frontend-Sprachumschalter mit lokal eingebundenen Flaggen-Icons
- Frontend-Sprachauswahl pro Browser-Tab über `sessionStorage`
- ALTCHA-Botschutz bei HTTPS
- Eigenes Branding, Logo und Primärfarbe
- Favicons werden – sofern unterstützt – aus dem hochgeladenen Logo erzeugt
- Im normalen Betrieb ausschließlich lokal eingebundene Assets
- MIT-Lizenz

## Wichtig: DocumentRoot / Webroot

**Die Domain bzw. der Virtual Host darf ausschließlich auf das Verzeichnis `public/` zeigen.**

Beispiel:

```text
/pfad/zu/QuickFileUpload/
├── config/
├── storage/
├── vendor/
└── public/        <-- DocumentRoot / Webroot
```

Auf den Projekt-Hauptordner darf **keine** Domain, Subdomain, kein Alias und kein anderer öffentlicher Webroot zeigen.

Diese Verzeichnisse müssen außerhalb des öffentlichen Webroots liegen:

```text
config/
storage/
vendor/
```

Die enthaltenen `.htaccess`-Dateien stellen bei Apache eine zusätzliche Schutzschicht dar. Sie ersetzen **nicht** die korrekte Konfiguration des DocumentRoot. nginx wertet `.htaccess`-Dateien nicht aus; dort müssen bei Bedarf entsprechende Serverregeln eingerichtet werden.

## Voraussetzungen

- PHP 8.x
- Apache mit `mod_rewrite` oder entsprechende Rewrite-/Routing-Regeln auf einem anderen Webserver
- PHP-JSON-Unterstützung
- PHP-ZIP-Unterstützung für ZIP-Downloads
- PHP-GD wird empfohlen, um PNG-Favicons aus hochgeladenen Logos zu erzeugen
- HTTPS wird dringend empfohlen

HTTPS wird benötigt für:

- direkte Kamera-/Fotoaufnahme
- ALTCHA-Botschutz

`vendor/` ist bewusst Bestandteil des Repositories. Auf dem Zielserver muss daher Composer nicht mehr ausgeführt werden.

## Installation

1. Repository klonen oder auf den Server hochladen.
2. Domain/DocumentRoot so konfigurieren, dass **nur** `public/` öffentlich erreichbar ist.
3. Sicherstellen, dass PHP dort Schreibrechte besitzt, wo Laufzeitdateien angelegt werden müssen.
4. `manage.php` über die konfigurierte Domain aufrufen.
5. Ersteinrichtung durchführen.

Beispiel:

```text
https://upload.example.org/manage.php
```

Das Setup erzeugt die Laufzeitkonfiguration:

```text
config/config.json
```

Job-Metadaten und hochgeladene Dateien liegen unter:

```text
storage/
```

Diese Laufzeitdaten werden nicht in Git aufgenommen.

## Setup-Sprache und Anwendungssprache

Im ersten Schritt des Setups wird die Sprache ausgewählt. Danach lädt sich das Setup automatisch in dieser Sprache neu.

Verfügbare Sprachen werden automatisch aus folgendem Verzeichnis erkannt:

```text
config/lang/*.php
```

Die gewählte Anwendungssprache wird in `config/config.json` gespeichert und kann später in der Verwaltung geändert werden.

Der Sprach-Loader liegt unter:

```text
config/lang.php
```

Sprach-Metadaten liegen unter:

```text
config/lang/languages.json
```

Das öffentliche Frontend besitzt zusätzlich einen eigenen Sprachumschalter. Die gewählte Frontend-Sprache wird im Browser über `sessionStorage` gespeichert. Dadurch bleibt sie beim Wechsel zwischen Upload-, Kamera- und Downloadseite innerhalb desselben Tabs erhalten. Wird der Tab geschlossen, gilt beim nächsten Öffnen wieder die konfigurierte Standardsprache.

Es gibt bewusst **keinen Übersetzungs-Fallback**. Fehlende Übersetzungsschlüssel sollen auffallen und nicht zu gemischten Sprachen führen.

## Job-URLs

Für einen Job mit dem URL-Attribut `beispiel` gelten folgende öffentlichen Routen:

```text
/beispiel
/beispiel/cam
/beispiel/download
```

- `/beispiel` — Uploadbereich
- `/beispiel/cam` — direkte Kamera-/Fotoseite
- `/beispiel/download` — optionaler passwortgeschützter Frontend-Downloadbereich

Der Kamera-/QR-Bereich wird nur angeboten, wenn HTTPS verfügbar ist und der Browser mindestens eine Kamera erkennen kann.

## Upload-Jobs

Ein Job kann unter anderem enthalten:

- URL-Attribut
- sichtbaren Titel
- Start-/Endzeitraum
- erlaubte Dateityp-Gruppen
- Frontend-Download aktiv/inaktiv
- Frontend-Download-Passwort
- Besitzer

Ist der Frontend-Download für einen Job deaktiviert, wird im öffentlichen Uploadbereich kein Downloadzugang angezeigt und die Downloadroute wird zusätzlich serverseitig gesperrt.

Ist der Frontend-Download aktiviert, ist ein Download-Passwort Pflicht.

## Benutzer und Job-Besitz

Das erste während des Setups angelegte Konto wird zum **Hauptadministrator**.

Der Hauptadministrator kann:

- globale Einstellungen verwalten
- weitere Management-Benutzer anlegen
- eigene Jobs erstellen und verwalten
- optional auf eine Ansicht mit allen Jobs umschalten

Normale Management-Benutzer:

- können ihr eigenes Passwort ändern
- können Jobs anlegen
- sehen und verwalten nur die eigenen Jobs

Die Zugriffsregeln werden serverseitig geprüft und sind nicht nur eine optische Einschränkung der Oberfläche.

## Dateitypen

Unterstützt werden konfigurierbare Gruppen wie:

- Grafiken
- PDF
- Word
- Excel
- PowerPoint
- Text / CSV
- OpenDocument
- ZIP

Makro-fähige Office-Formate gehören bewusst nicht zu den vordefinierten Office-Gruppen.

ZIP-Dateien werden gespeichert und heruntergeladen, aber serverseitig nicht automatisch entpackt.

## Kameraaufnahme

Die direkte Kameraaufnahme steht nur über HTTPS zur Verfügung.

Die Kamera-Seite kann typische Fehlerfälle unterscheiden bzw. verständlich erklären, zum Beispiel:

- Kamerazugriff verweigert
- keine Kamera vorhanden
- HTTPS nicht verfügbar

Nach der Aufnahme wird das Bild zuerst als Vorschau angezeigt. Danach kann es erneut aufgenommen oder bestätigt und hochgeladen werden.

## Botschutz

ALTCHA-Botschutz kann verwendet werden, wenn HTTPS erkannt wurde.

Beim Setup gilt:

- HTTPS erkannt → Botschutz standardmäßig aktiv, aber abschaltbar
- kein HTTPS → Botschutz deaktiviert und nicht aktivierbar

Diese Einschränkung wird auch im laufenden Betrieb berücksichtigt.

## Branding

In den globalen Einstellungen können unter anderem angepasst werden:

- Firmen-/Organisationsname
- Logo
- Primärfarbe

Die Primärfarbe wird für Buttons, Links und weitere Branding-Elemente verwendet.

Wenn die PHP-Umgebung es unterstützt, werden aus dem hochgeladenen Logo Favicon-Dateien erzeugt und bei einem Logo-Wechsel neu erstellt.

## Projektinformationen

Projekt-Metadaten liegen in:

```text
config/project.json
```

Darin stehen unter anderem:

- Projektname
- Herausgeber
- GitHub-Link
- Projektwebsite
- Entwickler bzw. Teil-Team
- Entwickler-Kontaktadresse
- optionaler Endpunkt für die Installationsmeldung

Im öffentlichen Projektinfo-Dialog werden Repository und Projektwebsite verlinkt. Die Entwickler-Mail wird verschleiert eingebunden und steht nicht als vollständige Adresse direkt im initialen HTML.

## Freiwilliger Installationszähler

Das Setup kann optional fragen, ob einmalig eine Installationsmeldung gesendet werden darf.

Ohne ausdrückliche Auswahl von **Ja** wird kein Request gesendet.

Der zugehörige Receiver ist dafür vorgesehen, ausschließlich aggregierte Daten zu speichern, zum Beispiel:

- Anzahl der Installationen
- Zeitpunkt der ersten Installation
- Zeitpunkt der letzten Installation

Die Meldung ist freiwillig. Ist der Receiver nicht erreichbar, darf dies die Installation nicht verhindern.

## Repository- und Laufzeitdateien

Zum Repository gehören unter anderem:

```text
config/.htaccess
config/lang.php
config/lang/
config/project.json
storage/.htaccess
storage/.gitkeep
vendor/
public/.htaccess
public/index.php
public/manage.php
public/altcha.php
public/assets/
```

Nicht ins Repository gehören die Laufzeit-/Privatdateien:

```text
config/config.json
storage/jobs/
storage/<job>/...
public/build.php
hochgeladene Logos
erzeugte Favicons
```

`public/build.php` ist ein privates Entwicklungs-/Build-Werkzeug und wird bewusst nicht im öffentlichen Repository geführt.

## Sicherheit

- `config/`, `storage/` und `vendor/` müssen außerhalb des DocumentRoot bleiben.
- Bei Apache die mitgelieferten `.htaccess`-Schutzdateien nicht entfernen.
- Produktion ausschließlich über HTTPS betreiben.
- Hochgeladene Dateien dürfen niemals als PHP oder andere Skripte ausgeführt werden.
- Laufzeitkonfiguration und Uploads nicht in Git aufnehmen.
- Serverrechte so einschränken, dass PHP nur dort schreiben kann, wo es erforderlich ist.

Weitere Hinweise stehen in [SECURITY.md](SECURITY.md).

## Drittanbieter-Komponenten

QuickFileUpload verwendet bzw. bündelt unter anderem:

- Bootstrap
- Bootstrap Icons
- ALTCHA Widget
- ALTCHA PHP-Bibliothek
- QRCode.js
- flag-icons

Diese Komponenten werden für den normalen Betrieb lokal ausgeliefert. Weitere Angaben stehen in [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md) sowie in den mitgelieferten Lizenzdateien der Abhängigkeiten.

## Lizenz

QuickFileUpload wird unter der **MIT-Lizenz** veröffentlicht.

Siehe [LICENSE](LICENSE).
