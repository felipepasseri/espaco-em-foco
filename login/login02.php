<?php
class User
{
    private $email;
    private $senha;

    public function __construct($email, $senha)
    {
        $this->email = $email;
        $this->senha = $senha;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getSenha()
    {
        return $this->senha;
    }
}

class Auth
{

    private $db;

    public function __construct($conn)
    {
        $this->db = $conn;
    }

    public function login($emailLogin, $passwordLogin)
    {

        $sql = "SELECT email, senha FROM user WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$emailLogin]);

        $dados = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$dados) {
            return false;
        }

        $user = new User(
            $dados['email'],
            $dados['senha']
        );

        if (password_verify($passwordLogin, $user->getSenha())) {
            return $user;
        } else {
            return false;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}
require_once 'cryp2graph2.php';
require_once '../config.php';
session_start();

$email = trim($_POST['emailLogin'] ?? '');
$senha = $_POST['passwordLogin'] ?? '';

if ($email === '' || $senha === '') {
    header("Location: login.php"); // deu algum erro brutal e volta pro login
    exit;
}

$conn = getDB();

$auth = new Auth($conn);
$user = $auth->login($email, $senha);

if ($user) {
    session_regenerate_id(true);
    $_SESSION['user'] = $user->getEmail();
    require_once "verify-user.php";
    $userRoles = verificarUsuario($_SESSION['user']);
    if ($userRoles['codTypeRoles'] == 0) {
        header("Location: ../userScreen/home-user.php");
    } else if ($userRoles['codTypeRoles'] == 1) {
        header('Location: ../admScreen/home-adm.php');
    }
    exit;
} else {
    header("Location: login.php?erro=1");
    exit;
}
