<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido');
}

startSecureSession();
if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
    http_response_code(419);
    exit('Solicitação expirada.');
}

$name = trim((string) ($_POST['name'] ?? ''));
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$email = $email ? strtolower($email) : false;
$password = (string) ($_POST['password'] ?? '');
$confirmation = (string) ($_POST['password_confirmation'] ?? '');
$_SESSION['register_old'] = ['name' => $name, 'email' => $email ?: trim((string) ($_POST['email'] ?? ''))];

if ($name === '' || mb_strlen($name) > 120 || !$email || strlen($password) < 8) {
    $_SESSION['auth_error'] = 'Preencha seu nome, um e-mail válido e uma senha de pelo menos 8 caracteres.';
    header('Location: ' . appUrl('criar-conta'));
    exit;
}

if (!hash_equals($password, $confirmation)) {
    $_SESSION['auth_error'] = 'As senhas não coincidem.';
    header('Location: ' . appUrl('criar-conta'));
    exit;
}

try {
    $pdo = new PDO(
        'mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_NAME') . ';charset=utf8mb4',
        env('DB_USER'),
        env('DB_PASS'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
    );
    $statement = $pdo->prepare('INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)');
    $statement->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    $userId = (int) $pdo->lastInsertId();
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        $_SESSION['auth_error'] = 'Já existe uma conta com este e-mail. Entre para continuar.';
    } else {
        error_log('Subdrill register database error: ' . $exception->getMessage());
        $_SESSION['auth_error'] = 'Não foi possível criar sua conta agora. Tente novamente mais tarde.';
    }
    header('Location: ' . appUrl('criar-conta'));
    exit;
}

session_regenerate_id(true);
$_SESSION['user'] = ['id' => $userId, 'name' => $name, 'email' => $email];
unset($_SESSION['csrf'], $_SESSION['register_old']);
header('Location: ' . appUrl('dashboard'));
