export default function mudarAbasTopicos() {
    const botaoMenuTopicos = document.querySelectorAll('.topic-option')

    for (const botao of botaoMenuTopicos) {
        botao.addEventListener('click', (evento) => {
            for (const item of botaoMenuTopicos) {
                item.classList.remove('active')
            }
            botao.classList.add('active')

            const botaoInterno = botao.querySelector('button')
            const tipoCard = botaoInterno.dataset.tipo
            const todasAsListasCards = document.querySelectorAll('.topics-cards-list')

            for (const lista of todasAsListasCards) {
                lista.classList.remove('visible')
            }

            document.querySelector(`.topics-cards-list.${tipoCard}`).classList.add('visible')
        
        })
    }
}