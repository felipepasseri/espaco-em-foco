export default function abrirMenu() {
    const botaoMenu = document.getElementById('menu-header')
    const menuPrincipal = document.getElementById('main-nav-container')
    const botaoLogin = document.getElementById('login-container')
    botaoMenu.addEventListener('click', () => {
        if (botaoMenu.checked) {
            menuPrincipal.style.display = 'block'
            botaoLogin.style.display = 'block'
        }
        else {
            menuPrincipal.style.display = 'none'
            botaoLogin.style.display = 'none'
        }
    })
}