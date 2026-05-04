const botaoSubmit = document.querySelector('.submit-email')
const emptyEmail = document.querySelector('.empty-email')
const emailSign = document.querySelector('#emailSign')

const form = document.querySelector('form')

form.addEventListener('submit', (event) => {
    let enviar = true

    if (emailSign.value.trim() === "") {
        emptyEmail.style.display = 'block'
        enviar = false
    }

    if (!enviar) {
        event.preventDefault()
    }
})