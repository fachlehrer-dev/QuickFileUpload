<?php
session_start();
define('BASE_DIR', dirname(__DIR__));
define('CONFIG_FILE', BASE_DIR . '/config/config.json');
define('LANG_FILE', BASE_DIR . '/config/lang.php');
define('PROJECT_FILE', BASE_DIR . '/config/project.json');
define('JOBS_DIR', BASE_DIR . '/storage/jobs/');
define('STORAGE_DIR', BASE_DIR . '/storage/');
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$rootUrl = $baseUrl ?: '';

require_once LANG_FILE;
$projectMeta = [];
if (is_file(PROJECT_FILE)) {
    $decodedProjectMeta = json_decode((string)file_get_contents(PROJECT_FILE), true);
    if (is_array($decodedProjectMeta)) $projectMeta = $decodedProjectMeta;
}
$language = null;
$uiLanguage = null;
function setUiLanguage(string $code): void {
    global $language, $uiLanguage;
    $language = lang_load($code);
    $uiLanguage = $code;
}
function t(string $key, array $vars = []): string {
    global $language, $uiLanguage;
    if ($uiLanguage === null || !is_array($language) || !array_key_exists($key, $language['strings'])) {
        throw new RuntimeException('MISSING_TRANSLATION:' . ($uiLanguage ?? 'none') . ':' . $key);
    }
    $text = (string)$language['strings'][$key];
    foreach ($vars as $name => $value) $text = str_replace('{' . $name . '}', (string)$value, $text);
    return $text;
}
function languageMeta(string $key): string {
    global $language, $uiLanguage;
    if ($uiLanguage === null || !is_array($language) || !array_key_exists($key, $language['_meta'])) {
        throw new RuntimeException('MISSING_LANGUAGE_META:' . ($uiLanguage ?? 'none') . ':' . $key);
    }
    return (string)$language['_meta'][$key];
}

function sendUsagePing(string $url): void {
    if ($url === '') return;
    try {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_TIMEOUT => 3, CURLOPT_USERAGENT => 'IFL-FileUpload-Setup/1.0']);
            @curl_exec($ch);
            curl_close($ch);
            return;
        }
        $ctx = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true, 'header' => "User-Agent: IFL-FileUpload-Setup/1.0\r\n"]]);
        @file_get_contents($url, false, $ctx);
    } catch (Throwable $e) {
        // Die freiwillige Meldung darf das Setup niemals blockieren.
    }
}
function renderProjectInfoModal(): void {
    global $projectMeta;
    if (!$projectMeta) return;
    $developerKey = (($projectMeta['developer_type'] ?? '') === 'team') ? 'project.developed_by_team' : 'project.developed_by_person';
    $developerEmail = (string)($projectMeta['developer_email'] ?? '');
    [$developerEmailUser, $developerEmailDomain] = array_pad(explode('@', $developerEmail, 2), 2, '');
    $developerEmailUserEncoded = base64_encode($developerEmailUser);
    $developerEmailDomainEncoded = base64_encode($developerEmailDomain);
    ?>
    <style>
    .project-corner-wrap{position:fixed;right:0;bottom:0;width:92px;height:92px;z-index:1040;overflow:hidden}
    .project-corner-btn{position:relative;display:block;width:100%;height:100%;border:0;padding:0;background:transparent;cursor:pointer;overflow:hidden;clip-path:polygon(100% 36%,100% 100%,36% 100%);transition:clip-path .28s cubic-bezier(.2,.8,.2,1)}
    .project-corner-btn::before{content:"";position:absolute;inset:0;background:var(--bs-primary);z-index:0}
    .project-corner-btn i{position:absolute;right:12px;bottom:11px;z-index:1;display:grid;place-items:center;width:40px;height:40px;color:#fff;font-size:2rem;line-height:1}
    .project-corner-wrap:focus-within .project-corner-btn{clip-path:polygon(100% 0,100% 100%,0 100%)}
    @media (hover:hover) and (pointer:fine){.project-corner-wrap:hover .project-corner-btn{clip-path:polygon(100% 0,100% 100%,0 100%)}}
    @media (max-width:575.98px){.project-corner-wrap{width:74px;height:74px}.project-corner-btn i{right:9px;bottom:8px;width:32px;height:32px;font-size:1.65rem}}
    @media (prefers-reduced-motion:reduce){.project-corner-btn{transition:none}}
    </style>
    <div class="project-corner-wrap">
      <button type="button" class="project-corner-btn" data-bs-toggle="modal" data-bs-target="#projectInfoModal" aria-label="<?= htmlspecialchars(t('project.info_title')) ?>" title="<?= htmlspecialchars(t('project.info_title')) ?>"><i class="bi bi-github"></i></button>
    </div>
    <div class="modal fade" id="projectInfoModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><?= htmlspecialchars(t('project.info_title')) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body text-center">
        <i class="bi bi-github text-primary d-block mb-3" style="font-size:4.5rem"></i>
        <h5><?= htmlspecialchars((string)($projectMeta['project_name'] ?? '')) ?></h5>
        <p><?= htmlspecialchars(t('project.mit_text')) ?></p>
        <p class="mb-2"><span><?= htmlspecialchars(t('project.github_info_download')) ?>:</span> <a href="<?= htmlspecialchars((string)($projectMeta['github_url'] ?? '')) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars((string)($projectMeta['github_url'] ?? '')) ?></a></p>
        <p><span><?= htmlspecialchars(t('project.more_information')) ?>:</span> <a href="<?= htmlspecialchars((string)($projectMeta['website_url'] ?? '')) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars((string)($projectMeta['website_url'] ?? '')) ?></a></p>
        <hr>
        <p class="mb-1"><?= htmlspecialchars(t($developerKey, ['name' => (string)($projectMeta['developer_name'] ?? '')])) ?></p>
        <p class="mb-1"><?= htmlspecialchars(t('project.published_by', ['name' => (string)($projectMeta['publisher_name'] ?? '')])) ?></p>
        <p class="mb-0"><span><?= htmlspecialchars(t('project.contact')) ?>:</span> <a href="#" class="developer-mail" data-u="<?= htmlspecialchars($developerEmailUserEncoded) ?>" data-d="<?= htmlspecialchars($developerEmailDomainEncoded) ?>"><?= htmlspecialchars(t('project.show_email')) ?></a></p>
    </div></div></div></div>
    <script>
    document.querySelectorAll('.developer-mail').forEach(function(link) {
        if (link.dataset.mailBound === '1') return;
        link.dataset.mailBound = '1';
        link.addEventListener('click', function(event) {
            event.preventDefault();
            try {
                var address = atob(link.dataset.u || '') + '@' + atob(link.dataset.d || '');
                link.textContent = address;
                link.href = 'mailto:' + address;
                link.removeAttribute('data-u');
                link.removeAttribute('data-d');
            } catch (e) {
                return;
            }
        });
    });
    </script>
    <?php
}

// Setup ist bewusst ausschliesslich Englisch.
$initialUiLanguage = 'en';
if (!file_exists(CONFIG_FILE)) {
    $requestedSetupLanguage = lang_normalize_code((string)($_POST['setup_language'] ?? $_GET['setup_language'] ?? 'en'));
    if (in_array($requestedSetupLanguage, lang_codes(), true)) $initialUiLanguage = $requestedSetupLanguage;
}
setUiLanguage($initialUiLanguage);

function isHttpsRequest(): bool {
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') return true;
    if ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443) return true;
    $forwardedProto = strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0] ?? ''));
    return $forwardedProto === 'https';
}
$isHttps = isHttpsRequest();

if (!file_exists(BASE_DIR . '/vendor/autoload.php')) die(t('system.composer_dependencies_are_missing'));
require_once BASE_DIR . '/vendor/autoload.php';
use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\VerifySolutionOptions;
use AltchaOrg\Altcha\Algorithm\Pbkdf2;

function ensurePrivateDirectory(string $dir): void {
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $htaccess = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . '.htaccess';
    if (!is_file($htaccess)) {
        $rules = <<<'HTACCESS'
Options -Indexes

<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order allow,deny
    Deny from all
</IfModule>
HTACCESS;
        file_put_contents($htaccess, $rules . PHP_EOL);
    }
}


function clearGeneratedFavicons(): void {
    foreach ([
        __DIR__ . '/assets/img/favicon-16x16.png',
        __DIR__ . '/assets/img/favicon-32x32.png',
        __DIR__ . '/assets/img/apple-touch-icon.png',
        __DIR__ . '/assets/img/favicon.svg',
        __DIR__ . '/assets/img/favicon.png',
        __DIR__ . '/assets/img/favicon.jpg',
        __DIR__ . '/assets/img/favicon.jpeg',
        __DIR__ . '/assets/img/favicon.webp',
        __DIR__ . '/assets/img/favicon.gif',
    ] as $file) {
        if (is_file($file)) @unlink($file);
    }
}

