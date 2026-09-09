<?php
declare(strict_types=1);
$user = currentUser();
$view = (string) ($_GET['view'] ?? 'jogar');
if (!in_array($view, ['jogar', 'nova-frase', 'frases'], true)) $view = 'jogar';

$phrases = [];
$totalPhrases = 0;
$totalPages = 1;
$page = max(1, (int) ($_GET['page'] ?? 1));
if ($view === 'frases') {
    try {
        $pdo = database();
        $totalPhrases = (int) $pdo->query('SELECT COUNT(*) FROM frases')->fetchColumn();
        $totalPages = max(1, (int) ceil($totalPhrases / 10));
        $page = min($page, $totalPages);
        $statement = $pdo->prepare('SELECT id, frase, idioma_frase, descricao, idioma_descricao FROM frases ORDER BY id DESC LIMIT 10 OFFSET :offset');
        $statement->bindValue(':offset', ($page - 1) * 10, PDO::PARAM_INT);
        $statement->execute();
        $phrases = $statement->fetchAll();
    } catch (PDOException $exception) {
        error_log('Subdrill phrase list database error: ' . $exception->getMessage());
        $_SESSION['phrase_error'] = 'Não foi possível carregar as frases agora.';
    }
}

$success = $_SESSION['phrase_success'] ?? null;
$error = $_SESSION['phrase_error'] ?? null;
unset($_SESSION['phrase_success'], $_SESSION['phrase_error']);
pageHeader($view === 'frases' ? 'Frases' : ($view === 'nova-frase' ? 'Nova frase' : 'Jogar'));
?>
<div class="app-shell">
    <aside id="sidebar" class="sidebar" aria-label="Navegação principal">
        <a class="logo" href="<?= htmlspecialchars(appUrl('dashboard')) ?>"><span>◈</span> Subdrill</a>
        <nav class="sidebar-nav">
            <a class="<?= $view === 'jogar' ? 'active' : '' ?>" href="<?= htmlspecialchars(appUrl('dashboard')) ?>">Jogar</a>
            <a class="icon-link <?= $view === 'nova-frase' ? 'active' : '' ?>" href="<?= htmlspecialchars(appUrl('dashboard?view=nova-frase')) ?>" aria-label="Adicionar frase">+</a>
            <a class="<?= $view === 'frases' ? 'active' : '' ?>" href="<?= htmlspecialchars(appUrl('dashboard?view=frases')) ?>">Frases</a>
        </nav>
        <div class="sidebar-user"><span><?= htmlspecialchars($user['name'] ?? $user['email']) ?></span><a href="<?= htmlspecialchars(appUrl('logout')) ?>">Sair</a></div>
    </aside>
    <main class="app-content">
        <?php if ($success): ?><p class="notice success"><?= htmlspecialchars($success) ?></p><?php endif; ?>
        <?php if ($error): ?><p class="notice error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <?php if ($view === 'nova-frase'): ?>
            <header class="page-heading"><p class="eyebrow">NOVA FRASE</p><h1>Adicione material para praticar.</h1><p>Cadastre a frase e uma descrição no idioma que fizer mais sentido para você.</p></header>
            <form class="phrase-form" method="post" action="<?= htmlspecialchars(appUrl('frases')) ?>">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
                <label>Frase<textarea name="frase" required maxlength="5000" placeholder="Ex.: I am learning English."></textarea></label>
                <label>Idioma da frase<select name="idioma_frase" required><option value="pt-BR">Português (Brasil)</option><option value="en-GB">English (United Kingdom)</option></select></label>
                <label>Descrição<textarea name="descricao" required maxlength="5000" placeholder="Ex.: Estou aprendendo inglês."></textarea></label>
                <label>Idioma da descrição<select name="idioma_descricao" required><option value="pt-BR">Português (Brasil)</option><option value="en-GB">English (United Kingdom)</option></select></label>
                <button class="button" type="submit">Salvar frase</button>
            </form>
        <?php elseif ($view === 'frases'): ?>
            <header class="page-heading"><p class="eyebrow">FRASES</p><h1>Sua biblioteca de prática.</h1><p><?= $totalPhrases ?> frase<?= $totalPhrases === 1 ? '' : 's' ?> cadastrada<?= $totalPhrases === 1 ? '' : 's' ?>.</p></header>
            <?php if (!$phrases): ?><section class="empty-state"><p>Ainda não há frases cadastradas.</p><a class="button" href="<?= htmlspecialchars(appUrl('dashboard?view=nova-frase')) ?>">Adicionar a primeira frase</a></section>
            <?php else: ?><section class="phrase-list"><?php foreach ($phrases as $phrase): ?><article class="phrase-card"><div class="phrase-card-title"><span>#<?= (int) $phrase['id'] ?></span><span class="language-tag"><?= htmlspecialchars($phrase['idioma_frase']) ?></span></div><h2><?= nl2br(htmlspecialchars($phrase['frase'])) ?></h2><p><?= nl2br(htmlspecialchars($phrase['descricao'])) ?></p><span class="language-tag muted-tag"><?= htmlspecialchars($phrase['idioma_descricao']) ?></span></article><?php endforeach; ?></section>
            <?php if ($totalPages > 1): ?><nav class="pagination" aria-label="Paginação de frases"><?php if ($page > 1): ?><a href="<?= htmlspecialchars(appUrl('dashboard?view=frases&page=' . ($page - 1))) ?>">Anterior</a><?php endif; ?><span>Página <?= $page ?> de <?= $totalPages ?></span><?php if ($page < $totalPages): ?><a href="<?= htmlspecialchars(appUrl('dashboard?view=frases&page=' . ($page + 1))) ?>">Próxima</a><?php endif; ?></nav><?php endif; ?><?php endif; ?>
        <?php else: ?>
            <header class="page-heading"><p class="eyebrow">JOGAR</p><h1>Em breve.</h1><p>O modo de jogo será adicionado aqui.</p></header>
        <?php endif; ?>
    </main>
</div>
<?php pageFooter();
