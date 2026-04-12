// scripts/index.js
export default function login() {
    // ---- Lógica de Transição (Slide) ----
    const signUpSection = document.querySelector('.sign-up-section');
    const btnGoLogin = document.querySelectorAll('.login-link');
    const btnGoSignup = document.querySelectorAll('.signup-link');

    btnGoLogin.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            signUpSection.classList.add('is-login');
        });
    });

    btnGoSignup.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            signUpSection.classList.remove('is-login');
        });
    });

    // ---- Lógica de Validação: Cadastro ----
    const nameSign = document.querySelector('#nameSign')
    const lastNameSign = document.querySelector('#lastNameSign')
    const emailSign = document.querySelector('#emailSign')
    const passwordSign = document.querySelector('#passwordSign')
    const terms = document.querySelector('#terms')
    const botaoSign = document.querySelector('.create-account-btn')
    
    // Agrupa todos os avisos de erro de ambos os forms
    const inputEmpty = document.querySelectorAll('.input-empty')
    
    const emptyName = document.querySelector('.empty-name')
    const emptyLastName = document.querySelector('.empty-lastname')
    const emptyEmail = document.querySelector('.empty-email')
    const emptyPassword = document.querySelector('.empty-password')
    const emptyTerms = document.querySelector('.empty-terms')

    botaoSign.addEventListener('click', (event) => {
        let enviar = true
        
        // Esconde os erros apenas do form de cadastro para não interferir
        const signupErrors = document.querySelectorAll('.signup-active .input-empty');
        signupErrors.forEach(input => {
            input.setAttribute('style', 'display: none')
        })

        if (nameSign.value.trim() == "") {
            emptyName.setAttribute('style', 'display: block')
            enviar = false
        }

        if (lastNameSign.value.trim() == "") {
            emptyLastName.setAttribute('style', 'display: block')
            enviar = false
        }
        if (emailSign.value.trim() == "") {
            emptyEmail.setAttribute('style', 'display: block')
            enviar = false
        }
        if (passwordSign.value.trim() == "") {
            emptyPassword.setAttribute('style', 'display: block')
            enviar = false
        }

        if (!terms.checked) {
            emptyTerms.setAttribute('style', 'display: block')
            enviar = false
        }
        
        if(!enviar) {
            event.preventDefault()
        }
    });

    // ---- Lógica de Validação: Login ----
    const emailLogin = document.querySelector('#emailLogin');
    const passwordLogin = document.querySelector('#passwordLogin');
    const btnLogin = document.querySelector('.login-account-btn');
    const emptyEmailLogin = document.querySelector('.empty-email-login');
    const emptyPasswordLogin = document.querySelector('.empty-password-login');

    btnLogin.addEventListener('click', (event) => {
        let enviar = true;
        
        const loginErrors = document.querySelectorAll('.login-active .input-empty');
        loginErrors.forEach(input => {
            input.setAttribute('style', 'display: none');
        });

        if (emailLogin.value.trim() == "") {
            emptyEmailLogin.setAttribute('style', 'display: block');
            enviar = false;
        }
        if (passwordLogin.value.trim() == "") {
            emptyPasswordLogin.setAttribute('style', 'display: block');
            enviar = false;
        }

        if(!enviar) {
            event.preventDefault();
        }
    });
}