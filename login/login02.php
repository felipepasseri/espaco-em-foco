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

        $sql = "SELECT email, senha, email_verified, nome, sobrenome, nomeDeUsuario, fotoPerfil, bannerPerfil FROM user WHERE email = ?";
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
            if ($dados['email_verified'] == 0) {
                return 'not_verified';
            }

            // Geração de dados faltantes (se o usuário for antigo e estiver com campos nulos)
            if (is_null($dados['nomeDeUsuario']) || is_null($dados['fotoPerfil']) || is_null($dados['bannerPerfil'])) {
                $updFields = [];
                $params = [];
                
                if (is_null($dados['nomeDeUsuario'])) {
                    $nomeBase = strtolower(trim($dados['nome'] . $dados['sobrenome']));
                    $nomeBase = preg_replace('/[^a-z0-9]/', '', $nomeBase);
                    
                    $nicknameUnico = false;
                    $nickname = $nomeBase;
                    while (!$nicknameUnico) {
                        $stmtCheck = $this->db->prepare("SELECT email FROM user WHERE nomeDeUsuario = :nickname");
                        $stmtCheck->bindValue(':nickname', $nickname);
                        $stmtCheck->execute();
                        if ($stmtCheck->rowCount() == 0) {
                            $nicknameUnico = true;
                        } else {
                            $nickname = $nomeBase . rand(10, 9999);
                        }
                    }
                    $updFields[] = "nomeDeUsuario = :nickname";
                    $params[':nickname'] = $nickname;
                }
                
                if (is_null($dados['fotoPerfil'])) {
                    $updFields[] = "fotoPerfil = :foto";
                    $params[':foto'] = 'img/user-profile-default.jpg';
                }
                
                if (is_null($dados['bannerPerfil'])) {
                    $updFields[] = "bannerPerfil = :banner";
                    $params[':banner'] = 'img/banner-default.jpg'; 
                }
                
                if (count($updFields) > 0) {
                    $sqlUpd = "UPDATE user SET " . implode(', ', $updFields) . " WHERE email = :email";
                    $params[':email'] = $dados['email'];
                    $stmtUpd = $this->db->prepare($sqlUpd);
                    $stmtUpd->execute($params);
                }
            }

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

if ($user === 'not_verified') {
    $_SESSION['email_verificacao'] = $email;
    ob_clean();
    header("Location: login.php?erro_verif=1");
    exit;
} elseif ($user) {
    session_regenerate_id(true);
    $_SESSION['user'] = $user->getEmail();
    require_once "verify-user.php";
    $userroles = verificarUsuario($_SESSION['user']);
    if ($userroles['codTypeRoles'] == 0) {
        header("Location: ../userScreen/home-user.php");
    } else if ($userroles['codTypeRoles'] == 1) {
        header('Location: ../admScreen/home-adm.php');
    }
    exit;
} else {
    ob_clean();
    header("Location: login.php?erro=1");
    exit;
}