function generateFaviconsFromLogo(string $sourcePath): void {
    if (!is_file($sourcePath)) return;
    if (!is_dir(__DIR__ . '/assets/img')) mkdir(__DIR__ . '/assets/img', 0755, true);
    clearGeneratedFavicons();

    $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
    if ($ext === 'svg') {
        @copy($sourcePath, __DIR__ . '/assets/img/favicon.svg');
        return;
    }

    $source = null;
    if (function_exists('imagecreatefrompng') && $ext === 'png') $source = @imagecreatefrompng($sourcePath);
    elseif (function_exists('imagecreatefromjpeg') && in_array($ext, ['jpg','jpeg'], true)) $source = @imagecreatefromjpeg($sourcePath);
    elseif (function_exists('imagecreatefromgif') && $ext === 'gif') $source = @imagecreatefromgif($sourcePath);
    elseif (function_exists('imagecreatefromwebp') && $ext === 'webp') $source = @imagecreatefromwebp($sourcePath);

    if ($source !== false && $source !== null && function_exists('imagecreatetruecolor') && function_exists('imagepng')) {
        $srcW = imagesx($source); $srcH = imagesy($source);
        if ($srcW > 0 && $srcH > 0) {
            foreach ([16 => 'favicon-16x16.png', 32 => 'favicon-32x32.png', 180 => 'apple-touch-icon.png'] as $size => $filename) {
                $canvas = imagecreatetruecolor($size, $size);
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                imagefill($canvas, 0, 0, $transparent);
                $scale = min($size / $srcW, $size / $srcH);
                $dstW = max(1, (int)round($srcW * $scale));
                $dstH = max(1, (int)round($srcH * $scale));
                $dstX = (int)(($size - $dstW) / 2); $dstY = (int)(($size - $dstH) / 2);
                imagecopyresampled($canvas, $source, $dstX, $dstY, 0, 0, $dstW, $dstH, $srcW, $srcH);
                imagepng($canvas, __DIR__ . '/assets/img/' . $filename, 9);
                imagedestroy($canvas);
            }
            imagedestroy($source);
            return;
        }
        if (is_resource($source)) @imagedestroy($source);
    }

    // Falls GD fuer dieses Format nicht verfuegbar ist, bleibt das Originallogo
    // als Browser-Favicon erhalten. Moderne Browser unterstuetzen diese Formate.
    if (in_array($ext, ['png','jpg','jpeg','gif','webp'], true)) {
        @copy($sourcePath, __DIR__ . '/assets/img/favicon.' . $ext);
    }
}

function faviconLinkTags(string $rootUrl): string {
    $base = rtrim($rootUrl, '/') . '/assets/img/';
    $tags = [];
    $map = [
        'favicon-32x32.png' => '<link rel="icon" type="image/png" sizes="32x32" href="%s">',
        'favicon-16x16.png' => '<link rel="icon" type="image/png" sizes="16x16" href="%s">',
        'apple-touch-icon.png' => '<link rel="apple-touch-icon" sizes="180x180" href="%s">',
        'favicon.svg' => '<link rel="icon" type="image/svg+xml" href="%s">',
        'favicon.png' => '<link rel="icon" type="image/png" href="%s">',
        'favicon.jpg' => '<link rel="icon" type="image/jpeg" href="%s">',
        'favicon.jpeg' => '<link rel="icon" type="image/jpeg" href="%s">',
        'favicon.webp' => '<link rel="icon" type="image/webp" href="%s">',
        'favicon.gif' => '<link rel="icon" type="image/gif" href="%s">',
    ];
    foreach ($map as $filename => $template) {
        $path = __DIR__ . '/assets/img/' . $filename;
        if (!is_file($path)) continue;
        $url = $base . rawurlencode($filename) . '?v=' . (string)@filemtime($path);
        $tags[] = sprintf($template, htmlspecialchars($url, ENT_QUOTES, 'UTF-8'));
    }
    return implode("\n", $tags);
}

if (!is_dir(__DIR__ . '/assets/img')) mkdir(__DIR__ . '/assets/img', 0755, true);
$error = $msg = '';

$fileTypeGroups = [
    'images' => ['label' => t('filetype.images'), 'icon' => 'bi-file-image', 'extensions' => ['jpg','jpeg','png','gif','webp','heic','heif','bmp','tif','tiff']],
    'pdf'    => ['label' => t('filetype.pdf'), 'icon' => 'bi-file-pdf', 'extensions' => ['pdf']],
    'word'   => ['label' => t('filetype.word'), 'icon' => 'bi-file-word', 'extensions' => ['docx']],
    'excel'  => ['label' => t('filetype.excel'), 'icon' => 'bi-file-excel', 'extensions' => ['xlsx']],
    'powerpoint' => ['label' => t('filetype.powerpoint'), 'icon' => 'bi-file-slides', 'extensions' => ['pptx']],
    'text'   => ['label' => t('filetype.text_csv'), 'icon' => 'bi-file-text', 'extensions' => ['txt','csv']],
    'opendocument' => ['label' => t('filetype.opendocument'), 'icon' => 'bi-file-earmark-text', 'extensions' => ['odt','ods','odp']],
    'zip'    => ['label' => t('filetype.zip'), 'icon' => 'bi-file-zip', 'extensions' => ['zip']],
];

