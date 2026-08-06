const botaoSubmit = document.querySelector('.submit-password')
const emptypassword = document.querySelector('.empty-password')
const emptypassword2 = document.querySelector('.empty-password2')
const emptypassword3 = document.querySelector('.empty-password3')
const passwordSign = document.querySelector('#passwordSign')
const password2Sign = document.querySelector('#password2Sign')

const form = document.querySelector('form')

form.addEventListener('submit', (event) => {
    let enviar = true

    if (passwordSign.value.trim() === "") {
        emptypassword.style.display = 'block'
        enviar = false
    }
    if (password2Sign.value.trim() === "") {
        emptypassword2.style.display = 'block'
        enviar = false
    }
    if (passwordSign.value.trim() != password2Sign.value.trim()){
        emptypassword3.style.display = 'block'
        enviar = false
    }

    if (!enviar) {
        event.preventDefault()
    }
})