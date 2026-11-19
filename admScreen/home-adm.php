<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../login/verify-user.php';
$userroles = verificarUsuario($_SESSION['user']);
if (!$userroles || $userroles['codTypeRoles'] == 0) {
    header("Location: ../userScreen/home-user.php");
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../userScreen/user-functions.php';

$pdo = getDB();

// 1. Dados do Administrador
$adminData = getUserData($pdo, $_SESSION['user']);
if (!$adminData) {
    $adminData = [
        'nome' => 'Admin',
        'sobrenome' => 'Geral',
        'nomeDeUsuario' => 'admin',
        'fotoPerfil' => 'img/user-profile-default.jpg',
        'bannerPerfil' => 'banner-exemplo.jpg'
    ];
}



// 2. Contadores para KPIs
$totalDenunciasPendentes = (int)$pdo->query("SELECT COUNT(*) FROM denuncias WHERE status = 'Em Analise'")->fetchColumn();
$totalBanimentosAtivos = (int)$pdo->query("SELECT COUNT(*) FROM banimentos WHERE ativo = 1 AND (tipo_banimento = 'permanente' OR data_fim > NOW())")->fetchColumn();
$totalPostsPendentes = (int)$pdo->query("SELECT COUNT(*) FROM postagens_forum WHERE avaliacao_adm = 'Em Analise'")->fetchColumn();
$totalArtigosPendentes = (int)$pdo->query("SELECT COUNT(*) FROM artigo WHERE avaliacao_adm = 'Em Analise'")->fetchColumn();
$totalTopicos = (int)$pdo->query("SELECT COUNT(*) FROM topiccards")->fetchColumn();

// 3. Listagem de Denúncias
$stmtDen = $pdo->prepare("SELECT * FROM denuncias ORDER BY id DESC");
$stmtDen->execute();
$denuncias = $stmtDen->fetchAll(PDO::FETCH_ASSOC);

// 4. Listagem de Banimentos
$stmtBan = $pdo->prepare("
    SELECT b.*, u.nome, u.sobrenome, u.nomeDeUsuario, u.fotoPerfil,
           CASE 
               WHEN b.ativo = 0 THEN 'Revogado'
               WHEN b.tipo_banimento = 'temporario' AND b.data_fim <= NOW() THEN 'Expirado'
               ELSE 'Ativo'
           END AS status_calculado
    FROM banimentos b
    LEFT JOIN user u ON u.email = b.email
    ORDER BY b.data_inicio DESC
");
$stmtBan->execute();
$banimentos = $stmtBan->fetchAll(PDO::FETCH_ASSOC);

// 5. Listagem de Solicitações de Posts
$stmtPost = $pdo->prepare("
    SELECT pf.*, u.nome, u.sobrenome, u.fotoPerfil
    FROM postagens_forum pf
    LEFT JOIN user u ON u.nomeDeUsuario = pf.nome_usuario_post
    ORDER BY 
        CASE WHEN pf.avaliacao_adm = 'Em Analise' THEN 0 ELSE 1 END,
        pf.created_at DESC
");
$stmtPost->execute();
$posts = $stmtPost->fetchAll(PDO::FETCH_ASSOC);

// 6. Listagem de Solicitações de Artigos
$stmtArt = $pdo->prepare("
    SELECT a.*, tc.nameTopic as nome_topico
    FROM artigo a
    LEFT JOIN topiccards tc ON tc.id = a.id_topic
    ORDER BY 
        CASE WHEN a.avaliacao_adm = 'Em Analise' THEN 0 ELSE 1 END,
        a.id DESC
");
$stmtArt->execute();
$artigos = $stmtArt->fetchAll(PDO::FETCH_ASSOC);

// 7. Listagem de Tópicos
$stmtTop = $pdo->prepare("SELECT * FROM topiccards ORDER BY id DESC");
$stmtTop->execute();
$topicos = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Espaço em Foco</title>
    <link rel="stylesheet" href="../global.css" />
    <link rel="stylesheet" href="../style.css" />
    <link rel="stylesheet" href="styleadm.css" />
    <script src="../scripts/home-adm.js" defer></script>
</head>

<body>
    <header id="main-header">
        <?php include __DIR__ . "/../navBar.php"; ?>
    </header>

    <main class="home-user-main adm-container">
        <!-- =========================================
             HERO / CABEÇALHO DO ADMINISTRADOR
        ========================================= -->
        <section class="profile-dashboard adm-dashboard-hero">
            <div class="user-info adm-user-info">
                <div class="user-banner">
                    <img src="../<?= !empty($adminData['bannerPerfil']) ? htmlspecialchars($adminData['bannerPerfil']) : 'banner-exemplo.jpg' ?>" alt="Banner do Painel ADM" class="banner-img" />
                </div>

                <div class="user-profile adm-profile-row">
                    <div class="profile-avatar">
                        <img src="../<?= !empty($adminData['fotoPerfil']) ? htmlspecialchars($adminData['fotoPerfil']) : 'img/user-profile-default.jpg' ?>" alt="Avatar Admin" class="avatar-img" />
                    </div>
                    <div class="profile-details">
                        <h1 class="user-name"><?= htmlspecialchars($adminData['nome'] . " " . $adminData['sobrenome']) ?></h1>
                        <span class="user-handle">@<?= htmlspecialchars($adminData['nomeDeUsuario']) ?></span>
                        <div class="adm-badge-wrap">
                            <span class="adm-shield-badge">🛡️ Painel Administrativo</span>
                        </div>
                    </div>
                    <div class="adm-quick-actions">
                        <button type="button" class="btn-adm-primary" id="btn-abrir-banimento-manual">
                            🔨 Banimento Manual
                        </button>
                        <button type="button" class="btn-adm-outline" id="btn-abrir-novo-topico">
                            🪐 Novo Tópico
                        </button>
                        <a href="../userScreen/home-user.php" class="btn-adm-ghost" title="Visualizar área de usuário">
                            👁️ Ver como Usuário
                        </a>
                    </div>
                </div>

                <div class="adm-header-stats">
                    <div class="adm-header-stat-item">
                        <span class="adm-stat-kpi-val"><?= $totalDenunciasPendentes ?></span>
                        <span class="adm-stat-kpi-lbl">Denúncias Pendentes</span>
                    </div>
                    <div class="adm-header-stat-item">
                        <span class="adm-stat-kpi-val"><?= $totalBanimentosAtivos ?></span>
                        <span class="adm-stat-kpi-lbl">Banimentos Ativos</span>
                    </div>
                    <div class="adm-header-stat-item">
                        <span class="adm-stat-kpi-val"><?= $totalPostsPendentes + $totalArtigosPendentes ?></span>
                        <span class="adm-stat-kpi-lbl">Conteúdos Pendentes</span>
                    </div>
                </div>
            </div>

            <!-- CARDS DE ESTATÍSTICA (KPI GRID) -->
            <div class="user-extra-stats adm-extra-stats">
                <div class="extra-stats-grid adm-kpi-grid">
                    <div class="extra-card adm-kpi-card <?= $totalDenunciasPendentes > 0 ? 'kpi-alerta' : '' ?>" data-tab-target="tab-denuncias">
                        <span class="adm-kpi-icon">🚨</span>
                        <span class="extra-title">Denúncias</span>
                        <span class="extra-value"><?= $totalDenunciasPendentes ?></span>
                        <span class="adm-kpi-sub">pendentes</span>
                    </div>

                    <div class="extra-card adm-kpi-card" data-tab-target="tab-banimentos">
                        <span class="adm-kpi-icon">🔨</span>
                        <span class="extra-title">Banimentos</span>
                        <span class="extra-value"><?= $totalBanimentosAtivos ?></span>
                        <span class="adm-kpi-sub">ativos</span>
                    </div>

                    <div class="extra-card adm-kpi-card <?= $totalPostsPendentes > 0 ? 'kpi-alerta' : '' ?>" data-tab-target="tab-posts">
                        <span class="adm-kpi-icon">📝</span>
                        <span class="extra-title">Posts Fórum</span>
                        <span class="extra-value"><?= $totalPostsPendentes ?></span>
                        <span class="adm-kpi-sub">em análise</span>
                    </div>

                    <div class="extra-card adm-kpi-card <?= $totalArtigosPendentes > 0 ? 'kpi-alerta' : '' ?>" data-tab-target="tab-artigos">
                        <span class="adm-kpi-icon">📚</span>
                        <span class="extra-title">Artigos</span>
                        <span class="extra-value"><?= $totalArtigosPendentes ?></span>
                        <span class="adm-kpi-sub">em análise</span>
                    </div>

                    <div class="extra-card adm-kpi-card" data-tab-target="tab-topicos">
                        <span class="adm-kpi-icon">🪐</span>
                        <span class="extra-title">Tópicos</span>
                        <span class="extra-value"><?= $totalTopicos ?></span>
                        <span class="adm-kpi-sub">cadastrados</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- =========================================
             BARRA DE NAVEGAÇÃO DE ABAS ADMINISTRATIVAS
        ========================================= -->
        <nav class="adm-tabs-nav">
            <button class="adm-tab-btn active" data-tab="tab-denuncias">
                🚨 Denúncias
                <?php if ($totalDenunciasPendentes > 0): ?>
                    <span class="tab-badge"><?= $totalDenunciasPendentes ?></span>
                <?php endif; ?>
            </button>
            <button class="adm-tab-btn" data-tab="tab-banimentos">
                🔨 Banimentos
                <span class="tab-badge tab-badge-neutral"><?= count($banimentos) ?></span>
            </button>
            <button class="adm-tab-btn" data-tab="tab-posts">
                📝 Solicitações de Posts
                <?php if ($totalPostsPendentes > 0): ?>
                    <span class="tab-badge"><?= $totalPostsPendentes ?></span>
                <?php endif; ?>
            </button>
            <button class="adm-tab-btn" data-tab="tab-artigos">
                📚 Solicitações de Artigos
                <?php if ($totalArtigosPendentes > 0): ?>
                    <span class="tab-badge"><?= $totalArtigosPendentes ?></span>
                <?php endif; ?>
            </button>
            <button class="adm-tab-btn" data-tab="tab-topicos">
                🪐 Gerenciar Tópicos
                <span class="tab-badge tab-badge-neutral"><?= $totalTopicos ?></span>
            </button>
        </nav>

        <!-- =======================================================
             ABA 1: DENÚNCIAS
        ======================================================= -->
        <section id="tab-denuncias" class="adm-tab-content active">
            <div class="adm-section-header">
                <div>
                    <h2 class="adm-section-title">Gerenciamento de Denúncias</h2>
                    <p class="adm-section-subtitle">Analise infrações reportadas pela comunidade e aplique avisos ou banimentos.</p>
                </div>
                <div class="adm-filter-pills" data-filter-group="denuncias">
                    <button class="filter-pill active" data-filter="all">Todas (<?= count($denuncias) ?>)</button>
                    <button class="filter-pill" data-filter="Em Analise">Em Análise (<?= $totalDenunciasPendentes ?>)</button>
                    <button class="filter-pill" data-filter="Aprovado">Resolvidas</button>
                    <button class="filter-pill" data-filter="Reprovado">Recusadas</button>
                </div>
            </div>

            <?php if (empty($denuncias)): ?>
                <div class="adm-empty-state glass-card">
                    <span class="empty-icon">✨</span>
                    <h3>Nenhuma denúncia registrada</h3>
                    <p>A comunidade está em perfeita harmonia cósmica.</p>
                </div>
            <?php else: ?>
                <div class="adm-cards-grid">
                    <?php foreach ($denuncias as $den): ?>
                        <?php 
                            $statusClass = 'status-analise';
                            if ($den['status'] === 'Aprovado') $statusClass = 'status-aprovado';
                            if ($den['status'] === 'Reprovado') $statusClass = 'status-reprovado';
                        ?>
                        <div class="adm-item-card glass-card" data-status="<?= htmlspecialchars($den['status']) ?>">
                            <div class="adm-card-header">
                                <span class="adm-card-id">#<?= htmlspecialchars($den['id']) ?></span>
                                <span class="adm-status-badge <?= $statusClass ?>">
                                    <?= htmlspecialchars($den['status'] ?: 'Em Análise') ?>
                                </span>
                            </div>

                            <div class="adm-card-body">
                                <div class="adm-info-row">
                                    <span class="adm-info-label">Alvo Denunciado:</span>
                                    <strong class="adm-info-user">@<?= htmlspecialchars($den['nome_usuario_alvo']) ?></strong>
                                    <span class="adm-target-type">(<?= htmlspecialchars($den['tipo_alvo']) ?>)</span>
                                </div>
                                <div class="adm-info-row">
                                    <span class="adm-info-label">Denunciante:</span>
                                    <span>@<?= htmlspecialchars($den['nome_usuario_denunciante']) ?></span>
                                </div>
                                <div class="adm-info-row">
                                    <span class="adm-info-label">Categoria:</span>
                                    <span class="adm-cat-tag"><?= htmlspecialchars($den['categoria_denuncia'] ?: 'Geral') ?></span>
                                </div>
                                <div class="adm-reason-box">
                                    <span class="adm-info-label">Motivo do Relato:</span>
                                    <p><?= nl2br(htmlspecialchars($den['motivo'])) ?></p>
                                </div>

                                <?php if (!empty($den['resolucao_acao'])): ?>
                                    <div class="adm-resolution-box">
                                        <span class="adm-info-label">Resolução: <strong><?= htmlspecialchars($den['resolucao_acao']) ?></strong></span>
                                        <?php if (!empty($den['resolucao_obs'])): ?>
                                            <p class="adm-res-obs">"<?= htmlspecialchars($den['resolucao_obs']) ?>"</p>
                                        <?php endif; ?>
                                        <?php if (!empty($den['data_resolucao'])): ?>
                                            <span class="adm-res-date">Em <?= date('d/m/Y \à\s H:i', strtotime($den['data_resolucao'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="adm-card-footer">
                                <?php if ($den['status'] === 'Em Analise'): ?>
                                    <button class="btn-adm-action btn-analisar-denuncia" data-id="<?= htmlspecialchars($den['id']) ?>">
                                        🔍 Analisar / Resolver
                                    </button>
                                <?php else: ?>
                                    <button class="btn-adm-ghost btn-ver-detalhes-denuncia" data-id="<?= htmlspecialchars($den['id']) ?>">
                                        📋 Ver Detalhes
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- =======================================================
             ABA 2: BANIMENTOS
        ======================================================= -->
        <section id="tab-banimentos" class="adm-tab-content">
            <div class="adm-section-header">
                <div>
                    <h2 class="adm-section-title">Usuários Banidos</h2>
                    <p class="adm-section-subtitle">Histórico e gestão em tempo real de banimentos temporários e permanentes.</p>
                </div>
                <div class="adm-header-actions-group">
                    <button type="button" class="btn-adm-primary" id="btn-novo-banimento-tab">
                        + Aplicar Banimento Manual
                    </button>
                    <div class="adm-filter-pills" data-filter-group="banimentos">
                        <button class="filter-pill active" data-filter="all">Todos (<?= count($banimentos) ?>)</button>
                        <button class="filter-pill" data-filter="Ativo">Ativos (<?= $totalBanimentosAtivos ?>)</button>
                        <button class="filter-pill" data-filter="Expirado">Expirados</button>
                        <button class="filter-pill" data-filter="Revogado">Revogados</button>
                    </div>
                </div>
            </div>

            <?php if (empty($banimentos)): ?>
                <div class="adm-empty-state glass-card">
                    <span class="empty-icon">🕊️</span>
                    <h3>Nenhum banimento registrado</h3>
                    <p>Nenhum usuário foi banido até o momento.</p>
                </div>
            <?php else: ?>
                <div class="adm-cards-grid">
                    <?php foreach ($banimentos as $ban): ?>
                        <?php 
                            $statusCalc = $ban['status_calculado'];
                            $statusClass = 'status-ban-ativo';
                            if ($statusCalc === 'Expirado') $statusClass = 'status-ban-expirado';
                            if ($statusCalc === 'Revogado') $statusClass = 'status-ban-revogado';

                            $isPerm = ($ban['tipo_banimento'] === 'permanente');
                        ?>
                        <div class="adm-item-card glass-card" data-status="<?= htmlspecialchars($statusCalc) ?>">
                            <div class="adm-card-header">
                                <div class="adm-ban-user-header">
                                    <img src="../<?= !empty($ban['fotoPerfil']) ? htmlspecialchars($ban['fotoPerfil']) : 'img/user-profile-default.jpg' ?>" alt="Avatar" class="adm-mini-avatar" />
                                    <div>
                                        <strong class="adm-info-user">@<?= htmlspecialchars($ban['nomeDeUsuario'] ?? 'desconhecido') ?></strong>
                                        <span class="adm-user-email"><?= htmlspecialchars($ban['email']) ?></span>
                                    </div>
                                </div>
                                <span class="adm-status-badge <?= $statusClass ?>">
                                    <?= htmlspecialchars($statusCalc) ?>
                                </span>
                            </div>

                            <div class="adm-card-body">
                                <div class="adm-info-row">
                                    <span class="adm-info-label">Tipo de Banimento:</span>
                                    <span class="adm-ban-tipo-badge <?= $isPerm ? 'tipo-permanente' : 'tipo-temporario' ?>">
                                        <?= $isPerm ? '🔨 Permanente' : '⏱️ Temporário' ?>
                                    </span>
                                </div>
                                <div class="adm-info-row">
                                    <span class="adm-info-label">Data de Início:</span>
                                    <span><?= date('d/m/Y \à\s H:i', strtotime($ban['data_inicio'])) ?></span>
                                </div>
                                <div class="adm-info-row">
                                    <span class="adm-info-label">Data de Término:</span>
                                    <strong>
                                        <?= (!$isPerm && !empty($ban['data_fim'])) ? date('d/m/Y \à\s H:i', strtotime($ban['data_fim'])) : '— Permanente —' ?>
                                    </strong>
                                </div>
                                <div class="adm-reason-box">
                                    <span class="adm-info-label">Motivo Registrado:</span>
                                    <p><?= htmlspecialchars($ban['motivo'] ?: 'Nenhum motivo detalhado informado.') ?></p>
                                </div>
                                <div class="adm-info-meta">
                                    <span>Admin: <strong><?= htmlspecialchars($ban['admin_responsavel'] ?: 'Sistema') ?></strong></span>
                                    <?php if (!empty($ban['id_denuncia'])): ?>
                                        <span>• Via Denúncia #<?= htmlspecialchars($ban['id_denuncia']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="adm-card-footer">
                                <?php if ($statusCalc === 'Ativo'): ?>
                                    <button class="btn-adm-danger btn-revogar-banimento" data-id="<?= htmlspecialchars($ban['id']) ?>" data-user="@<?= htmlspecialchars($ban['nomeDeUsuario'] ?? $ban['email']) ?>">
                                        🔓 Revogar Banimento (Desbanir)
                                    </button>
                                <?php else: ?>
                                    <span class="adm-footer-note">Acesso liberado ao usuário</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- =======================================================
             ABA 3: SOLICITAÇÕES DE POSTS
        ======================================================= -->
        <section id="tab-posts" class="adm-tab-content">
            <div class="adm-section-header">
                <div>
                    <h2 class="adm-section-title">Moderação de Posts do Fórum</h2>
                    <p class="adm-section-subtitle">Avalie as postagens enviadas pela comunidade antes de serem publicadas.</p>
                </div>
                <div class="adm-filter-pills" data-filter-group="posts">
                    <button class="filter-pill active" data-filter="all">Todos (<?= count($posts) ?>)</button>
                    <button class="filter-pill" data-filter="Em Analise">Em Análise (<?= $totalPostsPendentes ?>)</button>
                    <button class="filter-pill" data-filter="Aprovado">Aprovados</button>
                    <button class="filter-pill" data-filter="Reprovado">Reprovados</button>
                </div>
            </div>

            <?php if (empty($posts)): ?>
                <div class="adm-empty-state glass-card">
                    <span class="empty-icon">📝</span>
                    <h3>Nenhum post no fórum</h3>
                    <p>Nenhuma solicitação de publicação de post encontrada.</p>
                </div>
            <?php else: ?>
                <div class="adm-cards-grid">
                    <?php foreach ($posts as $post): ?>
                        <?php 
                            $statusClass = 'status-analise';
                            if ($post['avaliacao_adm'] === 'Aprovado') $statusClass = 'status-aprovado';
                            if ($post['avaliacao_adm'] === 'Reprovado') $statusClass = 'status-reprovado';
                        ?>
                        <div class="adm-item-card glass-card" data-status="<?= htmlspecialchars($post['avaliacao_adm']) ?>">
                            <div class="adm-card-header">
                                <span class="adm-post-topic-tag">🪐 <?= htmlspecialchars($post['topic_post']) ?></span>
                                <span class="adm-status-badge <?= $statusClass ?>">
                                    <?= htmlspecialchars($post['avaliacao_adm']) ?>
                                </span>
                            </div>

                            <div class="adm-card-body">
                                <h3 class="adm-post-title"><?= htmlspecialchars($post['titulo_post']) ?></h3>
                                <div class="adm-info-row">
                                    <span class="adm-info-label">Autor:</span>
                                    <strong class="adm-info-user">@<?= htmlspecialchars($post['nome_usuario_post']) ?></strong>
                                </div>
                                <div class="adm-post-preview">
                                    <?= strip_tags(mb_strimwidth($post['desc_post'], 0, 180, '...')) ?>
                                </div>
                                <span class="adm-post-date">Enviado em <?= date('d/m/Y \à\s H:i', strtotime($post['created_at'])) ?></span>
                            </div>

                            <div class="adm-card-footer">
                                <button class="btn-adm-ghost btn-ver-conteudo-post" 
                                    data-id="<?= htmlspecialchars($post['id']) ?>"
                                    data-titulo="<?= htmlspecialchars($post['titulo_post']) ?>"
                                    data-autor="<?= htmlspecialchars($post['nome_usuario_post']) ?>"
                                    data-topico="<?= htmlspecialchars($post['topic_post']) ?>"
                                    data-conteudo="<?= htmlspecialchars($post['desc_post']) ?>">
                                    👁️ Ver Completo
                                </button>
                                <?php if ($post['avaliacao_adm'] === 'Em Analise'): ?>
                                    <button class="btn-adm-success btn-aprovar-post" data-id="<?= htmlspecialchars($post['id']) ?>">
                                        ✔ Aprovar
                                    </button>
                                    <button class="btn-adm-danger btn-reprovar-post" data-id="<?= htmlspecialchars($post['id']) ?>">
                                        ✖ Reprovar
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- =======================================================
             ABA 4: SOLICITAÇÕES DE ARTIGOS
        ======================================================= -->
        <section id="tab-artigos" class="adm-tab-content">
            <div class="adm-section-header">
                <div>
                    <h2 class="adm-section-title">Moderação de Artigos Interativos</h2>
                    <p class="adm-section-subtitle">Avalie os artigos interativos submetidos para o catálogo educativo.</p>
                </div>
                <div class="adm-filter-pills" data-filter-group="artigos">
                    <button class="filter-pill active" data-filter="all">Todos (<?= count($artigos) ?>)</button>
                    <button class="filter-pill" data-filter="Em Analise">Em Análise (<?= $totalArtigosPendentes ?>)</button>
                    <button class="filter-pill" data-filter="Aprovado">Aprovados</button>
                    <button class="filter-pill" data-filter="Reprovado">Reprovados</button>
                </div>
            </div>

            <?php if (empty($artigos)): ?>
                <div class="adm-empty-state glass-card">
                    <span class="empty-icon">📚</span>
                    <h3>Nenhum artigo encontrado</h3>
                    <p>Não há solicitações de artigos no momento.</p>
                </div>
            <?php else: ?>
                <div class="adm-cards-grid">
                    <?php foreach ($artigos as $art): ?>
                        <?php 
                            $statusClass = 'status-analise';
                            if ($art['avaliacao_adm'] === 'Aprovado') $statusClass = 'status-aprovado';
                            if ($art['avaliacao_adm'] === 'Reprovado') $statusClass = 'status-reprovado';
                        ?>
                        <div class="adm-item-card glass-card" data-status="<?= htmlspecialchars($art['avaliacao_adm']) ?>">
                            <div class="adm-card-header">
                                <span class="adm-post-topic-tag">📖 <?= htmlspecialchars($art['nome_topico'] ?: 'Tópico #' . $art['id_topic']) ?></span>
                                <span class="adm-status-badge <?= $statusClass ?>">
                                    <?= htmlspecialchars($art['avaliacao_adm']) ?>
                                </span>
                            </div>

                            <div class="adm-card-body">
                                <h3 class="adm-post-title"><?= htmlspecialchars($art['titulo']) ?></h3>
                                <div class="adm-post-preview">
                                    <?= strip_tags(mb_strimwidth($art['conteudo'], 0, 200, '...')) ?>
                                </div>
                            </div>

                            <div class="adm-card-footer">
                                <button class="btn-adm-ghost btn-ver-conteudo-artigo" 
                                    data-id="<?= htmlspecialchars($art['id']) ?>"
                                    data-titulo="<?= htmlspecialchars($art['titulo']) ?>"
                                    data-topico="<?= htmlspecialchars($art['nome_topico'] ?: 'Tópico #' . $art['id_topic']) ?>"
                                    data-conteudo="<?= htmlspecialchars($art['conteudo']) ?>">
                                    👁️ Ler Artigo
                                </button>
                                <?php if ($art['avaliacao_adm'] === 'Em Analise'): ?>
                                    <button class="btn-adm-success btn-aprovar-artigo" data-id="<?= htmlspecialchars($art['id']) ?>">
                                        ✔ Aprovar
                                    </button>
                                    <button class="btn-adm-danger btn-reprovar-artigo" data-id="<?= htmlspecialchars($art['id']) ?>">
                                        ✖ Reprovar
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- =======================================================
             ABA 5: GERENCIAR TÓPICOS
        ======================================================= -->
        <section id="tab-topicos" class="adm-tab-content">
            <div class="adm-section-header">
                <div>
                    <h2 class="adm-section-title">Gerenciamento de Tópicos</h2>
                    <p class="adm-section-subtitle">Crie, edite e organize os cards temáticos de astronomia da plataforma.</p>
                </div>
                <div>
                    <button type="button" class="btn-adm-primary" id="btn-novo-topico-tab">
                        + Adicionar Novo Tópico
                    </button>
                </div>
            </div>

            <?php if (empty($topicos)): ?>
                <div class="adm-empty-state glass-card">
                    <span class="empty-icon">🪐</span>
                    <h3>Nenhum tópico cadastrado</h3>
                    <p>Cadastre tópicos para que os artigos possam ser organizados.</p>
                </div>
            <?php else: ?>
                <div class="adm-topics-grid">
                    <?php foreach ($topicos as $top): ?>
                        <div class="adm-topic-card glass-card">
                            <div class="adm-topic-img-wrap" style="background-image: url('../<?= htmlspecialchars($top['imgCard']) ?>');">
                                <span class="adm-topic-cat-badge"><?= htmlspecialchars($top['tipoTopic']) ?></span>
                            </div>
                            <div class="adm-topic-body">
                                <h3><?= htmlspecialchars($top['nameTopic']) ?></h3>
                                <p><?= htmlspecialchars($top['descTopic']) ?></p>
                            </div>
                            <div class="adm-topic-actions">
                                <a href="editaCard.php?id=<?= htmlspecialchars($top['id']) ?>" class="btn-adm-outline">
                                    ✏️ Editar
                                </a>
                                <button class="btn-adm-danger btn-excluir-topico" data-id="<?= htmlspecialchars($top['id']) ?>" data-nome="<?= htmlspecialchars($top['nameTopic']) ?>">
                                    🗑️ Excluir
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- =======================================================
         MODAL 1: ANÁLISE DE DENÚNCIA
    ======================================================= -->
    <div id="modalDenuncia" class="modal-overlay hidden">
        <div class="modal-content glass-card adm-modal-box">
            <div class="modal-header adm-modal-header">
                <div>
                    <h3 id="modal-denuncia-header-title">Analisar Denúncia</h3>
                    <span class="adm-modal-subtitle">Revise os detalhes e decida a ação cabível</span>
                </div>
                <button class="close-btn" id="fecharModalDenuncia" aria-label="Fechar">&times;</button>
            </div>

            <div class="modal-body adm-modal-body">
                <div class="adm-modal-details-grid">
                    <div class="adm-detail-box">
                        <span class="detail-label">Usuário Denunciado:</span>
                        <strong class="adm-info-user" id="modal-denunciado-val">...</strong>
                        <span class="detail-sub" id="modal-denunciado-email">...</span>
                    </div>
                    <div class="adm-detail-box">
                        <span class="detail-label">Denunciante:</span>
                        <span id="modal-denunciante-val">...</span>
                    </div>
                    <div class="adm-detail-box">
                        <span class="detail-label">Categoria:</span>
                        <span class="adm-cat-tag" id="modal-categoria-val">...</span>
                    </div>
                    <div class="adm-detail-box">
                        <span class="detail-label">Tipo do Alvo:</span>
                        <span id="modal-tipoalvo-val">...</span>
                    </div>
                </div>

                <div class="adm-reason-box adm-modal-reason">
                    <span class="detail-label">Motivo Relatado pelo Usuário:</span>
                    <p id="modal-motivo-val">...</p>
                </div>
            </div>

            <div class="adm-modal-footer">
                <button type="button" class="btn-adm-danger" id="btn-recusar-denuncia">
                    ✖ Recusar Denúncia
                </button>
                <button type="button" class="btn-adm-primary" id="btn-abrir-resolucao">
                    ⚖️ Resolver Denúncia
                </button>
            </div>
        </div>
    </div>

    <!-- =======================================================
         MODAL 2: RESOLUÇÃO DE DENÚNCIA
    ======================================================= -->
    <div id="modalResolucao" class="modal-overlay hidden">
        <div class="modal-content glass-card adm-modal-box">
            <div class="modal-header adm-modal-header">
                <div>
                    <h3>Resolver Denúncia #<span id="res-modal-id"></span></h3>
                    <span class="adm-modal-subtitle">Escolha uma ação disciplinar para o usuário <strong id="res-target-user">@...</strong></span>
                </div>
                <button class="close-btn" id="fecharModalResolucao" aria-label="Fechar">&times;</button>
            </div>

            <div class="modal-body adm-modal-body">
                <span class="detail-label" style="margin-bottom: 10px; display: block;">Selecione a Ação a ser Executada:</span>

                <div class="adm-radio-options">
                    <!-- Opção 1: Aviso por E-mail -->
                    <label class="adm-radio-card">
                        <input type="radio" name="acaoResolucao" value="aviso_email" checked />
                        <div class="radio-card-content">
                            <div class="radio-header">
                                <span class="radio-icon">✉️</span>
                                <strong>Opção 1 — Enviar aviso por e-mail</strong>
                            </div>
                            <p>Envia uma advertência oficial orientando o usuário sobre a conduta. <strong>Não aplica banimento.</strong></p>
                        </div>
                    </label>

                    <!-- Opção 2: Banimento Permanente -->
                    <label class="adm-radio-card">
                        <input type="radio" name="acaoResolucao" value="banir_permanente" />
                        <div class="radio-card-content">
                            <div class="radio-header">
                                <span class="radio-icon">🔨</span>
                                <strong>Opção 2 — Banimento permanente</strong>
                            </div>
                            <p>Suspende a conta indefinidamente sem data de término. O acesso só poderá ser restaurado por um administrador.</p>
                        </div>
                    </label>

                    <!-- Opção 3: Banimento Personalizado -->
                    <label class="adm-radio-card">
                        <input type="radio" name="acaoResolucao" value="banir_personalizado" id="radio-ban-personalizado" />
                        <div class="radio-card-content">
                            <div class="radio-header">
                                <span class="radio-icon">⏱️</span>
                                <strong>Opção 3 — Banimento personalizado</strong>
                            </div>
                            <p>Define uma data específica de término. Após o período, <strong>o usuário recupera o acesso automaticamente</strong>.</p>
                            
                            <!-- Campo dinâmico de data -->
                            <div id="campo-data-personalizada" class="campo-data-ban hidden">
                                <label for="dataFimResolucao" class="detail-label">Data e Horário de Término (Desbanimento Automático):</label>
                                <input type="datetime-local" id="dataFimResolucao" class="adm-input" />
                            </div>
                        </div>
                    </label>
                </div>

                <div class="adm-form-group" style="margin-top: 18px;">
                    <label for="observacaoResolucao" class="detail-label">Observação / Justificativa da Decisão (Obrigatório):</label>
                    <textarea id="observacaoResolucao" class="adm-textarea" rows="4" placeholder="Explique o motivo da decisão tomada. Esse texto será registrado no sistema e enviado ao usuário..."></textarea>
                </div>
            </div>

            <div class="adm-modal-footer">
                <button type="button" class="btn-adm-ghost" id="btn-cancelar-resolucao">Cancelar</button>
                <button type="button" class="btn-adm-primary" id="btn-confirmar-resolucao">Confirmar e Aplicar</button>
            </div>
        </div>
    </div>

    <!-- =======================================================
         MODAL 3: BANIMENTO MANUAL DIRETO
    ======================================================= -->
    <div id="modalBanimentoManual" class="modal-overlay hidden">
        <div class="modal-content glass-card adm-modal-box">
            <div class="modal-header adm-modal-header">
                <div>
                    <h3>Aplicar Banimento Manual</h3>
                    <span class="adm-modal-subtitle">Aplique uma punição direta a qualquer conta de usuário</span>
                </div>
                <button class="close-btn" id="fecharModalBanManual" aria-label="Fechar">&times;</button>
            </div>

            <div class="modal-body adm-modal-body">
                <div class="adm-form-group">
                    <label for="manualTargetUser" class="detail-label">Usuário Alvo (E-mail ou @nomeDeUsuario):</label>
                    <input type="text" id="manualTargetUser" class="adm-input" placeholder="ex: @usuario ou usuario@email.com" />
                </div>

                <div class="adm-radio-options" style="margin-top: 14px;">
                    <label class="adm-radio-card">
                        <input type="radio" name="manualTipoBan" value="permanente" checked />
                        <div class="radio-card-content">
                            <strong>🔨 Banimento Permanente</strong>
                            <p>Sem data de expiração definida.</p>
                        </div>
                    </label>

                    <label class="adm-radio-card">
                        <input type="radio" name="manualTipoBan" value="temporario" id="radio-manual-temp" />
                        <div class="radio-card-content">
                            <strong>⏱️ Banimento Temporário</strong>
                            <p>Expira automaticamente na data selecionada.</p>
                            <div id="campo-data-manual" class="campo-data-ban hidden">
                                <label for="manualDataFim" class="detail-label">Data e Hora de Desbanimento:</label>
                                <input type="datetime-local" id="manualDataFim" class="adm-input" />
                            </div>
                        </div>
                    </label>
                </div>

                <div class="adm-form-group" style="margin-top: 14px;">
                    <label for="manualMotivo" class="detail-label">Motivo / Justificativa:</label>
                    <textarea id="manualMotivo" class="adm-textarea" rows="3" placeholder="Descreva a infração cometida pelo usuário..."></textarea>
                </div>
            </div>

            <div class="adm-modal-footer">
                <button type="button" class="btn-adm-ghost" id="btn-cancelar-ban-manual">Cancelar</button>
                <button type="button" class="btn-adm-danger" id="btn-confirmar-ban-manual">Aplicar Banimento</button>
            </div>
        </div>
    </div>

    <!-- =======================================================
         MODAL 4: VISUALIZAÇÃO DE CONTEÚDO (POST/ARTIGO)
    ======================================================= -->
    <div id="modalVisualizacao" class="modal-overlay hidden">
        <div class="modal-content glass-card adm-modal-box">
            <div class="modal-header adm-modal-header">
                <div>
                    <h3 id="vis-modal-title">Visualização</h3>
                    <span class="adm-modal-subtitle" id="vis-modal-meta">...</span>
                </div>
                <button class="close-btn" id="fecharModalVis" aria-label="Fechar">&times;</button>
            </div>

            <div class="modal-body adm-modal-body">
                <div id="vis-modal-body-content" class="adm-full-content-box">
                    ...
                </div>
            </div>

            <div class="adm-modal-footer">
                <button type="button" class="btn-adm-ghost" id="btn-fechar-vis">Fechar</button>
            </div>
        </div>
    </div>

    <!-- =======================================================
         MODAL 5: NOVO TÓPICO
    ======================================================= -->
    <div id="modalNovoTopico" class="modal-overlay hidden">
        <div class="modal-content glass-card adm-modal-box">
            <div class="modal-header adm-modal-header">
                <div>
                    <h3>Cadastrar Novo Tópico</h3>
                    <span class="adm-modal-subtitle">Adicione uma nova categoria de exploração</span>
                </div>
                <button class="close-btn" id="fecharModalNovoTopico" aria-label="Fechar">&times;</button>
            </div>

            <form action="adicionaCard2.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="redirect" value="home-adm.php" />
                <div class="modal-body adm-modal-body">
                    <div class="adm-form-group">
                        <label for="topicImage" class="detail-label">Imagem de Capa (Arquivo):</label>
                        <input type="file" id="topicImage" name="image" class="adm-input" accept="image/*" required />
                    </div>

                    <div class="adm-form-group">
                        <label for="topicTitle" class="detail-label">Título do Tópico:</label>
                        <input type="text" id="topicTitle" name="title" class="adm-input" placeholder="ex: Buracos Negros, Marte, etc." required />
                    </div>

                    <div class="adm-form-group">
                        <label for="topicType" class="detail-label">Categoria do Tópico:</label>
                        <select id="topicType" name="type" class="adm-input" required>
                            <option value="planets">🪐 Planetas (Planets)</option>
                            <option value="stars">⭐ Estrelas (Stars)</option>
                            <option value="galaxies">🚀 Galáxias (Galaxies)</option>
                            <option value="cosmology">💥 Cosmologia (Cosmology)</option>
                            <option value="other">🔭 Outros (Other)</option>
                        </select>
                    </div>

                    <div class="adm-form-group">
                        <label for="topicDesc" class="detail-label">Descrição Resumida:</label>
                        <textarea id="topicDesc" name="description" class="adm-textarea" rows="3" placeholder="Breve introdução sobre o tema..." required></textarea>
                    </div>
                </div>

                <div class="adm-modal-footer">
                    <button type="button" class="btn-adm-ghost" id="btn-cancelar-novo-topico">Cancelar</button>
                    <button type="submit" class="btn-adm-primary">Salvar Tópico</button>
                </div>
            </form>
        </div>
    </div>

    <?php include_once "../footer.php"; ?>
</body>

</html>
