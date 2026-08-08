// ==========================================
// COMMUNITY (FÓRUM) — JavaScript
// ==========================================
(function () {
    const postsContainer = document.getElementById('posts-container');
    const loadMoreBtn = document.getElementById('load-more-btn');
    const friendsList = document.getElementById('friends-list');
    const friendsModal = document.getElementById('friends-modal');
    const friendsModalList = document.getElementById('friends-modal-list');
    const hoverCard = document.getElementById('user-hover-card');
    let hoverTimeout;

    let currentType = 'all';
    let currentTopic = 'all';
    let currentSearch = '';
    let currentPage = 1;

    // ==========================================
    // 0. FORMATAÇÃO DE XP
    // ==========================================
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
    // 1. CARREGAR POSTS
    // ==========================================
    function loadPosts(page = 1, append = false) {
        if (!append) {
            postsContainer.innerHTML = '<div class="loading-indicator">Carregando postagens...</div>';
        }

        const params = new URLSearchParams({
            type: currentType,
            topic: currentTopic,
            search: currentSearch,
            page: page
        });

        fetch(`../api/api-community.php?${params}`)
            .then(res => res.json())
            .then(data => {
                if (!append) postsContainer.innerHTML = '';

                if (data.posts && data.posts.length > 0) {
                    data.posts.forEach(post => {
                        postsContainer.appendChild(createPostCard(post));
                    });
                    // Se recebeu menos que 10 posts, esconde "carregar mais"
                    if (data.posts.length < 10) {
                        loadMoreBtn.classList.add('hidden');
                    } else {
                        loadMoreBtn.classList.remove('hidden');
                    }
                    currentPage = page;
                } else if (!append) {
                    postsContainer.innerHTML = `
                        <div class="posts-empty-state">
                            <span class="posts-empty-icon">🔭</span>
                            <p>Nenhuma postagem encontrada.</p>
                        </div>
                    `;
                    loadMoreBtn.classList.add('hidden');
                }
            })
            .catch(() => {
                if (!append) {
                    postsContainer.innerHTML = '<div class="loading-indicator">Erro ao carregar postagens.</div>';
                }
            });
    }

    function createPostCard(post) {
        const card = document.createElement('a');
        card.className = 'post-card';
        card.href = `post.php?id=${post.id}`;

        const foto = post.fotoPerfil ? `../${post.fotoPerfil}` : '../img/user-profile-default.jpg';
        const desc = post.desc_post ? post.desc_post.replace(/<[^>]*>/g, '').substring(0, 180) + '...' : '';

        let imageHtml = '';
        if (post.primeira_img) {
            imageHtml = `<img src="../${post.primeira_img}" alt="" class="post-card-image" />`;
        }

        card.innerHTML = `
            <div class="post-card-header">
                <img src="${foto}" alt="Perfil" class="post-card-avatar" />
                <div class="post-card-meta">
                    <span class="post-card-username hover-trigger"
                          data-avatar="${foto}" data-user="${post.nome_usuario_post}"
                          data-name="${post.nome} ${post.sobrenome}" data-level=""
                          data-xp="" data-followers="" data-following=""
                          data-username="${post.nome_usuario_post}" data-isfollowing="0" data-isme="false">
                        ${post.nome_usuario_post}
                    </span>
                    <span class="post-card-time">${post.tempo_relativo}</span>
                </div>
                <span class="post-card-topic">${post.topic_post}</span>
            </div>
            <h3 class="post-card-title">${escapeHtml(post.titulo_post)}</h3>
            <p class="post-card-desc">${escapeHtml(desc)}</p>
            ${imageHtml}
            <div class="post-card-stats">
                <span>👍 ${post.likes_post}</span>
                <span>👎 ${post.deslikes_post}</span>
                <span>🔁 ${post.reposts}</span>
            </div>
        `;
        return card;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ==========================================
    // 2. TABS (Postagens / Curtidos / Reposts)
    // ==========================================
    document.querySelectorAll('.community-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.community-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            currentType = tab.dataset.type;
            currentPage = 1;
            loadPosts(1);
        });
    });

    // ==========================================
    // 3. FILTROS POR TÓPICO
    // ==========================================
    document.querySelectorAll('.topic-filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.topic-filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentTopic = btn.dataset.topic;
            currentPage = 1;
            loadPosts(1);
        });
    });

    // ==========================================
    // 4. PESQUISA
    // ==========================================
    let searchTimeout;
    document.getElementById('community-search').addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentSearch = this.value.trim();
            currentPage = 1;
            loadPosts(1);
        }, 400);
    });

    // ==========================================
    // 5. CARREGAR MAIS
    // ==========================================
    loadMoreBtn.addEventListener('click', () => {
        loadPosts(currentPage + 1, true);
    });

    // ==========================================
    // 6. AMIGOS (Sidebar)
    // ==========================================
    function loadFriends() {
        fetch('../api/api-friends.php')
            .then(res => res.json())
            .then(data => {
                friendsList.innerHTML = '';
                if (data.friends && data.friends.length > 0) {
                    data.friends.forEach(friend => {
                        const foto = friend.fotoPerfil ? `../${friend.fotoPerfil}` : '../img/user-profile-default.jpg';
                        const li = document.createElement('li');
                        li.className = 'friend-item hover-trigger';
                        li.setAttribute('data-avatar', foto);
                        li.setAttribute('data-user', friend.nomeDeUsuario);
                        li.setAttribute('data-name', `${friend.nome} ${friend.sobrenome}`);
                        li.setAttribute('data-level', friend.userLevel);
                        li.setAttribute('data-xp', friend.userPoints);
                        li.setAttribute('data-followers', friend.total_followers);
                        li.setAttribute('data-following', friend.total_following);
                        li.setAttribute('data-username', friend.nomeDeUsuario);
                        li.setAttribute('data-isfollowing', '1');
                        li.setAttribute('data-isme', 'false');
                        li.innerHTML = `
                            <img src="${foto}" alt="Perfil" class="friend-avatar" />
                            <span class="friend-name">${friend.nomeDeUsuario}</span>
                        `;
                        friendsList.appendChild(li);
                    });

                    // Mostra "Ver todos" se tem mais amigos
                    if (data.total > data.friends.length) {
                        document.getElementById('btn-ver-todos-amigos').classList.remove('hidden');
                    }
                } else {
                    friendsList.innerHTML = '<li style="color: #a09bba; font-size: 13px; padding: 8px;">Nenhum amigo encontrado.</li>';
                }
            })
            .catch(() => {});
    }

    // Ver todos os amigos
    document.getElementById('btn-ver-todos-amigos')?.addEventListener('click', () => {
        friendsModal.classList.remove('hidden');
        friendsModalList.innerHTML = '<div style="text-align:center; padding: 20px; color: #a09bba;">Carregando...</div>';

        fetch('../api/api-friends.php?all=1')
            .then(res => res.json())
            .then(data => {
                friendsModalList.innerHTML = '';
                if (data.friends && data.friends.length > 0) {
                    data.friends.forEach(friend => {
                        const foto = friend.fotoPerfil ? `../${friend.fotoPerfil}` : '../img/user-profile-default.jpg';
                        const li = document.createElement('li');
                        li.className = 'user-list-item';
                        li.innerHTML = `
                            <div class="user-list-info">
                                <img src="${foto}" alt="Perfil" class="user-list-avatar">
                                <div class="user-list-names">
                                    <span class="user-list-username hover-trigger"
                                          data-avatar="${foto}" data-user="${friend.nomeDeUsuario}"
                                          data-name="${friend.nome} ${friend.sobrenome}" data-level="${friend.userLevel}"
                                          data-xp="${friend.userPoints}" data-followers="${friend.total_followers}"
                                          data-following="${friend.total_following}"
                                          data-username="${friend.nomeDeUsuario}" data-isfollowing="1" data-isme="false">
                                        ${friend.nomeDeUsuario}
                                    </span>
                                    <span class="user-list-fullname">${friend.nome} ${friend.sobrenome}</span>
                                </div>
                            </div>
                        `;
                        friendsModalList.appendChild(li);
                    });
                } else {
                    friendsModalList.innerHTML = '<div style="text-align:center; padding: 20px; color: #a09bba;">Nenhum amigo.</div>';
                }
            });
    });

    document.getElementById('close-friends-modal')?.addEventListener('click', () => friendsModal.classList.add('hidden'));
    window.addEventListener('click', (e) => {
        if (e.target === friendsModal) friendsModal.classList.add('hidden');
    });

    // ==========================================
    // 7. HOVER CARD
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
            hcFollowBtn.style.display = 'none'; // Simplificado para community

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

    // Click em username → perfil
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('.hover-trigger');
        if (trigger && !e.target.closest('.post-card')) {
            e.preventDefault();
            const username = trigger.dataset.username;
            if (username) {
                window.location.href = `/espaco-em-foco/userScreen/profile.php?user=${encodeURIComponent(username)}`;
            }
        }
    });

    // ==========================================
    // 8. NAVBAR DROPDOWN
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
    loadPosts(1);
    loadFriends();
})();
