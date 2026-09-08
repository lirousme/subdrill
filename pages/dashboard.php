<?php
declare(strict_types=1);
$user = currentUser();
pageHeader('Painel');
?>
<main class="dashboard"><header><a class="logo" href="<?= htmlspecialchars(appUrl('dashboard')) ?>"><span>◈</span> Subdrill</a><nav><span><?= htmlspecialchars($user['name'] ?? $user['email']) ?></span><a href="<?= htmlspecialchars(appUrl('logout')) ?>">Sair</a></nav></header><section class="hero"><p class="eyebrow">PAINEL</p><h1>Olá, <?= htmlspecialchars(explode(' ', $user['name'] ?? '')[0] ?: 'você') ?>.</h1><p>Seu acesso está protegido e isolado das outras aplicações desta hospedagem.</p></section><section class="stat-grid"><article><small>Trilhas ativas</small><strong>0</strong></article><article><small>Itens estudados</small><strong>0</strong></article><article><small>Próxima sessão</small><strong>—</strong></article></section></main>
<?php pageFooter();
