// ==========================================
// CRIAR ARTIGO — JavaScript
// ==========================================
(function () {
    const form = document.getElementById('create-article-form');
    const contentTextarea = document.getElementById('article-content');
    const charCount = document.getElementById('char-count');
    const questionsContainer = document.getElementById('questions-container');
    const btnAddQuestion = document.getElementById('btn-add-question');
    const submitBtn = document.getElementById('submit-article-btn');

    let internalIdCounter = 0;

    // ==========================================
    // 1. CONTADOR DE CARACTERES DO ARTIGO
    // ==========================================
    contentTextarea.addEventListener('input', function () {
        const len = this.value.length;
        charCount.innerText = len;
        
        if (len > 4000) {
            charCount.classList.add('limit-reached');
        } else {
            charCount.classList.remove('limit-reached');
        }
    });

    // ==========================================
    // 2. GERENCIAMENTO DE PERGUNTAS DINÂMICAS
    // ==========================================
    btnAddQuestion.addEventListener('click', addQuestionBlock);

    function addQuestionBlock() {
        internalIdCounter++;
        const qId = internalIdCounter;
        const visualNumber = document.querySelectorAll('.question-block').length + 1;

        const block = document.createElement('div');
        block.className = 'question-block';
        block.dataset.qId = qId;

        block.innerHTML = `
            <div class="question-header">
                <span class="question-number">Pergunta ${visualNumber}</span>
                <button type="button" class="btn-remove-question" onclick="removeQuestion(${qId})">Remover</button>
            </div>

            <div class="form-group">
                <label class="form-label">Título / Texto da Pergunta</label>
                <input type="text" class="form-input q-titulo" placeholder="Digite a pergunta..." required maxlength="150" />
            </div>

            <div class="question-options-row">
                <div class="form-group">
                    <label class="form-label">Tipo de Pergunta</label>
                    <select class="form-select q-tipo" onchange="changeQuestionType(${qId}, this.value)" required>
                        <option value="multipla_escolha">Múltipla Escolha</option>
                        <option value="lacuna">Lacuna</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Dificuldade</label>
                    <select class="form-select q-dificuldade" required>
                        <option value="facil">Fácil (200 XP)</option>
                        <option value="medio">Médio (500 XP)</option>
                        <option value="dificil">Difícil (720 XP)</option>
                    </select>
                </div>
            </div>

            <!-- Container Dinâmico para as respostas -->
            <div id="q-dynamic-area-${qId}" class="q-dynamic-area">
                <!-- Por padrão, carrega múltipla escolha -->
            </div>
        `;

        questionsContainer.appendChild(block);
        
        // Inicializa com Múltipla Escolha
        renderMultiplaEscolhaArea(qId);
    }

    window.removeQuestion = function(qId) {
        const block = document.querySelector(`.question-block[data-q-id="${qId}"]`);
        if (block) block.remove();
        
        // Re-numerar visualmente (opcional)
        const allHeaders = document.querySelectorAll('.question-number');
        allHeaders.forEach((el, index) => {
            el.innerText = `Pergunta ${index + 1}`;
        });
    };

    window.changeQuestionType = function(qId, type) {
        if (type === 'multipla_escolha') {
            renderMultiplaEscolhaArea(qId);
        } else if (type === 'lacuna') {
            renderLacunaArea(qId);
        }
    };

    // ==========================================
    // 3. MÚLTIPLA ESCOLHA
    // ==========================================
    function renderMultiplaEscolhaArea(qId) {
        const area = document.getElementById(`q-dynamic-area-${qId}`);
        area.innerHTML = `
            <div class="alternatives-container" id="alts-container-${qId}">
                <!-- Inicia com 2 opções -->
            </div>
            <button type="button" class="btn-add-alt" onclick="addAlternative(${qId})">+ Adicionar Alternativa</button>
        `;
        
        // Adiciona 2 alternativas obrigatórias
        addAlternative(qId);
        addAlternative(qId);
        
        // Marca a primeira como certa por padrão
        const firstRadio = document.querySelector(`input[name="correct_alt_${qId}"]`);
        if (firstRadio) firstRadio.checked = true;
    }

    window.addAlternative = function(qId) {
        const container = document.getElementById(`alts-container-${qId}`);
        const currentAlts = container.querySelectorAll('.alternative-item').length;
        
        if (currentAlts >= 4) {
            alert('Máximo de 4 alternativas permitidas.');
            return;
        }

        const altId = currentAlts;
        const item = document.createElement('div');
        item.className = 'alternative-item';
        item.innerHTML = `
            <input type="radio" name="correct_alt_${qId}" value="${altId}" class="alternative-radio" required title="Marcar como correta" />
            <input type="text" class="form-input alt-text" placeholder="Texto da alternativa..." required maxlength="150" />
            <button type="button" class="btn-remove-alt" onclick="removeAlternative(this)" title="Remover alternativa">-</button>
        `;
        
        container.appendChild(item);
        updateAlternativeRadios(qId);
    };

    window.removeAlternative = function(btn) {
        const container = btn.closest('.alternatives-container');
        const qId = container.id.split('-')[2];
        const items = container.querySelectorAll('.alternative-item');
        
        if (items.length <= 2) {
            alert('Mínimo de 2 alternativas obrigatórias.');
            return;
        }
        
        btn.closest('.alternative-item').remove();
        updateAlternativeRadios(qId);
        
        // Se a correta foi removida, marca a primeira
        const checked = container.querySelector(`input[type="radio"]:checked`);
        if (!checked) {
            container.querySelector(`input[type="radio"]`).checked = true;
        }
    };

    function updateAlternativeRadios(qId) {
        const container = document.getElementById(`alts-container-${qId}`);
        if (!container) return;
        
        const radios = container.querySelectorAll('.alternative-radio');
        radios.forEach((radio, index) => {
            radio.value = index;
        });
    }

    // ==========================================
    // 4. LACUNAS
    // ==========================================
    function renderLacunaArea(qId) {
        const area = document.getElementById(`q-dynamic-area-${qId}`);
        area.innerHTML = `
            <div class="lacuna-tools">
                <button type="button" class="btn-add-lacuna-text" onclick="insertLacunaText(${qId})">+ Adicionar [lacuna] no texto</button>
                <span style="font-size: 12px; color: #a09bba;">O texto acima deve conter a palavra [lacuna] onde ficarão os espaços.</span>
            </div>
            
            <div class="lacuna-answers-container" id="lacuna-answers-${qId}">
                <!-- Campos de resposta adicionados via listener do input -->
            </div>
        `;

        const titleInput = document.querySelector(`.question-block[data-q-id="${qId}"] .q-titulo`);
        
        // Listener para atualizar campos de resposta dinamicamente
        titleInput.removeEventListener('input', handleLacunaInput); // Evita duplicados se trocar de tipo
        titleInput.addEventListener('input', handleLacunaInput);
        
        // Força atualização inicial caso já tenha texto
        handleLacunaInput({ target: titleInput });
    }

    window.insertLacunaText = function(qId) {
        const input = document.querySelector(`.question-block[data-q-id="${qId}"] .q-titulo`);
        const cursorPos = input.selectionStart;
        const textBefore = input.value.substring(0, cursorPos);
        const textAfter = input.value.substring(cursorPos);
        
        // Verifica limite de 3
        const match = input.value.match(/\[lacuna\]/g);
        if (match && match.length >= 3) {
            alert('Máximo de 3 lacunas por pergunta.');
            return;
        }

        input.value = textBefore + '[lacuna]' + textAfter;
        input.focus();
        
        // Atualiza campos
        handleLacunaInput({ target: input });
    };

    function handleLacunaInput(e) {
        const input = e.target;
        const block = input.closest('.question-block');
        const qId = block.dataset.qId;
        const tipoSelect = block.querySelector('.q-tipo').value;
        
        if (tipoSelect !== 'lacuna') return;

        const lacunaContainer = document.getElementById(`lacuna-answers-${qId}`);
        if (!lacunaContainer) return;

        const match = input.value.match(/\[lacuna\]/g);
        const count = match ? match.length : 0;

        // Se passar de 3, avisa mas quem bloqueia o envio é o formulário
        if (count > 3) {
            alert('Atenção: Máximo de 3 lacunas permitidas. Você precisará remover as excedentes para salvar.');
        }

        // Mantém os valores já digitados ao recriar
        const oldValues = Array.from(lacunaContainer.querySelectorAll('.lacuna-val')).map(el => el.value);

        lacunaContainer.innerHTML = '';
        
        if (count === 0) {
            lacunaContainer.innerHTML = '<span style="font-size: 13px; color:#ff3366;">Nenhuma [lacuna] encontrada no texto.</span>';
            return;
        }

        // Limita a criação de inputs até 3, mesmo se tiver mais texto, para refletir a regra
        const renderCount = Math.min(count, 3);

        for (let i = 0; i < renderCount; i++) {
            const item = document.createElement('div');
            item.className = 'lacuna-item';
            const val = oldValues[i] || '';
            item.innerHTML = `
                <span class="lacuna-label">Resposta ${i + 1}:</span>
                <input type="text" class="form-input lacuna-val" placeholder="Palavra correta..." value="${val}" required maxlength="150" />
            `;
            lacunaContainer.appendChild(item);
        }
    }

    // ==========================================
    // 5. SUBMIT DO FORMULÁRIO
    // ==========================================
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const topicId = document.getElementById('article-topic').value;
        const titulo = document.getElementById('article-title').value.trim();
        const conteudo = contentTextarea.value.trim();

        if (conteudo.length < 500) {
            alert('O conteúdo do artigo deve ter no mínimo 500 caracteres.');
            return;
        }
        
        if (conteudo.length > 4000) {
            alert('O conteúdo do artigo deve ter no máximo 4000 caracteres.');
            return;
        }

        // Construir Array de Perguntas
        const perguntas = [];
        const questionBlocks = document.querySelectorAll('.question-block');
        
        if (questionBlocks.length < 3) {
            alert('Você precisa adicionar no mínimo 3 perguntas de fixação para salvar o artigo.');
            return;
        }
        
        for (let block of questionBlocks) {
            const qId = block.dataset.qId;
            const qTitulo = block.querySelector('.q-titulo').value.trim();
            const qTipo = block.querySelector('.q-tipo').value;
            const qDificuldade = block.querySelector('.q-dificuldade').value;

            if (!qTitulo) {
                alert(`Preencha o título da Pergunta ${qId}.`);
                return;
            }

            let perguntaData = {
                titulo: qTitulo,
                tipo: qTipo,
                dificuldade: qDificuldade
            };

            if (qTipo === 'multipla_escolha') {
                const altsContainer = document.getElementById(`alts-container-${qId}`);
                const altTexts = altsContainer.querySelectorAll('.alt-text');
                const checkedRadio = altsContainer.querySelector(`input[type="radio"]:checked`);

                if (altTexts.length < 2) {
                    alert(`A Pergunta ${qId} precisa de no mínimo 2 alternativas.`);
                    return;
                }

                if (!checkedRadio) {
                    alert(`Selecione qual é a alternativa correta na Pergunta ${qId}.`);
                    return;
                }

                let alternativas = [];
                for (let textInput of altTexts) {
                    if (!textInput.value.trim()) {
                        alert(`Preencha todas as alternativas da Pergunta ${qId}.`);
                        return;
                    }
                    alternativas.push(textInput.value.trim());
                }

                perguntaData.alternativas = alternativas;
                perguntaData.resposta_correta = checkedRadio.value;

            } else if (qTipo === 'lacuna') {
                const match = qTitulo.match(/\[lacuna\]/g);
                const lacunasEncontradas = match ? match.length : 0;

                if (lacunasEncontradas === 0) {
                    alert(`A Pergunta ${qId} é do tipo Lacuna mas não possui '[lacuna]' no texto.`);
                    return;
                }

                if (lacunasEncontradas > 3) {
                    alert(`A Pergunta ${qId} excede o limite de 3 lacunas.`);
                    return;
                }

                const lacunaContainer = document.getElementById(`lacuna-answers-${qId}`);
                const lacunaInputs = lacunaContainer.querySelectorAll('.lacuna-val');

                if (lacunaInputs.length !== lacunasEncontradas) {
                    alert(`Erro interno: quantidade de respostas não bate com lacunas na Pergunta ${qId}.`);
                    return;
                }

                let respostasLacuna = [];
                for (let textInput of lacunaInputs) {
                    if (!textInput.value.trim()) {
                        alert(`Preencha todas as respostas das lacunas na Pergunta ${qId}.`);
                        return;
                    }
                    respostasLacuna.push(textInput.value.trim());
                }

                perguntaData.lacunas = respostasLacuna;
            }

            perguntas.push(perguntaData);
        }

        const payload = {
            topic: topicId,
            titulo: titulo,
            conteudo: conteudo,
            perguntas: perguntas
        };

        submitBtn.disabled = true;
        submitBtn.innerText = 'Salvando...';

        fetch('../api/api-create-article.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Artigo enviado com sucesso! Ele será avaliado por nossa equipe e, se aprovado, ficará disponível.');
                window.location.href = 'community.php';
            } else {
                alert('Falha ao salvar: ' + (data.error || 'Erro desconhecido.'));
                submitBtn.disabled = false;
                submitBtn.innerText = 'Salvar Artigo';
            }
        })
        .catch(err => {
            alert('Erro de conexão ao salvar artigo.');
            submitBtn.disabled = false;
            submitBtn.innerText = 'Salvar Artigo';
        });
    });

    // Inicia com uma pergunta por padrão
    addQuestionBlock();

    // NAVBAR FIX
    window.toggleProfileDropdown = function (e) {
        e.stopPropagation();
        document.getElementById('profile-dropdown-content').classList.toggle('show');
    };
    window.addEventListener('click', function (event) {
        var dropdown = document.getElementById('profile-dropdown-content');
        if (dropdown && dropdown.classList.contains('show') && !event.target.matches('#login-icon')) {
            dropdown.classList.remove('show');
        }
    });

})();
