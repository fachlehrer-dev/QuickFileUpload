# QuickFileUpload

**Collect, upload & share files.**

QuickFileUpload is a small, self-hosted PHP application for creating temporary file collection areas. Each upload area can have its own URL, title, active period, allowed file types and optional password-protected frontend download area.

The application is designed for simple deployments where users should be able to upload files quickly without needing an account. Administrators can create and manage multiple upload jobs, while regular management users only see their own jobs.

## Features

- Self-hosted PHP application
- Drag & drop file uploads
- Configurable file-type groups
- Optional frontend download area per job
- Password-protected frontend downloads
- Direct smartphone photo capture over HTTPS
- QR code for opening the camera page on a smartphone
- Photo preview before upload
- Multiple management users
- Job ownership: regular users only manage their own jobs
- Main administrator can optionally display all jobs
- Per-job active period
- Multi-language frontend
- Frontend language selector with locally bundled flag icons
- Per-tab frontend language selection using `sessionStorage`
- ALTCHA bot protection when HTTPS is available
- Custom branding, logo and primary colour
- Favicons generated from the uploaded logo where supported
- Local assets only during normal application use
- MIT licensed

## Important deployment rule

**The domain or virtual host must point to the `public/` directory only.**

Example:

```text
/path/to/QuickFileUpload/
├── config/
├── storage/
├── vendor/
└── public/        <-- DocumentRoot / web root
```

Do **not** point a domain, subdomain, alias or public document root at the project root.

The following directories must stay outside the public web root:

```text
config/
storage/
vendor/
```

The included `.htaccess` files provide an additional protection layer for Apache, but they are **not a replacement** for a correctly configured document root. nginx does not process `.htaccess` files and requires equivalent server-side rules where necessary.

## Requirements

- PHP 8.x
- Apache with `mod_rewrite`, or equivalent rewrite/routing rules on another web server
- PHP JSON support
- PHP ZIP support for ZIP downloads
- PHP GD is recommended for generating PNG favicons from uploaded logos
- HTTPS is strongly recommended

HTTPS is required for:

- direct camera/photo capture
- ALTCHA bot protection

The repository intentionally includes `vendor/`, so Composer does not need to be run on the target server.

## Installation

1. Clone or upload the repository to the server.
2. Configure the domain/document root to point **only** to `public/`.
3. Ensure PHP has write access where runtime files need to be created.
4. Open `manage.php` through the configured domain.
5. Complete the initial setup.

Example:

```text
https://upload.example.org/manage.php
```

The setup creates the runtime configuration:

```text
config/config.json
```

Job metadata and uploaded files are stored below:

```text
storage/
```

These runtime files are excluded from Git.

## Setup language and application language

The first step of the setup lets you select the language. The setup then reloads in that language.

Available languages are discovered from:

```text
config/lang/*.php
```

The selected application language is stored in `config/config.json` and can later be changed in the management interface.

The language loader is located at:

```text
config/lang.php
```

Language metadata is stored in:

```text
config/lang/languages.json
```

The frontend also provides its own language selector. A visitor's frontend language is stored in browser `sessionStorage`, so it remains active while navigating between upload, camera and download pages in the same tab. Closing the tab resets the choice and the configured default language is used again.

There is intentionally **no translation fallback**. Missing translation keys should be detected instead of silently mixing languages.

## Job URLs

For a job whose URL attribute is `example`, the public routes are:

```text
/example
/example/cam
/example/download
```

- `/example` — upload area
- `/example/cam` — direct camera/photo page
- `/example/download` — optional password-protected frontend download area

The camera/QR entry is only offered when HTTPS is available and a camera can be detected by the browser.

## Upload jobs

A job can contain, among other settings:

- URL attribute
- display title
- start/end period
- allowed file-type groups
- frontend-download enabled/disabled
- frontend-download password
- owner

If frontend downloads are disabled for a job, the download control is not shown publicly and the download route is blocked server-side.

If frontend downloads are enabled, a download password is required.

## Management users

The first account created during setup becomes the **main administrator**.

The main administrator can:

- manage global settings
- create additional management users
- create and manage their own jobs
- optionally switch to a view showing all jobs

Regular management users:

- can change their own password
- can create jobs
- only see and manage jobs they own

Ownership restrictions are enforced server-side and are not only visual UI restrictions.

## File types

The application supports configurable groups such as:

- Images
- PDF
- Word
- Excel
- PowerPoint
- Text / CSV
- OpenDocument
- ZIP

Macro-enabled Office formats are intentionally not part of the predefined Office groups.

ZIP files are stored and downloaded as files; they are not automatically extracted on the server.

## Camera capture

Direct camera capture is available only over HTTPS.

The camera page can detect common failure situations such as:

- camera access denied
- no camera available
- HTTPS not available

A captured image is shown as a preview first. The user can either retake it or confirm the upload.

## Bot protection

ALTCHA bot protection is available when HTTPS is detected.

During setup:

- HTTPS detected → bot protection is enabled by default and can be disabled
- no HTTPS → bot protection is disabled and cannot be enabled

The same restriction is enforced at runtime.

## Branding

Global settings support custom branding including:

- company/organisation name
- logo
- primary colour

The primary colour is used for buttons, links and other branding elements.

When supported by the PHP environment, favicon files are generated from the uploaded logo and regenerated when the logo changes.

## Project information

Project metadata is stored in:

```text
config/project.json
```

This contains information such as:

- project name
- publisher
- GitHub URL
- project website
- developer/team name
- developer contact address
- optional usage-notification endpoint

The public project-information dialog links to the repository and project website and shows the developer contact in an obfuscated form rather than placing the complete email address directly in the initial HTML.

## Optional installation counter

The project can optionally ask during setup whether a one-time installation notice may be sent.

No request is made unless the user explicitly selects **Yes**.

The corresponding receiver is intended to store only aggregate installation information such as:

- total installation count
- timestamp of the first installation
- timestamp of the most recent installation

The notice is optional and a receiver failure must not prevent installation.

## Repository and runtime files

Files that belong in the repository include:

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

Runtime/private files excluded by `.gitignore` include:

```text
config/config.json
storage/jobs/
storage/<job>/...
public/build.php
uploaded logo files
generated favicon files
```

`public/build.php` is a private development/build helper and is intentionally not part of the public repository.

## Security

- Keep `config/`, `storage/` and `vendor/` outside the document root.
- Keep the supplied `.htaccess` protection files when using Apache.
- Use HTTPS in production.
- Do not make uploaded files executable as PHP or scripts.
- Runtime configuration and uploaded files must not be committed to Git.
- Review server permissions so PHP can write only where required.

See [SECURITY.md](SECURITY.md) for additional deployment notes.

## Third-party components

QuickFileUpload bundles or uses components including:

- Bootstrap
- Bootstrap Icons
- ALTCHA Widget
- ALTCHA PHP library
- QRCode.js
- flag-icons

These are bundled locally for normal operation. See [THIRD_PARTY_NOTICES.md](THIRD_PARTY_NOTICES.md) and the bundled dependency license files for details.

## License

QuickFileUpload is released under the **MIT License**.

See [LICENSE](LICENSE).
