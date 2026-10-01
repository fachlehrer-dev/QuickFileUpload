<?php
session_start();
define('BASE_DIR', dirname(__DIR__));
define('LANG_FILE', BASE_DIR . '/config/lang.php');
define('PROJECT_FILE', BASE_DIR . '/config/project.json');
$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

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
    if ($uiLanguage === null || !is_array($language) || !array_key_exists($key, $language['strings'])) throw new RuntimeException('MISSING_TRANSLATION:' . ($uiLanguage ?? 'none') . ':' . $key);
    $text = (string)$language['strings'][$key];
    foreach ($vars as $name => $value) $text = str_replace('{' . $name . '}', (string)$value, $text);
    return $text;
}
function languageMeta(string $key): string {
    global $language, $uiLanguage;
    if ($uiLanguage === null || !is_array($language) || !array_key_exists($key, $language['_meta'])) throw new RuntimeException('MISSING_LANGUAGE_META:' . ($uiLanguage ?? 'none') . ':' . $key);
    return (string)$language['_meta'][$key];
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
      <i class="bi bi-github text-primary d-block mb-3" style="font-size:4.5rem"></i><h5><?= htmlspecialchars((string)($projectMeta['project_name'] ?? '')) ?></h5><p><?= htmlspecialchars(t('project.mit_text')) ?></p>
      <p class="mb-2"><span><?= htmlspecialchars(t('project.github_info_download')) ?>:</span> <a href="<?= htmlspecialchars((string)($projectMeta['github_url'] ?? '')) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars((string)($projectMeta['github_url'] ?? '')) ?></a></p><p><span><?= htmlspecialchars(t('project.more_information')) ?>:</span> <a href="<?= htmlspecialchars((string)($projectMeta['website_url'] ?? '')) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars((string)($projectMeta['website_url'] ?? '')) ?></a></p><hr>
      <p class="mb-1"><?= htmlspecialchars(t($developerKey, ['name'=>(string)($projectMeta['developer_name'] ?? '')])) ?></p><p class="mb-1"><?= htmlspecialchars(t('project.published_by', ['name'=>(string)($projectMeta['publisher_name'] ?? '')])) ?></p><p class="mb-0"><span><?= htmlspecialchars(t('project.contact')) ?>:</span> <a href="#" class="developer-mail" data-u="<?= htmlspecialchars($developerEmailUserEncoded) ?>" data-d="<?= htmlspecialchars($developerEmailDomainEncoded) ?>"><?= htmlspecialchars(t('project.show_email')) ?></a></p>
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

// Vor dem Einlesen der Konfiguration wird fuer technische Fehler nur Englisch geladen.
setUiLanguage('en');

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

$config = json_decode(file_get_contents(BASE_DIR . '/config/config.json'), true);
$configuredLanguage = lang_normalize_code((string)($config['language'] ?? 'en'));
$frontendLanguages = lang_available_meta();
$requestedFrontendLanguage = lang_normalize_code((string)($_GET['lang'] ?? ''));
$frontendLanguageOverride = array_key_exists($requestedFrontendLanguage, $frontendLanguages) ? $requestedFrontendLanguage : '';
setUiLanguage($frontendLanguageOverride !== '' ? $frontendLanguageOverride : $configuredLanguage);
$botProtectionEnabled = $isHttps && (!array_key_exists('bot_protection', $config) || !empty($config['bot_protection']));
$pbkdf2 = new Pbkdf2();
$altcha = new Altcha(hmacSignatureSecret: $config['altcha_secret']);

$jobname = isset($_GET['job']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['job']) : '';
if (!$jobname) die(t('job.no_job_specified'));
$route = $_GET['route'] ?? 'upload';
if (!in_array($route, ['upload','cam','download'], true)) $route = 'upload';
$rootUrl = $baseUrl ?: '';
$languageQuery = $frontendLanguageOverride !== '' ? ('?lang=' . rawurlencode($frontendLanguageOverride)) : '';
$jobUrlBase = $rootUrl . '/' . rawurlencode($jobname);
$camUrlBase = $jobUrlBase . '/cam';
$downloadUrlBase = $jobUrlBase . '/download';
$jobUrl = $jobUrlBase . $languageQuery;
$camUrl = $camUrlBase . $languageQuery;
$downloadUrl = $downloadUrlBase . $languageQuery;
$downloadActionUrl = $rootUrl . '/index.php?job=' . rawurlencode($jobname) . '&route=download' . ($frontendLanguageOverride !== '' ? ('&lang=' . rawurlencode($frontendLanguageOverride)) : '');
$logoutUrl = $downloadUrlBase . '?logout=1' . ($frontendLanguageOverride !== '' ? ('&lang=' . rawurlencode($frontendLanguageOverride)) : '');

if (isset($_GET['logout'])) { unset($_SESSION['dl_auth'][$jobname]); header("Location: " . $downloadUrl); exit; }
$jobFile = BASE_DIR . '/storage/jobs/' . $jobname . '.json';
if (!file_exists($jobFile)) die(t('job.job_does_not_exist'));

$job = json_decode(file_get_contents($jobFile), true);
$jobTitle = trim((string)($job['title'] ?? '')) ?: $jobname;
// Existing jobs created before this option was introduced keep frontend downloads enabled.
$frontendDownloadEnabled = !array_key_exists('frontend_download', $job) || !empty($job['frontend_download']);
if ($route === 'download' && !$frontendDownloadEnabled) {
    http_response_code(404);
    die(t('job.frontend_downloads_are_disabled_for_this_job'));
}
$jobDir = BASE_DIR . '/storage/' . $jobname . '/';
$now = date('Y-m-d\TH:i');
$isUploadActive = ($job['active'] && $now >= $job['start'] && $now <= $job['end']);
$allowedTypes = array_values(array_filter(array_map('trim', explode(',', strtolower($job['types'] ?? '')))));
$isPhotoAllowed = in_array('jpg', $allowedTypes, true) || in_array('jpeg', $allowedTypes, true) || in_array('png', $allowedTypes, true);
$directPhotoAvailable = $isHttps && $isPhotoAllowed;
$cameraBlockedBySsl = ($route === 'cam' && !$isHttps);
if ($cameraBlockedBySsl && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    die(json_encode(['error' => t('camera.direct_photo_capture_requires_an_https_connection') ]));
}

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
$selectedTypeGroups = [];
if (!empty($job['type_groups']) && is_array($job['type_groups'])) {
    foreach ($job['type_groups'] as $groupKey) if (isset($fileTypeGroups[$groupKey])) $selectedTypeGroups[] = $groupKey;
} else {
    foreach ($fileTypeGroups as $groupKey => $group) {
        foreach ($group['extensions'] as $ext) {
            if (in_array($ext, $allowedTypes, true)) { $selectedTypeGroups[] = $groupKey; break; }
        }
    }
}
$acceptAttr = implode(',', array_map(static fn($ext) => '.' . $ext, $allowedTypes));

$action = $route;
$msg = $error = '';

$pColor = $config['primary_color'] ?? '#0d6efd'; list($r, $g, $b) = sscanf($pColor, "#%02x%02x%02x"); $pColorRgb = "$r, $g, $b";
$dynamicCSS = <<<CSS
<style>:root { --bs-primary: {$pColor}; --bs-primary-rgb: {$pColorRgb}; --bs-link-color: var(--bs-primary); --bs-link-hover-color: color-mix(in srgb, var(--bs-primary), #000 20%); } .btn-primary { --bs-btn-bg: var(--bs-primary); --bs-btn-border-color: var(--bs-primary); --bs-btn-hover-bg: color-mix(in srgb, var(--bs-primary), #000 15%); --bs-btn-hover-border-color: color-mix(in srgb, var(--bs-primary), #000 20%); --bs-btn-active-bg: color-mix(in srgb, var(--bs-primary), #000 20%); --bs-btn-active-border-color: color-mix(in srgb, var(--bs-primary), #000 25%); } .btn-outline-primary { --bs-btn-color: var(--bs-primary); --bs-btn-border-color: var(--bs-primary); --bs-btn-hover-color: #fff; --bs-btn-hover-bg: var(--bs-primary); --bs-btn-hover-border-color: var(--bs-primary); --bs-btn-active-color: #fff; --bs-btn-active-bg: color-mix(in srgb, var(--bs-primary), #000 10%); --bs-btn-active-border-color: color-mix(in srgb, var(--bs-primary), #000 15%); } .form-check-input:checked { background-color: var(--bs-primary); border-color: var(--bs-primary); } .form-check-input:focus { border-color: color-mix(in srgb, var(--bs-primary), #fff 35%); box-shadow: 0 0 0 .25rem color-mix(in srgb, var(--bs-primary), transparent 75%); } .bg-primary { background-color: var(--bs-primary) !important; } .text-primary, .link-primary, a:not(.btn):not(.badge):not(.project-corner-btn):not(.dropdown-item) { color: var(--bs-primary) !important; } a:not(.btn):not(.badge):not(.project-corner-btn):not(.dropdown-item):hover, a:not(.btn):not(.badge):not(.project-corner-btn):not(.dropdown-item):focus { color: color-mix(in srgb, var(--bs-primary), #000 20%) !important; }</style>
CSS;

if ($action === 'cam' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cam_file'])) {
    header('Content-Type: application/json');
    if (!$isUploadActive) die(json_encode(['error' => t('upload.uploads_are_currently_closed') ]));
    if (!$directPhotoAvailable) die(json_encode(['error' => t('job.direct_photo_capture_is_only_available_over_https_and_must_be_enabled_for_this_job') ]));
    $filename = time() . '_smartphone.jpg';
    if (move_uploaded_file($_FILES['cam_file']['tmp_name'], $jobDir . $filename)) die(json_encode(['success' => true]));
    die(json_encode(['error' => t('file.error_saving_the_file') ]));
}

if (isset($_GET['stream'])) {
    if (empty($_SESSION['dl_auth'][$jobname])) die(t('system.access_denied'));
    $file = basename($_GET['stream']); $path = $jobDir . $file;
    if (file_exists($path)) {
        header('Content-Type: ' . mime_content_type($path)); header('Content-Length: ' . filesize($path));
        if(isset($_GET['dl'])) header('Content-Disposition: attachment; filename="' . $file . '"'); else header('Content-Disposition: inline; filename="' . $file . '"');
        readfile($path);
    } exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    if (!$isUploadActive) { $error = t('upload.the_upload_period_has_expired_or_is_disabled'); } 
    elseif (!empty($_FILES['file']['name'])) {
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowedTypes, true)) {
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', $_FILES['file']['name']);
            if (move_uploaded_file($_FILES['file']['tmp_name'], $jobDir . $filename)) $msg = t('upload.file_success'); else $error = t('ui.error_saving_the_file');
        } else { $error = t('file.file_type_ext_is_not_allowed_allowed_types', ['ext' => $ext, 'types' => $job['types']]); }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auth_pass'])) {
    $altchaValid = !$botProtectionEnabled;
    if ($botProtectionEnabled && !empty($_POST['altcha'])) { try { $res = $altcha->verifySolution(new VerifySolutionOptions(payload: $_POST['altcha'], algorithm: $pbkdf2)); $altchaValid = $res->verified; } catch (\Throwable $e) {} }
    if ($botProtectionEnabled && !$altchaValid) { $error = t('ui.bot_protection_failed'); } 
    elseif (password_verify($_POST['auth_pass'], $job['dl_pass'])) { $_SESSION['dl_auth'][$jobname] = true; header("Location: " . $downloadUrl); exit; } 
    else { $error = t('ui.incorrect_password'); }
}

if (!empty($_SESSION['dl_auth'][$jobname])) {
    if (isset($_GET['delfile'])) { $file = basename($_GET['delfile']); if (file_exists($jobDir . $file)) unlink($jobDir . $file); header("Location: " . $downloadUrl); exit; }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all'])) {
        if (password_verify($_POST['confirm_pass'], $job['dl_pass'])) { array_map('unlink', glob("$jobDir/*.*")); $msg = t('file.all_files_deleted'); } else { $error = t('job.incorrect_download_password'); }
    }
    if (isset($_GET['zip'])) {
        $zipFile = sys_get_temp_dir() . '/' . $jobname . '.zip'; $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach (glob("$jobDir/*.*") as $f) { $zip->addFile($f, basename($f)); }
            $zip->close(); header('Content-Type: application/zip'); header('Content-Length: ' . filesize($zipFile)); header('Content-Disposition: attachment; filename="'.$jobname.'.zip"'); readfile($zipFile); unlink($zipFile); exit;
        }
    }
}
function getIcon($file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $map = ['pdf'=>'bi-file-pdf text-danger', 'jpg'=>'bi-file-image text-success', 'jpeg'=>'bi-file-image text-success', 'png'=>'bi-file-image text-success', 'gif'=>'bi-file-image text-success', 'webp'=>'bi-file-image text-success', 'heic'=>'bi-file-image text-success', 'heif'=>'bi-file-image text-success', 'bmp'=>'bi-file-image text-success', 'tif'=>'bi-file-image text-success', 'tiff'=>'bi-file-image text-success', 'zip'=>'bi-file-zip text-warning', 'docx'=>'bi-file-word text-primary', 'xlsx'=>'bi-file-excel text-success', 'pptx'=>'bi-file-slides text-danger', 'txt'=>'bi-file-text text-secondary', 'csv'=>'bi-file-text text-secondary', 'odt'=>'bi-file-earmark-text text-primary', 'ods'=>'bi-file-earmark-spreadsheet text-success', 'odp'=>'bi-file-earmark-slides text-danger'];
    return $map[$ext] ?? 'bi-file-earmark text-secondary';
}
?>
<!DOCTYPE html><html lang="<?= htmlspecialchars(languageMeta('html')) ?>"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($config['title'] ?? 'Portal') ?> - <?= htmlspecialchars($jobTitle) ?></title>
<?= faviconLinkTags($rootUrl) ?>
<link href="<?= htmlspecialchars($rootUrl) ?>/assets/css/bootstrap.min.css" rel="stylesheet"><link href="<?= htmlspecialchars($rootUrl) ?>/assets/css/user.css" rel="stylesheet"><link href="<?= htmlspecialchars($rootUrl) ?>/assets/css/bootstrap-icons.css" rel="stylesheet"><link href="<?= htmlspecialchars($rootUrl) ?>/assets/css/flag-icons.min.css" rel="stylesheet">
<?= $dynamicCSS ?>
<style>
.frontend-language-toggle{width:44px;height:38px;padding:0;overflow:hidden}
.frontend-language-stack{position:relative;display:block;width:31px;height:20px}
.frontend-language-stack .fi{position:absolute;top:3px;width:20px;height:14px;box-shadow:0 0 0 1px rgba(0,0,0,.12);left:calc(var(--flag-index) * 5px)}
.frontend-language-menu .fi{width:1.35em;height:1em;box-shadow:0 0 0 1px rgba(0,0,0,.1)}
.frontend-language-menu .dropdown-item{color:var(--bs-body-color)!important}
.frontend-language-menu .dropdown-item:hover,.frontend-language-menu .dropdown-item:focus{color:var(--bs-primary)!important;background:color-mix(in srgb,var(--bs-primary),transparent 90%)}
.frontend-language-menu .dropdown-item.active,.frontend-language-menu .dropdown-item:active{background:var(--bs-primary)!important;color:#fff!important}
.frontend-language-menu .dropdown-item.active:hover,.frontend-language-menu .dropdown-item.active:focus{background:color-mix(in srgb,var(--bs-primary),#000 10%)!important;color:#fff!important}
</style>
<?php if ($botProtectionEnabled && empty($_SESSION['dl_auth'][$jobname]) && $action !== 'cam'): ?>
<script src="<?= htmlspecialchars($rootUrl) ?>/assets/js/altcha-v3.2.2.min.js" type="module"></script>
    <?php if (languageMeta('altcha_script') !== ''): ?><script src="<?= htmlspecialchars($rootUrl . '/' . languageMeta('altcha_script')) ?>" type="module"></script><?php endif; ?>
<?php endif; ?>
<script src="<?= htmlspecialchars($rootUrl) ?>/assets/js/qrcode.min.js"></script>
<script>
(function(){
    try {
        var params = new URLSearchParams(window.location.search);
        var fromUrl = params.get('lang');
        var stored = sessionStorage.getItem('frontendLanguage');
        var available = <?= json_encode(array_keys($frontendLanguages), JSON_UNESCAPED_SLASHES) ?>;
        if (fromUrl && available.indexOf(fromUrl) !== -1) {
            sessionStorage.setItem('frontendLanguage', fromUrl);
            return;
        }
        if (stored && available.indexOf(stored) !== -1) {
            params.set('lang', stored);
            window.location.replace(window.location.pathname + '?' + params.toString() + window.location.hash);
        }
    } catch (e) {}
})();
</script>
</head><body class="bg-light d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand bg-white shadow-sm mb-4">
    <div class="container" style="max-width: 800px;">
        <span class="navbar-brand">
            <?php if(!empty($config['logo']) && file_exists(__DIR__ . '/' . $config['logo'])): ?><img src="<?= htmlspecialchars($rootUrl . '/' . ltrim($config['logo'], '/')) ?>" alt="<?= htmlspecialchars(t('common.logo')) ?>" style="max-height:40px; margin-right:15px;"><?php endif; ?>
            <strong><?= htmlspecialchars($config['title'] ?? 'Upload Portal') ?></strong>
        </span>
        <div class="ms-auto d-flex align-items-center gap-2">
            <?php if (count($frontendLanguages) > 1): ?>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center frontend-language-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= htmlspecialchars(t('common.language')) ?>" title="<?= htmlspecialchars(t('common.language')) ?>">
                    <span class="frontend-language-stack" aria-hidden="true">
                        <?php $flagIndex = 0; foreach ($frontendLanguages as $langCode => $langMeta): if ($flagIndex >= 3) break; ?>
                            <span class="fi fi-<?= htmlspecialchars($langMeta['flag']) ?>" style="--flag-index:<?= $flagIndex ?>"></span>
                        <?php $flagIndex++; endforeach; ?>
                    </span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm frontend-language-menu">
                    <?php foreach ($frontendLanguages as $langCode => $langMeta):
                        $langBaseUrl = $action === 'cam' ? $camUrlBase : ($action === 'download' ? $downloadUrlBase : $jobUrlBase);
                        $langHref = $langBaseUrl . '?lang=' . rawurlencode($langCode);
                        $isActiveLanguage = ($langCode === $uiLanguage);
                    ?>
                    <li><a class="dropdown-item d-flex align-items-center gap-2<?= $isActiveLanguage ? ' active' : '' ?>" href="<?= htmlspecialchars($langHref) ?>" data-frontend-lang="<?= htmlspecialchars($langCode) ?>"<?= $isActiveLanguage ? ' aria-current="true"' : '' ?>><span class="fi fi-<?= htmlspecialchars($langMeta['flag']) ?>"></span><span><?= htmlspecialchars($langMeta['name']) ?></span></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            <?php if ($action === 'cam'): ?>
                <a id="cam-exit" href="<?= htmlspecialchars($jobUrl) ?>" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center" style="width:38px;height:38px" aria-label="<?= htmlspecialchars(t('download.back_to_upload')) ?>" title="<?= htmlspecialchars(t('download.back_to_upload')) ?>"><i class="bi bi-x-lg"></i></a>
            <?php elseif ($action === 'upload' && $frontendDownloadEnabled): ?>
                <?php if (empty($_SESSION['dl_auth'][$jobname])): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center" style="width:38px;height:38px" data-bs-toggle="modal" data-bs-target="#loginModal" title="<?= htmlspecialchars(t('download.go_to')) ?>" aria-label="<?= htmlspecialchars(t('download.go_to')) ?>"><i class="bi bi-download"></i></button>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($downloadUrl) ?>" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center" style="width:38px;height:38px" title="<?= htmlspecialchars(t('download.go_to')) ?>" aria-label="<?= htmlspecialchars(t('download.go_to')) ?>"><i class="bi bi-download"></i></a>
                <?php endif; ?>
            <?php elseif ($action === 'download' && !empty($_SESSION['dl_auth'][$jobname])): ?>
                <a href="<?= htmlspecialchars($logoutUrl) ?>" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center" style="width:38px;height:38px" title="<?= htmlspecialchars(t('auth.sign_out')) ?>" aria-label="<?= htmlspecialchars(t('auth.sign_out')) ?>"><i class="bi bi-box-arrow-right"></i></a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container flex-grow-1" style="max-width: 800px;">
    <?php if ($action === 'cam'): ?>
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-camera"></i> <?= htmlspecialchars(t('camera.area_for')) ?> <?= htmlspecialchars($jobTitle) ?></h5>
            </div>
            <?php if ($cameraBlockedBySsl): ?>
                <div class="card-body p-4">
                    <div class="alert alert-warning mb-0 text-start">
                        <h5 class="alert-heading mb-3"><i class="bi bi-camera-video-off me-2"></i><?= htmlspecialchars(t('camera.unavailable_title')) ?></h5>
                        <p class="mb-2"><?= htmlspecialchars(t('camera.unavailable_intro')) ?></p>
                        <ul class="mb-3">
                            <li><?= htmlspecialchars(t('camera.access_denied')) ?></li>
                            <li><?= htmlspecialchars(t('camera.no_camera')) ?></li>
                            <li><?= htmlspecialchars(t('camera.https_required')) ?></li>
                        </ul>
                        <a href="<?= htmlspecialchars($jobUrl) ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i><?= htmlspecialchars(t('download.back_to_upload')) ?></a>
                    </div>
                </div>
            <?php else: ?>
            <div id="cam-stage" class="card-body text-center bg-black p-0 position-relative" style="overflow: hidden;">
                <video id="cam-video" autoplay playsinline style="width:100%; max-height: 60vh; background:#000; object-fit: cover;"></video>
                <img id="cam-preview" alt="<?= htmlspecialchars(t('camera.preview_alt')) ?>" style="display:none; width:100%; max-height:60vh; object-fit:contain; background:#000;">
                <canvas id="cam-canvas" style="display:none;"></canvas>
            </div>
            <div class="card-footer text-center pb-4 pt-4">
                <div id="cam-msg" class="mb-3"></div>
                <div id="cam-live-actions">
                    <button id="cam-switch" type="button" class="btn btn-outline-secondary btn-lg me-3" title="<?= htmlspecialchars(t('camera.switch')) ?>"><i class="bi bi-arrow-repeat"></i></button>
                    <button id="cam-capture" type="button" class="btn btn-primary btn-lg rounded-circle shadow" style="width:70px; height:70px;" title="<?= htmlspecialchars(t('camera.take_photo')) ?>"><i class="bi bi-camera fs-3"></i></button>
                </div>
                <div id="cam-preview-actions" style="display:none;">
                    <div class="fw-semibold mb-3"><?= htmlspecialchars(t('camera.confirm_title')) ?></div>
                    <button id="cam-retake" type="button" class="btn btn-outline-secondary btn-lg me-2"><i class="bi bi-arrow-counterclockwise me-1"></i> <?= htmlspecialchars(t('camera.retake')) ?></button>
                    <button id="cam-upload" type="button" class="btn btn-success btn-lg"><i class="bi bi-cloud-arrow-up me-1"></i> <?= htmlspecialchars(t('camera.upload')) ?></button>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php if (!$cameraBlockedBySsl): ?>
        <script>
        let currentFacingMode = 'environment';
        let stream = null;
        let pendingPhotoBlob = null;
        let previewUrl = null;

        const video = document.getElementById('cam-video');
        const preview = document.getElementById('cam-preview');
        const canvas = document.getElementById('cam-canvas');
        const stage = document.getElementById('cam-stage');
        const msgBox = document.getElementById('cam-msg');
        const liveActions = document.getElementById('cam-live-actions');
        const previewActions = document.getElementById('cam-preview-actions');
        const switchBtn = document.getElementById('cam-switch');
        const captureBtn = document.getElementById('cam-capture');
        const retakeBtn = document.getElementById('cam-retake');
        const uploadBtn = document.getElementById('cam-upload');

        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
            video.srcObject = null;
        }

        function clearPreview() {
            pendingPhotoBlob = null;
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = null;
            }
            preview.removeAttribute('src');
            preview.style.display = 'none';
        }

        async function startCamera() {
            stopCamera();
            clearPreview();
            video.style.display = 'block';
            stage.style.display = '';
            liveActions.style.display = '';
            previewActions.style.display = 'none';
            msgBox.innerHTML = '';
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: currentFacingMode }
                });
                video.srcObject = stream;
            } catch (err) {
                msgBox.innerHTML =
                    "<div class='alert alert-warning text-start fs-6'>" +
                    "<div class='fw-semibold mb-2'><i class='bi bi-camera-video-off me-2'></i>" + <?= json_encode(t('camera.unavailable_title')) ?> + "</div>" +
                    "<div class='mb-2'>" + <?= json_encode(t('camera.unavailable_intro')) ?> + "</div>" +
                    "<ul class='mb-3'><li>" + <?= json_encode(t('camera.access_denied')) ?> + "</li><li>" + <?= json_encode(t('camera.no_camera')) ?> + "</li></ul>" +
                    "<a class='btn btn-outline-secondary' href='" + <?= json_encode($jobUrl) ?> + "'><i class='bi bi-arrow-left me-1'></i>" + <?= json_encode(t('download.back_to_upload')) ?> + "</a></div>";
                liveActions.style.display = 'none';
                stage.style.display = 'none';
            }
        }

        startCamera();

        switchBtn.addEventListener('click', () => {
            currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
            startCamera();
        });

        captureBtn.addEventListener('click', () => {
            if (!video.videoWidth || !video.videoHeight) {
                msgBox.innerHTML = "<div class='alert alert-warning p-2 fs-6'>" + <?= json_encode(t('camera.the_camera_is_not_ready_yet')) ?> + "</div>";
                return;
            }

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);

            canvas.toBlob((blob) => {
                if (!blob) {
                    msgBox.innerHTML = "<div class='alert alert-danger p-2 fs-6'>" + <?= json_encode(t('camera.the_photo_could_not_be_created')) ?> + "</div>";
                    return;
                }

                pendingPhotoBlob = blob;
                previewUrl = URL.createObjectURL(blob);
                preview.src = previewUrl;
                preview.style.display = 'block';
                video.style.display = 'none';
                liveActions.style.display = 'none';
                previewActions.style.display = '';
                msgBox.innerHTML = '';
                stopCamera();
            }, 'image/jpeg', 0.85);
        });

        retakeBtn.addEventListener('click', () => {
            startCamera();
        });

        uploadBtn.addEventListener('click', () => {
            if (!pendingPhotoBlob) return;

            uploadBtn.disabled = true;
            retakeBtn.disabled = true;
            uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + <?= json_encode(t('file.uploading')) ?>;

            const formData = new FormData();
            formData.append('cam_file', pendingPhotoBlob, 'foto.jpg');

            fetch(<?= json_encode($camUrl) ?>, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        clearPreview();
                        stopCamera();
                        stage.style.display = 'none';
                        liveActions.style.display = 'none';
                        previewActions.style.display = 'none';
                        msgBox.innerHTML = "<div class='alert alert-success mb-0'><i class='bi bi-check-circle-fill me-2'></i>" + <?= json_encode(t('upload.success')) ?> + "</div>";
                    } else {
                        uploadBtn.disabled = false;
                        retakeBtn.disabled = false;
                        uploadBtn.innerHTML = '<i class="bi bi-cloud-arrow-up me-1"></i> <?= htmlspecialchars(t('camera.upload')) ?>';
                        msgBox.innerHTML = "<div class='alert alert-danger p-2 fs-6'>" + (data.error || <?= json_encode(t('upload.upload_failed')) ?>) + "</div>";
                    }
                })
                .catch(() => {
                    uploadBtn.disabled = false;
                    retakeBtn.disabled = false;
                    uploadBtn.innerHTML = '<i class="bi bi-cloud-arrow-up me-1"></i> <?= htmlspecialchars(t('camera.upload')) ?>';
                    msgBox.innerHTML = "<div class='alert alert-danger p-2 fs-6'>" + <?= json_encode(t('ui.network_error')) ?> + "</div>";
                });
        });

        window.addEventListener('pagehide', () => {
            stopCamera();
            clearPreview();
        });
        </script>
        <?php endif; ?>
    <?php else: ?>
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <?php if ($action === 'download'): ?>
                        <i class="bi bi-folder2-open"></i> <?= htmlspecialchars(t('download.area_for')) ?> <?= htmlspecialchars($jobTitle) ?>
                    <?php else: ?>
                        <i class="bi bi-cloud-arrow-up"></i> <?= htmlspecialchars(t('upload.area_for')) ?> <?= htmlspecialchars($jobTitle) ?>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if($error && !isset($_POST['auth_pass'])) echo "<div class='alert alert-danger'>$error</div>"; ?>
                <?php if($msg) echo "<div class='alert alert-success'>$msg</div>"; ?>
                <?php if ($action === 'upload'): ?>
                    <?php if ($isUploadActive): ?>
                        <div class="text-center p-4 mb-3 border rounded bg-white">
                            <i class="bi bi-cloud-arrow-up display-1 text-primary"></i>
                            <div class="mt-3 mb-3">
                                <div class="small text-muted mb-2"><?= htmlspecialchars(t('job.allowed_file_types')) ?></div>
                                <div>
                                    <?php foreach($selectedTypeGroups as $groupKey): $group = $fileTypeGroups[$groupKey]; ?>
                                        <span class="filetype-chip" title="<?= htmlspecialchars(implode(', ', array_map('strtoupper', $group['extensions']))) ?>"><i class="bi <?= htmlspecialchars($group['icon']) ?>"></i><?= htmlspecialchars($group['label']) ?></span>
                                    <?php endforeach; ?>
                                    <?php if(empty($selectedTypeGroups)): ?><span class="text-muted small"><?= htmlspecialchars($job['types'] ?? '') ?></span><?php endif; ?>
                                </div>
                            </div>
                            <form id="uploadForm" method="post" enctype="multipart/form-data" class="mt-3 mb-4">
                                <input type="hidden" name="upload" value="1">
                                <input id="uploadFileInput" type="file" name="file" accept="<?= htmlspecialchars($acceptAttr) ?>" required hidden>
                                <div id="uploadDropzone" class="upload-dropzone text-center" role="button" tabindex="0" aria-controls="uploadFileInput">
                                    <i class="bi bi-cloud-arrow-up dropzone-icon text-primary"></i>
                                    <div class="fw-semibold mt-2"><?= htmlspecialchars(t('upload.drop_here')) ?></div>
                                    <div class="small text-muted mt-1"><?= htmlspecialchars(t('upload.click_to_select')) ?></div>
                                    <div id="uploadFilename" class="small fw-semibold mt-3 d-none"></div>
                                </div>
                                <button id="uploadSubmitBtn" type="submit" class="btn btn-primary w-100 mt-3 d-none" disabled>
                                    <i class="bi bi-cloud-arrow-up me-1"></i> Datei hochladen
                                </button>
                            </form>
                            <script>
                            (() => {
                                const zone = document.getElementById('uploadDropzone');
                                const input = document.getElementById('uploadFileInput');
                                const filename = document.getElementById('uploadFilename');
                                const submit = document.getElementById('uploadSubmitBtn');
                                if (!zone || !input || !filename || !submit) return;

                                const updateSelection = () => {
                                    const file = input.files && input.files[0] ? input.files[0] : null;
                                    if (file) {
                                        filename.textContent = file.name;
                                        filename.classList.remove('d-none');
                                        submit.classList.remove('d-none');
                                        submit.disabled = false;
                                    } else {
                                        filename.textContent = '';
                                        filename.classList.add('d-none');
                                        submit.classList.add('d-none');
                                        submit.disabled = true;
                                    }
                                };

                                const openPicker = () => input.click();
                                zone.addEventListener('click', openPicker);
                                zone.addEventListener('keydown', (e) => {
                                    if (e.key === 'Enter' || e.key === ' ') {
                                        e.preventDefault();
                                        openPicker();
                                    }
                                });
                                input.addEventListener('change', updateSelection);

                                ['dragenter', 'dragover'].forEach(type => zone.addEventListener(type, (e) => {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    zone.classList.add('is-dragover');
                                }));
                                ['dragleave', 'drop'].forEach(type => zone.addEventListener(type, (e) => {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    zone.classList.remove('is-dragover');
                                }));
                                zone.addEventListener('drop', (e) => {
                                    if (!e.dataTransfer || !e.dataTransfer.files || !e.dataTransfer.files.length) return;
                                    const dt = new DataTransfer();
                                    dt.items.add(e.dataTransfer.files[0]);
                                    input.files = dt.files;
                                    updateSelection();
                                });
                            })();
                            </script>
                            <?php if($directPhotoAvailable): ?>
                                <div id="camera-entry" style="display:none;">
                                    <hr class="my-4">
                                    <p class="text-muted small"><?= htmlspecialchars(t('camera.would_you_like_to_take_a_photo_directly_with_your_smartphone')) ?></p>
                                    <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#qrCamModal"><i class="bi bi-phone"></i> <?= htmlspecialchars(t('camera.use_smartphone')) ?></button>
                                </div>
                                <script>
                                (() => {
                                    const entry = document.getElementById('camera-entry');
                                    if (!entry || !navigator.mediaDevices || typeof navigator.mediaDevices.enumerateDevices !== 'function') return;

                                    const updateCameraEntry = async () => {
                                        try {
                                            const devices = await navigator.mediaDevices.enumerateDevices();
                                            const hasCamera = devices.some(device => device.kind === 'videoinput');
                                            entry.style.display = hasCamera ? '' : 'none';
                                        } catch (e) {
                                            entry.style.display = 'none';
                                        }
                                    };

                                    updateCameraEntry();
                                    if (typeof navigator.mediaDevices.addEventListener === 'function') {
                                        navigator.mediaDevices.addEventListener('devicechange', updateCameraEntry);
                                    }
                                })();
                                </script>
                            <?php endif; ?>
                        </div>
                    <?php else: ?><div class="alert alert-warning text-center"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars(t('upload.uploads_are_closed')) ?></div><?php endif; ?>
                <?php elseif ($action === 'download'): ?>
                    <?php if (empty($_SESSION['dl_auth'][$jobname])): ?>
                        <div class="text-center p-5"><i class="bi bi-shield-lock display-1 text-secondary"></i><h4 class="mt-3"><?= htmlspecialchars(t('download.protected_area')) ?></h4><p class="text-muted"><?= htmlspecialchars(t('upload.please_sign_in_to_view_the_files_in_this_upload_area')) ?></p><div class="d-flex flex-wrap justify-content-center gap-2 mt-2"><a href="<?= htmlspecialchars($jobUrl) ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> <?= htmlspecialchars(t('download.back_to_upload')) ?></a><button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal"><i class="bi bi-box-arrow-in-right"></i> <?= htmlspecialchars(t('download.open_login')) ?></button></div></div>
                    <?php else: ?>
                        <?php $files = array_diff(scandir($jobDir), ['.', '..']); ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><?= htmlspecialchars(t('file.files')) ?> (<?= count($files) ?>)</h5>
                            <div><?php if(count($files)>0): ?><a href="<?= htmlspecialchars($downloadActionUrl . '&zip=1') ?>" class="btn btn-sm btn-success"><i class="bi bi-file-zip"></i> <?= htmlspecialchars(t('download.all_zip')) ?></a> <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAllModal"><i class="bi bi-trash"></i> <?= htmlspecialchars(t('file.delete_all')) ?></button><?php endif; ?></div>
                        </div>
                        <ul class="list-group">
                            <?php foreach($files as $f): $sz = round(filesize($jobDir.$f) / 1024, 2); $time = date('d.m.Y H:i', filemtime($jobDir.$f)); $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION)); $isMedia = in_array($ext, ['jpg','jpeg','png','pdf']); ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div><i class="bi <?= getIcon($f) ?> fs-4 me-2 align-middle"></i><span><?= htmlspecialchars(substr($f, 11)) ?></span><div class="text-muted" style="font-size: 0.8em; margin-left:35px;"><?= htmlspecialchars(t('file.uploaded_at')) ?> <?= $time ?> | <?= htmlspecialchars(t('file.size')) ?> <?= $sz ?> KB</div></div>
                                <div>
                                    <?php if($isMedia): ?><button class="btn btn-sm btn-outline-primary" onclick="openPreview('<?= htmlspecialchars($downloadActionUrl . '&stream=' . rawurlencode($f)) ?>', '<?= $ext ?>')"><i class="bi bi-eye"></i></button><?php endif; ?>
                                    <a href="<?= htmlspecialchars($downloadActionUrl . '&stream=' . rawurlencode($f) . '&dl=1') ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-download"></i></a>
                                    <a href="<?= htmlspecialchars($downloadActionUrl . '&delfile=' . rawurlencode($f)) ?>" onclick="return confirm(<?= json_encode(t('file.delete_confirm')) ?>);" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></a>
                                </div>
                            </li>
                            <?php endforeach; ?>
                            <?php if(count($files) === 0): ?><li class="list-group-item text-center text-muted p-4"><?= htmlspecialchars(t('file.no_files_available_yet')) ?></li><?php endif; ?>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
