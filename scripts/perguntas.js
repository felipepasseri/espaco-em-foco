// scripts/perguntas.js
(function() {
    // Referências DOM
    const loaderScreen = document.getElementById('loader-screen');
    const errorScreen = document.getElementById('error-screen');
    const errorTitle = document.getElementById('error-title');
    const errorMessage = document.getElementById('error-message');
    
    const quizContainer = document.getElementById('quiz-container');
    const resultContainer = document.getElementById('result-container');
    
    const questionTitle = document.getElementById('question-title');
    const answersWrapper = document.getElementById('answers-wrapper');
    const btnNext = document.getElementById('btn-next');
    
    const currentQuestionNum = document.getElementById('current-question-num');
    const totalQuestionsNum = document.getElementById('total-questions-num');
    const progressBarFill = document.getElementById('progress-bar-fill');
    
    const dificuldadeBadge = document.getElementById('dificuldade-badge');
    const xpBadge = document.getElementById('xp-badge');

    // Estado do Quiz
    let perguntas = [];
    let currentIndex = 0;
    let isWaiting = false;

    // Inicialização
    initQuiz();

    function initQuiz() {
        // id_artigo vem de uma variável global setada na perguntas.php
        fetch(`../../api/api-get-perguntas.php?id_artigo=${id_artigo}`)
            .then(res => res.json())
            .then(data => {
                loaderScreen.style.display = 'none';
                if (!data.success) {
                    showError(data.status === 'bloqueado' || data.status === 'cooldown' ? 'Aviso' : 'Erro', data.message || data.error);
                    return;
                }
                
                perguntas = data.perguntas;
                
                if (perguntas.length === 0) {
                    showError('Aviso', 'Não há novas perguntas para você responder neste momento.');
                    return;
                }

                quizContainer.style.display = 'block';
                totalQuestionsNum.innerText = perguntas.length;
                renderQuestion();
            })
            .catch(err => {
                loaderScreen.style.display = 'none';
                showError('Erro', 'Não foi possível conectar ao servidor.');
            });
    }

    function showError(title, msg) {
        quizContainer.style.display = 'none';
        errorScreen.style.display = 'block';
        errorTitle.innerText = title;
        errorMessage.innerText = msg;
    }

    function renderQuestion() {
        if (currentIndex >= perguntas.length) {
            finishQuiz();
            return;
        }

        const p = perguntas[currentIndex];
        
        currentQuestionNum.innerText = currentIndex + 1;
        updateProgressBar();
        
        dificuldadeBadge.innerText = p.dificuldade.toUpperCase();
        xpBadge.innerText = `+${p.xp_recompensa} XP`;

        answersWrapper.innerHTML = '';
        btnNext.disabled = true;

        if (p.tipo === 'lacuna') {
            const parts = p.texto_pergunta.split('[lacuna]');
            let html = '';
            for (let i = 0; i < parts.length; i++) {
                html += parts[i];
                if (i < parts.length - 1) {
                    html += `<input type="text" class="lacuna-input" data-index="${i}" autocomplete="off" />`;
                }
            }
            questionTitle.innerHTML = html;

            const inputs = questionTitle.querySelectorAll('.lacuna-input');
            inputs.forEach(input => {
                input.addEventListener('input', checkInputFilled);
            });
            // Quando a última input for preenchida, ou enter for apertado
            inputs.forEach(input => {
                input.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter' && !btnNext.disabled && !isWaiting) {
                        btnNext.click();
                    }
                });
            });

        } else {
            questionTitle.innerText = p.texto_pergunta;
            
            p.alternativas.forEach(alt => {
                const label = document.createElement('label');
                label.className = 'alt-label';
                
                const input = document.createElement('input');
                input.type = 'radio';
                input.name = 'alt_answer';
                input.value = alt.id;
                
                input.addEventListener('change', () => {
                    btnNext.disabled = false;
                });

                const span = document.createElement('span');
                span.className = 'alt-btn';
                span.innerText = alt.texto_alternativa;

                label.appendChild(input);
                label.appendChild(span);
                answersWrapper.appendChild(label);
            });
        }
    }

    function checkInputFilled() {
        const inputs = questionTitle.querySelectorAll('.lacuna-input');
        let allFilled = true;
        let responseArr = [];
        inputs.forEach(inp => {
            if (inp.value.trim() === '') allFilled = false;
            responseArr.push(inp.value.trim());
        });
        btnNext.disabled = !allFilled;
        return responseArr.join(',');
    }

    function updateProgressBar() {
        const percent = (currentIndex / perguntas.length) * 100;
        progressBarFill.style.width = `${percent}%`;
    }

    btnNext.addEventListener('click', () => {
        if (isWaiting) return;
        
        const p = perguntas[currentIndex];
        let respostaDada = '';

        if (p.tipo === 'lacuna') {
            respostaDada = checkInputFilled();
        } else {
            const selected = document.querySelector('input[name="alt_answer"]:checked');
            if (!selected) return;
            respostaDada = selected.value;
        }

        isWaiting = true;
        btnNext.disabled = true;
        btnNext.innerText = 'Validando...';

        const payload = {
            id_pergunta: p.id,
            tipo: p.tipo,
            resposta_dada: respostaDada
        };

        fetch('../../api/api-valida-pergunta.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.error);
                isWaiting = false;
                btnNext.disabled = false;
                btnNext.innerText = 'Confirmar Resposta';
                return;
            }

            const isCorrect = data.is_correct;
            applyFeedbackVisual(p.tipo, isCorrect);

            setTimeout(() => {
                isWaiting = false;
                btnNext.innerText = 'Confirmar Resposta';
                currentIndex++;
                renderQuestion();
            }, 2000);
        })
        .catch(err => {
            alert('Erro de conexão.');
            isWaiting = false;
            btnNext.disabled = false;
            btnNext.innerText = 'Confirmar Resposta';
        });
    });

    function applyFeedbackVisual(tipo, isCorrect) {
        if (tipo === 'lacuna') {
            const inputs = questionTitle.querySelectorAll('.lacuna-input');
            inputs.forEach(inp => {
                inp.disabled = true;
                inp.classList.add(isCorrect ? 'lacuna-correct' : 'lacuna-wrong');
            });
        } else {
            const selected = document.querySelector('input[name="alt_answer"]:checked');
            const span = selected.nextElementSibling;
            span.classList.add(isCorrect ? 'alt-correct' : 'alt-wrong');
            
            const allInputs = document.querySelectorAll('input[name="alt_answer"]');
            allInputs.forEach(inp => inp.disabled = true);
        }
    }

    function finishQuiz() {
        quizContainer.style.display = 'none';
        loaderScreen.style.display = 'block';
        loaderScreen.querySelector('p').innerText = 'Processando resultado final...';

        fetch('../../userScreen/article-screen/processa-quiz.php', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                loaderScreen.style.display = 'none';
                
                if (!data.success) {
                    showError('Erro', data.error);
                    return;
                }

                resultContainer.style.display = 'block';
                
                document.getElementById('result-score').innerText = `${data.acertos_sessao}/${data.total_sessao}`;
                document.getElementById('result-xp').innerText = `+${data.xp_ganho}`;
                
                const title = document.getElementById('result-title');
                const msg = document.getElementById('result-message');

                if (data.aprovado) {
                    title.innerText = 'Missão Cumprida!';
                    title.classList.add('result-aprovado');
                    msg.innerText = data.total_acertos_geral >= data.total_artigo 
                        ? 'Parabéns! Você acertou todas as perguntas e completou este artigo em 100%!'
                        : 'Muito bem! Você atingiu a média. Você poderá refazer as perguntas que errou em 3 dias.';
                    
                    if (data.upou_de_nivel) {
                        msg.innerText += `\n\n🎉 INCRÍVEL! Você alcançou o Nível ${data.novo_nivel}!`;
                    }
                } else {
                    title.innerText = 'Falha na Missão!';
                    title.classList.add('result-reprovado');
                    msg.innerText = 'Infelizmente você não atingiu a pontuação mínima para ganhar XP. Revise o artigo e tente novamente em 10 minutos.';
                }
            })
            .catch(err => {
                loaderScreen.style.display = 'none';
                showError('Erro', 'Não foi possível processar o resultado.');
            });
    }

})();
