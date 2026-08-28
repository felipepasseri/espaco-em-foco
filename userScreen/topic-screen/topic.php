<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../../login/login.php");
    exit();
}

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../user-functions.php';
$pdo = getDB();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$email = $_SESSION['user'];

// Pega o ID do tópico pela URL
$id_topico = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_topico === 0) {
    header("Location: ../home-user.php");
    exit();
}

try {
    // 1. Busca os dados do Tópico selecionado
    $stmtTopico = $pdo->prepare("SELECT id, tipoTopic, imgCard, nameTopic, descTopic FROM topiccards WHERE id = :id");
    $stmtTopico->execute(['id' => $id_topico]);
    $topico = $stmtTopico->fetch(PDO::FETCH_ASSOC);

    if (!$topico) {
        die("Tópico não encontrado.");
    }

    // Puxa o nome de usuário (para o artigo_completo)
    $userData = getUserData($pdo, $email);
    $nomeUsuario = $userData['nomeDeUsuario'];

    // 2. Busca todos os artigos deste tópico
    $sqlArtigos = "
        SELECT a.id, a.titulo, 
               COALESCE((SELECT SUM(xp_recompensa) FROM quiz_pergunta WHERE id_artigo = a.id), 0) AS xp_recompensa,
               (SELECT COUNT(*) FROM artigo_completo WHERE id_artigo = a.id AND nome_usuario_artigo = :username) AS concluido,
               (SELECT COUNT(*) FROM quiz_pergunta WHERE id_artigo = a.id) AS total_perguntas,
               (SELECT COUNT(DISTINCT id_pergunta) FROM usuario_progresso WHERE id_artigo = a.id AND email_usuario = :email AND status = 'aprovado') AS acertos
        FROM artigo a
        WHERE a.id_topic = :id_topico AND a.avaliacao_adm = 'Aprovado'
        ORDER BY a.id ASC
    ";

    $stmtArtigos = $pdo->prepare($sqlArtigos);
    $stmtArtigos->execute(['email' => $email, 'username' => $nomeUsuario, 'id_topico' => $id_topico]);
    $artigos = $stmtArtigos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Erro ao carregar o tópico: ' . $e->getMessage());
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($topico['nameTopic']) ?> - Espaço em Foco</title>
    <link rel="stylesheet" href="../../global.css" />
    <link rel="stylesheet" href="../../style.css" />
    <link rel="stylesheet" href="topic.css" />
    <script src="../../scripts/index.js" type="module" defer></script>
</head>

<body>
    <header id="main-header">
        <?php include __DIR__ . "/../../navBar.php"; ?>
    </header>

    <main class="topic-page fade-in animate">
        <div class="topic-container">

            <a href="../home-user.php" class="back-link">← Voltar para o Início</a>

            <header class="topic-banner glass-card" style="background-image: linear-gradient(to right, rgba(11, 7, 34, 0.9), rgba(11, 7, 34, 0.4)), url('/<?= htmlspecialchars($topico['imgCard']) ?>');">
                <div class="topic-banner-content">
                    <h1><?= htmlspecialchars($topico['nameTopic']) ?></h1>
                    <p><?= htmlspecialchars($topico['descTopic']) ?></p>
                </div>
            </header>

            <section class="topic-articles-section">
                <h2 class="section-title">Artigos Disponíveis</h2>

                <div class="articles-grid">
                    <?php if (!empty($artigos)): ?>
                        <?php foreach ($artigos as $artigo): ?>
                            <?php 
                                $acertos = (int)$artigo['acertos'];
                                $total = (int)$artigo['total_perguntas'];
                                $passou = ($total > 0 && $acertos >= ceil($total / 2));
                                
                                if ($artigo['concluido']) {
                                    $statusClasse = 'mission-completed';
                                    $badgeHTML = '<span class="badge-done">✔ Concluído</span>';
                                } else if ($passou) {
                                    $statusClasse = 'mission-completed';
                                    $badgeHTML = '<span class="badge-done">✔ ' . $acertos . '/' . $total . ' Acertos</span>';
                                    $cooldownEnd = false;
                                } else {
                                    $cooldownEnd = getArticleCooldown($pdo, $_SESSION['user'], $artigo['id']);
                                    if ($cooldownEnd) {
                                        $statusClasse = 'mission-blocked article-bloqueado';
                                        $badgeHTML = '<span class="badge-pending article-xp" style="background: rgba(255, 51, 102, 0.2); color: #ff3366; border: 1px solid #ff3366;">⏳ Tente novamente</span>';
                                    } else {
                                        $badgeHTML = '<span class="badge-pending">Iniciar</span>';
                                    }
                                }
                            ?>
                            <a href="../article-screen/artigo.php?id=<?= $artigo['id'] ?>" class="article-mission-card <?= $statusClasse ?>" <?= $cooldownEnd ? 'data-cooldown="' . $cooldownEnd . '" data-texto-padrao="' . htmlspecialchars($textoPadrao) . '"' : '' ?>>
                                <div class="mission-info">
                                    <h3 class="mission-title"><?= htmlspecialchars($artigo['titulo']) ?></h3>
                                    <span class="mission-xp">+<?= htmlspecialchars($artigo['xp_recompensa']) ?> XP</span>
                                </div>
                                <div class="mission-status">
                                    <?= $badgeHTML ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>Nenhum artigo encontrado para este tópico no momento. Volte em breve!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </main>

    <?php include_once "../../footer.php" ?>
    
    <script>
    function updateCooldowns() {
      const articles = document.querySelectorAll('.article-mission-card[data-cooldown]');
      const now = Math.floor(Date.now() / 1000);
      
      articles.forEach(article => {
        const cooldownEnd = parseInt(article.getAttribute('data-cooldown'), 10);
        const xpSpan = article.querySelector('.article-xp'); // A tag com o timer
        
        if (cooldownEnd > now) {
          let diff = cooldownEnd - now;
          let days = Math.floor(diff / 86400);
          diff -= days * 86400;
          let hours = Math.floor(diff / 3600);
          diff -= hours * 3600;
          let mins = Math.floor(diff / 60);
          let secs = diff % 60;
          
          let timeString = '';
          if (days > 0) timeString = `${days}d `;
          else if (hours > 0) timeString = `${hours}h `;
          else if (mins > 0) timeString = `${mins}m ${secs}s`;
          else timeString = `${secs}s`;
          
          if (xpSpan) xpSpan.textContent = `⏳ Tente em ${timeString}`;
        } else {
          // Cooldown acabou!
          article.classList.remove('mission-blocked', 'article-bloqueado');
          if (xpSpan) {
              const txtPadrao = article.getAttribute('data-texto-padrao');
              xpSpan.textContent = txtPadrao ? txtPadrao : 'Iniciar';
              xpSpan.style = ''; // Remove inline styles
              xpSpan.classList.remove('article-xp');
          }
          article.removeAttribute('data-cooldown');
        }
      });
    }

    setInterval(updateCooldowns, 1000);
    updateCooldowns();
    </script>
</body>

</html>