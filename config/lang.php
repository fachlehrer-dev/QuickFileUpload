<?php
declare(strict_types=1);

const LANG_DIR = __DIR__ . '/lang';
const LANG_META_FILE = LANG_DIR . '/languages.json';

function lang_codes(): array {
    if (!is_dir(LANG_DIR)) return [];
    $codes = [];
    foreach (glob(LANG_DIR . '/*.php') ?: [] as $file) {
        $code = basename($file, '.php');
        if (preg_match('/^[a-z]{2}(?:-[A-Z]{2})?$/', $code)) $codes[] = $code;
    }
    sort($codes, SORT_STRING);
    return array_values(array_unique($codes));
}

function lang_validate(array $language, string $code): array {
    if (!isset($language['_meta']) || !is_array($language['_meta'])) {
        throw new RuntimeException('INVALID_LANGUAGE_META:' . $code);
    }
    foreach (['name','html','flag','altcha_language','altcha_script'] as $key) {
        if (!array_key_exists($key, $language['_meta']) || !is_string($language['_meta'][$key])) {
            throw new RuntimeException('INVALID_LANGUAGE_META:' . $code . ':' . $key);
        }
    }
    if (!isset($language['strings']) || !is_array($language['strings'])) {
        throw new RuntimeException('INVALID_LANGUAGE_STRINGS:' . $code);
    }
    foreach ($language['strings'] as $key => $value) {
        if (!is_string($key) || $key === '' || !preg_match('/^[a-z0-9]+(?:[._][a-z0-9]+)*$/', $key) || !is_string($value)) {
            throw new RuntimeException('INVALID_LANGUAGE_STRING:' . $code . ':' . (string)$key);
        }
    }
    return $language;
}

function lang_available_meta(): array {
    if (!is_file(LANG_META_FILE)) throw new RuntimeException('LANGUAGE_META_FILE_MISSING');
    $decoded = json_decode((string)file_get_contents(LANG_META_FILE), true);
    if (!is_array($decoded) || !$decoded) throw new RuntimeException('INVALID_LANGUAGE_META_FILE');
    $result = [];
    foreach ($decoded as $code => $meta) {
        if (!is_string($code) || !preg_match('/^[a-z]{2}(?:-[A-Z]{2})?$/', $code) || !is_array($meta)) throw new RuntimeException('INVALID_LANGUAGE_META_FILE');
        foreach (['name','html','flag'] as $key) {
            if (!isset($meta[$key]) || !is_string($meta[$key]) || trim($meta[$key]) === '') throw new RuntimeException('INVALID_LANGUAGE_META_FILE:' . $code . ':' . $key);
        }
        if (!preg_match('/^[a-z]{2}$/', $meta['flag'])) throw new RuntimeException('INVALID_LANGUAGE_FLAG:' . $code);
        $result[$code] = $meta;
    }
    return $result;
}

function lang_normalize_code(string $code): string {
    $code = trim(str_replace('_', '-', $code));
    if ($code === '') return '';
    $parts = explode('-', $code, 2);
    $normalized = strtolower($parts[0]);
    if (isset($parts[1]) && $parts[1] !== '') $normalized .= '-' . strtoupper($parts[1]);
    return $normalized;
}

function lang_load(string $code): array {
    $code = lang_normalize_code($code);
    if (!preg_match('/^[a-z]{2}(?:-[A-Z]{2})?$/', $code)) {
        throw new RuntimeException('LANGUAGE_NOT_AVAILABLE:' . $code);
    }
    $file = LANG_DIR . '/' . $code . '.php';
    if (!is_file($file)) throw new RuntimeException('LANGUAGE_NOT_AVAILABLE:' . $code);
    $language = require $file;
    if (!is_array($language)) throw new RuntimeException('INVALID_LANGUAGE_FILE:' . $code);
    return lang_validate($language, $code);
}

// Vollstaendige Validierung fuer Build/Deployment. Im normalen Request wird
// bewusst nur lang_load(<konfigurierte Sprache>) verwendet.
function lang_validate_all(): array {
    $codes = lang_codes();
    if (!$codes) throw new RuntimeException('NO_LANGUAGE_FILES');
    if (!in_array('en', $codes, true)) throw new RuntimeException('ENGLISH_LANGUAGE_REQUIRED');

    $base = lang_load('en');
    $baseKeys = array_keys($base['strings']);
    sort($baseKeys, SORT_STRING);
    $result = [];

    foreach ($codes as $code) {
        $language = lang_load($code);
        $keys = array_keys($language['strings']);
        sort($keys, SORT_STRING);
        if ($keys !== $baseKeys) {
            $missing = array_values(array_diff($baseKeys, $keys));
            $extra = array_values(array_diff($keys, $baseKeys));
            throw new RuntimeException('LANGUAGE_KEY_MISMATCH:' . $code . ':missing=' . implode(',', $missing) . ':extra=' . implode(',', $extra));
        }
        $result[$code] = $language['_meta'];
    }
    return $result;
}
