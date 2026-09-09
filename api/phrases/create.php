<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido');
}

$user = currentUser();
if (!$user) {
    header('Location: ' . appUrl('login'));
    exit;
}

if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
    http_response_code(419);
    exit('Solicitação expirada.');
}

$phrase = trim((string) ($_POST['phrase'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$phraseLanguage = (string) ($_POST['phrase_language'] ?? '');
$descriptionLanguage = (string) ($_POST['description_language'] ?? '');
$languages = ['pt-BR', 'en-GB'];

$_SESSION['phrase_old'] = [
    'phrase' => $phrase,
    'description' => $description,
    'phrase_language' => $phraseLanguage,
    'description_language' => $descriptionLanguage,
];

if ($phrase === '' || $description === '' || !in_array($phraseLanguage, $languages, true) || !in_array($descriptionLanguage, $languages, true)) {
    $_SESSION['phrase_error'] = 'Preencha a frase, a descrição e selecione idiomas válidos.';
    header('Location: ' . appUrl('dashboard?view=new'));
    exit;
}

if (mb_strlen($phrase) > 5000 || mb_strlen($description) > 5000) {
    $_SESSION['phrase_error'] = 'A frase e a descrição podem ter no máximo 5.000 caracteres.';
    header('Location: ' . appUrl('dashboard?view=new'));
    exit;
}

try {
    $pdo = database();
    $duplicate = $pdo->prepare('SELECT id FROM phrases WHERE id_user = :user_id AND frase = :phrase LIMIT 1');
    $duplicate->execute(['user_id' => $user['id'], 'phrase' => $phrase]);
    if ($duplicate->fetch()) {
        $_SESSION['phrase_error'] = 'Esta frase já está cadastrada na sua biblioteca.';
        header('Location: ' . appUrl('dashboard?view=new'));
        exit;
    }

    $statement = $pdo->prepare(
        'INSERT INTO phrases (frase, idioma_frase, descricao, idioma_descricao, id_user)
         VALUES (:phrase, :phrase_language, :description, :description_language, :user_id)'
    );
    $statement->execute([
        'phrase' => $phrase,
        'phrase_language' => $phraseLanguage,
        'description' => $description,
        'description_language' => $descriptionLanguage,
        'user_id' => $user['id'],
    ]);
} catch (PDOException $exception) {
    error_log('Subdrill phrase creation database error: ' . $exception->getMessage());
    $_SESSION['phrase_error'] = 'Não foi possível salvar a frase agora. Tente novamente mais tarde.';
    header('Location: ' . appUrl('dashboard?view=new'));
    exit;
}

unset($_SESSION['phrase_old']);
$_SESSION['phrase_success'] = 'Frase adicionada com sucesso.';
header('Location: ' . appUrl('dashboard?view=phrases'));
