<?php

require_once __DIR__ . '/../config.php';

/**
 * Busca o banimento ativo do usuário, se houver.
 * Retorna array associativo ou null.
 */
function obterBanimentoAtivo($email)
{
    if (empty($email)) {
        return null;
    }

    try {
        $pdo = getDB();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Banimento ativo: ativo = 1 E (permanente OU data_fim no futuro)
        $stmt = $pdo->prepare("
            SELECT b.*, u.nome, u.sobrenome, u.nomeDeUsuario
            FROM banimentos b
            LEFT JOIN user u ON u.email = b.email
            WHERE b.email = :email
              AND b.ativo = 1
              AND (b.tipo_banimento = 'permanente' OR b.data_fim > NOW())
            ORDER BY b.data_inicio DESC
            LIMIT 1
        ");
        $stmt->execute(['email' => $email]);
        $banimento = $stmt->fetch(PDO::FETCH_ASSOC);

        return $banimento ?: null;
    } catch (PDOException $e) {
        error_log('Erro ao verificar banimento: ' . $e->getMessage());
        return null;
    }
}

/**
 * Responde via JSON quando a requisição for AJAX/API
 */
function responderBanidoAjax($banimento)
{
    if (!headers_sent()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
    }

    $isPermanente = ($banimento['tipo_banimento'] === 'permanente');
    $mensagem = $isPermanente
        ? 'Sua conta foi banida permanentemente devido a uma violação das regras da comunidade. Entre em contato com a administração para obter mais informações.'
        : 'Sua conta foi temporariamente banida devido a uma violação das regras da comunidade. Seu acesso será restabelecido em ' . date('d/m/Y \à\s H:i', strtotime($banimento['data_fim'])) . '.';

    echo json_encode([
        'sucesso' => false,
        'banido' => true,
        'erro' => 'Conta banida.',
        'mensagem' => $mensagem,
        'motivo' => $banimento['motivo'] ?? null,
        'data_inicio' => $banimento['data_inicio'] ?? null,
        'data_fim' => $banimento['data_fim'] ?? null,
        'tipo_banimento' => $banimento['tipo_banimento'] ?? 'permanente'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Renderiza a tela de banimento em HTML com identidade visual cósmica
 */
function renderizarTelaBanimento($banimento)
{
    if (!headers_sent()) {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
    }

    $isPermanente = ($banimento['tipo_banimento'] === 'permanente');
    $tipoBadge = $isPermanente ? 'Permanente' : 'Temporário';
    $badgeClass = $isPermanente ? 'badge-permanente' : 'badge-temporario';

    $dataInicioFormatada = !empty($banimento['data_inicio'])
        ? date('d/m/Y \à\s H:i', strtotime($banimento['data_inicio']))
        : 'Não informada';

    $dataFimFormatada = (!$isPermanente && !empty($banimento['data_fim']))
        ? date('d/m/Y \à\s H:i', strtotime($banimento['data_fim']))
        : 'Indeterminada (Permanente)';

    $motivo = !empty($banimento['motivo'])
        ? htmlspecialchars($banimento['motivo'])
        : 'Violação das regras e diretrizes da comunidade.';

    $mensagemPrincipal = $isPermanente
        ? 'Sua conta foi banida permanentemente devido a uma violação das regras da comunidade. Entre em contato com a administração para obter mais informações.'
        : 'Sua conta foi temporariamente banida devido a uma violação das regras da comunidade. Seu acesso será restabelecido em <strong>' . $dataFimFormatada . '</strong>.';

    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Conta Banida - Espaço em Foco</title>
        <style>
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }
            body {
                min-height: 100vh;
                background: radial-gradient(circle at 50% 20%, #170d3d 0%, #04001F 80%);
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                position: relative;
                overflow-x: hidden;
            }
            body::before {
                content: '';
                position: absolute;
                top: 0; left: 0; right: 0; bottom: 0;
                background-image: 
                    radial-gradient(1px 1px at 25px 30px, #fff, rgba(0,0,0,0)),
                    radial-gradient(1.5px 1.5px at 75px 120px, #00e5ff, rgba(0,0,0,0)),
                    radial-gradient(1px 1px at 150px 70px, #fff, rgba(0,0,0,0)),
                    radial-gradient(2px 2px at 220px 180px, #ff3366, rgba(0,0,0,0)),
                    radial-gradient(1px 1px at 300px 90px, #FFAE00, rgba(0,0,0,0));
                background-repeat: repeat;
                background-size: 350px 350px;
                opacity: 0.35;
                pointer-events: none;
            }
            .ban-card {
                position: relative;
                width: 100%;
                max-width: 540px;
                background: rgba(11, 7, 34, 0.88);
                backdrop-filter: blur(16px);
                border: 1px solid rgba(255, 51, 102, 0.35);
                border-radius: 20px;
                padding: 36px 30px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8), 0 0 35px rgba(255, 51, 102, 0.2);
                text-align: center;
                animation: fadeIn 0.4s ease-out;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(12px) scale(0.98); }
                to { opacity: 1; transform: translateY(0) scale(1); }
            }
            .ban-icon-container {
                width: 80px;
                height: 80px;
                margin: 0 auto 20px;
                border-radius: 50%;
                background: rgba(255, 51, 102, 0.12);
                border: 2px solid rgba(255, 51, 102, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 38px;
                box-shadow: 0 0 25px rgba(255, 51, 102, 0.35);
            }
            .ban-title {
                font-size: 26px;
                font-weight: 800;
                color: #fff;
                margin-bottom: 8px;
                letter-spacing: 0.5px;
            }
            .ban-badge {
                display: inline-block;
                padding: 4px 14px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.8px;
                margin-bottom: 20px;
            }
            .badge-permanente {
                background: rgba(255, 51, 102, 0.2);
                color: #ff3366;
                border: 1px solid rgba(255, 51, 102, 0.4);
                box-shadow: 0 0 12px rgba(255, 51, 102, 0.3);
            }
            .badge-temporario {
                background: rgba(255, 174, 0, 0.2);
                color: #FFAE00;
                border: 1px solid rgba(255, 174, 0, 0.4);
                box-shadow: 0 0 12px rgba(255, 174, 0, 0.3);
            }
            .ban-message {
                font-size: 15px;
                line-height: 1.6;
                color: #c9c3e2;
                margin-bottom: 24px;
            }
            .ban-details {
                background: rgba(255, 255, 255, 0.03);
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 12px;
                padding: 16px 20px;
                text-align: left;
                margin-bottom: 26px;
                font-size: 13px;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .detail-row {
                display: flex;
                flex-direction: column;
                gap: 3px;
            }
            .detail-label {
                color: #a09bba;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .detail-value {
                color: #fff;
                font-size: 13px;
                word-break: break-word;
            }
            .ban-actions {
                display: flex;
                gap: 12px;
                justify-content: center;
            }
            .btn-action {
                padding: 12px 28px;
                border-radius: 10px;
                font-size: 14px;
                font-weight: 700;
                text-decoration: none;
                cursor: pointer;
                transition: all 0.3s ease;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                border: none;
            }
            .btn-logout {
                background: linear-gradient(135deg, #ff3366 0%, #ba0038 100%);
                color: #fff;
                box-shadow: 0 4px 18px rgba(255, 51, 102, 0.4);
            }
            .btn-logout:hover {
                background: linear-gradient(135deg, #ff4d79 0%, #d1003f 100%);
                transform: translateY(-2px);
                box-shadow: 0 6px 22px rgba(255, 51, 102, 0.6);
            }
        </style>
    </head>
    <body>
        <div class="ban-card">
            <div class="ban-icon-container">
                <?= $isPermanente ? '🚫' : '⏳' ?>
            </div>
            <h1 class="ban-title">Sua conta foi banida</h1>
            <span class="ban-badge <?= $badgeClass ?>"><?= $tipoBadge ?></span>
            
            <p class="ban-message">
                <?= $mensagemPrincipal ?>
            </p>

            <div class="ban-details">
                <div class="detail-row">
                    <span class="detail-label">Motivo do Banimento</span>
                    <span class="detail-value"><?= $motivo ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Data de Aplicação</span>
                    <span class="detail-value"><?= $dataInicioFormatada ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Data de Término</span>
                    <span class="detail-value"><?= $dataFimFormatada ?></span>
                </div>
            </div>

            <div class="ban-actions">
                <a href="/logoff.php" class="btn-action btn-logout">
                    Encerrar Sessão
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Verifica se a requisição atual é assíncrona / API JSON
 */
function isAjaxOuApi()
{
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        return true;
    }
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        return true;
    }
    return false;
}

/**
 * Verifica banimento do usuário. Bloqueia execução se banido e $bloquear = true.
 */
function verificarBanimentoUsuario($email, $bloquear = true)
{
    $banimento = obterBanimentoAtivo($email);
    if ($banimento && $bloquear) {
        if (isAjaxOuApi()) {
            responderBanidoAjax($banimento);
        } else {
            renderizarTelaBanimento($banimento);
        }
    }
    return $banimento;
}

/**
 * Função principal de verificação de usuário com verificação automática de banimento
 */
function verificarUsuario($email, $bloquearSeBanido = true)
{
    require_once __DIR__ . '/../config.php';
    try {
        $pdo = getDB();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare('SELECT codTypeRoles FROM userroles WHERE emailRoles = :email');
        $stmt->execute(['email' => $email]);
        $userRole = $stmt->fetch(PDO::FETCH_ASSOC);

        // Se o usuário não for administrador (codTypeRoles != 1), checa se está banido
        if ($userRole && $userRole['codTypeRoles'] != 1 && $bloquearSeBanido) {
            verificarBanimentoUsuario($email, true);
        }

        return $userRole;
    } catch (PDOException $e) {
        error_log('Erro ao verificar usuário: ' . $e->getMessage());
        return null;
    }
}