</main>
<footer class="text-center mt-auto py-3 text-muted"><small>
    <?php if(!empty($config['impressum'])): ?><a href="<?= htmlspecialchars($config['impressum']) ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-muted"><?= htmlspecialchars(t('common.legal_notice')) ?></a><?php endif; ?>
    <?php if(!empty($config['impressum']) && !empty($config['datenschutz'])): ?> &middot; <?php endif; ?>
    <?php if(!empty($config['datenschutz'])): ?><a href="<?= htmlspecialchars($config['datenschutz']) ?>" target="_blank" rel="noopener noreferrer" class="text-decoration-none text-muted"><?= htmlspecialchars(t('common.privacy_policy')) ?></a><?php endif; ?>
</small></footer>

<?php if($directPhotoAvailable && $action !== 'cam'): ?>
<div class="modal fade" id="qrCamModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-qr-code-scan"></i> <?= htmlspecialchars(t('common.scan_code')) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body text-center">
        <p class="small text-muted"><?= htmlspecialchars(t('camera.scan_the_qr_code_or_open_the_address_directly_on_your_smartphone')) ?></p>
        <a id="qrcodeLink" href="#" class="d-inline-block text-decoration-none" title="<?= htmlspecialchars(t('upload.open_page')) ?>">
            <div id="qrcodeBox" class="d-flex justify-content-center p-3 bg-white border rounded"></div>
        </a>
        <div class="mt-3 small text-break">
            <a id="qrcodeUrl" href="#" class="link-primary" target="_blank" rel="noopener"></a>
        </div>
    </div>
