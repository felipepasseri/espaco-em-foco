<?php
    class Auth {

        private $db;

        public function __construct($conn) {
            $this->db = $conn;
        }

        public function verificar($emailSubmit, $code) {
            $sql = "UPDATE user SET senha = ? WHERE email = ?";
            $stmt = $this->db->prepare($sql);
            $resultado = $stmt->execute([$code, $emailSubmit]);

            return $resultado;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: mudarsenha.php');
        exit;
    }
    require_once '../config.php';
    session_start();
    $email = $_SESSION['email_reset'];
    $senha = trim($_POST['passwordSign'] ?? '');

    if ($senha === '') {
        header("Location: mudarsenha.php"); // deu algum erro brutal e volta pro login
        exit;
    }
    $hash = password_hash($senha, PASSWORD_DEFAULT);

    $conn = getDB();

    $auth = new Auth($conn);
    $user = $auth->verificar($email, $hash);

    
    if ($user) { // Tudo certo, continuar para mudarsenha05.php
        unset($_SESSION['permitido_reset05']);
        header("Location: ../index.php");
        exit;
    }
    else { // Código errado
        header("Location: mudarsenha05.php?erro=1");
        exit;
    }
?>