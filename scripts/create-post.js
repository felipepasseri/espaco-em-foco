// ==========================================
// CRIAR POSTAGEM — JavaScript
// ==========================================
(function () {
    const form = document.getElementById('create-post-form');
    const editor = document.getElementById('post-editor');
    const imageInput = document.getElementById('image-input');
    const uploadBtn = document.getElementById('upload-btn');
    const previewContainer = document.getElementById('image-previews');
    const submitBtn = document.getElementById('submit-post-btn');

    let selectedFiles = []; // DataTransfer para manter controle dos arquivos

    // ==========================================
    // 1. TOOLBAR DO EDITOR
    // ==========================================
    document.querySelectorAll('.toolbar-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const command = btn.dataset.command;
            document.execCommand(command, false, null);
            editor.focus();

            // Toggle visual
            btn.classList.toggle('active', document.queryCommandState(command));
        });
    });

    // Atualiza estado visual dos botões ao selecionar texto
    editor.addEventListener('keyup', updateToolbarState);
    editor.addEventListener('mouseup', updateToolbarState);

    function updateToolbarState() {
        document.querySelectorAll('.toolbar-btn').forEach(btn => {
            const command = btn.dataset.command;
            btn.classList.toggle('active', document.queryCommandState(command));
        });
    }

    // Impede mudar tamanho e fonte (ctrl+shift+>, etc)
    editor.addEventListener('paste', function (e) {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text/plain');
        document.execCommand('insertText', false, text);
    });

    // ==========================================
    // 2. UPLOAD DE IMAGENS COM PREVIEW
    // ==========================================
    uploadBtn.addEventListener('click', () => imageInput.click());

    imageInput.addEventListener('change', function () {
        const files = Array.from(this.files);

        // Limita a 5 no total
        files.forEach(file => {
            if (selectedFiles.length >= 5) return;
            if (!file.type.startsWith('image/')) return;
            selectedFiles.push(file);
        });

        renderPreviews();
        this.value = ''; // Reset para permitir re-selecionar mesmos arquivos
    });

    function renderPreviews() {
        previewContainer.innerHTML = '';

        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const item = document.createElement('div');
                item.className = 'image-preview-item';
                item.innerHTML = `
                    <img src="${e.target.result}" alt="Preview" />
                    <button type="button" class="image-preview-remove" data-index="${index}">×</button>
                `;
                previewContainer.appendChild(item);

                // Botão de remover
                item.querySelector('.image-preview-remove').addEventListener('click', function () {
                    selectedFiles.splice(parseInt(this.dataset.index), 1);
                    renderPreviews();
                });
            };
            reader.readAsDataURL(file);
        });

        // Atualiza o label
        if (selectedFiles.length >= 5) {
            uploadBtn.style.opacity = '0.5';
            uploadBtn.style.pointerEvents = 'none';
        } else {
            uploadBtn.style.opacity = '1';
            uploadBtn.style.pointerEvents = 'auto';
        }
    }

    // ==========================================
    // 3. SUBMIT DO FORMULÁRIO
    // ==========================================
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const topic = document.getElementById('post-topic').value;
        const titulo = document.getElementById('post-titulo').value.trim();
        const conteudo = editor.innerHTML.trim();

        if (!topic || !titulo || !conteudo || conteudo === '<br>') {
            alert('Preencha todos os campos obrigatórios.');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerText = 'Publicando...';

        const formData = new FormData();
        formData.append('topic', topic);
        formData.append('titulo', titulo);
        formData.append('conteudo', conteudo);

        // Adiciona imagens
        selectedFiles.forEach(file => {
            formData.append('imagens[]', file);
        });

        fetch('../api/api-create-post.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Postagem criada com sucesso! Ela será analisada por um administrador antes de ser publicada.');
                    window.location.href = 'community.php';
                } else {
                    alert('Erro: ' + (data.error || 'Tente novamente.'));
                    submitBtn.disabled = false;
                    submitBtn.innerText = 'Publicar Postagem';
                }
            })
            .catch(() => {
                alert('Erro ao enviar postagem.');
                submitBtn.disabled = false;
                submitBtn.innerText = 'Publicar Postagem';
            });
    });

    // ==========================================
    // 4. NAVBAR
    // ==========================================
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