</div></div></div>
<script>
    document.getElementById('qrCamModal').addEventListener('show.bs.modal', function () {
        const url = window.location.origin + <?= json_encode($camUrl) ?>;
        const box = document.getElementById("qrcodeBox");
        const qrLink = document.getElementById("qrcodeLink");
        const qrUrl = document.getElementById("qrcodeUrl");
        box.innerHTML = "";
        qrLink.href = url;
        qrLink.target = "_blank";
        qrLink.rel = "noopener";
        qrUrl.href = url;
        qrUrl.textContent = url;
        new QRCode(box, { text: url, width: 200, height: 200 });
    });
</script>
<?php endif; ?>

<?php if ($frontendDownloadEnabled && empty($_SESSION['dl_auth'][$jobname])): ?>
<div class="modal fade" id="loginModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form method="post" action="<?= htmlspecialchars($downloadUrl) ?>">
        <div class="modal-header bg-primary text-white"><h5 class="modal-title"><i class="bi bi-lock"></i> <?= htmlspecialchars(t('download.area_for')) ?> <?= htmlspecialchars($jobTitle) ?></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
        <?php if($error && isset($_POST['auth_pass'])): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <input type="password" name="auth_pass" class="form-control mb-3" placeholder="<?= htmlspecialchars(t('job.download_password')) ?>" required>
        
        <?php if ($botProtectionEnabled): ?>
        <altcha-widget 
            challenge="<?= htmlspecialchars($rootUrl) ?>/altcha.php"
            language="<?= htmlspecialchars(languageMeta('altcha_language')) ?>">
        </altcha-widget>
        <?php endif; ?>
        
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-success w-100 mt-3"><i class="bi bi-unlock"></i> <?= htmlspecialchars(t('download.unlock')) ?></button></div>
    </form>
