<?php
    class Auth {

        private $db;

        public function __construct($conn) {
            $this->db = $conn;
        }

        public function verificar($emailSubmit, $code) {

            $sql = "SELECT codigo FROM userResetCode WHERE email = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$emailSubmit]);

            $dados = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$dados) {
                return false;
            }

            if ($code === $dados['codigo']){
                return true;
            } else {
                return false;
            }
        }
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: mudarsenha.php');
        exit;
    }
    require_once '../config.php';
    session_start();
    $email = $_SESSION['email_reset'];
    $code = trim($_POST['codeSign'] ?? '');

    if ($code === '') {
        header("Location: mudarsenha.php"); // deu algum erro brutal e volta pro login
        exit;
    }

    $conn = getDB();

    $auth = new Auth($conn);
    $user = $auth->verificar($email, $code);

    
    if ($user) { // Tudo certo, continuar para mudarsenha05.php
        $_SESSION['permitido_reset05'] = true;
        header("Location: mudarsenha05.php");
        exit;
    }
    else { // Código errado
        $_SESSION['permitido_reset'] = true;
        header("Location: mudarsenha03.php?erro=1");
        exit;
    }
?>