<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método não permitido'); }
$user = currentUser();
if (!$user) { header('Location: ' . appUrl('login')); exit; }
if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Solicitação expirada.'); }

$phraseId = filter_input(INPUT_POST, 'phrase_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$exercisePhrase = trim((string) ($_POST['exercise_phrase'] ?? ''));
$answer = trim((string) ($_POST['answer'] ?? ''));
// The browser sends punctuation as a separate non-selectable token. Retain this
// safeguard for requests submitted outside the exercise builder as well.
$answer = preg_replace('/[\p{P}\p{S}]+$/u', '', $answer) ?? $answer;
$answer = trim($answer);
$redirect = appUrl('dashboard?view=phrase&id=' . (int) $phraseId);
if (!$phraseId || $exercisePhrase === '' || $answer === '' || mb_strlen($exercisePhrase) > 5000 || mb_strlen($answer) > 5000) {
    $_SESSION['exercise_error'] = 'Selecione ao menos uma palavra para criar um exercício válido.';
    header('Location: ' . $redirect); exit;
}

try {
    $pdo = database();
    $phrase = $pdo->prepare('SELECT id FROM phrases WHERE id = :id AND id_user = :user_id LIMIT 1');
    $phrase->execute(['id' => $phraseId, 'user_id' => $user['id']]);
    if (!$phrase->fetch()) {
        $_SESSION['exercise_error'] = 'Frase não encontrada ou sem permissão de acesso.';
    } else {
        $duplicate = $pdo->prepare('SELECT id FROM exercises WHERE id_phrase = :phrase_id AND frase_exercicio = :exercise_phrase LIMIT 1');
        $duplicate->execute(['phrase_id' => $phraseId, 'exercise_phrase' => $exercisePhrase]);
        if ($duplicate->fetch()) {
            $_SESSION['exercise_error'] = 'Este exercício já foi criado para a frase selecionada.';
        } else {
            $insert = $pdo->prepare('INSERT INTO exercises (id_phrase, frase_exercicio, resposta) VALUES (:phrase_id, :exercise_phrase, :answer)');
            $insert->execute(['phrase_id' => $phraseId, 'exercise_phrase' => $exercisePhrase, 'answer' => $answer]);
            $_SESSION['exercise_success'] = 'Exercício salvo com sucesso.';
        }
    }
} catch (PDOException $exception) {
    error_log('Subdrill exercise creation database error: ' . $exception->getMessage());
    $_SESSION['exercise_error'] = 'Não foi possível salvar o exercício agora. Tente novamente mais tarde.';
}
header('Location: ' . $redirect);
