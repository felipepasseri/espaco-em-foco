 const quizForm = document.getElementById('quiz-form');
 const timerElement = document.getElementById('cooldown-timer');
    const feedbackMsg = document.getElementById('quiz-feedback');
if (quizForm) {
    quizForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const tipo = document.getElementById('tipo_pergunta').value;
        const dados = {
            pergunta_id: document.getElementById('pergunta_id').value,
            artigo_id: document.getElementById('artigo_id').value,
            resposta: tipo === 'lacuna' ? document.getElementById('lacuna-input').value : document.querySelector('input[name="alternativa_id"]:checked').value,
            tipo: tipo
        };
        console.log(dados)
        // Enviará para o PHP validar (Criaremos na próxima etapa)
        fetch('processa-quiz.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dados)
            })
            .then(response => response.json())
            .then(data => {
                if (data.acertou) {
                    feedbackMsg.innerHTML = `<span style="color: #00e5ff;">Correto! Você ganhou +${data.xp_ganho} XP!</span>`;
                    quizForm.querySelector('.btn-submit-quiz').disabled = true;
                } else {
                    feedbackMsg.innerHTML = `<span style="color: #ff3366;">Resposta incorreta. Tente novamente!</span>`;
                    // Efeito de erro balançando o form
                    quizForm.classList.add('shake');
                    setTimeout(() => quizForm.classList.remove('shake'), 500);
                }
            })
            .catch(err => console.error("Erro na requisição", err));
    });
}

if (timerElement) {
    // Pega os segundos gerados pelo PHP
    let timeLeft = parseInt(timerElement.getAttribute('data-time'), 10);
    
    const updateTimer = () => {
        if (timeLeft <= 0) {
            // Quando o tempo acabar, recarrega a página para liberar o quiz
            timerElement.textContent = "00:00";
            window.location.reload();
            return;
        }
        
        // Transforma os segundos totais em Minutos e Segundos
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        
        // Formata para ter sempre dois dígitos (ex: 04:09)
        timerElement.textContent = 
            `${minutes < 10 ? '0' : ''}${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
        
        timeLeft--;
    };
    
    updateTimer(); // Roda a primeira vez imediatamente
    setInterval(updateTimer, 1000); // Atualiza a cada 1 segundo (1000ms)
}