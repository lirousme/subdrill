<?php
declare(strict_types=1);

$user = currentUser();
$view = (string) ($_GET['view'] ?? 'play');
if (!in_array($view, ['play', 'new', 'phrases', 'phrase'], true)) $view = 'play';
$error = $_SESSION['phrase_error'] ?? $_SESSION['exercise_error'] ?? '';
$success = $_SESSION['phrase_success'] ?? $_SESSION['exercise_success'] ?? '';
$old = $_SESSION['phrase_old'] ?? ['phrase' => '', 'description' => '', 'phrase_language' => 'pt-BR', 'description_language' => 'pt-BR'];
unset($_SESSION['phrase_error'], $_SESSION['phrase_success'], $_SESSION['phrase_old'], $_SESSION['exercise_error'], $_SESSION['exercise_success']);

function dashboardUrl(string $view, array $parameters = []): string
{
    return appUrl('dashboard?' . http_build_query(['view' => $view] + $parameters));
}

$phrases = [];
$phrase = null;
$existingExercises = [];
$gameExercise = null;
$gameScore = 0;
$totalPhrases = 0;
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$totalPages = 1;

try {
    $pdo = database();
    if ($view === 'phrases') {
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
    }

    if ($view === 'play') {
        $scoreStatement = $pdo->prepare('SELECT score FROM user_game_scores WHERE id_user = :user_id LIMIT 1');
        $scoreStatement->execute(['user_id' => $user['id']]);
        $gameScore = (int) ($scoreStatement->fetchColumn() ?: 0);
        $gameStatement = $pdo->query('SELECT exercises.id, exercises.frase_exercicio, phrases.frase, phrases.descricao FROM exercises INNER JOIN phrases ON phrases.id = exercises.id_phrase ORDER BY RAND() LIMIT 1');
        $gameExercise = $gameStatement->fetch();
    }

    if ($view === 'phrase') {
        $phraseId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$phraseId) {
            $error = 'Escolha uma frase válida para criar exercícios.';
        } else {
            $statement = $pdo->prepare('SELECT id, frase, idioma_frase, descricao, idioma_descricao FROM phrases WHERE id = :id AND id_user = :user_id LIMIT 1');
            $statement->execute(['id' => $phraseId, 'user_id' => $user['id']]);
            $phrase = $statement->fetch();
            if (!$phrase) {
                $error = 'Frase não encontrada ou sem permissão de acesso.';
            } else {
                $exercises = $pdo->prepare('SELECT id, frase_exercicio, resposta, created_at FROM exercises WHERE id_phrase = :phrase_id ORDER BY created_at DESC, id DESC');
                $exercises->execute(['phrase_id' => $phrase['id']]);
                $existingExercises = $exercises->fetchAll();
            }
        }
    }
} catch (PDOException $exception) {
    error_log('Subdrill dashboard database error: ' . $exception->getMessage());
    $error = 'Não foi possível carregar os dados agora. Tente novamente mais tarde.';
}