if (!file_exists(CONFIG_FILE)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_user'])) {
        // Private Bereiche werden erst bei Abschluss der Ersteinrichtung angelegt.
        // Die .htaccess-Dateien sind nur eine zweite Schutzschicht; der Webroot
        // muss weiterhin zwingend auf /public zeigen.
        ensurePrivateDirectory(dirname(CONFIG_FILE));
        ensurePrivateDirectory(STORAGE_DIR);
        if (!is_dir(JOBS_DIR)) mkdir(JOBS_DIR, 0755, true);

        $logoPath = '';
        if (!empty($_FILES['logo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if(in_array($ext, ['png','jpg','jpeg','svg','gif','webp'])) {
                $logoPath = 'assets/img/logo_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], __DIR__ . '/' . $logoPath)) {
                    generateFaviconsFromLogo(__DIR__ . '/' . $logoPath);
                }
            }
        }
        $setupLanguage = lang_normalize_code((string)($_POST['setup_language'] ?? 'en'));
        if (!in_array($setupLanguage, lang_codes(), true)) {
            throw new RuntimeException('LANGUAGE_NOT_AVAILABLE:' . $setupLanguage);
        }
        $usageChoice = (string)($_POST['usage_choice'] ?? '');
        if (!empty($projectMeta['usage_enabled']) && !in_array($usageChoice, ['yes','no'], true)) {
            die(t('usage.choice_required'));
        }

        $cfg = [
            'users' => [[
                'id' => bin2hex(random_bytes(8)),
                'username' => trim((string)$_POST['setup_user']),
                'pass' => password_hash((string)$_POST['setup_pass'], PASSWORD_DEFAULT),
                'role' => 'admin',
                'active' => true
            ]],
            'altcha_secret' => bin2hex(random_bytes(32)),
            'title' => $_POST['page_title'] ?: t('common.default_portal_title'), 'primary_color' => $_POST['primary_color'] ?: '#0d6efd',
            'impressum' => $_POST['impressum'] ?? '', 'datenschutz' => $_POST['datenschutz'] ?? '', 'logo' => $logoPath,
            'bot_protection' => $isHttps && isset($_POST['bot_protection']),
            'language' => $setupLanguage
        ];
        file_put_contents(CONFIG_FILE, json_encode($cfg));
        if (!empty($projectMeta['usage_enabled']) && $usageChoice === 'yes') {
            sendUsagePing((string)($projectMeta['usage_url'] ?? ''));
        }
        header("Location: manage.php"); exit;
    }
    ?>
    <!DOCTYPE html><html lang="<?= htmlspecialchars(languageMeta('html')) ?>"><head><meta charset="utf-8"><title><?= htmlspecialchars(t('setup.page_title')) ?></title>
    <?= faviconLinkTags($rootUrl) ?>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet"><link href="assets/css/user.css" rel="stylesheet"><link href="assets/css/bootstrap-icons.css" rel="stylesheet"></head>
    <body class="bg-light"><div class="container mt-5" style="max-width:500px;">
        <div class="card shadow"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-4"><h4 class="card-title mb-0"><?= htmlspecialchars(t('setup.heading')) ?></h4></div>
            <form method="post" enctype="multipart/form-data">
                <h5 class="text-primary mt-3"><?= htmlspecialchars(t('setup.section_language')) ?></h5>
                <div class="mb-4">
                    <label class="form-label"><?= htmlspecialchars(t('setup.language_for_application')) ?></label>
                    <select name="setup_language" class="form-select" required onchange="window.location.href='manage.php?setup_language='+encodeURIComponent(this.value)">
                        <?php foreach (lang_available_meta() as $code => $meta): ?>
                            <option value="<?= htmlspecialchars($code) ?>" <?= $code === $uiLanguage ? 'selected' : '' ?>><?= htmlspecialchars($meta['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text"><?= htmlspecialchars(t('setup.language_help')) ?></div>
                </div>
                <h5 class="text-primary mt-3"><?= htmlspecialchars(t('setup.section_branding')) ?></h5>
                <div class="mb-2"><label><?= htmlspecialchars(t('settings.portal_title')) ?></label><input type="text" name="page_title" class="form-control" placeholder="<?= htmlspecialchars(t('setup.e_g_my_company_upload')) ?>"></div>
                <div class="mb-2"><label><?= htmlspecialchars(t('settings.primary_color')) ?></label><input type="color" name="primary_color" class="form-control form-control-color w-100" value="#0d6efd"></div>
                <div class="mb-3"><label><?= htmlspecialchars(t('settings.logo_optional')) ?></label><input type="file" name="logo" class="form-control" accept="image/*"></div>
                <h5 class="text-primary mt-4"><?= htmlspecialchars(t('setup.section_legal')) ?></h5>
                <div class="mb-2"><label><?= htmlspecialchars(t('settings.legal_notice_url')) ?></label><input type="url" name="impressum" class="form-control"></div>
                <div class="mb-3"><label><?= htmlspecialchars(t('settings.privacy_url')) ?></label><input type="url" name="datenschutz" class="form-control"></div>
                <h5 class="text-primary mt-4"><?= htmlspecialchars(t('setup.section_bot_protection')) ?></h5>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="setupBotProtection" name="bot_protection" value="1" <?= $isHttps ? 'checked' : 'disabled' ?>>
                    <label class="form-check-label" for="setupBotProtection"><?= htmlspecialchars(t('settings.bot_protection_enable')) ?></label>
                </div>
                <?php if ($isHttps): ?>
                    <div class="form-text mb-3"><i class="bi bi-shield-check me-1"></i><?= htmlspecialchars(t('setup.https_was_detected_bot_protection_is_enabled_by_default_and_can_be_disabled_if_required')) ?></div>
                <?php else: ?>
                    <div class="alert alert-warning py-2 mb-3"><i class="bi bi-exclamation-triangle me-1"></i><strong><?= htmlspecialchars(t('setup.no_ssl_https_detected')) ?></strong> <?= htmlspecialchars(t('setup.altcha_bot_protection_remains_disabled_and_can_only_be_enabled_over_https')) ?></div>
                <?php endif; ?>
                <h5 class="text-primary mt-4"><?= htmlspecialchars(t('setup.section_admin')) ?></h5>
                <div class="mb-2"><label><?= htmlspecialchars(t('common.username')) ?></label><input type="text" name="setup_user" class="form-control" required></div>
                <div class="mb-4"><label><?= htmlspecialchars(t('common.password')) ?></label><input type="password" name="setup_pass" class="form-control" required></div>
                <?php if (!empty($projectMeta['usage_enabled'])): ?>
                <h5 class="text-primary mt-4"><?= htmlspecialchars(t('usage.section_title')) ?></h5>
                <p class="small text-muted"><?= htmlspecialchars(t('usage.question')) ?></p>
                <div class="mb-2 form-check"><input class="form-check-input" type="radio" name="usage_choice" id="usageYes" value="yes" required><label class="form-check-label" for="usageYes"><?= htmlspecialchars(t('usage.yes')) ?></label></div>
                <div class="mb-4 form-check"><input class="form-check-input" type="radio" name="usage_choice" id="usageNo" value="no" required><label class="form-check-label" for="usageNo"><?= htmlspecialchars(t('usage.no')) ?></label></div>
                <?php endif; ?>
                <button type="submit" class="btn btn-success w-100"><?= htmlspecialchars(t('setup.finish')) ?></button>
            </form>
        </div></div>
    </div>
    <?php renderProjectInfoModal(); ?>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    </body></html>
    <?php exit;
}

// Auch bei bestehenden Installationen fehlende private Schutzdateien nachziehen.
ensurePrivateDirectory(dirname(CONFIG_FILE));
ensurePrivateDirectory(STORAGE_DIR);
if (!is_dir(JOBS_DIR)) mkdir(JOBS_DIR, 0755, true);

$config = json_decode(file_get_contents(CONFIG_FILE), true);
if (!empty($config['logo']) && is_file(__DIR__ . '/' . $config['logo'])) {
    $hasFavicon = false;
    foreach (glob(__DIR__ . '/assets/img/favicon*') ?: [] as $fav) { if (is_file($fav)) { $hasFavicon = true; break; } }
    if (!$hasFavicon) generateFaviconsFromLogo(__DIR__ . '/' . $config['logo']);
}
$configChanged = false;
if (empty($config['users']) || !is_array($config['users'])) {
    $legacyUser = trim((string)($config['user'] ?? 'admin'));
    $legacyPass = (string)($config['pass'] ?? '');
    $config['users'] = [[
        'id' => bin2hex(random_bytes(8)),
        'username' => $legacyUser !== '' ? $legacyUser : 'admin',
        'pass' => $legacyPass,
        'role' => 'admin',
        'active' => true
    ]];
    unset($config['user'], $config['pass']);
    $configChanged = true;
}
foreach ($config['users'] as &$cfgUser) {
    if (empty($cfgUser['id'])) { $cfgUser['id'] = bin2hex(random_bytes(8)); $configChanged = true; }
    if (!array_key_exists('active', $cfgUser)) { $cfgUser['active'] = true; $configChanged = true; }
    if (empty($cfgUser['role'])) { $cfgUser['role'] = 'user'; $configChanged = true; }
}
unset($cfgUser);
$normalizedStoredLanguage = lang_normalize_code((string)($config['language'] ?? ''));
if ($normalizedStoredLanguage === '' || !in_array($normalizedStoredLanguage, lang_codes(), true)) { $normalizedStoredLanguage = 'en'; }
if (($config['language'] ?? null) !== $normalizedStoredLanguage) { $config['language'] = $normalizedStoredLanguage; $configChanged = true; }
if ($configChanged) file_put_contents(CONFIG_FILE, json_encode($config));
setUiLanguage(lang_normalize_code((string)($config['language'] ?? 'en')));

function findPortalUserById(array $users, string $id): ?array {
    foreach ($users as $user) if (($user['id'] ?? '') === $id) return $user;
    return null;
}
function findPortalUserByName(array $users, string $username): ?array {
    foreach ($users as $user) if (strcasecmp((string)($user['username'] ?? ''), $username) === 0) return $user;
    return null;
}
function canManageJob(array $job, array $currentUser, bool $isMainAdmin): bool {
    return $isMainAdmin || (($job['owner_id'] ?? '') === ($currentUser['id'] ?? ''));
}
if (!array_key_exists('bot_protection', $config)) { $config['bot_protection'] = true; }
if (empty($config['altcha_secret'])) { $config['altcha_secret'] = bin2hex(random_bytes(32)); file_put_contents(CONFIG_FILE, json_encode($config)); }
$botProtectionEnabled = $isHttps && !empty($config['bot_protection']);
$pbkdf2 = new Pbkdf2();
$altcha = new Altcha(hmacSignatureSecret: $config['altcha_secret']);

$pColor = $config['primary_color'] ?? '#0d6efd'; list($r, $g, $b) = sscanf($pColor, "#%02x%02x%02x"); $pColorRgb = "$r, $g, $b";
$dynamicCSS = <<<CSS
<style>:root { --bs-primary: {$pColor}; --bs-primary-rgb: {$pColorRgb}; --bs-link-color: var(--bs-primary); --bs-link-hover-color: color-mix(in srgb, var(--bs-primary), #000 20%); } .btn-primary { --bs-btn-bg: var(--bs-primary); --bs-btn-border-color: var(--bs-primary); --bs-btn-hover-bg: color-mix(in srgb, var(--bs-primary), #000 15%); --bs-btn-hover-border-color: color-mix(in srgb, var(--bs-primary), #000 20%); --bs-btn-active-bg: color-mix(in srgb, var(--bs-primary), #000 20%); --bs-btn-active-border-color: color-mix(in srgb, var(--bs-primary), #000 25%); } .btn-outline-primary { --bs-btn-color: var(--bs-primary); --bs-btn-border-color: var(--bs-primary); --bs-btn-hover-color: #fff; --bs-btn-hover-bg: var(--bs-primary); --bs-btn-hover-border-color: var(--bs-primary); --bs-btn-active-color: #fff; --bs-btn-active-bg: color-mix(in srgb, var(--bs-primary), #000 10%); --bs-btn-active-border-color: color-mix(in srgb, var(--bs-primary), #000 15%); } .form-check-input:checked { background-color: var(--bs-primary); border-color: var(--bs-primary); } .form-check-input:focus { border-color: color-mix(in srgb, var(--bs-primary), #fff 35%); box-shadow: 0 0 0 .25rem color-mix(in srgb, var(--bs-primary), transparent 75%); } .bg-primary { background-color: var(--bs-primary) !important; } .text-primary, .link-primary, a:not(.btn):not(.badge):not(.project-corner-btn):not(.dropdown-item) { color: var(--bs-primary) !important; } a:not(.btn):not(.badge):not(.project-corner-btn):not(.dropdown-item):hover, a:not(.btn):not(.badge):not(.project-corner-btn):not(.dropdown-item):focus { color: color-mix(in srgb, var(--bs-primary), #000 20%) !important; }</style>
CSS;

if (isset($_GET['logout'])) { session_destroy(); header("Location: manage.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $sessionUser = findPortalUserById($config['users'], (string)($_SESSION['portal_user_id'] ?? ''));
    if (!$sessionUser || ($sessionUser['role'] ?? '') !== 'admin') { http_response_code(403); exit(t('system.access_denied')); }
    $config['title'] = $_POST['page_title']; $config['primary_color'] = $_POST['primary_color'];
    $config['impressum'] = $_POST['impressum']; $config['datenschutz'] = $_POST['datenschutz'];
    $config['bot_protection'] = $isHttps && isset($_POST['bot_protection']);
    if (!empty($_FILES['logo']['name'])) {
        $ext = strtolower(pathinfo((string)$_FILES['logo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['png','jpg','jpeg','svg','gif','webp'], true)) {
            $newLogoPath = 'assets/img/logo_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], __DIR__ . '/' . $newLogoPath)) {
                $oldLogoPath = (string)($config['logo'] ?? '');
                if ($oldLogoPath !== '' && is_file(__DIR__ . '/' . $oldLogoPath)) @unlink(__DIR__ . '/' . $oldLogoPath);
                $config['logo'] = $newLogoPath;
                generateFaviconsFromLogo(__DIR__ . '/' . $newLogoPath);
            }
        }
    }
    $selectedLanguage = lang_normalize_code((string)($_POST['language'] ?? ''));
    if (!in_array($selectedLanguage, lang_codes(), true)) throw new RuntimeException('LANGUAGE_NOT_AVAILABLE');
    $config['language'] = $selectedLanguage;
    file_put_contents(CONFIG_FILE, json_encode($config)); header("Location: manage.php?msg=saved"); exit;
}
if(isset($_GET['msg'])) {
    if ($_GET['msg'] === 'saved') $msg = t('settings.settings_saved');
    elseif ($_GET['msg'] === 'password') $msg = t('ui.password_changed_successfully');
    elseif ($_GET['msg'] === 'usercreated') $msg = t('user.user_created');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $altchaValid = !$botProtectionEnabled;
    if ($botProtectionEnabled && !empty($_POST['altcha'])) { try { $res = $altcha->verifySolution(new VerifySolutionOptions(payload: $_POST['altcha'], algorithm: $pbkdf2)); $altchaValid = $res->verified; } catch (\Throwable $e) {} }
    if ($botProtectionEnabled && !$altchaValid) { $error = t('settings.bot_protection_altcha_failed'); }
    else {
        $loginUser = findPortalUserByName($config['users'], trim((string)($_POST['user'] ?? '')));
        if ($loginUser && !empty($loginUser['active']) && password_verify((string)($_POST['pass'] ?? ''), (string)($loginUser['pass'] ?? ''))) {
            $_SESSION['portal_user_id'] = $loginUser['id'];
            unset($_SESSION['show_all_jobs']);
            header("Location: manage.php"); exit;
        }
        $error = t('auth.incorrect_credentials');
    }
}

$currentUser = findPortalUserById($config['users'], (string)($_SESSION['portal_user_id'] ?? ''));
if (!$currentUser || empty($currentUser['active'])) {
    unset($_SESSION['portal_user_id']);
    ?>
    <!DOCTYPE html><html lang="<?= htmlspecialchars(languageMeta('html')) ?>"><head><meta charset="utf-8"><title><?= htmlspecialchars($config['title'] ?? t('auth.admin_login')) ?></title>
    <?= faviconLinkTags($rootUrl) ?>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet"><link href="assets/css/user.css" rel="stylesheet"><link href="assets/css/bootstrap-icons.css" rel="stylesheet">
    <?= $dynamicCSS ?>
    <?php if ($botProtectionEnabled): ?>
    <script src="<?= htmlspecialchars($rootUrl) ?>/assets/js/altcha-v3.2.2.min.js" type="module"></script>
    <?php if (languageMeta('altcha_script') !== ''): ?><script src="<?= htmlspecialchars($rootUrl . '/' . languageMeta('altcha_script')) ?>" type="module"></script><?php endif; ?>
    <?php endif; ?>
    </head><body class="bg-light">
    <div class="text-center mt-5 mb-4">
        <?php if(!empty($config['logo']) && file_exists(__DIR__ . '/' . $config['logo'])): ?><img src="<?= htmlspecialchars($rootUrl . '/' . ltrim($config['logo'], '/')) ?>" alt="<?= htmlspecialchars(t('common.logo')) ?>" style="max-height:80px; margin-bottom:15px;"><br><?php endif; ?>
        <h3><?= htmlspecialchars($config['title'] ?? t('admin.management')) ?></h3>
    </div>
    <div class="container" style="max-width:400px;">
        <div class="card shadow"><div class="card-body">
            <h4 class="card-title mb-4"><?= htmlspecialchars(t('auth.admin_login')) ?></h4>
            <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>
            <form method="post"><input type="hidden" name="login" value="1">
                <div class="mb-3"><label><?= htmlspecialchars(t('common.username')) ?></label><input type="text" name="user" class="form-control" required></div>
                <div class="mb-3"><label><?= htmlspecialchars(t('common.password')) ?></label><input type="password" name="pass" class="form-control" required></div>
                
                <?php if ($botProtectionEnabled): ?>
                <altcha-widget 
                    challenge="<?= htmlspecialchars($rootUrl) ?>/altcha.php"
                    language="<?= htmlspecialchars(languageMeta('altcha_language')) ?>">
                </altcha-widget>
                <?php endif; ?>
                
                <button type="submit" class="btn btn-primary w-100 mt-3"><?= htmlspecialchars(t('auth.sign_in')) ?></button>
            </form>
        </div></div>
    </div>
    <?php renderProjectInfoModal(); ?>
    <script src="<?= htmlspecialchars($rootUrl) ?>/assets/js/bootstrap.bundle.min.js"></script>
    </body></html>
    <?php exit;
}

$currentUser = findPortalUserById($config['users'], (string)$_SESSION['portal_user_id']);
$isMainAdmin = (($currentUser['role'] ?? '') === 'admin');
$mainAdmin = null;
foreach ($config['users'] as $u) { if (($u['role'] ?? '') === 'admin') { $mainAdmin = $u; break; } }
if (!$mainAdmin) { http_response_code(500); exit(t('system.access_denied')); }

// Bestehende Jobs ohne Besitzer werden einmalig dem Hauptadmin zugeordnet.
foreach (glob(JOBS_DIR . '*.json') ?: [] as $legacyJobFile) {
    $legacyJob = json_decode(file_get_contents($legacyJobFile), true);
    if (is_array($legacyJob) && empty($legacyJob['owner_id'])) {
        $legacyJob['owner_id'] = $mainAdmin['id'];
        file_put_contents($legacyJobFile, json_encode($legacyJob));
    }
}

if (isset($_GET['view']) && $isMainAdmin) {
    $_SESSION['show_all_jobs'] = ($_GET['view'] === 'all');
    header('Location: manage.php'); exit;
}
$showAllJobs = $isMainAdmin && !empty($_SESSION['show_all_jobs']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $oldPass = (string)($_POST['current_password'] ?? '');
    $newPass = (string)($_POST['new_password'] ?? '');
    $newPass2 = (string)($_POST['new_password_repeat'] ?? '');
    if (!password_verify($oldPass, (string)$currentUser['pass'])) $error = t('ui.the_current_password_is_incorrect');
    elseif ($newPass !== $newPass2) $error = t('user.the_new_passwords_do_not_match');
    elseif (strlen($newPass) < 8) $error = t('ui.the_new_password_must_be_at_least_8_characters_long');
    else {
        foreach ($config['users'] as &$u) if (($u['id'] ?? '') === $currentUser['id']) { $u['pass'] = password_hash($newPass, PASSWORD_DEFAULT); break; }
        unset($u);
        file_put_contents(CONFIG_FILE, json_encode($config));
        header('Location: manage.php?msg=password'); exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    if (!$isMainAdmin) { http_response_code(403); exit(t('system.access_denied')); }
    $username = trim((string)($_POST['new_username'] ?? ''));
    $newPass = (string)($_POST['new_user_password'] ?? '');
    $newPass2 = (string)($_POST['new_user_password_repeat'] ?? '');
    if ($username === '') $error = t('user.please_enter_a_username');
    elseif (findPortalUserByName($config['users'], $username)) $error = t('user.this_username_is_already_in_use');
    elseif ($newPass !== $newPass2) $error = t('user.the_new_passwords_do_not_match');
    elseif (strlen($newPass) < 8) $error = t('ui.the_new_password_must_be_at_least_8_characters_long');
    else {
        $config['users'][] = ['id' => bin2hex(random_bytes(8)), 'username' => $username, 'pass' => password_hash($newPass, PASSWORD_DEFAULT), 'role' => 'user', 'active' => true];
        file_put_contents(CONFIG_FILE, json_encode($config));
        header('Location: manage.php?msg=usercreated'); exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['create_job']) || isset($_POST['save_job']))) {
    $isEdit = isset($_POST['save_job']);
    $originalName = $isEdit ? preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($_POST['original_name'] ?? '')) : '';
    $jobname = preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($_POST['jobname'] ?? ''));
    $jobtitle = trim($_POST['jobtitle'] ?? '');
    $selectedGroups = array_values(array_intersect(array_keys($fileTypeGroups), $_POST['type_groups'] ?? []));
    $frontendDownload = !empty($_POST['frontend_download']);
    $extensions = [];
    foreach ($selectedGroups as $groupKey) {
        $extensions = array_merge($extensions, $fileTypeGroups[$groupKey]['extensions']);
    }
    $extensions = array_values(array_unique($extensions));

    $oldFile = $isEdit ? JOBS_DIR . $originalName . '.json' : '';
    $newFile = JOBS_DIR . $jobname . '.json';
    $existing = ($isEdit && is_file($oldFile)) ? json_decode(file_get_contents($oldFile), true) : null;

    if ($isEdit && $existing && !canManageJob($existing, $currentUser, $isMainAdmin)) { http_response_code(403); exit(t('job.access_to_this_job_is_not_allowed')); }
    if ($jobname === '') { $error = t('job.please_enter_a_valid_url_attribute'); }
    elseif ($jobtitle === '') { $error = t('ui.please_enter_a_title'); }
    elseif (empty($selectedGroups)) { $error = t('file.please_select_at_least_one_file_type'); }
    elseif ($isEdit && !$existing) { $error = t('job.the_job_to_edit_was_not_found'); }
    elseif ((!$isEdit || $jobname !== $originalName) && file_exists($newFile)) { $error = t('job.this_url_attribute_is_already_in_use'); }
    else {
        $downloadPassword = $existing['dl_pass'] ?? '';
        $postedDownloadPassword = trim((string)($_POST['dl_pass'] ?? ''));
        if ($postedDownloadPassword !== '') {
            $downloadPassword = password_hash($postedDownloadPassword, PASSWORD_DEFAULT);
        }
        if ($frontendDownload && $downloadPassword === '') {
            $error = t('job.a_download_password_is_required_when_frontend_downloads_are_enabled');
        }
    }

    if ($error === '') {
        $jobData = [
            'name' => $jobname,
            'title' => $jobtitle,
            'start' => $_POST['start'],
            'end' => $_POST['end'],
            'types' => implode(',', $extensions),
            'type_groups' => $selectedGroups,
            'dl_pass' => $downloadPassword,
            'frontend_download' => $frontendDownload,
            'active' => $existing['active'] ?? true,
            'owner_id' => $existing['owner_id'] ?? $currentUser['id']
        ];

        if ($isEdit && $jobname !== $originalName) {
            $oldStorage = STORAGE_DIR . $originalName;
            $newStorage = STORAGE_DIR . $jobname;
            if (is_dir($oldStorage) && !file_exists($newStorage)) {
                rename($oldStorage, $newStorage);
            }
            if (is_file($oldFile)) unlink($oldFile);
        }

        file_put_contents($newFile, json_encode($jobData));
        if (!is_dir(STORAGE_DIR . $jobname)) mkdir(STORAGE_DIR . $jobname, 0755, true);
        $msg = $isEdit ? t('job.job_saved_successfully') : t('job.job_created_successfully');
    }
}

if (isset($_GET['toggle'])) {
    $job = basename($_GET['toggle']); $file = JOBS_DIR . $job . '.json';
    if(file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data) || !canManageJob($data, $currentUser, $isMainAdmin)) { http_response_code(403); exit(t('job.access_to_this_job_is_not_allowed')); }
        $data['active'] = !$data['active']; file_put_contents($file, json_encode($data));
    }
    header("Location: manage.php"); exit;
}

if (isset($_GET['delete'])) {
    $job = basename($_GET['delete']);
    $deleteFile = JOBS_DIR . $job . '.json';
    if(file_exists($deleteFile)) {
        $deleteData = json_decode(file_get_contents($deleteFile), true);
        if (!is_array($deleteData) || !canManageJob($deleteData, $currentUser, $isMainAdmin)) { http_response_code(403); exit(t('job.access_to_this_job_is_not_allowed')); }
        unlink($deleteFile);
    }
    $job_dir = STORAGE_DIR . $job;
    if(is_dir($job_dir)) { foreach (glob($job_dir . '/*') ?: [] as $f) { if (is_file($f)) unlink($f); } @rmdir($job_dir); }
    header("Location: manage.php"); exit;
}

// Dateiaktionen im Adminbereich
if (isset($_GET['admin_job'])) {
    $adminJob = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['admin_job']);
    $adminJobFile = JOBS_DIR . $adminJob . '.json';
    $adminJobDir = STORAGE_DIR . $adminJob . '/';
    if (!is_file($adminJobFile)) { http_response_code(404); exit(t('job.job_not_found')); }
    $adminJobData = json_decode(file_get_contents($adminJobFile), true);
    if (!is_array($adminJobData) || !canManageJob($adminJobData, $currentUser, $isMainAdmin)) { http_response_code(403); exit(t('job.access_to_this_job_is_not_allowed')); }

    if (isset($_GET['stream'])) {
        $file = basename($_GET['stream']);
        $path = $adminJobDir . $file;
        if (!is_file($path)) { http_response_code(404); exit(t('file.file_not_found')); }
        $mime = function_exists('mime_content_type') ? mime_content_type($path) : 'application/octet-stream';
        header('Content-Type: ' . ($mime ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($path));
        if (isset($_GET['dl'])) header('Content-Disposition: attachment; filename="' . basename(substr($file, 11)) . '"');
        readfile($path); exit;
    }

    if (isset($_GET['delfile'])) {
        $file = basename($_GET['delfile']);
        $path = $adminJobDir . $file;
        if (is_file($path)) unlink($path);
        header('Location: manage.php?edit=' . urlencode($adminJob) . '&tab=files'); exit;
    }

    if (isset($_GET['zip'])) {
        $files = array_values(array_filter(glob($adminJobDir . '*') ?: [], 'is_file'));
        if ($files) {
            $zipFile = tempnam(sys_get_temp_dir(), 'uploadportal_');
            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::OVERWRITE) === true) {
                foreach ($files as $file) $zip->addFile($file, substr(basename($file), 11));
                $zip->close();
                header('Content-Type: application/zip');
                header('Content-Length: ' . filesize($zipFile));
                header('Content-Disposition: attachment; filename="' . $adminJob . '.zip"');
                readfile($zipFile); @unlink($zipFile); exit;
            }
        }
    }
}

