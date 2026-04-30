<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login</title>
  <link rel="stylesheet" href="../style.css" />
  <script src="../scripts/index.js" type="module" defer></script>
  <script src="login.js" defer></script>
</head>

<body>



  <header id="main-header">
    <?php include __DIR__ . "/../navBar.php"; ?>
  </header>

  <div id="terms-screen">
    <h1>Termos de Serviço</h1>

    <p><strong>Última atualização:</strong> 28/04/2026</p>

    <p>Bem-vindo ao Espaço em Foco. Ao acessar ou usar nossos serviços, você concorda com estes Termos de Serviço. Leia atentamente.</p>

    <h2>1. Aceitação dos Termos</h2>
    <p>Ao acessar ou utilizar o site <strong>espacoemfoco.online</strong>, você declara que leu, compreendeu e concorda com estes Termos. Caso não concorde, não utilize nossos serviços.</p>

    <h2>2. Uso do Site</h2>
    <p>Você concorda em utilizar o site apenas para fins legais e de acordo com estes Termos. É proibido:</p>
    <ul>
      <li> - Violar leis ou regulamentos aplicáveis;</li>
      <li> - Infringir direitos de terceiros;</li>
      <li> - Tentar acessar áreas restritas sem autorização;</li>
      <li> - Introduzir vírus ou códigos maliciosos.</li>
    </ul>

    <h2>3. Cadastro de Usuário</h2>
    <p>Para acessar determinadas funcionalidades, pode ser necessário criar uma conta. Você concorda em:</p>
    <ul>
      <li> - Fornecer informações verdadeiras e atualizadas;</li>
      <li> - Manter a confidencialidade de suas credenciais;</li>
      <li> - Notificar-nos imediatamente sobre uso não autorizado da sua conta.</li>
    </ul>

    <h2>4. Propriedade Intelectual</h2>
    <p>Todo o conteúdo do site (textos, imagens, logos, etc.) é de propriedade de <strong>Espaço em Foco</strong> ou de seus licenciadores e é protegido por leis de propriedade intelectual. É proibido copiar, reproduzir ou distribuir sem autorização.</p>

    <h2>5. Conteúdo do Usuário</h2>
    <p>Ao enviar conteúdo para o site (comentários, uploads, etc.), você concede a <strong>Espaço em Foco</strong> uma licença não exclusiva para usar, reproduzir e exibir esse conteúdo. Você é responsável pelo conteúdo que publica.</p>

    <h2>6. Limitação de Responsabilidade</h2>
    <p>O site é fornecido "como está", sem garantias de qualquer tipo. Não nos responsabilizamos por:</p>
    <ul>
      <li> - Erros ou interrupções no serviço;</li>
      <li> - Perdas ou danos decorrentes do uso do site;</li>
      <li> - Conteúdos de terceiros.</li>
    </ul>

    <h2>7. Links para Terceiros</h2>
    <p>Nosso site pode conter links para sites externos. Não somos responsáveis pelo conteúdo ou práticas desses sites.</p>

    <h2>8. Encerramento</h2>
    <p>Podemos suspender ou encerrar seu acesso ao site a qualquer momento, sem aviso prévio, caso você viole estes Termos.</p>

    <h2>9. Alterações nos Termos</h2>
    <p>Reservamo-nos o direito de modificar estes Termos a qualquer momento. As alterações entram em vigor após publicação no site.</p>

    <h2>10. Contato</h2>
    <p>Se tiver dúvidas sobre estes Termos, entre em contato:</p>
    <ul>
      <li> - <strong>Email:</strong> espacoemfoco509@gmail.com</li>
    </ul>

    <p><strong>Ao utilizar este site, você concorda com estes Termos de Serviço.</strong></p>

    <button class="button terms-screen-button">Fechar</button>
  </div>

  <main class="login-main">
    <section id="sign-up" class="sign-up-section">
      <div id="welcome" class="welcome-panel">
        <div class="welcome-content signup-active">
          <h1>Seja bem-vindo!</h1>
          <h4>
            Estamos muito felizes em ter você aqui <span>😊</span><br />
            Para começar, crie sua conta e aproveite tudo o que o nosso site
            tem a oferecer.<br /><br />
            Já faz parte da nossa comunidade? Então é só fazer login e
            continuar de onde parou!
          </h4>
          <a href="#" class="button login-btn login-link">Fazer Login</a>
        </div>

        <div class="welcome-content login-active">
          <h1>Bem-vindo de volta!</h1>
          <h4>
            Que bom ver você novamente por aqui <span>🚀</span><br />
            Faça login para acessar sua conta e continuar explorando nosso
            conteúdo.<br /><br />
            Ainda não faz parte da nossa comunidade? Venha se juntar a nós!
          </h4>
          <a href="#" class="button login-btn signup-link">Criar Conta</a>
        </div>
      </div>

      <div id="sign-up-form" class="signup-form-panel">
        <div class="form-content signup-active">
          <h2>Crie sua Conta</h2>
          <p>Tem uma conta? <a href="#" class="login-link">Faça Login</a></p>
          <?php
          if (isset($_GET['errocad'])) {
            echo "<p style='color:red;'>Já existe um usuário com esse email!</p>";
          }
          ?>
          <form method="POST" action="cadastro02.php" id="form-signup">
            <div class="name-fields">
              <div class="name">
                <input
                  type="text"
                  id="nameSign"
                  name="nameSign"
                  placeholder="Primeiro Nome" />
                <p class="input-empty empty-name">
                  Esse campo não pode estar vazio!
                </p>
              </div>
              <div class="lastName">
                <input
                  type="text"
                  id="lastNameSign"
                  name="lastNameSign"
                  placeholder="Sobrenome" />
                <p class="input-empty empty-lastname">
                  Esse campo não pode estar vazio!
                </p>
              </div>
            </div>
            <input
              type="email"
              id="emailSign"
              name="emailSign"
              placeholder="Digite seu Email" />
            <p class="input-empty empty-email">
              Esse campo não pode estar vazio!
            </p>
            <input
              type="password"
              id="passwordSign"
              name="passwordSign"
              placeholder="Digite sua Senha" />
            <p class="input-empty empty-password">
              Esse campo não pode estar vazio!
            </p>
            <div class="terms">
              <input type="checkbox" id="terms" />
              <label>Eu aceito os <a id="terms-screen-link">Termos e condições</a></label>
            </div>
            <p class="input-empty empty-terms">
              Você precisa aceitar os termos e condições
            </p>
            <div class="buttons-sign">
              <button type="submit" class="button create-account-btn">
                Criar Conta
              </button>
              <div class="divider">
                <hr />
                <span> Ou se registre com </span>
                <hr />
              </div>
              <div class="social-buttons">
                <button type="button" class="button social-btn google-btn">
                  <img
                    src="loginImg/google-icon.png"
                    alt="Google"
                    class="icon" />
                  Google
                </button>
                <button type="button" class="button social-btn microsoft-btn">
                  <img
                    src="loginImg/microsoft-icon.png"
                    alt="Microsoft"
                    class="icon" />
                  Microsoft
                </button>
              </div>
            </div>
          </form>
        </div>

        <div class="form-content login-active">
          <h2>Faça Login</h2>
          <p>
            Não tem uma conta?
            <a href="#" class="signup-link">Crie uma Conta</a>
          </p>
          <?php
          if (isset($_GET['erro'])) {
            echo "<p style='color:red;'>Email ou senha incorretos!</p>";
          ?>
            <script>
              document.querySelector('.sign-up-section').classList.add('is-login');
            </script>
          <?php
          }
          ?>
          <form method="POST" action="login02.php" id="form-login">
            <input
              type="email"
              id="emailLogin"
              name="emailLogin"
              placeholder="Digite seu Email" />
            <p class="input-empty empty-email-login">
              Esse campo não pode estar vazio!
            </p>
            <input
              type="password"
              id="passwordLogin"
              name="passwordLogin"
              placeholder="Digite sua Senha" />
            <p class="input-empty empty-password-login">
              Esse campo não pode estar vazio!
            </p>
            <div class="terms">
              <input type="checkbox" id="rememberMe" />
              <label for="rememberMe">Lembrar de mim</label>
            </div>
            <div class="buttons-sign">
              <button type="submit" class="button login-account-btn">
                Entrar
              </button>
              <div class="divider">
                <hr />
                <span> Ou faça login com </span>
                <hr />
              </div>
              <div class="social-buttons">
                <button type="button" class="button social-btn google-btn">
                  <img
                    src="loginImg/google-icon.png"
                    alt="Google"
                    class="icon" />
                  Google
                </button>
                <button type="button" class="button social-btn microsoft-btn">
                  <img
                    src="loginImg/microsoft-icon.png"
                    alt="Microsoft"
                    class="icon" />
                  Microsoft
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </section>
  </main>
</body>

</html>