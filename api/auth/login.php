<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método não permitido'); }
startSecureSession();
if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Solicitação expirada.'); }
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = (string) ($_POST['password'] ?? '');
if (!$email || $password === '') { $_SESSION['auth_error'] = 'Informe um e-mail e uma senha válidos.'; header('Location: ' . appUrl('login')); exit; }

try {
    $pdo = new PDO('mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_NAME') . ';charset=utf8mb4', env('DB_USER'), env('DB_PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
    $statement = $pdo->prepare('SELECT id, name, email, password_hash FROM users WHERE email = :email LIMIT 1');
    $statement->execute(['email' => $email]);
    $user = $statement->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $exception) { error_log('Subdrill login database error: ' . $exception->getMessage()); $_SESSION['auth_error'] = 'Não foi possível entrar agora. Tente novamente mais tarde.'; header('Location: ' . appUrl('login')); exit; }

if (!$user || !password_verify($password, $user['password_hash'])) { usleep(250000); $_SESSION['auth_error'] = 'E-mail ou senha incorretos.'; header('Location: ' . appUrl('login')); exit; }
session_regenerate_id(true);
$_SESSION['user'] = ['id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email']];
unset($_SESSION['csrf']);
header('Location: ' . appUrl('dashboard'));