function adminFileIcon($file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $map = ['pdf'=>'bi-file-pdf text-danger','jpg'=>'bi-file-image text-success','jpeg'=>'bi-file-image text-success','png'=>'bi-file-image text-success','gif'=>'bi-file-image text-success','webp'=>'bi-file-image text-success','heic'=>'bi-file-image text-success','heif'=>'bi-file-image text-success','bmp'=>'bi-file-image text-success','tif'=>'bi-file-image text-success','tiff'=>'bi-file-image text-success','zip'=>'bi-file-zip text-warning','docx'=>'bi-file-word text-primary','xlsx'=>'bi-file-excel text-success','pptx'=>'bi-file-slides text-danger','txt'=>'bi-file-text text-secondary','csv'=>'bi-file-text text-secondary','odt'=>'bi-file-earmark-text text-primary','ods'=>'bi-file-earmark-spreadsheet text-success','odp'=>'bi-file-earmark-slides text-danger'];
    return $map[$ext] ?? 'bi-file-earmark text-secondary';
}

$jobs = [];
$usersById = [];
foreach ($config['users'] as $u) $usersById[$u['id']] = $u;
foreach (glob(JOBS_DIR . '*.json') as $file) {
    $data = json_decode(file_get_contents($file), true);
    if (!is_array($data)) continue;
    if ($showAllJobs || (($data['owner_id'] ?? '') === $currentUser['id'])) $jobs[] = $data;
}
usort($jobs, fn($a, $b) => strcasecmp($a['title'] ?? $a['name'], $b['title'] ?? $b['name']));
?>
<!DOCTYPE html><html lang="<?= htmlspecialchars(languageMeta('html')) ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= htmlspecialchars(t('admin.management')) ?> - <?= htmlspecialchars($config['title'] ?? '') ?></title>
    <?= faviconLinkTags($rootUrl) ?>