pageHeader('Painel');
?>
<div class="app-shell">
  <aside class="sidebar" id="sidebar" aria-label="Navegação principal">
    <a class="sidebar-brand" href="<?= htmlspecialchars(dashboardUrl('play')) ?>"><span aria-hidden="true">◈</span><span>Subdrill</span></a>
    <nav class="sidebar-nav" aria-label="Área do aluno">
      <a class="sidebar-link <?= $view === 'play' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(dashboardUrl('play')) ?>"><span aria-hidden="true">▶</span> Jogar</a>
      <a class="sidebar-link sidebar-add <?= $view === 'new' ? 'is-active' : '' ?>" href="<?= htmlspecialchars(dashboardUrl('new')) ?>" aria-label="Adicionar frase"><span aria-hidden="true">+</span><span>Adicionar</span></a>
      <a class="sidebar-link <?= in_array($view, ['phrases', 'phrase'], true) ? 'is-active' : '' ?>" href="<?= htmlspecialchars(dashboardUrl('phrases')) ?>"><span aria-hidden="true">☷</span> Frases</a>
    </nav>
    <div class="sidebar-footer"><span><?= htmlspecialchars($user['name'] ?? $user['email']) ?></span><a href="<?= htmlspecialchars(appUrl('logout')) ?>">Sair</a></div>
  </aside>
  <main class="app-content">
    <?php if ($view === 'play'): ?>
      <section class="game-page">
        <header class="game-header"><div><p class="eyebrow">SUBSTITUTION DRILL</p><h1>Hora de jogar.</h1><p>Observe, complete e acumule pontos.</p></div><div class="score-card" aria-label="Sua pontuação"><span>SEUS PONTOS</span><strong id="game-score"><?= $gameScore ?></strong><small>+1 ao acertar · −1 ao errar</small></div></header>
        <?php if (!$gameExercise): ?>
          <section class="empty-state game-empty"><span aria-hidden="true">✦</span><h2>Prepare o primeiro desafio.</h2><p>Crie exercícios nas frases da biblioteca para começar a jogar.</p><a class="button" href="<?= htmlspecialchars(dashboardUrl('phrases')) ?>">Ir para frases <span>→</span></a></section>
        <?php else: ?>
          <section class="game-arena" id="game-arena">
            <div class="game-orb orb-a" aria-hidden="true"></div><div class="game-orb orb-b" aria-hidden="true"></div>
            <div class="game-step" id="game-intro"><span class="round-label">DESAFIO NOVO</span><p class="game-prompt">Leia a frase e guarde cada palavra.</p><blockquote class="game-source">“<?= nl2br(htmlspecialchars($gameExercise['frase'])) ?>”</blockquote><p class="game-description"><?= nl2br(htmlspecialchars($gameExercise['descricao'])) ?></p><button class="button game-start" type="button" id="start-game">Estou pronto <span>→</span></button></div>
            <div class="game-step" id="game-question" hidden><span class="round-label">AGORA É A SUA VEZ</span><p class="game-instruction">PREENCHA A LACUNA DE ACORDO COM A FRASE ANTERIOR</p><p class="game-exercise"><?= nl2br(htmlspecialchars($gameExercise['frase_exercicio'])) ?></p><p class="game-description"><?= nl2br(htmlspecialchars($gameExercise['descricao'])) ?></p><form id="game-answer-form" class="game-answer-form"><label for="game-answer">Sua resposta</label><div><input id="game-answer" name="answer" type="text" required autocomplete="off" autofocus><button class="button" type="submit">Conferir <span>→</span></button></div></form></div>
            <div class="game-step game-win" id="game-win" hidden><span aria-hidden="true" class="win-icon">★</span><p class="round-label">ACERTOU!</p><h2>Mandou bem!</h2><p>Você completou a frase corretamente e ganhou 1 ponto.</p><a class="button" href="<?= htmlspecialchars(dashboardUrl('play')) ?>">Próximo desafio <span>→</span></a></div><output id="game-feedback" class="game-feedback" aria-live="assertive"></output>
          </section>
          <script>
            (() => {
              const intro = document.querySelector('#game-intro'), question = document.querySelector('#game-question'), win = document.querySelector('#game-win'), start = document.querySelector('#start-game'), form = document.querySelector('#game-answer-form'), input = document.querySelector('#game-answer'), feedback = document.querySelector('#game-feedback'), score = document.querySelector('#game-score'), arena = document.querySelector('#game-arena');
              start.addEventListener('click', () => { intro.hidden = true; question.hidden = false; input.focus(); });
              form.addEventListener('submit', async event => { event.preventDefault(); const button = form.querySelector('button'); button.disabled = true; feedback.textContent = ''; try { const response = await fetch(<?= json_encode(appUrl('api/game/answer.php')) ?>, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify({ csrf: <?= json_encode(csrfToken()) ?>, exercise_id: <?= (int) $gameExercise['id'] ?>, answer: input.value }) }); const result = await response.json(); if (!response.ok) throw new Error(result.message || 'Não foi possível conferir a resposta.'); score.textContent = result.score; if (result.correct) { question.hidden = true; win.hidden = false; arena.classList.add('is-correct'); } else { feedback.textContent = 'Quase! Você perdeu 1 ponto. Veja a frase mais uma vez e tente novamente.'; feedback.className = 'game-feedback is-wrong'; arena.classList.remove('is-wrong'); void arena.offsetWidth; arena.classList.add('is-wrong'); intro.hidden = false; question.hidden = true; input.value = ''; } } catch (error) { feedback.textContent = error.message; feedback.className = 'game-feedback is-wrong'; } finally { button.disabled = false; } });
            })();
          </script>
        <?php endif; ?>
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
    <?php elseif ($view === 'phrase'): ?>
      <section class="content-heading"><a class="back-link" href="<?= htmlspecialchars(dashboardUrl('phrases')) ?>">← Voltar para frases</a><p class="eyebrow">CRIAR EXERCÍCIOS</p><h1>Selecione as palavras</h1><p>Clique nas palavras que devem ficar em branco. Você pode selecionar mais de uma palavra para formar uma única resposta.</p></section>
      <?php if ($error): ?><div class="alert max-content" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if ($phrase): ?>
        <section class="content-card exercise-builder">
          <div class="source-phrase"><span class="language-tag"><?= htmlspecialchars($phrase['idioma_frase']) ?></span><h2><?= nl2br(htmlspecialchars($phrase['frase'])) ?></h2><p><?= nl2br(htmlspecialchars($phrase['descricao'])) ?></p></div>
          <form id="exercise-form" action="<?= htmlspecialchars(appUrl('api/exercises/create.php')) ?>" method="post" class="exercise-form">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>"><input type="hidden" name="phrase_id" value="<?= (int) $phrase['id'] ?>"><input type="hidden" name="exercise_phrase" id="exercise-phrase"><input type="hidden" name="answer" id="exercise-answer">
            <p class="field-label">Palavras da frase</p><div class="word-picker" id="word-picker" aria-label="Selecione as palavras da frase"></div>
            <div class="exercise-preview"><span>Prévia do exercício</span><output id="exercise-preview" aria-live="polite">Selecione uma ou mais palavras acima.</output></div>
            <div class="form-actions"><a href="<?= htmlspecialchars(dashboardUrl('phrases')) ?>">Cancelar</a><button class="button" type="submit" id="save-exercise" disabled>Salvar exercício <span>→</span></button></div>
          </form>
        </section>
        <section class="saved-exercises" aria-labelledby="saved-exercises-title">
          <div class="section-title"><p class="eyebrow">BIBLIOTECA DA FRASE</p><h2 id="saved-exercises-title">Exercícios já criados</h2></div>
          <?php if (!$existingExercises): ?>
            <div class="empty-saved-exercises"><p>Nenhum exercício criado para esta frase ainda.</p></div>
          <?php else: ?>
            <div class="exercise-list"><?php foreach ($existingExercises as $exercise): ?><article class="saved-exercise"><span class="phrase-id">Exercício #<?= (int) $exercise['id'] ?></span><p><?= nl2br(htmlspecialchars($exercise['frase_exercicio'])) ?></p><span class="description-language">Resposta: <?= htmlspecialchars($exercise['resposta']) ?></span></article><?php endforeach; ?></div>
          <?php endif; ?>
        </section>
        <script>
          (() => {
            const phrase = <?= json_encode($phrase['frase'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            const picker = document.querySelector('#word-picker'), preview = document.querySelector('#exercise-preview'), exercisePhrase = document.querySelector('#exercise-phrase'), answer = document.querySelector('#exercise-answer'), save = document.querySelector('#save-exercise');
            const parts = phrase.match(/\s+|[^\s]+/g) || [];
            const words = parts.map((text, index) => ({ text, index, selectable: /\S/.test(text), selected: false }));
            const update = () => { const selected = words.filter(part => part.selected); const masked = words.map(part => part.selected ? '_' : part.text).join(''); preview.textContent = selected.length ? masked : 'Selecione uma ou mais palavras acima.'; exercisePhrase.value = selected.length ? masked : ''; answer.value = selected.map(part => part.text).join(' '); save.disabled = !selected.length; };
            words.forEach(part => { if (!part.selectable) return; const button = document.createElement('button'); button.type = 'button'; button.className = 'word-token'; button.textContent = part.text; button.addEventListener('click', () => { part.selected = !part.selected; button.classList.toggle('is-selected', part.selected); update(); }); picker.append(button); });
          })();
        </script>
      <?php endif; ?>
    <?php else: ?>
      <section class="content-heading heading-row"><div><p class="eyebrow">BIBLIOTECA</p><h1>Frases</h1><p><?= $totalPhrases ?> <?= $totalPhrases === 1 ? 'frase cadastrada' : 'frases cadastradas' ?>.</p></div><a class="button" href="<?= htmlspecialchars(dashboardUrl('new')) ?>">+ <span>Adicionar</span></a></section>
      <?php if ($success): ?><div class="notice max-content" role="status"><?= htmlspecialchars($success) ?></div><?php endif; ?><?php if ($error): ?><div class="alert max-content" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if (!$phrases && !$error): ?><section class="empty-state compact"><span aria-hidden="true">☷</span><h2>Nenhuma frase ainda.</h2><p>Comece adicionando a primeira frase da sua biblioteca.</p></section>
      <?php else: ?><section class="phrase-list" aria-label="Frases cadastradas"><?php foreach ($phrases as $item): ?><a class="phrase-card" href="<?= htmlspecialchars(dashboardUrl('phrase', ['id' => $item['id']])) ?>" aria-label="Criar exercício para: <?= htmlspecialchars($item['frase']) ?>"><div class="phrase-card-top"><span class="phrase-id">#<?= (int) $item['id'] ?></span><span class="language-tag"><?= htmlspecialchars($item['idioma_frase']) ?></span></div><h2><?= nl2br(htmlspecialchars($item['frase'])) ?></h2><p><?= nl2br(htmlspecialchars($item['descricao'])) ?></p><span class="description-language">Descrição em <?= htmlspecialchars($item['idioma_descricao']) ?> · Criar exercícios →</span></a><?php endforeach; ?></section>
      <?php if ($totalPages > 1): ?><nav class="pagination" aria-label="Paginação de frases"><?php if ($page > 1): ?><a href="<?= htmlspecialchars(dashboardUrl('phrases', ['page' => $page - 1])) ?>">← Anterior</a><?php endif; ?><span>Página <?= $page ?> de <?= $totalPages ?></span><?php if ($page < $totalPages): ?><a href="<?= htmlspecialchars(dashboardUrl('phrases', ['page' => $page + 1])) ?>">Próxima →</a><?php endif; ?></nav><?php endif; ?><?php endif; ?>
    <?php endif; ?>
  </main>
</div>
<?php pageFooter();
