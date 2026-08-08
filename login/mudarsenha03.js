const botaoSubmit = document.querySelector('.submit-code')
const emptyCode = document.querySelector('.empty-email')
const codeSign = document.querySelector('#codeSign')

const form = document.querySelector('form')

form.addEventListener('submit', (event) => {
    let enviar = true

    if (codeSign.value.trim() === "") {
        emptyCode.style.display = 'block'
        enviar = false
    }

    if (!enviar) {
        event.preventDefault()
    }
})