<link href="<?= htmlspecialchars($rootUrl) ?>/assets/css/bootstrap.min.css" rel="stylesheet"><link href="<?= htmlspecialchars($rootUrl) ?>/assets/css/user.css" rel="stylesheet"><link href="<?= htmlspecialchars($rootUrl) ?>/assets/css/bootstrap-icons.css" rel="stylesheet">
<?= $dynamicCSS ?>
</head><body class="bg-light">
<nav class="navbar navbar-expand bg-white shadow-sm mb-4"><div class="container">
    <span class="navbar-brand">
        <?php if(!empty($config['logo']) && file_exists(__DIR__ . '/' . $config['logo'])): ?><img src="<?= htmlspecialchars($rootUrl . '/' . ltrim($config['logo'], '/')) ?>" alt="<?= htmlspecialchars(t('common.logo')) ?>" style="max-height:40px; margin-right:15px;"><?php endif; ?>
        <strong><?= htmlspecialchars($config['title'] ?? t('admin.management')) ?></strong>
    </span>
    <div class="d-flex align-items-center gap-2">
        <span class="text-muted small d-none d-md-inline"><?= htmlspecialchars(t('user.signed_in_as')) ?> <strong><?= htmlspecialchars($currentUser['username']) ?></strong></span>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#accountModal" title="<?= htmlspecialchars(t('user.my_account')) ?>"><i class="bi bi-person-circle"></i></button>
        <a href="?logout=1" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center justify-content-center" style="width:38px;height:38px" title="<?= htmlspecialchars(t('auth.sign_out')) ?>" aria-label="<?= htmlspecialchars(t('auth.sign_out')) ?>"><i class="bi bi-box-arrow-right"></i></a>
    </div>
