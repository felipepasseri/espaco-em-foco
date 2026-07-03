const modal = document.getElementById('follow-modal');
const closeModal = document.getElementById('close-modal');
const userListContainer = document.getElementById('user-list-container');
const modalTitle = document.getElementById('modal-title');

// Contadores da tela inicial
const countSeguidores = document.getElementById('count-seguidores');
const countSeguindo = document.getElementById('count-seguindo');

// Elementos do Hover Card
const hoverCard = document.getElementById('user-hover-card');
let hoverTimeout;

// ==========================================
// 1. EVENTOS DO MODAL
// ==========================================
document.getElementById('btn-seguidores').addEventListener('click', () => openModal('followers'));
document.getElementById('btn-seguindo').addEventListener('click', () => openModal('following'));

closeModal.addEventListener('click', () => modal.classList.add('hidden'));
window.addEventListener('click', (e) => { 
    if (e.target === modal) modal.classList.add('hidden'); 
});

function openModal(type) {
    modalTitle.innerText = type === 'followers' ? 'Seguidores' : 'Seguindo';
    userListContainer.innerHTML = '<div style="text-align:center; padding: 20px; color: #a09bba;">Carregando...</div>';
    modal.classList.remove('hidden');

    fetch(`../api/api-follow-list.php?type=${type}`)
        .then(res => res.json())
        .then(data => renderList(data, type))
        .catch(err => console.error(err));
}

// ==========================================
// 2. RENDERIZAÇÃO DA LISTA
// ==========================================
function renderList(users, currentType) {
    userListContainer.innerHTML = '';
    
    if (users.length === 0) {
        userListContainer.innerHTML = '<div style="text-align:center; padding: 20px; color: #a09bba;">Nenhum usuário encontrado.</div>';
        return;
    }

    users.forEach(user => {
        const foto = user.fotoPerfil ? `../${user.fotoPerfil}` : '../img/user-profile-default.jpg';
        const li = document.createElement('li');
        li.className = 'user-list-item';
        
        let html = `
          <div class="user-list-info">
            <img src="${foto}" alt="Perfil" class="user-list-avatar">
            <div class="user-list-names">
              <div style="display: flex; align-items: center; gap: 6px;">
                
                <span class="user-list-username hover-trigger" 
                      data-avatar="${foto}"
                      data-user="${user.nomeDeUsuario}"
                      data-name="${user.nome} ${user.sobrenome}"
                      data-level="${user.userLevel}"
                      data-xp="${user.userPoints}"
                      data-followers="${user.total_followers}"
                      data-following="${user.total_following}">
                  ${user.nomeDeUsuario}
                </span>
        `;

        // Se for na aba seguidores e não segue de volta, mostra o textinho azul "Seguir"
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

        // Botões grandes da direita
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
// 3. AÇÕES (SEGUIR, REMOVER, DEIXAR DE SEGUIR)
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
            // Atualiza os contadores na tela de fundo
            if (data.newFollowersCount !== undefined) countSeguidores.innerText = data.newFollowersCount;
            if (data.newFollowingCount !== undefined) countSeguindo.innerText = data.newFollowingCount;

            element.disabled = false; 

            // ==========================================
            // LÓGICA DE ALTERNÂNCIA (TOGGLE) DOS BOTÕES
            // ==========================================
            if (action === 'remove_follower') {
                element.closest('.user-list-item').remove();
            } 
            else if (action === 'unfollow' && element.classList.contains('btn-action')) {
                element.innerText = 'Seguir';
                element.classList.remove('btn-seguindo');
                element.classList.add('btn-seguir');
                element.setAttribute('onclick', `handleAction('follow', '${targetEmail}', this, '${currentType}')`);
            } 
            else if (action === 'follow' && element.classList.contains('btn-action')) {
                element.innerText = 'Seguindo';
                element.classList.remove('btn-seguir');
                element.classList.add('btn-seguindo');
                element.setAttribute('onclick', `handleAction('unfollow', '${targetEmail}', this, '${currentType}')`);
            }
            else if (action === 'follow' && element.classList.contains('follow-back-btn')) {
                // Apenas remove o textinho azul se ele clicar nele
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

// ==========================================
// 4. LÓGICA DO HOVER CARD (MINI PERFIL)
// ==========================================
userListContainer.addEventListener('mouseover', (e) => {
    const trigger = e.target.closest('.hover-trigger');
    if (trigger) {
        clearTimeout(hoverTimeout);
        
        // Preenche o cartão flutuante com os dados da pessoa
        document.getElementById('hc-avatar').src = trigger.dataset.avatar;
        document.getElementById('hc-username').innerText = trigger.dataset.user;
        document.getElementById('hc-fullname').innerText = trigger.dataset.name;
        document.getElementById('hc-level').innerText = trigger.dataset.level;
        document.getElementById('hc-xp').innerText = trigger.dataset.xp;
        document.getElementById('hc-followers').innerText = trigger.dataset.followers;
        document.getElementById('hc-following').innerText = trigger.dataset.following;
        
        // Pega as coordenadas e exibe
        const rect = trigger.getBoundingClientRect();
        hoverCard.style.top = `${rect.bottom + 5}px`; 
        hoverCard.style.left = `${rect.left}px`;
        hoverCard.classList.remove('hidden');
    }
});

userListContainer.addEventListener('mouseout', (e) => {
    if (e.target.closest('.hover-trigger')) {
        // Tolerância para o mouse poder ir até o Hover Card sem ele sumir
        hoverTimeout = setTimeout(() => {
            hoverCard.classList.add('hidden');
        }, 300);
    }
});

// Garante que o cartão fique aberto enquanto o mouse estiver sobre ele
hoverCard.addEventListener('mouseover', () => clearTimeout(hoverTimeout));
hoverCard.addEventListener('mouseout', () => {
    hoverTimeout = setTimeout(() => hoverCard.classList.add('hidden'), 300);
});