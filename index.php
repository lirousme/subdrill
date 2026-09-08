<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

$path = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
$base = appBasePath();
if ($base && ($path === $base || str_starts_with($path, $base . '/'))) $path = substr($path, strlen($base)) ?: '/';
$path = '/' . ltrim($path, '/');

switch ($path) {
    case '/': case '/login': requireGuest(); require __DIR__ . '/pages/login.php'; break;
    case '/criar-conta': requireGuest(); require __DIR__ . '/pages/register.php'; break;
    case '/dashboard': requireAuth(); require __DIR__ . '/pages/dashboard.php'; break;
    case '/logout': require __DIR__ . '/api/auth/logout.php'; break;
    default: http_response_code(404); pageHeader('Página não encontrada'); echo '<main class="center"><section class="card"><p class="eyebrow">404</p><h1>Página não encontrada</h1><a class="button" href="' . htmlspecialchars(appUrl(), ENT_QUOTES) . '">Voltar ao início</a></section></main>'; pageFooter();
}