</div></nav>

<div class="container pb-5">
    <?php if($error) echo "<div class='alert alert-danger'>" . htmlspecialchars($error) . "</div>"; ?>
    <?php if($msg) echo "<div class='alert alert-success'>" . htmlspecialchars($msg) . "</div>"; ?>

    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
        <div class="d-flex flex-wrap gap-2">
            <?php if($isMainAdmin): ?>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#settingsModal"><i class="bi bi-gear me-1"></i> <?= htmlspecialchars(t('settings.global')) ?></button>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#usersModal"><i class="bi bi-people me-1"></i> <?= htmlspecialchars(t('user.management')) ?></button>
            <form method="get" class="d-flex align-items-center border rounded px-3 bg-white">
                <input type="hidden" name="view" value="<?= $showAllJobs ? 'mine' : 'all' ?>">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="showAllJobsSwitch" <?= $showAllJobs ? 'checked' : '' ?> onchange="this.form.submit()">
                    <label class="form-check-label" for="showAllJobsSwitch"><?= htmlspecialchars(t('user.show_all_jobs')) ?></label>
                </div>
            </form>
            <?php endif; ?>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newJobModal"><i class="bi bi-plus-circle me-1"></i> <?= htmlspecialchars(t('job.create')) ?></button>
    </div>

    <div class="card shadow-sm"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3"><h5 class="mb-0"><?= htmlspecialchars(t('job.list')) ?></h5><span class="text-muted small"><?= count($jobs) ?> <?= htmlspecialchars(t('common.available')) ?></span></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead><tr><th><?= htmlspecialchars(t('common.title')) ?></th><th><?= htmlspecialchars(t('common.url')) ?></th><?php if($isMainAdmin && $showAllJobs): ?><th><?= htmlspecialchars(t('user.owner')) ?></th><?php endif; ?><th><?= htmlspecialchars(t('common.period')) ?></th><th><?= htmlspecialchars(t('common.types')) ?></th><th><?= htmlspecialchars(t('common.status')) ?></th><th class="text-end"><?= htmlspecialchars(t('common.actions')) ?></th></tr></thead>
            <tbody>
            <?php foreach($jobs as $j): $modalId = 'jobModal_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $j['name']); ?>
                <tr>
                    <td><strong><?= htmlspecialchars($j['title'] ?? $j['name']) ?></strong></td>
                    <td><a href="/<?= rawurlencode($j['name']) ?>" target="_blank">/<?= htmlspecialchars($j['name']) ?></a></td>
                    <?php if($isMainAdmin && $showAllJobs): ?><td><?= htmlspecialchars($usersById[$j['owner_id']]['username'] ?? '') ?></td><?php endif; ?>
                    <td class="small"><?= date('d.m.y H:i', strtotime($j['start'])) ?><br><?= date('d.m.y H:i', strtotime($j['end'])) ?></td>
                    <td>
                        <?php if(!empty($j['type_groups'])): foreach($j['type_groups'] as $groupKey): if(isset($fileTypeGroups[$groupKey])): ?>
                            <span class="badge bg-secondary me-1 mb-1"><?= htmlspecialchars($fileTypeGroups[$groupKey]['label']) ?></span>
                        <?php endif; endforeach; else: ?><span class="badge bg-secondary"><?= htmlspecialchars($j['types'] ?? '') ?></span><?php endif; ?>
                    </td>
                    <td><a href="?toggle=<?= urlencode($j['name']) ?>" class="badge text-bg-<?= !empty($j['active']) ? 'success' : 'danger' ?> text-decoration-none"><?= !empty($j['active']) ? t('common.active') : t('common.paused') ?></a></td>
                    <td class="text-end text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#<?= htmlspecialchars($modalId) ?>" title="<?= htmlspecialchars(t('job.edit')) ?>"><i class="bi bi-pencil-square"></i></button>
                        <a href="?delete=<?= urlencode($j['name']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm(<?= json_encode(t('job.really_delete_the_job_including_all_uploaded_files')) ?>);" title="<?= htmlspecialchars(t('job.delete')) ?>"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if(!$jobs): ?><tr><td colspan="<?= ($isMainAdmin && $showAllJobs) ? 7 : 6 ?>" class="text-center text-muted py-5"><?= htmlspecialchars(t('job.no_jobs_available_yet')) ?></td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </div></div>
</div>

