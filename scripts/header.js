export default function abrirMenu() {
    const botaoMenu = document.getElementById('menu-header')
    const menuPrincipal = document.getElementById('main-nav-container')
    const botaoLogin = document.getElementById('login-container')
    botaoMenu.addEventListener('click', () => {
        if (window.innerWidth <= 425) {
            if (botaoMenu.checked) {
                menuPrincipal.style.display = 'block'
                botaoLogin.style.display = 'block'
            }
            else {
                menuPrincipal.style.display = 'none'
                botaoLogin.style.display = 'none'
            }
        }
        else {
            return
        }
    })
    window.addEventListener('resize', () => {
        if (window.innerWidth > 425) {
            menuPrincipal.style.display = 'flex'
            botaoLogin.style.display = 'flex'
        }
        else {
            menuPrincipal.style.display = 'none'
            botaoLogin.style.display = 'none'
        }
    })
}