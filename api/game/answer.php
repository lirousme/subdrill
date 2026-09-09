<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['message' => 'Método não permitido.']); exit; }
$user = currentUser();
if (!$user) { http_response_code(401); echo json_encode(['message' => 'Faça login para jogar.']); exit; }
$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input) || !hash_equals($_SESSION['csrf'] ?? '', (string) ($input['csrf'] ?? ''))) { http_response_code(419); echo json_encode(['message' => 'Solicitação expirada.']); exit; }
$exerciseId = filter_var($input['exercise_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$answer = trim((string) ($input['answer'] ?? ''));
if (!$exerciseId || $answer === '') { http_response_code(422); echo json_encode(['message' => 'Digite uma resposta para continuar.']); exit; }

try {
    $pdo = database();
    $exercise = $pdo->prepare('SELECT resposta FROM exercises WHERE id = :id LIMIT 1');
    $exercise->execute(['id' => $exerciseId]);
    $expected = $exercise->fetchColumn();
    if ($expected === false) { http_response_code(404); echo json_encode(['message' => 'Desafio não encontrado.']); exit; }
    $normalise = static function (string $value): string {
        $value = mb_strtolower(preg_replace('/\s+/u', ' ', trim($value)) ?? '', 'UTF-8');
        if (class_exists('Normalizer')) {
            $value = Normalizer::normalize($value, Normalizer::FORM_D) ?: $value;
            $value = preg_replace('/\p{Mn}+/u', '', $value) ?? $value;
        }
        return strtr($value, ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n']);
    };
    $correct = hash_equals($normalise((string) $expected), $normalise($answer));
    $change = $correct ? 1 : -1;
    $score = $pdo->prepare('INSERT INTO user_game_scores (id_user, score) VALUES (:user_id, :change) ON DUPLICATE KEY UPDATE score = score + :update_change');
    $score->execute(['user_id' => $user['id'], 'change' => $change, 'update_change' => $change]);
    $current = $pdo->prepare('SELECT score FROM user_game_scores WHERE id_user = :user_id');
    $current->execute(['user_id' => $user['id']]);
    echo json_encode(['correct' => $correct, 'score' => (int) $current->fetchColumn()]);
} catch (PDOException $exception) {
    error_log('Subdrill game answer database error: ' . $exception->getMessage());
    http_response_code(500); echo json_encode(['message' => 'Não foi possível conferir a resposta agora.']);
}
