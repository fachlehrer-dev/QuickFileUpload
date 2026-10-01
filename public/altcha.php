<?php
// Fehler unterdrücken, damit kein HTML das JSON zerstört
ini_set('display_errors', 0);
error_reporting(0);

define('BASE_DIR', dirname(__DIR__));
if (!file_exists(BASE_DIR . '/vendor/autoload.php')) die(json_encode(['error' => 'Composer missing']));
require_once BASE_DIR . '/vendor/autoload.php';

use AltchaOrg\Altcha\Altcha;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\Algorithm\Pbkdf2;

$configFile = BASE_DIR . '/config/config.json';
if (!file_exists($configFile)) die(json_encode(['error' => 'Config missing']));

function isHttpsRequest(): bool {
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') return true;
    if ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443) return true;
    $forwardedProto = strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0] ?? ''));
    return $forwardedProto === 'https';
}

$config = json_decode(file_get_contents($configFile), true);
$botProtectionEnabled = isHttpsRequest() && (!array_key_exists('bot_protection', $config) || !empty($config['bot_protection']));
if (!$botProtectionEnabled) die(json_encode(['error' => 'Bot protection disabled']));
if (empty($config['altcha_secret'])) die(json_encode(['error' => 'Secret missing']));

$pbkdf2 = new Pbkdf2();
$altcha = new Altcha(hmacSignatureSecret: $config['altcha_secret']);

// Output-Buffer radikal leeren (entfernt unsichtbare Zeichen/BOMs)
while (ob_get_level()) ob_end_clean();

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    $challenge = $altcha->createChallenge(new CreateChallengeOptions(
        algorithm: $pbkdf2,
        cost: 5000,
        counter: random_int(5000, 10000),
        expiresAt: time() + 300
    ));
    echo json_encode($challenge->toArray());
} catch (\Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
exit;