<?php if($isMainAdmin): ?>
<!-- Globale Einstellungen -->
<div class="modal fade" id="settingsModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form method="post" enctype="multipart/form-data"><input type="hidden" name="update_settings" value="1">
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-gear me-2"></i><?= htmlspecialchars(t('settings.global')) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label"><?= htmlspecialchars(t('common.title')) ?></label><input type="text" name="page_title" class="form-control" value="<?= htmlspecialchars($config['title'] ?? '') ?>"></div>
            <div class="mb-3"><label class="form-label"><?= htmlspecialchars(t('settings.primary_color')) ?></label><input type="color" name="primary_color" class="form-control form-control-color w-100" value="<?= htmlspecialchars($config['primary_color'] ?? '#0d6efd') ?>"></div>
            <div class="mb-3"><label class="form-label"><?= htmlspecialchars(t('settings.logo_optional')) ?></label><input type="file" name="logo" class="form-control" accept="image/*"></div>
            <div class="mb-3"><label class="form-label"><?= htmlspecialchars(t('settings.legal_notice_url')) ?></label><input type="url" name="impressum" class="form-control" value="<?= htmlspecialchars($config['impressum'] ?? '') ?>"></div>
            <div class="mb-3"><label class="form-label"><?= htmlspecialchars(t('settings.privacy_url')) ?></label><input type="url" name="datenschutz" class="form-control" value="<?= htmlspecialchars($config['datenschutz'] ?? '') ?>"></div>
            <div class="mb-3"><label class="form-label"><?= htmlspecialchars(t('common.language')) ?></label><select name="language" class="form-select" required><?php foreach(lang_available_meta() as $code => $meta): ?><option value="<?= htmlspecialchars($code) ?>" <?= $code === $uiLanguage ? 'selected' : '' ?>><?= htmlspecialchars($meta['name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-check form-switch mb-1">
                <input class="form-check-input" type="checkbox" role="switch" id="settingsBotProtection" name="bot_protection" value="1" <?= ($isHttps && !empty($config['bot_protection'])) ? 'checked' : '' ?> <?= !$isHttps ? 'disabled' : '' ?>>
                <label class="form-check-label" for="settingsBotProtection"><?= htmlspecialchars(t('settings.bot_protection_enable')) ?></label>
            </div>
            <?php if ($isHttps): ?>
                <div class="form-text"><?= htmlspecialchars(t('settings.https_was_detected_bot_protection_can_be_enabled_or_disabled')) ?></div>
            <?php else: ?>
                <div class="alert alert-warning py-2 mt-2 mb-0"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars(t('settings.no_ssl_https_detected_bot_protection_is_disabled_and_cannot_be_enabled_here')) ?></div>
            <?php endif; ?>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= htmlspecialchars(t('common.cancel')) ?></button><button class="btn btn-primary"><?= htmlspecialchars(t('common.save')) ?></button></div>
    </form>
</div></div></div>
<?php endif; ?>

<!-- Mein Konto -->
<div class="modal fade" id="accountModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form method="post"><input type="hidden" name="change_password" value="1">
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-circle me-2"></i><?= htmlspecialchars(t('user.my_account')) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label"><?= htmlspecialchars(t('common.username')) ?></label><input type="text" class="form-control" value="<?= htmlspecialchars($currentUser['username']) ?>" disabled></div>
            <div class="mb-3"><label class="form-label"><?= htmlspecialchars(t('user.current_password')) ?></label><input type="password" name="current_password" class="form-control" required></div>
            <div class="mb-3"><label class="form-label"><?= htmlspecialchars(t('user.new_password')) ?></label><input type="password" name="new_password" class="form-control" minlength="8" required></div>
            <div class="mb-0"><label class="form-label"><?= htmlspecialchars(t('user.repeat_new_password')) ?></label><input type="password" name="new_password_repeat" class="form-control" minlength="8" required></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= htmlspecialchars(t('common.cancel')) ?></button><button class="btn btn-primary"><?= htmlspecialchars(t('user.change_password')) ?></button></div>
    </form>
</div></div></div>

<?php if($isMainAdmin): ?>
<!-- Benutzerverwaltung -->
<div class="modal fade" id="usersModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-people me-2"></i><?= htmlspecialchars(t('user.management')) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="table-responsive mb-4"><table class="table table-sm align-middle"><thead><tr><th><?= htmlspecialchars(t('common.username')) ?></th><th><?= htmlspecialchars(t('user.role')) ?></th></tr></thead><tbody>
        <?php foreach($config['users'] as $u): ?><tr><td><?= htmlspecialchars($u['username']) ?></td><td><?= htmlspecialchars(($u['role'] ?? '') === 'admin' ? t('user.main_admin') : t('user.account')) ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
        <hr>
        <h6 class="mb-3"><?= htmlspecialchars(t('user.create')) ?></h6>
        <form method="post" id="createUserForm"><input type="hidden" name="create_user" value="1">
            <div class="row g-3">
                <div class="col-md-12"><label class="form-label"><?= htmlspecialchars(t('common.username')) ?></label><input type="text" name="new_username" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label"><?= htmlspecialchars(t('common.password')) ?></label><input type="password" name="new_user_password" class="form-control" minlength="8" required></div>
                <div class="col-md-6"><label class="form-label"><?= htmlspecialchars(t('user.repeat_password')) ?></label><input type="password" name="new_user_password_repeat" class="form-control" minlength="8" required></div>
            </div>
        </form>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= htmlspecialchars(t('common.close')) ?></button><button type="submit" form="createUserForm" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i><?= htmlspecialchars(t('user.create')) ?></button></div>
</div></div></div>
<?php endif; ?>

<!-- Neuer Job -->
<div class="modal fade" id="newJobModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
    <form method="post"><input type="hidden" name="create_job" value="1">
        <div class="modal-header"><h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i><?= htmlspecialchars(t('job.create')) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#newJobData" type="button"><?= htmlspecialchars(t('job.data')) ?></button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#newJobFiles" type="button"><?= htmlspecialchars(t('file.files')) ?></button></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="newJobData">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label"><?= htmlspecialchars(t('job.url_slug')) ?></label><input type="text" name="jobname" class="form-control" placeholder="<?= htmlspecialchars(t('job.example_slug')) ?>" required><div class="form-text"><?= htmlspecialchars(t('job.becomes_part_of_the_url')) ?></div></div>
                        <div class="col-md-6"><label class="form-label"><?= htmlspecialchars(t('common.title')) ?></label><input type="text" name="jobtitle" class="form-control" placeholder="<?= htmlspecialchars(t('job.example_title')) ?>" required></div>
                        <div class="col-md-6"><label class="form-label"><?= htmlspecialchars(t('common.start')) ?></label><input type="datetime-local" name="start" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label"><?= htmlspecialchars(t('common.end')) ?></label><input type="datetime-local" name="end" class="form-control" required></div>
                        <div class="col-12"><label class="form-label"><?= htmlspecialchars(t('job.allowed_file_types')) ?></label><div>
                            <?php foreach($fileTypeGroups as $key => $group): $id = 'new_type_' . $key; ?>
                                <input type="checkbox" class="btn-check" name="type_groups[]" value="<?= htmlspecialchars($key) ?>" id="<?= $id ?>" autocomplete="off">
                                <label class="btn btn-outline-secondary btn-sm mb-1 me-1" for="<?= $id ?>" title="<?= htmlspecialchars(implode(', ', array_map('strtoupper', $group['extensions']))) ?>"><i class="bi <?= htmlspecialchars($group['icon']) ?>"></i> <?= htmlspecialchars($group['label']) ?></label>
                            <?php endforeach; ?>
                        </div></div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input frontend-download-toggle" type="checkbox" name="frontend_download" value="1" id="new_frontend_download" data-password-target="new_download_password_wrap" checked>
                                <label class="form-check-label" for="new_frontend_download"><strong><?= htmlspecialchars(t('job.frontend_download_enable')) ?></strong></label>
                            </div>
                            <div class="form-text"><?= htmlspecialchars(t('job.when_enabled_visitors_can_access_the_download_area_after_signing_in_with_the_download_password')) ?></div>
                        </div>
                        <div class="col-12" id="new_download_password_wrap"><label class="form-label"><?= htmlspecialchars(t('job.download_password')) ?></label><input type="password" name="dl_pass" class="form-control frontend-download-password"></div>
                    </div>
                </div>
                <div class="tab-pane fade" id="newJobFiles"><div class="text-center text-muted py-5"><i class="bi bi-folder2-open display-5 d-block mb-2"></i><?= htmlspecialchars(t('job.files_are_available_after_the_job_has_been_created')) ?></div></div>
            </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= htmlspecialchars(t('common.cancel')) ?></button><button class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> <?= htmlspecialchars(t('job.create_submit')) ?></button></div>
    </form>
</div></div></div>

<?php foreach($jobs as $j):
    $jobName = $j['name'];
    $modalId = 'jobModal_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $jobName);
    $dataTabId = $modalId . '_data';
    $filesTabId = $modalId . '_files';
    $jobDir = STORAGE_DIR . $jobName . '/';
    $jobFiles = is_dir($jobDir) ? array_values(array_filter(array_diff(scandir($jobDir), ['.','..']), fn($f) => is_file($jobDir . $f))) : [];
?>
<div class="modal fade job-edit-modal" id="<?= htmlspecialchars($modalId) ?>" tabindex="-1" data-job="<?= htmlspecialchars($jobName) ?>"><div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i><?= htmlspecialchars($j['title'] ?? $jobName) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#<?= $dataTabId ?>" type="button"><i class="bi bi-sliders me-1"></i> <?= htmlspecialchars(t('job.data')) ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#<?= $filesTabId ?>" type="button"><i class="bi bi-folder2-open me-1"></i> <?= htmlspecialchars(t('file.files')) ?> <span class="badge bg-secondary ms-1"><?= count($jobFiles) ?></span></button></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="<?= $dataTabId ?>">
                <form method="post" id="form_<?= htmlspecialchars($modalId) ?>">
                    <input type="hidden" name="save_job" value="1"><input type="hidden" name="original_name" value="<?= htmlspecialchars($jobName) ?>">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label"><?= htmlspecialchars(t('job.url_slug')) ?></label><input type="text" name="jobname" class="form-control" value="<?= htmlspecialchars($jobName) ?>" required></div>
                        <div class="col-md-6"><label class="form-label"><?= htmlspecialchars(t('common.title')) ?></label><input type="text" name="jobtitle" class="form-control" value="<?= htmlspecialchars($j['title'] ?? $jobName) ?>" required></div>
                        <div class="col-md-6"><label class="form-label"><?= htmlspecialchars(t('common.start')) ?></label><input type="datetime-local" name="start" class="form-control" value="<?= htmlspecialchars($j['start'] ?? '') ?>" required></div>
                        <div class="col-md-6"><label class="form-label"><?= htmlspecialchars(t('common.end')) ?></label><input type="datetime-local" name="end" class="form-control" value="<?= htmlspecialchars($j['end'] ?? '') ?>" required></div>
                        <div class="col-12"><label class="form-label"><?= htmlspecialchars(t('job.allowed_file_types')) ?></label><div>
                            <?php foreach($fileTypeGroups as $key => $group): $id = $modalId . '_type_' . $key; $checked = in_array($key, $j['type_groups'] ?? [], true); ?>
                                <input type="checkbox" class="btn-check" name="type_groups[]" value="<?= htmlspecialchars($key) ?>" id="<?= htmlspecialchars($id) ?>" autocomplete="off" <?= $checked ? 'checked' : '' ?>>
                                <label class="btn btn-outline-secondary btn-sm mb-1 me-1" for="<?= htmlspecialchars($id) ?>" title="<?= htmlspecialchars(implode(', ', array_map('strtoupper', $group['extensions']))) ?>"><i class="bi <?= htmlspecialchars($group['icon']) ?>"></i> <?= htmlspecialchars($group['label']) ?></label>
                            <?php endforeach; ?>
                        </div></div>
                        <?php $frontendDownloadChecked = !array_key_exists('frontend_download', $j) || !empty($j['frontend_download']); $downloadWrapId = $modalId . '_download_password_wrap'; ?>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input frontend-download-toggle" type="checkbox" name="frontend_download" value="1" id="<?= htmlspecialchars($modalId . '_frontend_download') ?>" data-password-target="<?= htmlspecialchars($downloadWrapId) ?>" <?= $frontendDownloadChecked ? 'checked' : '' ?>>
                                <label class="form-check-label" for="<?= htmlspecialchars($modalId . '_frontend_download') ?>"><strong><?= htmlspecialchars(t('job.frontend_download_enable')) ?></strong></label>
                            </div>
                            <div class="form-text"><?= htmlspecialchars(t('job.when_enabled_visitors_can_access_the_download_area_after_signing_in_with_the_download_password')) ?></div>
                        </div>
                        <div class="col-12 <?= $frontendDownloadChecked ? '' : 'd-none' ?>" id="<?= htmlspecialchars($downloadWrapId) ?>"><label class="form-label"><?= htmlspecialchars(t('job.new_download_password')) ?></label><input type="password" name="dl_pass" class="form-control frontend-download-password" placeholder="<?= htmlspecialchars(t('ui.leave_empty_to_keep_the_current_password')) ?>"><div class="form-text"><?= htmlspecialchars(t('job.only_fill_this_in_if_the_download_password_should_be_changed')) ?></div></div>
                    </div>
                </form>
            </div>
            <div class="tab-pane fade" id="<?= $filesTabId ?>">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div><strong><?= htmlspecialchars(t('file.files')) ?> (<?= count($jobFiles) ?>)</strong></div>
                    <?php if($jobFiles): ?><a href="?admin_job=<?= urlencode($jobName) ?>&zip=1" class="btn btn-sm btn-success"><i class="bi bi-file-zip me-1"></i> <?= htmlspecialchars(t('download.all_zip')) ?></a><?php endif; ?>
                </div>
                <div class="list-group">
                    <?php foreach($jobFiles as $f): $path = $jobDir . $f; $sz = round(filesize($path)/1024,2); $time = date('d.m.Y H:i', filemtime($path)); $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION)); $isPreview = in_array($ext, ['jpg','jpeg','png','gif','webp','pdf'], true); ?>
                    <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="min-w-0"><i class="bi <?= adminFileIcon($f) ?> fs-4 me-2 align-middle"></i><span><?= htmlspecialchars(substr($f, 11)) ?></span><div class="text-muted small ms-4 ps-2"><?= htmlspecialchars(t('file.uploaded_at')) ?> <?= $time ?> · <?= $sz ?> KB</div></div>
                        <div class="text-nowrap">
                            <?php if($isPreview): ?><button type="button" class="btn btn-sm btn-outline-primary admin-preview-btn" data-url="?admin_job=<?= urlencode($jobName) ?>&stream=<?= urlencode($f) ?>" data-ext="<?= htmlspecialchars($ext) ?>" title="<?= htmlspecialchars(t('common.preview')) ?>"><i class="bi bi-eye"></i></button><?php endif; ?>
                            <a href="?admin_job=<?= urlencode($jobName) ?>&stream=<?= urlencode($f) ?>&dl=1" class="btn btn-sm btn-outline-success" title="<?= htmlspecialchars(t('common.download')) ?>"><i class="bi bi-download"></i></a>
                            <a href="?admin_job=<?= urlencode($jobName) ?>&delfile=<?= urlencode($f) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm(<?= json_encode(t('file.really_delete_this_file')) ?>);" title="<?= htmlspecialchars(t('common.delete')) ?>"><i class="bi bi-x-lg"></i></a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if(!$jobFiles): ?><div class="list-group-item text-center text-muted py-5"><?= htmlspecialchars(t('file.no_files_available_yet')) ?></div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= htmlspecialchars(t('common.close')) ?></button><button type="submit" form="form_<?= htmlspecialchars($modalId) ?>" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> <?= htmlspecialchars(t('common.save_changes')) ?></button></div>
