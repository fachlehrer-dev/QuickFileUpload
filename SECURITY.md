# Security

## Deployment

The application must be deployed with `public/` as the only public document root.

Do not expose the project root directly. In particular, these directories must not be directly web-accessible:

- `config/`
- `storage/`
- `vendor/`

`config/.htaccess` and `storage/.htaccess` deny access on Apache and disable directory listings as an additional safeguard. nginx and other servers do not process `.htaccess`; configure equivalent access rules there if necessary.

## Runtime secrets and uploads

`config/config.json` contains runtime configuration and secrets and must not be committed to Git.

`storage/` contains job metadata and uploaded files and must not be committed. The repository keeps only `storage/.htaccess` and `storage/.gitkeep`.

## HTTPS

Use HTTPS in production. ALTCHA bot protection and direct camera capture are automatically unavailable over plain HTTP.

## Reporting a vulnerability

Please report security issues privately to the project maintainer rather than opening a public issue containing exploit details or secrets.
