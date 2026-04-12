export default function login() {
    const nameSign = document.querySelector('#nameSign')
    const lastNameSign = document.querySelector('#lastNameSign')
    const emailSign = document.querySelector('#emailSign')
    const passwordSign = document.querySelector('#passwordSign')
    const terms = document.querySelector('#terms')
    const botaoSign = document.querySelector('.create-account-btn')
    const inputEmpty = document.querySelectorAll('.input-empty')
    const emptyName = document.querySelector('.empty-name')
    const emptyLastName = document.querySelector('.empty-lastname')
    const emptyEmail = document.querySelector('.empty-email')
    const emptyPassword = document.querySelector('.empty-password')
    const emptyTerms = document.querySelector('.empty-terms')
    botaoSign.addEventListener('click', (event) => {
        let enviar = true
        inputEmpty.forEach(input => {
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
    })
}