</div></div></div>
<?php endforeach; ?>

<!-- Datei-Vorschau -->
<div class="modal fade" id="adminPreviewModal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-eye me-2"></i><?= htmlspecialchars(t('file.preview')) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body p-0 text-center bg-light" id="adminPreviewBody" style="min-height:200px;"></div>
</div></div></div>

<?php renderProjectInfoModal(); ?>
<script src="<?= htmlspecialchars($rootUrl) ?>/assets/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.frontend-download-toggle').forEach(function(toggle) {
    const syncDownloadPassword = function() {
        const targetId = toggle.getAttribute('data-password-target');
        const wrap = targetId ? document.getElementById(targetId) : null;
        if (!wrap) return;
        const input = wrap.querySelector('.frontend-download-password');
        wrap.classList.toggle('d-none', !toggle.checked);
        if (input) {
            const isNewJob = toggle.id === 'new_frontend_download';
            input.required = toggle.checked && isNewJob;
            if (!toggle.checked) input.value = '';
        }
    };
    toggle.addEventListener('change', syncDownloadPassword);
    syncDownloadPassword();
});
(() => {
    const previewModalEl = document.getElementById('adminPreviewModal');
    const previewBody = document.getElementById('adminPreviewBody');
    const previewModal = new bootstrap.Modal(previewModalEl);

    document.querySelectorAll('.admin-preview-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const url = btn.dataset.url;
            const ext = (btn.dataset.ext || '').toLowerCase();
            previewBody.innerHTML = '';
            if (ext === 'pdf') {
                const frame = document.createElement('iframe');
                frame.src = url;
                frame.style.width = '100%';
                frame.style.height = '70vh';
                frame.style.border = '0';
                frame.title = <?= json_encode(t('file.pdf_preview')) ?>;
                previewBody.appendChild(frame);
            } else {
                const img = document.createElement('img');
                img.src = url;
                img.alt = <?= json_encode(t('file.preview')) ?>;
                img.style.maxWidth = '100%';
                img.style.maxHeight = '70vh';
                img.style.objectFit = 'contain';
                previewBody.appendChild(img);
            }
            previewModal.show();
        });
    });

    // Nach einer Dateiaktion das passende Job-Modal direkt wieder im Dateien-Tab öffnen.
    const params = new URLSearchParams(window.location.search);
    const editJob = params.get('edit');
    const tab = params.get('tab');
    if (editJob) {
        const modal = document.querySelector('.job-edit-modal[data-job="' + CSS.escape(editJob) + '"]');
        if (modal) {
            const instance = new bootstrap.Modal(modal);
            instance.show();
            if (tab === 'files') {
                const filesBtn = modal.querySelector('[data-bs-target$="_files"]');
                if (filesBtn) bootstrap.Tab.getOrCreateInstance(filesBtn).show();
            }
        }
    }
})();
</script>
</body></html>