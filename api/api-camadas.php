<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!headers_sent()) {
    header('Content-Type: application/json');
}

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../userScreen/user-functions.php';

$email = $_SESSION['user'];

try {
    $pdo = getDB();

    // XP do usuário autenticado
    $userXp = (int) getUserPoints($pdo, $email);

    // Consulta SQL agregada consolidada das estatísticas das camadas
    // Regras:
    // 1. Somente usuários com email_verified = 1
    // 2. Somente usuários comuns (codTypeRoles = 0 em userroles)
    // 3. EXISTS para evitar duplicações se houver múltiplos registros de papéis
    // 4. COALESCE para tratar usuários sem registro em userpoints como 0 XP
    // 5. Total de usuários consistente para todas as camadas
    // 6. Camada Núcleo (0 XP) contabiliza 100% dos usuários elegíveis
    // 7. NULLIF no denominador para evitar divisão por zero
    $sql = "
        WITH usuarios_elegiveis AS (
            SELECT
                u.email,
                COALESCE(up.userpoints, 0) AS xp
            FROM `user` u
            LEFT JOIN userpoints up
                ON up.emailPoints = u.email
            WHERE u.email_verified = 1
            AND EXISTS (
                SELECT 1
                FROM userroles ur
                WHERE ur.emailRoles = u.email
                AND ur.codTypeRoles = 0
            )
        )
        SELECT
            c.id AS id_camada,
            c.nome_camada,
            c.xp_necessario,
            COUNT(ue.email) AS total_usuarios,
            COALESCE(
                SUM(
                    CASE
                        WHEN ue.xp >= c.xp_necessario THEN 1
                        ELSE 0
                    END
                ), 0
            ) AS usuarios_que_alcancaram,
            COALESCE(
                ROUND(
                    100.0 * SUM(
                        CASE
                            WHEN ue.xp >= c.xp_necessario THEN 1
                            ELSE 0
                        END
                    ) / NULLIF(COUNT(ue.email), 0),
                    1
                ), 0
            ) AS porcentagem
        FROM camadas c
        LEFT JOIN usuarios_elegiveis ue
            ON 1 = 1
        GROUP BY
            c.id,
            c.nome_camada,
            c.xp_necessario
        ORDER BY c.xp_necessario ASC
    ";

    $stmt = $pdo->query($sql);
    $camadasRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Camada atual do usuário
    $camadaAtual = getCamadaPorXP($pdo, $userXp);

    // Identifica próxima camada e estrutura a lista
    $proximaCamada = null;
    $camadas = [];

    foreach ($camadasRaw as $c) {
        $id = (int) $c['id_camada'];
        $xpReq = (int) $c['xp_necessario'];
        $desbloqueada = ($userXp >= $xpReq);
        $isAtual = ($id === (int) $camadaAtual['id_camada']);

        if (!$proximaCamada && $xpReq > $userXp) {
            $proximaCamada = [
                'id_camada' => $id,
                'nome_camada' => $c['nome_camada'],
                'xp_necessario' => $xpReq,
                'xp_faltante' => max(0, $xpReq - $userXp)
            ];
        }

        $camadas[] = [
            'id_camada' => $id,
            'nome_camada' => $c['nome_camada'],
            'xp_necessario' => $xpReq,
            'total_usuarios' => (int) $c['total_usuarios'],
            'usuarios_que_alcancaram' => (int) $c['usuarios_que_alcancaram'],
            'porcentagem' => (float) $c['porcentagem'],
            'desbloqueada' => $desbloqueada,
            'is_atual' => $isAtual
        ];
    }

    // Calcula progresso específico em direção à próxima camada
    $progressoProxima = 100.0;
    if ($proximaCamada) {
        $delta = $proximaCamada['xp_necessario'] - (int) $camadaAtual['xp_necessario'];
        if ($delta > 0) {
            $progresso = (($userXp - (int) $camadaAtual['xp_necessario']) / $delta) * 100.0;
            $progressoProxima = round(max(0.0, min(100.0, $progresso)), 1);
        } else {
            $progressoProxima = 0.0;
        }
        $proximaCamada['progresso_percentual'] = $progressoProxima;
    }

    echo json_encode([
        'success' => true,
        'user_xp' => $userXp,
        'camada_atual' => $camadaAtual,
        'proxima_camada' => $proximaCamada,
        'progresso_proxima' => $progressoProxima,
        'camadas' => $camadas
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno ao consultar camadas'
    ], JSON_UNESCAPED_UNICODE);
}
