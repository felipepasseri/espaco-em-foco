const modal = document.getElementById('follow-modal');
    const rankingModal = document.getElementById('ranking-modal');
    const userListContainer = document.getElementById('user-list-container');
    const rankingListContainer = document.getElementById('ranking-list-container');
    const myRankingContainer = document.getElementById('my-ranking-container');
    const countSeguidores = document.getElementById('count-seguidores');
    const countSeguindo = document.getElementById('count-seguindo');
    const hoverCard = document.getElementById('user-hover-card');
    let hoverTimeout;

    // ==========================================
    // 1. EVENTOS DOS MODAIS
    // ==========================================
    document.getElementById('btn-seguidores').addEventListener('click', () => openFollowModal('followers'));
    document.getElementById('btn-seguindo').addEventListener('click', () => openFollowModal('following'));
    document.getElementById('close-modal').addEventListener('click', () => modal.classList.add('hidden'));

    document.getElementById('btn-ranking').addEventListener('click', () => loadRanking(10));
    document.getElementById('close-ranking').addEventListener('click', () => rankingModal.classList.add('hidden'));

    window.addEventListener('click', (e) => { 
        if (e.target === modal) modal.classList.add('hidden'); 
        if (e.target === rankingModal) rankingModal.classList.add('hidden'); 
    });

    document.querySelectorAll('.rank-tab').forEach(tab => {
        tab.addEventListener('click', (e) => {
            document.querySelectorAll('.rank-tab').forEach(t => t.classList.remove('active'));
            e.target.classList.add('active');
            loadRanking(e.target.dataset.limit);
        });
    });

    // ==========================================
    // 2. BUSCAS (FETCH) E RENDERIZAÇÕES
    // ==========================================
    function openFollowModal(type) {
        document.getElementById('modal-title').innerText = type === 'followers' ? 'Seguidores' : 'Seguindo';
        userListContainer.innerHTML = '<div style="text-align:center; padding: 20px; color: #a09bba;">Carregando...</div>';
        modal.classList.remove('hidden');

        fetch(`../api/api-follow-list.php?type=${type}`)
            .then(res => res.json())
            .then(data => renderList(data, type))
            .catch(err => console.error(err));
    }

    function renderList(users, currentType) {
        userListContainer.innerHTML = '';
        if (users.length === 0) {
            userListContainer.innerHTML = '<div style="text-align:center; padding: 20px; color: #a09bba;">Nenhum usuário encontrado.</div>';
            return;
        }

        users.forEach(user => {
            const foto = user.fotoPerfil ? `../${user.fotoPerfil}` : '../img/user-profile-default.jpg';
            const isFollowing = currentType === 'following' ? 1 : (user.segue_de_volta ? 1 : 0);
            
            const li = document.createElement('li');
            li.className = 'user-list-item';
            let html = `
              <div class="user-list-info">
                <img src="${foto}" alt="Perfil" class="user-list-avatar">
                <div class="user-list-names">
                  <div style="display: flex; align-items: center; gap: 6px;">
                    <span class="user-list-username hover-trigger" 
                          data-avatar="${foto}" data-user="${user.nomeDeUsuario}"
                          data-name="${user.nome} ${user.sobrenome}" data-level="${user.userLevel}"
                          data-xp="${user.userPoints}" data-followers="${user.total_followers}"
                          data-following="${user.total_following}"
                          data-email="${user.email}" data-isfollowing="${isFollowing}" data-isme="false">
                      ${user.nomeDeUsuario}
                    </span>
            `;
            if (currentType === 'followers' && !user.segue_de_volta) {
                html += `<span class="follow-back-btn" onclick="handleAction('follow', '${user.email}', this, '${currentType}')">Seguir</span>`;
            }
            html += `
                  </div>
                  <span class="user-list-fullname">${user.nome} ${user.sobrenome}</span>
                </div>
              </div>
              <div class="user-list-actions">
            `;
            if (currentType === 'followers') {
                html += `<button class="btn-action btn-remover" onclick="handleAction('remove_follower', '${user.email}', this, '${currentType}')">Remover</button>`;
            } else {
                html += `<button class="btn-action btn-seguindo" onclick="handleAction('unfollow', '${user.email}', this, '${currentType}')">Seguindo</button>`;
            }
            html += `</div>`;
            li.innerHTML = html;
            userListContainer.appendChild(li);
        });
    }

    // ==========================================
    // RANKING RENDER LOGIC
    // ==========================================
    function loadRanking(limit) {
        rankingModal.classList.remove('hidden');
        rankingListContainer.innerHTML = '<div style="text-align:center; padding: 20px; color: #a09bba;">Carregando ranking...</div>';
        myRankingContainer.innerHTML = '';

        fetch(`../api/api-ranking.php?limit=${limit}`)
            .then(res => res.json())
            .then(data => {
                renderRankingList(data.topUsers, data.me);
                renderMyRanking(data.me);
            })
            .catch(err => console.error(err));
    }

    function renderRankingList(users, me) {
        rankingListContainer.innerHTML = '';
        users.forEach((user) => {
            const foto = user.fotoPerfil ? `../${user.fotoPerfil}` : '../img/user-profile-default.jpg';
            const li = document.createElement('li');
            li.className = 'user-list-item';
            
            // Destaque de Posição
            let rankBadgeClass = 'rank-badge';
            if(user.rank === 1) rankBadgeClass += ' rank-1';
            if(user.rank === 2) rankBadgeClass += ' rank-2';
            if(user.rank === 3) rankBadgeClass += ' rank-3';

            // Verifica se este usuário do ranking sou "Eu"
            const isMe = user.email === me.email;
            const displayName = isMe 
                ? `<span style="color: #FFAE00;">${user.nomeDeUsuario} (Você)</span>` 
                : user.nomeDeUsuario;

            li.innerHTML = `
              <div class="user-list-info">
                <img src="${foto}" alt="Perfil" class="user-list-avatar" ${isMe ? 'style="border: 2px solid #FFAE00;"' : ''}>
                <div class="user-list-names">
                  <div style="display: flex; align-items: center; gap: 6px;">
                    <span class="user-list-username hover-trigger" 
                          data-avatar="${foto}" data-user="${user.nomeDeUsuario}"
                          data-name="${user.nome} ${user.sobrenome}" data-level="${user.userLevel}"
                          data-xp="${user.userPoints}" data-followers="${user.total_followers}"
                          data-following="${user.total_following}"
                          data-email="${user.email}" data-isfollowing="${user.estou_seguindo}" data-isme="${isMe}">
                      ${displayName}
                    </span>
                    <span style="font-size: 11px; color: #00e5ff; background: rgba(0, 229, 255, 0.1); padding: 2px 6px; border-radius: 4px;">Lv. ${user.userLevel}</span>
                  </div>
                  <span class="user-list-fullname">${user.nome} ${user.sobrenome}</span>
                </div>
              </div>
              <div class="user-list-actions">
                 <div class="${rankBadgeClass}">#${user.rank}</div>
              </div>
            `;
            rankingListContainer.appendChild(li);
        });
    }

    function renderMyRanking(me) {
        const foto = me.fotoPerfil ? `../${me.fotoPerfil}` : '../img/user-profile-default.jpg';
        myRankingContainer.innerHTML = `
              <div class="user-list-item" style="padding: 0;">
                <div class="user-list-info">
                  <img src="${foto}" alt="Perfil" class="user-list-avatar" style="border: 2px solid #FFAE00;">
                  <div class="user-list-names">
                    <span class="user-list-username" style="color: #FFAE00;">Você (${me.nomeDeUsuario})</span>
                    <span class="user-list-fullname">Nível ${me.userLevel} • ${me.userPoints} XP</span>
                  </div>
                </div>
                <div class="user-list-actions">
                   <div class="rank-badge" style="background: rgba(255, 174, 0, 0.2); color: #FFAE00; border-color: #FFAE00;">#${me.rank}</div>
                </div>
              </div>
        `;
    }

    // ==========================================
    // 3. AÇÃO UNIVERSAL (SEGUIR/REMOVER/UNFOLLOW)
    // ==========================================
    function handleAction(action, targetEmail, element, currentType) {
        if (element.disabled) return;
        element.disabled = true;
        const originalText = element.innerText;
        element.innerText = '...';

        fetch('../api/api-follow-action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, targetEmail })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.newFollowersCount !== undefined) countSeguidores.innerText = data.newFollowersCount;
                if (data.newFollowingCount !== undefined) countSeguindo.innerText = data.newFollowingCount;
                element.disabled = false; 

                if (action === 'remove_follower') {
                    element.closest('.user-list-item').remove();
                } 
                else if (action === 'unfollow' && element.classList.contains('btn-action')) {
                    element.innerText = 'Seguir';
                    element.classList.remove('btn-seguindo');
                    element.classList.add('btn-seguir');
                    element.setAttribute('onclick', `handleAction('follow', '${targetEmail}', this, '${currentType}')`);
                    
                    // Se foi pelo hover, atualiza o data-attribute para o próximo mouseover
                    if(currentType === 'hover') updateHoverTriggerData(targetEmail, '0');
                } 
                else if (action === 'follow' && element.classList.contains('btn-action')) {
                    element.innerText = 'Seguindo';
                    element.classList.remove('btn-seguir');
                    element.classList.add('btn-seguindo');
                    element.setAttribute('onclick', `handleAction('unfollow', '${targetEmail}', this, '${currentType}')`);
                    
                    if(currentType === 'hover') updateHoverTriggerData(targetEmail, '1');
                }
                else if (action === 'follow' && element.classList.contains('follow-back-btn')) {
                    element.remove(); 
                }
            } else {
                alert('Erro ao realizar ação.');
                element.innerText = originalText;
                element.disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            element.innerText = originalText;
            element.disabled = false;
        });
    }

    // Atualiza silenciosamente a lista por trás caso o card suma
    function updateHoverTriggerData(email, isFollowingStr) {
        const trigger = document.querySelector(`.hover-trigger[data-email="${email}"]`);
        if(trigger) trigger.setAttribute('data-isfollowing', isFollowingStr);
    }

    // ==========================================
    // 4. LÓGICA DO HOVER CARD (MINI PERFIL)
    // ==========================================
    // Ouve o evento no documento inteiro, cobrindo Seguidores, Seguindo e Ranking
    document.addEventListener('mouseover', (e) => {
        const trigger = e.target.closest('.hover-trigger');
        if (trigger) {
            clearTimeout(hoverTimeout);
            
            // Popula os dados
            document.getElementById('hc-avatar').src = trigger.dataset.avatar;
            document.getElementById('hc-username').innerText = trigger.dataset.user;
            document.getElementById('hc-fullname').innerText = trigger.dataset.name;
            document.getElementById('hc-level').innerText = trigger.dataset.level;
            document.getElementById('hc-xp').innerText = trigger.dataset.xp;
            document.getElementById('hc-followers').innerText = trigger.dataset.followers;
            document.getElementById('hc-following').innerText = trigger.dataset.following;
            
            // Lógica do botão de Seguir no Hover
            const hcFollowBtn = document.getElementById('hc-follow-btn');
            const isMe = trigger.dataset.isme === 'true';
            
            if (isMe) {
                // Esconde o botão se a pessoa for você mesmo
                hcFollowBtn.style.display = 'none';
            } else {
                hcFollowBtn.style.display = 'inline-block';
                const isFollowing = trigger.dataset.isfollowing === '1';
                const userEmail = trigger.dataset.email;

                if (isFollowing) {
                    hcFollowBtn.className = 'btn-action btn-seguindo';
                    hcFollowBtn.innerText = 'Seguindo';
                    hcFollowBtn.setAttribute('onclick', `handleAction('unfollow', '${userEmail}', this, 'hover')`);
                } else {
                    hcFollowBtn.className = 'btn-action btn-seguir';
                    hcFollowBtn.innerText = 'Seguir';
                    hcFollowBtn.setAttribute('onclick', `handleAction('follow', '${userEmail}', this, 'hover')`);
                }
            }

            // Exibe o card flutuante
            const rect = trigger.getBoundingClientRect();
            hoverCard.style.top = `${rect.bottom + 5}px`; 
            hoverCard.style.left = `${rect.left}px`;
            hoverCard.classList.remove('hidden');
        }
    });

    document.addEventListener('mouseout', (e) => {
        if (e.target.closest('.hover-trigger')) {
            hoverTimeout = setTimeout(() => { hoverCard.classList.add('hidden'); }, 300);
        }
    });

    hoverCard.addEventListener('mouseover', () => clearTimeout(hoverTimeout));
    hoverCard.addEventListener('mouseout', () => {
        hoverTimeout = setTimeout(() => hoverCard.classList.add('hidden'), 300);
    });