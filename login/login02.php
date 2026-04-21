<?php
	class Database {
        private $conn;

        public function __construct() {
            require("BDconn.php");
            $this->conn = $BDconn;
        }

        public function getConnection() {
            return $this->conn;
        }
    }
    class User {
        private $email;
        private $senha;
        private $errosLogin;

        public function __construct($email, $senha, $errosLogin) {
            $this->email = $email;
            $this->senha = $senha;
            $this->errosLogin = $errosLogin;
        }

        public function getEmail() {
            return $this->email;
        }

        public function getSenha() {
            return $this->senha;
        }

        public function getErrosLogin() {
            return $this->errosLogin;
        }

        public function incrementarErro() {
            $this->errosLogin++;
        }

        public function resetarErros() {
            $this->errosLogin = 0;
        }
    }

    class Auth {
        private $db;

        public function __construct($conn) {
            $this->db = $conn;
        }

        public function login($emailLogin, $passwordLogin) {
            $sql = "SELECT email, senha, errosLogin FROM user WHERE email = ?";
            $stmt = mysqli_prepare($this->db, $sql);

            mysqli_stmt_bind_param($stmt, "s", $emailLogin);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $email, $senhaCAD, $errosLogin);

            if (!mysqli_stmt_fetch($stmt)) {
                return false;
            }
            mysqli_stmt_close($stmt);
            $user = new User($email, $senhaCAD, $errosLogin);

            require("cryp2graph2.php");

            if (ChecaSenha($passwordLogin, $user->getSenha())) {
                $user->resetarErros();
                $this->atualizarErros($user);
                return $user;
            } else {
                $user->incrementarErro();
                $this->atualizarErros($user);
                return false;
            }
        }

        private function atualizarErros($user) {
            $email = $user->getEmail();
            $erros = $user->getErrosLogin();

            $sql = "UPDATE user SET errosLogin = ? WHERE email = ?";
            $stmt = mysqli_prepare($this->db, $sql);

            mysqli_stmt_bind_param($stmt, "is", $erros, $email);
            mysqli_stmt_execute($stmt);
        }
    }

    session_start();

    $email = $_POST['emailLogin'];
    $senha = $_POST['passwordLogin'];

    $db = new Database();
    $auth = new Auth($db->getConnection());

    $user = $auth->login($email, $senha);

    if ($user) {
        $_SESSION['user'] = $user->getEmail();
        header("Location: ../index.html");
    } else {
        echo "Login ou senha errados";
    }
?>