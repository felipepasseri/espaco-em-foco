export default function abrirMenu() {
    const botaoMenu = document.getElementById('hamburger-btn')
    const menuPrincipal = document.getElementById('main-nav-container')

    if (botaoMenu) {
        botaoMenu.addEventListener('click', () => {
            botaoMenu.classList.toggle('open')
            menuPrincipal.classList.toggle('mobile-open')
        })
    }

    window.addEventListener('resize', () => {
        if (window.innerWidth > 425) {
            if (menuPrincipal.classList.contains('mobile-open')) {
                menuPrincipal.classList.remove('mobile-open')
            }
            if (botaoMenu && botaoMenu.classList.contains('open')) {
                botaoMenu.classList.remove('open')
            }
        }
    })
}