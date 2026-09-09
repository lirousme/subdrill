<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método não permitido'); }
requireAuth();
startSecureSession();
if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Solicitação expirada.'); }

$frase = trim((string) ($_POST['frase'] ?? ''));
$descricao = trim((string) ($_POST['descricao'] ?? ''));
$idiomaFrase = (string) ($_POST['idioma_frase'] ?? '');
$idiomaDescricao = (string) ($_POST['idioma_descricao'] ?? '');
$languages = ['pt-BR', 'en-GB'];

if ($frase === '' || $descricao === '' || mb_strlen($frase) > 5000 || mb_strlen($descricao) > 5000 || !in_array($idiomaFrase, $languages, true) || !in_array($idiomaDescricao, $languages, true)) {
    $_SESSION['phrase_error'] = 'Preencha a frase, a descrição e selecione os idiomas válidos.';
    header('Location: ' . appUrl('dashboard?view=nova-frase'));
    exit;
}

try {
    $statement = database()->prepare('INSERT INTO frases (frase, idioma_frase, descricao, idioma_descricao) VALUES (:frase, :idioma_frase, :descricao, :idioma_descricao)');
    $statement->execute(['frase' => $frase, 'idioma_frase' => $idiomaFrase, 'descricao' => $descricao, 'idioma_descricao' => $idiomaDescricao]);
    $_SESSION['phrase_success'] = 'Frase adicionada com sucesso.';
    header('Location: ' . appUrl('dashboard?view=frases'));
} catch (PDOException $exception) {
    error_log('Subdrill phrase create database error: ' . $exception->getMessage());
    $_SESSION['phrase_error'] = 'Não foi possível salvar a frase agora. Tente novamente.';
    header('Location: ' . appUrl('dashboard?view=nova-frase'));
}
