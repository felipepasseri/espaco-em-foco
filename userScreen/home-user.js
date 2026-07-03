
    const modal = document.getElementById('follow-modal');
    const closeModal = document.getElementById('close-modal');
    const userListContainer = document.getElementById('user-list-container');
    const modalTitle = document.getElementById('modal-title');
    
    const countSeguidores = document.getElementById('count-seguidores');
    const countSeguindo = document.getElementById('count-seguindo');

    document.getElementById('btn-seguidores').addEventListener('click', () => openModal('followers'));
    document.getElementById('btn-seguindo').addEventListener('click', () => openModal('following'));

    closeModal.addEventListener('click', () => modal.classList.add('hidden'));
    window.addEventListener('click', (e) => { if (e.target === modal) modal.classList.add('hidden'); });

    function openModal(type) {
      modalTitle.innerText = type === 'followers' ? 'Seguidores' : 'Seguindo';
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
        const li = document.createElement('li');
        li.className = 'user-list-item';
        
        let html = `
          <div class="user-list-info">
            <img src="${foto}" alt="Perfil" class="user-list-avatar">
            <div class="user-list-names">
              <span class="user-list-username">${user.nomeDeUsuario}
        `;

        if (currentType === 'followers' && !user.segue_de_volta) {
          html += ` <span class="follow-back-btn" onclick="handleAction('follow', '${user.email}', this, '${currentType}')">Seguir</span>`;
        }

        html += `
              </span>
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

          element.disabled = false; // Libera o botão para novos cliques

          // Lógica de alternância (Toggle) dos botões
          if (action === 'remove_follower') {
             // Na aba de seguidores, o "remover" continua apagando a pessoa da lista na hora
             element.closest('.user-list-item').remove();
          } 
          else if (action === 'unfollow' && element.classList.contains('btn-action')) {
             // Transformar o botão "Seguindo" em "Seguir"
             element.innerText = 'Seguir';
             element.classList.remove('btn-seguindo');
             element.classList.add('btn-seguir');
             // Muda o que o botão fará no próximo clique
             element.setAttribute('onclick', `handleAction('follow', '${targetEmail}', this, '${currentType}')`);
          } 
          else if (action === 'follow' && element.classList.contains('btn-action')) {
             // Transformar o botão "Seguir" de volta em "Seguindo"
             element.innerText = 'Seguindo';
             element.classList.remove('btn-seguir');
             element.classList.add('btn-seguindo');
             // Muda o que o botão fará no próximo clique
             element.setAttribute('onclick', `handleAction('unfollow', '${targetEmail}', this, '${currentType}')`);
          }
          else if (action === 'follow' && element.classList.contains('follow-back-btn')) {
             // Se for aquele textinho azul pequeno de seguir de volta (aba seguidores), ele apenas some
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
