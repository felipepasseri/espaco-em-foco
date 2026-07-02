 const quizForm = document.getElementById('quiz-form');
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