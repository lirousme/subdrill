<?php
declare(strict_types=1);

/** Loads local configuration without requiring Composer. */
function env(string $key, string $default = ''): string
{
    static $values = null;
    if ($values === null) {
        $values = [];
        $file = __DIR__ . '/.env';
        if (is_readable($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                if (str_starts_with(ltrim($line), '#') || !str_contains($line, '=')) continue;
                [$name, $value] = explode('=', $line, 2);
                $values[trim($name)] = trim($value, " \t\n\r\0\x0B\"");
            }
        }
    }
    return $_ENV[$key] ?? getenv($key) ?: ($values[$key] ?? $default);
}

function appBasePath(): string
{
    $configured = trim(env('APP_BASE_PATH', ''), '/');
    if ($configured !== '') return '/' . $configured;
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
    // Some front-controller setups preserve the requested asset path in
    // SCRIPT_NAME. Only infer a base path when the executing script is one of
    // our entry points; otherwise use the site root.
    if (!in_array(basename($scriptName), ['index.php', 'app.index'], true)) return '';
    $script = dirname($scriptName);
    return $script === '/' ? '' : rtrim($script, '/');
}

function appUrl(string $path = ''): string
{
    return appBasePath() . ($path === '' ? '/' : '/' . ltrim($path, '/'));
}

/**
 * Builds a public asset URL and changes it when the local asset changes.
 *
 * This prevents a browser (or a hosting cache) from continuing to use an old
 * stylesheet after a deployment. The path is deliberately limited to files
 * inside the application's assets directory.
 */
function assetUrl(string $path): string
{
    $relativePath = ltrim($path, '/');
    $assetRoot = realpath(__DIR__ . '/assets');
    $asset = realpath(__DIR__ . '/assets/' . $relativePath);

    if ($assetRoot === false || $asset === false || !str_starts_with($asset, $assetRoot . DIRECTORY_SEPARATOR)) {
        return appUrl('assets/' . $relativePath);
    }

    return appUrl('assets/' . $relativePath) . '?v=' . filemtime($asset);
}

function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    session_name(env('APP_SESSION_NAME', 'subdrill_session'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => appBasePath() . '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function csrfToken(): string
{
    startSecureSession();
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function currentUser(): ?array
{
    startSecureSession();
    return $_SESSION['user'] ?? null;
}

function database(): PDO
{
    return new PDO(
        'mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_NAME') . ';charset=utf8mb4',
        env('DB_USER'),
        env('DB_PASS'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

function requireGuest(): void
{
    if (currentUser()) { header('Location: ' . appUrl('dashboard')); exit; }
}

function requireAuth(): void
{
    if (!currentUser()) { header('Location: ' . appUrl('login')); exit; }
}

function pageHeader(string $title): void
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . $safeTitle . ' · Subdrill</title><link rel="stylesheet" href="' . htmlspecialchars(assetUrl('css/app.css'), ENT_QUOTES) . '"></head><body>';
}

function pageFooter(): void { echo '</body></html>'; }
