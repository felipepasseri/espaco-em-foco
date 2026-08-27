// ==========================================
// TELA DO POST — JavaScript
// ==========================================
(function () {
    const postArticle = document.querySelector('.post-article');
    const postId = postArticle ? postArticle.dataset.postId : null;
    const commentsContainer = document.getElementById('comments-container');
    const loadMoreComments = document.getElementById('load-more-comments');
    const reportModal = document.getElementById('report-post-modal');
    const hoverCard = document.getElementById('user-hover-card');
    let hoverTimeout;
    let commentsOffset = 0;
    const commentsLimit = 5;

    function formatXP(xp) {
        xp = parseInt(xp) || 0;
        if (xp < 1000) return xp.toString();
        if (xp < 1000000) {
            const val = xp / 1000;
            return (val % 1 === 0 ? val.toFixed(0) : val.toFixed(1)) + 'k';
        }
        const val = xp / 1000000;
        return (val % 1 === 0 ? val.toFixed(0) : val.toFixed(1)) + 'M';
    }

    // ==========================================
    // 1. CARROSSEL DE IMAGENS
    // ==========================================
    const carouselTrack = document.getElementById('carousel-track');
    const prevBtn = document.getElementById('carousel-prev');
    const nextBtn = document.getElementById('carousel-next');
    const dots = document.querySelectorAll('.carousel-dot');
    let currentSlide = 0;

    function goToSlide(index) {
        if (!carouselTrack) return;
        const images = carouselTrack.querySelectorAll('.carousel-image');
        if (index < 0) index = images.length - 1;
        if (index >= images.length) index = 0;
        currentSlide = index;
        carouselTrack.style.transform = `translateX(-${index * 100}%)`;
        dots.forEach((dot, i) => dot.classList.toggle('active', i === index));
    }

    prevBtn?.addEventListener('click', () => goToSlide(currentSlide - 1));
    nextBtn?.addEventListener('click', () => goToSlide(currentSlide + 1));
    dots.forEach(dot => dot.addEventListener('click', () => goToSlide(parseInt(dot.dataset.index))));

    // ==========================================
    // 2. INTERAÇÕES (Like / Deslike / Repost)
    // ==========================================
    document.querySelectorAll('.interaction-btn[data-action]').forEach(btn => {
        btn.addEventListener('click', function () {
            const action = this.dataset.action;
            this.disabled = true;

            fetch('../api/api-post-interaction.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, post_id: postId })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.counts) {
                        document.getElementById('count-likes').innerText = data.counts.likes_post;
                        document.getElementById('count-deslikes').innerText = data.counts.deslikes_post;
                        document.getElementById('count-reposts').innerText = data.counts.reposts;

                        // Atualiza estilos visuais
                        const likeBtn = document.getElementById('btn-like');
                        const deslikeBtn = document.getElementById('btn-deslike');
                        const repostBtn = document.getElementById('btn-repost');

                        if (action === 'like') {
                            likeBtn.classList.toggle('active-like', data.toggled === 'on');
                            if (data.toggled === 'on') deslikeBtn.classList.remove('active-dislike');
                        } else if (action === 'deslike') {
                            deslikeBtn.classList.toggle('active-dislike', data.toggled === 'on');
                            if (data.toggled === 'on') likeBtn.classList.remove('active-like');
                        } else if (action === 'repost') {
                            repostBtn.classList.toggle('active-repost');
                        }

                        // Atualiza ícones SVG fill
                        likeBtn.querySelector('svg').setAttribute('fill', likeBtn.classList.contains('active-like') ? '#00e5ff' : 'none');
                        deslikeBtn.querySelector('svg').setAttribute('fill', deslikeBtn.classList.contains('active-dislike') ? '#ff3366' : 'none');
                    }
                    this.disabled = false;
                })
                .catch(() => { this.disabled = false; });
        });
    });

    // ==========================================
    // 3. COMENTÁRIOS
    // ==========================================
    function loadComments(offset = 0, append = false) {
        if (!append) {
            commentsContainer.innerHTML = '<div class="loading-indicator">Carregando comentários...</div>';
        }

        fetch(`../api/api-comments.php?post_id=${postId}&offset=${offset}&limit=${commentsLimit}`)
            .then(res => res.json())
            .then(data => {
                if (!append) commentsContainer.innerHTML = '';

                if (data.comments && data.comments.length > 0) {
                    data.comments.forEach(c => {
                        commentsContainer.appendChild(createCommentElement(c));
                    });
                    commentsOffset = offset + data.comments.length;

                    if (data.comments.length >= commentsLimit) {
                        loadMoreComments.classList.remove('hidden');
                    } else {
                        loadMoreComments.classList.add('hidden');
                    }
                } else if (!append) {
                    commentsContainer.innerHTML = '<p style="color: #a09bba; text-align: center; padding: 20px;">Nenhum comentário ainda. Seja o primeiro!</p>';
                    loadMoreComments.classList.add('hidden');
                }
            });
    }

    function createCommentElement(comment) {
        const div = document.createElement('div');
        div.className = 'comment-item';
        div.dataset.commentId = comment.id;

        const foto = comment.fotoPerfil ? `../${comment.fotoPerfil}` : '../img/user-profile-default.jpg';

        div.innerHTML = `
            <div class="comment-header">
                <img src="${foto}" alt="Perfil" class="comment-avatar" />
                <span class="comment-username hover-trigger"
                      data-avatar="${foto}" data-user="${comment.nome_usuario}"
                      data-name="${comment.nome} ${comment.sobrenome}"
                      data-level="${comment.userLevel}" data-xp="${comment.userPoints}"
                      data-followers="${comment.total_followers}" data-following="${comment.total_following}"
                      data-username="${comment.nome_usuario}" data-isfollowing="0" data-isme="false">
                    ${comment.nome_usuario}
                </span>
                <span class="comment-time">${comment.tempo_relativo}</span>
            </div>
            <p class="comment-text">${escapeHtml(comment.comentario)}</p>
            <div class="comment-actions">
                <button class="comment-action-btn ${comment.eu_curti > 0 ? 'liked' : ''}" onclick="toggleCommentLike(${comment.id}, this)">
                    ❤ <span>${comment.likes}</span>
                </button>
                <button class="comment-action-btn" onclick="showReplyForm(${comment.id})">
                    💬 Responder
                </button>
            </div>
            ${comment.total_respostas > 0 ? `
                <button class="show-replies-btn" onclick="loadReplies(${comment.id}, this)">
                    Mostrar respostas (${comment.total_respostas})
                </button>
            ` : ''}
            <div class="comment-replies" id="replies-${comment.id}" style="display:none;"></div>
        `;

        return div;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Submeter novo comentário
    document.getElementById('btn-submit-comment')?.addEventListener('click', function () {
        const textarea = document.getElementById('new-comment-text');
        const text = textarea.value.trim();
        if (!text) return;

        this.disabled = true;
        this.innerText = '...';

        fetch('../api/api-comments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: postId, comentario: text })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    textarea.value = '';
                    // Recarrega os comentários
                    commentsOffset = 0;
                    loadComments(0);
                }
                this.disabled = false;
                this.innerText = 'Comentar';
            })
            .catch(() => {
                this.disabled = false;
                this.innerText = 'Comentar';
            });
    });

    // Carregar mais comentários
    loadMoreComments?.addEventListener('click', () => {
        loadComments(commentsOffset, true);
    });

    // ==========================================
    // FUNÇÕES GLOBAIS PARA COMENTÁRIOS
    // ==========================================
    window.toggleCommentLike = function (commentId, btn) {
        btn.disabled = true;
        fetch('../api/api-post-interaction.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'like_comment', comment_id: commentId })
        })
        .then(res => res.json())
        .then(data => {
                if (data.success) {
                    btn.classList.toggle('liked');
                    const span = btn.querySelector('span');
                    if (data.likes !== undefined) span.innerText = data.likes;
                }
                btn.disabled = false;
            })
            .catch(() => { btn.disabled = false; });
    };

    window.showReplyForm = function (commentId) {
        const container = document.getElementById(`replies-${commentId}`);
        container.style.display = 'flex';

        // Checa se já tem o form
        if (container.querySelector('.reply-form')) return;

        const form = document.createElement('div');
        form.className = 'reply-form';
        form.innerHTML = `
            <input type="text" class="reply-input" placeholder="Escreva uma resposta..." />
            <button class="reply-submit-btn" onclick="submitReply(${commentId}, this)">Enviar</button>
        `;
        container.prepend(form);
    };

    window.submitReply = function (parentId, btn) {
        const input = btn.parentElement.querySelector('.reply-input');
        const text = input.value.trim();
        if (!text) return;

        btn.disabled = true;

        fetch('../api/api-comments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: postId, comentario: text, parent_id: parentId })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    input.value = '';
                    loadReplies(parentId, null);
                }
                btn.disabled = false;
            })
            .catch(() => { btn.disabled = false; });
    };

    window.loadReplies = function (parentId, btn) {
        const container = document.getElementById(`replies-${parentId}`);
        container.style.display = 'flex';

        // Remove o botão "Mostrar respostas"
        if (btn) btn.remove();

        // Mantém o form se existir
        const existingForm = container.querySelector('.reply-form');

        fetch(`../api/api-comments.php?post_id=${postId}&parent_id=${parentId}&limit=10&offset=0`)
            .then(res => res.json())
            .then(data => {
                // Limpa tudo exceto o form
                container.innerHTML = '';
                if (existingForm) container.appendChild(existingForm);

                if (data.comments && data.comments.length > 0) {
                    data.comments.forEach(c => {
                        container.appendChild(createCommentElement(c));
                    });
                }
            });
    };

    // ==========================================
    // 4. DENÚNCIA DO POST
    // ==========================================
    document.getElementById('btn-denunciar-post')?.addEventListener('click', () => {
        reportModal.classList.remove('hidden');
    });

    document.getElementById('close-report-post-modal')?.addEventListener('click', () => {
        reportModal.classList.add('hidden');
    });

    window.addEventListener('click', (e) => {
        if (e.target === reportModal) reportModal.classList.add('hidden');
    });

    document.getElementById('report-post-form')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = this.querySelector('.report-submit-btn');
        submitBtn.disabled = true;
        submitBtn.innerText = 'Enviando...';

        fetch('../api/api-report.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Denúncia enviada com sucesso!');
                    reportModal.classList.add('hidden');
                    this.reset();
                } else {
                    alert('Erro ao enviar denúncia.');
                }
                submitBtn.disabled = false;
                submitBtn.innerText = 'Enviar Denúncia';
            });
    });

    // ==========================================
    // 5. HOVER CARD
    // ==========================================
    document.addEventListener('mouseover', (e) => {
        const trigger = e.target.closest('.hover-trigger');
        if (trigger) {
            clearTimeout(hoverTimeout);
            document.getElementById('hc-avatar').src = trigger.dataset.avatar || '../img/user-profile-default.jpg';
            document.getElementById('hc-username').innerText = trigger.dataset.user || '';
            document.getElementById('hc-fullname').innerText = trigger.dataset.name || '';
            document.getElementById('hc-level').innerText = trigger.dataset.level || '';
            document.getElementById('hc-xp').innerText = formatXP(trigger.dataset.xp);
            document.getElementById('hc-followers').innerText = trigger.dataset.followers || '';
            document.getElementById('hc-following').innerText = trigger.dataset.following || '';

            const hcFollowBtn = document.getElementById('hc-follow-btn');
            hcFollowBtn.style.display = 'none';

            const rect = trigger.getBoundingClientRect();
            hoverCard.style.top = `${rect.bottom + 5}px`;
            hoverCard.style.left = `${rect.left}px`;
            hoverCard.classList.remove('hidden');
        }
    });

    document.addEventListener('mouseout', (e) => {
        if (e.target.closest('.hover-trigger')) {
            hoverTimeout = setTimeout(() => hoverCard.classList.add('hidden'), 300);
        }
    });

    hoverCard.addEventListener('mouseover', () => clearTimeout(hoverTimeout));
    hoverCard.addEventListener('mouseout', () => {
        hoverTimeout = setTimeout(() => hoverCard.classList.add('hidden'), 300);
    });

    // Click → perfil
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('.hover-trigger');
        if (trigger) {
            const username = trigger.dataset.username;
            if (username && trigger.dataset.isme !== 'true') {
                window.location.href = `/userScreen/profile.php?user=${encodeURIComponent(username)}`;
            }
        }
    });

    // ==========================================
    // 6. NAVBAR
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

    // ==========================================
    // INICIALIZAÇÃO
    // ==========================================
    if (postId) {
        loadComments(0);
    }
})();
