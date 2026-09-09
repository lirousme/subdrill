<?php
declare(strict_types=1);

$user = currentUser();
$view = (string) ($_GET['view'] ?? 'play');
if (!in_array($view, ['play', 'new', 'phrases'], true)) $view = 'play';
$error = $_SESSION['phrase_error'] ?? '';
$success = $_SESSION['phrase_success'] ?? '';
$old = $_SESSION['phrase_old'] ?? ['phrase' => '', 'description' => '', 'phrase_language' => 'pt-BR', 'description_language' => 'pt-BR'];
unset($_SESSION['phrase_error'], $_SESSION['phrase_success'], $_SESSION['phrase_old']);

$phrases = [];
$totalPhrases = 0;
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
if ($view === 'phrases') {
    try {
        $pdo = database();
        $count = $pdo->prepare('SELECT COUNT(*) FROM phrases WHERE id_user = :user_id');
        $count->execute(['user_id' => $user['id']]);
        $totalPhrases = (int) $count->fetchColumn();
        $totalPages = max(1, (int) ceil($totalPhrases / $perPage));
        $page = min($page, $totalPages);
        $statement = $pdo->prepare('SELECT id, frase, idioma_frase, descricao, idioma_descricao FROM phrases WHERE id_user = :user_id ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset');
        $statement->bindValue(':user_id', $user['id'], PDO::PARAM_INT);
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
        $statement->execute();
        $phrases = $statement->fetchAll();
    } catch (PDOException $exception) {
        error_log('Subdrill phrases list database error: ' . $exception->getMessage());
        $error = 'Não foi possível carregar as frases agora. Tente novamente mais tarde.';
        $totalPages = 1;
    }
} else {
    $totalPages = 1;
}

function dashboardUrl(string $view, array $parameters = []): string {
    return appUrl('dashboard?' . http_build_query(['view' => $view] + $parameters));
}

pageHeader('Painel');
?>
<div class="app-shell">
  <aside class="sidebar" id="sidebar" aria-label="Navegação principal">
    <a class="sidebar-brand" href="<?= htmlspecialchars(dashboardUrl('play')) ?>"><span aria-hidden="true">◈</span><span>Subdrill</span></a>
    <nav class="sidebar-nav" aria-label="Área do aluno">
      <a class="sidebar-link <?= $view === 'play' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(dashboardUrl('play')) ?>"><span aria-hidden="true">▶</span> Jogar</a>
      <a class="sidebar-link sidebar-add <?= $view === 'new' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(dashboardUrl('new')) ?>" aria-label="Adicionar frase"><span aria-hidden="true">+</span><span>Adicionar</span></a>
      <a class="sidebar-link <?= $view === 'phrases' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(dashboardUrl('phrases')) ?>"><span aria-hidden="true">☷</span> Frases</a>
    </nav>
    <div class="sidebar-footer"><span><?= htmlspecialchars($user['name'] ?? $user['email']) ?></span><a href="<?= htmlspecialchars(appUrl('logout')) ?>">Sair</a></div>
  </aside>
  <main class="app-content">
    <?php if ($view === 'play'): ?>
      <section class="content-heading"><p class="eyebrow">SUBSTITUTION DRILL</p><h1>Jogar</h1><p>Em breve, pratique suas frases com exercícios de substituição.</p></section>
      <section class="empty-state"><span aria-hidden="true">◎</span><h2>O jogo está a caminho.</h2><p>Enquanto isso, adicione frases para montar sua próxima sessão de treino.</p><a class="button" href="<?= htmlspecialchars(dashboardUrl('new')) ?>">Adicionar uma frase <span>→</span></a></section>
    <?php elseif ($view === 'new'): ?>
      <section class="content-heading"><p class="eyebrow">BIBLIOTECA</p><h1>Nova frase</h1><p>Cadastre uma frase e sua descrição para usar nos seus treinos.</p></section>
      <section class="content-card form-card">
        <?php if ($error): ?><div class="alert" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form action="<?= htmlspecialchars(appUrl('api/phrases/create.php')) ?>" method="post" class="phrase-form">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
          <div class="field-grid"><label>Frase<textarea name="phrase" required maxlength="5000" placeholder="Ex.: I would like a coffee."><?= htmlspecialchars((string) $old['phrase']) ?></textarea></label><label>Idioma da frase<select name="phrase_language" required><option value="pt-BR" <?= $old['phrase_language'] === 'pt-BR' ? 'selected' : '' ?>>Português (Brasil)</option><option value="en-GB" <?= $old['phrase_language'] === 'en-GB' ? 'selected' : '' ?>>Inglês (Reino Unido)</option></select></label></div>
          <div class="field-grid"><label>Descrição<textarea name="description" required maxlength="5000" placeholder="Ex.: Peça uma bebida de forma educada."><?= htmlspecialchars((string) $old['description']) ?></textarea></label><label>Idioma da descrição<select name="description_language" required><option value="pt-BR" <?= $old['description_language'] === 'pt-BR' ? 'selected' : '' ?>>Português (Brasil)</option><option value="en-GB" <?= $old['description_language'] === 'en-GB' ? 'selected' : '' ?>>Inglês (Reino Unido)</option></select></label></div>
          <div class="form-actions"><a href="<?= htmlspecialchars(dashboardUrl('phrases')) ?>">Cancelar</a><button class="button" type="submit">Salvar frase <span>→</span></button></div>
        </form>
      </section>
    <?php else: ?>
      <section class="content-heading heading-row"><div><p class="eyebrow">BIBLIOTECA</p><h1>Frases</h1><p><?= $totalPhrases ?> <?= $totalPhrases === 1 ? 'frase cadastrada' : 'frases cadastradas' ?>.</p></div><a class="button" href="<?= htmlspecialchars(dashboardUrl('new')) ?>">+ <span>Adicionar</span></a></section>
      <?php if ($success): ?><div class="notice" role="status"><?= htmlspecialchars($success) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if (!$phrases && !$error): ?><section class="empty-state compact"><span aria-hidden="true">☷</span><h2>Nenhuma frase ainda.</h2><p>Comece adicionando a primeira frase da sua biblioteca.</p></section>
      <?php else: ?><section class="phrase-list" aria-label="Frases cadastradas"><?php foreach ($phrases as $phrase): ?><article class="phrase-card"><div class="phrase-card-top"><span class="phrase-id">#<?= (int) $phrase['id'] ?></span><span class="language-tag"><?= htmlspecialchars($phrase['idioma_frase']) ?></span></div><h2><?= nl2br(htmlspecialchars($phrase['frase'])) ?></h2><p><?= nl2br(htmlspecialchars($phrase['descricao'])) ?></p><span class="description-language">Descrição em <?= htmlspecialchars($phrase['idioma_descricao']) ?></span></article><?php endforeach; ?></section>
      <?php if ($totalPages > 1): ?><nav class="pagination" aria-label="Paginação de frases"><?php if ($page > 1): ?><a href="<?= htmlspecialchars(dashboardUrl('phrases', ['page' => $page - 1])) ?>">← Anterior</a><?php endif; ?><span>Página <?= $page ?> de <?= $totalPages ?></span><?php if ($page < $totalPages): ?><a href="<?= htmlspecialchars(dashboardUrl('phrases', ['page' => $page + 1])) ?>">Próxima →</a><?php endif; ?></nav><?php endif; ?><?php endif; ?>
    <?php endif; ?>
  </main>
</div>
<?php pageFooter();
