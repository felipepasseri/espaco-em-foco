// ==========================================
// PERFIL PÚBLICO — JavaScript
// ==========================================
(function () {
    const followModal = document.getElementById('profile-follow-modal');
    const userListContainer = document.getElementById('profile-user-list');
    const hoverCard = document.getElementById('user-hover-card');
    const reportModal = document.getElementById('report-modal');
    let hoverTimeout;

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

    // Pega o username da URL
    const urlParams = new URLSearchParams(window.location.search);
    const profileUsername = urlParams.get('user');

    // ==========================================
    // 1. BOTÃO SEGUIR / SEGUINDO
    // ==========================================
    const followBtn = document.getElementById('profile-follow-btn');
    if (followBtn) {
        followBtn.addEventListener('click', function () {
            const btn = this;
            const username = btn.dataset.username;
            const isFollowing = btn.dataset.following === '1';
            const action = isFollowing ? 'unfollow' : 'follow';

            btn.disabled = true;
            btn.innerText = '...';

            fetch('../api/api-follow-action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, targetUsername: username })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        if (action === 'follow') {
                            btn.innerText = 'Seguindo';
                            btn.className = 'btn-action btn-seguindo';
                            btn.dataset.following = '1';
                        } else {
                            btn.innerText = 'Seguir';
                            btn.className = 'btn-action btn-seguir';
                            btn.dataset.following = '0';
                        }
                        // Atualiza contadores do perfil visitado
                        const seguidoresEl = document.getElementById('profile-count-seguidores');
                        if (seguidoresEl) {
                            const current = parseInt(seguidoresEl.innerText) || 0;
                            seguidoresEl.innerText = action === 'follow' ? current + 1 : Math.max(0, current - 1);
                        }
                    } else {
                        alert('Erro ao realizar ação.');
                    }
                    btn.disabled = false;
                })
                .catch(() => {
                    btn.innerText = isFollowing ? 'Seguindo' : 'Seguir';
                    btn.disabled = false;
                });
        });
    }

    // ==========================================
    // 2. MODAL DE SEGUIDORES / SEGUINDO
    // ==========================================
    document.getElementById('profile-btn-seguidores')?.addEventListener('click', () => openFollowModal('followers'));
    document.getElementById('profile-btn-seguindo')?.addEventListener('click', () => openFollowModal('following'));
    document.getElementById('profile-close-modal')?.addEventListener('click', () => followModal.classList.add('hidden'));

    window.addEventListener('click', (e) => {
        if (e.target === followModal) followModal.classList.add('hidden');
        if (e.target === reportModal) reportModal.classList.add('hidden');
    });

    function openFollowModal(type) {
        document.getElementById('profile-modal-title').innerText = type === 'followers' ? 'Seguidores' : 'Seguindo';
        userListContainer.innerHTML = '<div style="text-align:center; padding: 20px; color: #a09bba;">Carregando...</div>';
        followModal.classList.remove('hidden');

        // Busca seguidores/seguindo do perfil que estamos visitando
        fetch(`../api/api-follow-list.php?type=${type}&target=${encodeURIComponent(profileUsername)}`)
            .then(res => res.json())
            .then(data => renderFollowList(data, type))
            .catch(err => console.error(err));
    }

    function renderFollowList(users, type) {
        userListContainer.innerHTML = '';
        if (users.length === 0) {
            userListContainer.innerHTML = '<div style="text-align:center; padding: 20px; color: #a09bba;">Nenhum usuário encontrado.</div>';
            return;
        }

        users.forEach(user => {
            const foto = user.fotoPerfil ? `../${user.fotoPerfil}` : '../img/user-profile-default.jpg';
            const li = document.createElement('li');
            li.className = 'user-list-item';
            li.innerHTML = `
                <div class="user-list-info">
                    <img src="${foto}" alt="Perfil" class="user-list-avatar">
                    <div class="user-list-names">
                        <span class="user-list-username hover-trigger"
                              data-avatar="${foto}" data-user="${user.nomeDeUsuario}"
                              data-name="${user.nome} ${user.sobrenome}" data-level="${user.userLevel}"
                              data-xp="${user.userPoints}" data-followers="${user.total_followers}"
                              data-following="${user.total_following}"
                              data-username="${user.nomeDeUsuario}" data-isfollowing="0" data-isme="false">
                            ${user.nomeDeUsuario}
                        </span>
                        <span class="user-list-fullname">${user.nome} ${user.sobrenome}</span>
                    </div>
                </div>
            `;
            userListContainer.appendChild(li);
        });
    }

    // ==========================================
    // 3. TABS (Publicações / Reposts)
    // ==========================================
    document.querySelectorAll('.profile-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.profile-tab-content').forEach(c => c.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById(`tab-${tab.dataset.tab}`).classList.add('active');
        });
    });

    // ==========================================
    // 4. FILTRO DE ORDEM (Mais recentes / Mais relevantes)
    // ==========================================
    document.querySelectorAll('.profile-filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.profile-filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const order = btn.dataset.order;
            // Recarrega com novo filtro
            window.location.href = `profile.php?user=${encodeURIComponent(profileUsername)}&order=${order}`;
        });
    });

    // ==========================================
    // 5. HOVER CARD
    // ==========================================
    document.addEventListener('mouseover', (e) => {
        const trigger = e.target.closest('.hover-trigger');
        if (trigger) {
            clearTimeout(hoverTimeout);
            document.getElementById('hc-avatar').src = trigger.dataset.avatar;
            document.getElementById('hc-username').innerText = trigger.dataset.user;
            document.getElementById('hc-fullname').innerText = trigger.dataset.name;
            document.getElementById('hc-level').innerText = trigger.dataset.level;
            document.getElementById('hc-xp').innerText = formatXP(trigger.dataset.xp);
            document.getElementById('hc-followers').innerText = trigger.dataset.followers;
            document.getElementById('hc-following').innerText = trigger.dataset.following;

            const hcFollowBtn = document.getElementById('hc-follow-btn');
            const isMe = trigger.dataset.isme === 'true';
            if (isMe) {
                hcFollowBtn.style.display = 'none';
            } else {
                hcFollowBtn.style.display = 'inline-block';
                const isFollowing = trigger.dataset.isfollowing === '1';
                const userUsername = trigger.dataset.username;
                if (isFollowing) {
                    hcFollowBtn.className = 'btn-action btn-seguindo hc-btn-fixed';
                    hcFollowBtn.innerText = 'Seguindo';
                    hcFollowBtn.onclick = () => handleHoverFollow('unfollow', userUsername, hcFollowBtn);
                } else {
                    hcFollowBtn.className = 'btn-action btn-seguir hc-btn-fixed';
                    hcFollowBtn.innerText = 'Seguir';
                    hcFollowBtn.onclick = () => handleHoverFollow('follow', userUsername, hcFollowBtn);
                }
            }

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

    function handleHoverFollow(action, username, btn) {
        btn.disabled = true;
        fetch('../api/api-follow-action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, targetUsername: username })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (action === 'follow') {
                        btn.className = 'btn-action btn-seguindo hc-btn-fixed';
                        btn.innerText = 'Seguindo';
                        btn.onclick = () => handleHoverFollow('unfollow', username, btn);
                    } else {
                        btn.className = 'btn-action btn-seguir hc-btn-fixed';
                        btn.innerText = 'Seguir';
                        btn.onclick = () => handleHoverFollow('follow', username, btn);
                    }
                }
                btn.disabled = false;
            })
            .catch(() => { btn.disabled = false; });
    }

    // Click em username → perfil
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('.hover-trigger');
        if (trigger) {
            const username = trigger.dataset.username;
            if (username && trigger.dataset.isme !== 'true') {
                window.location.href = `/espaco-em-foco/userScreen/profile.php?user=${encodeURIComponent(username)}`;
            }
        }
    });

    // ==========================================
    // 6. DENÚNCIA
    // ==========================================
    document.getElementById('btn-denunciar')?.addEventListener('click', () => {
        reportModal.classList.remove('hidden');
    });

    document.getElementById('close-report-modal')?.addEventListener('click', () => {
        reportModal.classList.add('hidden');
    });

    document.getElementById('report-form')?.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = this.querySelector('.report-submit-btn');
        submitBtn.disabled = true;
        submitBtn.innerText = 'Enviando...';

        fetch('../api/api-report.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Denúncia enviada com sucesso! Ela será analisada por um administrador.');
                    reportModal.classList.add('hidden');
                    this.reset();
                } else {
                    alert('Erro ao enviar denúncia: ' + (data.error || 'Tente novamente.'));
                }
                submitBtn.disabled = false;
                submitBtn.innerText = 'Enviar Denúncia';
            })
            .catch(() => {
                alert('Erro ao enviar denúncia.');
                submitBtn.disabled = false;
                submitBtn.innerText = 'Enviar Denúncia';
            });
    });

    // ==========================================
    // 7. DROPDOWN DA NAVBAR
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