</div></div></div>
<?php endif; ?>

<div class="modal fade" id="previewModal" tabindex="-1"><div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title"><?= htmlspecialchars(t('common.preview')) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body text-center p-0" style="height: 70vh;">
        <img id="prevImg" src="" style="max-width:100%; max-height:100%; display:none; margin:auto;">
        <iframe id="prevPdf" src="" style="width:100%; height:100%; display:none; border:none;"></iframe>
    </div>
    <div class="modal-footer"><a id="prevDlBtn" href="#" class="btn btn-success"><i class="bi bi-download"></i> <?= htmlspecialchars(t('common.download')) ?></a></div>
</div></div></div>

<div class="modal fade" id="deleteAllModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="post" action="<?= htmlspecialchars($downloadUrl) ?>">
        <div class="modal-header bg-danger text-white"><h5 class="modal-title"><?= htmlspecialchars(t('file.delete_all_confirm')) ?></h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><input type="hidden" name="delete_all" value="1"><input type="password" name="confirm_pass" class="form-control" placeholder="<?= htmlspecialchars(t('job.download_password')) ?>" required></div>
        <div class="modal-footer"><button type="submit" class="btn btn-danger"><?= htmlspecialchars(t('common.delete')) ?></button></div>
    </form>
</div></div></div>

<?php renderProjectInfoModal(); ?>
<script src="<?= htmlspecialchars($rootUrl) ?>/assets/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('[data-frontend-lang]').forEach(function(link){
    link.addEventListener('click', function(){
        try { sessionStorage.setItem('frontendLanguage', link.getAttribute('data-frontend-lang') || ''); } catch (e) {}
    });
});
</script>
<script>
function openPreview(url, ext) {
    const isImg = ['jpg','jpeg','png'].includes(ext);
    document.getElementById('prevImg').style.display = isImg ? 'block' : 'none'; document.getElementById('prevImg').src = isImg ? url : '';
    document.getElementById('prevPdf').style.display = !isImg ? 'block' : 'none'; document.getElementById('prevPdf').src = !isImg ? url : '';
    document.getElementById('prevDlBtn').href = url + '&dl=1';
    new bootstrap.Modal(document.getElementById('previewModal')).show();
}
<?php if($error && isset($_POST['auth_pass'])): ?>
document.addEventListener("DOMContentLoaded", function() { new bootstrap.Modal(document.getElementById('loginModal')).show(); });
<?php endif; ?>
</script>
</body></html>