const modal = document.getElementById('follow-modal');
    const rankingModal = document.getElementById('ranking-modal');
    const camadasModal = document.getElementById('camadas-modal');
    const userListContainer = document.getElementById('user-list-container');
    const rankingListContainer = document.getElementById('ranking-list-container');
    const myRankingContainer = document.getElementById('my-ranking-container');
    const camadasTreeContainer = document.getElementById('camadas-tree-container');
    const countSeguidores = document.getElementById('count-seguidores');
    const countSeguindo = document.getElementById('count-seguindo');
    const hoverCard = document.getElementById('user-hover-card');
    let hoverTimeout;

    // ==========================================
    // 0. FUNÇÃO DE FORMATAÇÃO DE XP
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
    // 1. EVENTOS DOS MODAIS
    // ==========================================
    document.getElementById('btn-seguidores').addEventListener('click', () => openFollowModal('followers'));
    document.getElementById('btn-seguindo').addEventListener('click', () => openFollowModal('following'));
    document.getElementById('close-modal').addEventListener('click', () => modal.classList.add('hidden'));

    document.getElementById('btn-ranking').addEventListener('click', () => loadRanking(10));
    document.getElementById('close-ranking').addEventListener('click', () => rankingModal.classList.add('hidden'));

    document.getElementById('btn-camada')?.addEventListener('click', () => loadCamadas());
    document.getElementById('close-camadas')?.addEventListener('click', () => camadasModal?.classList.add('hidden'));

    window.addEventListener('click', (e) => { 
        if (e.target === modal) modal.classList.add('hidden'); 
        if (e.target === rankingModal) rankingModal.classList.add('hidden'); 
        if (e.target === camadasModal) camadasModal.classList.add('hidden'); 
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
                          data-name="${user.nome} ${user.sobrenome}" data-level="${user.userlevel ?? user.userLevel ?? 1}"
                          data-xp="${user.userpoints ?? user.userPoints ?? 0}" data-followers="${user.total_followers ?? 0}"
                          data-following="${user.total_following ?? 0}"
                          data-username="${user.nomeDeUsuario}" data-isfollowing="${isFollowing}" data-isme="false">
                      ${user.nomeDeUsuario}
                    </span>
            `;
            if (currentType === 'followers' && !user.segue_de_volta) {
                html += `<span class="follow-back-btn" onclick="handleAction('follow', '${user.nomeDeUsuario}', this, '${currentType}')">Seguir</span>`;
            }
            html += `
                  </div>
                  <span class="user-list-fullname">${user.nome} ${user.sobrenome}</span>
                </div>
              </div>
              <div class="user-list-actions">
            `;
            if (currentType === 'followers') {
                html += `<button class="btn-action btn-remover" onclick="handleAction('remove_follower', '${user.nomeDeUsuario}', this, '${currentType}')">Remover</button>`;
            } else {
                html += `<button class="btn-action btn-seguindo" onclick="handleAction('unfollow', '${user.nomeDeUsuario}', this, '${currentType}')">Seguindo</button>`;
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
            const isMe = user.nomeDeUsuario === me.nomeDeUsuario;
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
                          data-name="${user.nome} ${user.sobrenome}" data-level="${user.userlevel ?? user.userLevel ?? 1}"
                          data-xp="${user.userpoints ?? user.userPoints ?? 0}" data-followers="${user.total_followers ?? 0}"
                          data-following="${user.total_following ?? 0}"
                          data-username="${user.nomeDeUsuario}" data-isfollowing="${user.estou_seguindo}" data-isme="${isMe}">
                      ${displayName}
                    </span>
                    <span style="font-size: 11px; color: #00e5ff; background: rgba(0, 229, 255, 0.1); padding: 2px 6px; border-radius: 4px;">Lv. ${user.userlevel ?? user.userLevel ?? 1}</span>
                    <span style="font-size: 11px; color: #FFAE00; background: rgba(255, 174, 0, 0.1); padding: 2px 6px; border-radius: 4px;">${formatXP(user.userpoints ?? user.userPoints ?? 0)} XP</span>
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
                    <span class="user-list-fullname">Nível ${me.userlevel ?? me.userLevel ?? 1} • ${formatXP(me.userpoints ?? me.userPoints ?? 0)} XP</span>
                  </div>
                </div>
                <div class="user-list-actions">
                   <div class="rank-badge" style="background: rgba(255, 174, 0, 0.2); color: #FFAE00; border-color: #FFAE00;">#${me.rank}</div>
                </div>
              </div>
        `;
    }

    // ==========================================
    // 2.1 JORNADA DAS CAMADAS (ÁRVORE DE PROGRESSÃO)
    // ==========================================
    function loadCamadas() {
        if (!camadasModal) return;
        camadasModal.classList.remove('hidden');

        if (camadasTreeContainer) {
            camadasTreeContainer.innerHTML = `
                <div style="text-align:center; padding: 40px 20px; color: #a09bba;">
                    <div style="font-size: 28px; margin-bottom: 10px; animation: pulse-icon 1.5s infinite alternate;">🌌</div>
                    <span>Mapeando as camadas do Universo...</span>
                </div>
            `;
        }

        fetch('../api/api-camadas.php')
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    if (camadasTreeContainer) {
                        camadasTreeContainer.innerHTML = '<div style="text-align:center; padding: 30px; color: #ff5252;">Erro ao carregar dados das camadas.</div>';
                    }
                    return;
                }
                renderCamadasTree(data);
            })
            .catch(err => {
                console.error(err);
                if (camadasTreeContainer) {
                    camadasTreeContainer.innerHTML = '<div style="text-align:center; padding: 30px; color: #ff5252;">Erro ao conectar com o servidor.</div>';
                }
            });
    }

    function renderCamadasTree(data) {
        if (!camadasTreeContainer) return;

        // Atualiza valor dinâmico também no card da dashboard
        const currentCamadaCardValue = document.getElementById('user-current-camada');
        if (currentCamadaCardValue && data.camada_atual) {
            currentCamadaCardValue.innerText = data.camada_atual.nome_camada;
        }

        // Atualiza header de resumo no modal
        const summaryNome = document.getElementById('summary-camada-nome');
        const summaryXpInfo = document.getElementById('summary-xp-info');
        const summaryNextText = document.getElementById('summary-next-text');
        const summaryNextFill = document.getElementById('summary-next-bar-fill');

        if (summaryNome && data.camada_atual) {
            summaryNome.innerText = data.camada_atual.nome_camada;
        }
        if (summaryXpInfo) {
            summaryXpInfo.innerText = `XP Total: ${formatXP(data.user_xp)} XP`;
        }
        if (data.proxima_camada) {
            if (summaryNextText) {
                summaryNextText.innerHTML = `Próxima: <strong style="color:#00e5ff;">${data.proxima_camada.nome_camada}</strong> (${formatXP(data.proxima_camada.xp_necessario)} XP) • Faltam ${formatXP(data.proxima_camada.xp_faltante)} XP (${data.progresso_proxima}%)`;
            }
            if (summaryNextFill) {
                summaryNextFill.style.width = `${data.progresso_proxima}%`;
            }
        } else {
            if (summaryNextText) {
                summaryNextText.innerHTML = `<strong style="color:#FFAE00;">✨ Você alcançou o ápice do Universo Observável!</strong>`;
            }
            if (summaryNextFill) {
                summaryNextFill.style.width = '100%';
            }
        }

        const camadas = data.camadas || [];
        const userXp = Number(data.user_xp) || 0;

        let html = '<div class="camadas-tree">';

        // Renderização de baixo para cima:
        // Universo Observável (índice 9) no topo, até Núcleo (índice 0) na base.
        for (let i = camadas.length - 1; i >= 0; i--) {
            const camada = camadas[i];
            const isUnlocked = camada.desbloqueada;
            const isCurrent = camada.is_atual;

            let cardClass = 'camada-card';
            let badgeHtml = '';

            if (isCurrent) {
                cardClass += ' current';
                badgeHtml = '<span class="camada-status-badge current">★ Camada Atual</span>';
            } else if (isUnlocked) {
                cardClass += ' unlocked';
                badgeHtml = '<span class="camada-status-badge unlocked">✔ Desbloqueada</span>';
            } else {
                cardClass += ' locked';
                badgeHtml = '<span class="camada-status-badge locked">🔒 Bloqueada</span>';
            }

            const pctFormatada = Number(camada.porcentagem || 0).toFixed(1).replace('.', ',');

            html += `
                <div class="camada-item" data-id="${camada.id_camada}">
                    <div class="${cardClass}" id="camada-card-${camada.id_camada}">
                        <div class="camada-icon-box">
                            ${getCamadaSvg(camada.id_camada)}
                        </div>
                        <div class="camada-content">
                            <div class="camada-top-row">
                                <span class="camada-nome">${camada.nome_camada}</span>
                                <span class="camada-xp-req">${formatXP(camada.xp_necessario)} XP</span>
                            </div>
                            <div class="camada-bottom-row">
                                <div class="camada-stat-explorers" title="${camada.usuarios_que_alcancaram} de ${camada.total_usuarios} exploradores elegíveis">
                                    <span class="camada-stat-icon-mini">👥</span>
                                    <span><strong class="stat-pct">${pctFormatada}%</strong> dos exploradores alcançaram esta camada</span>
                                </div>
                                ${badgeHtml}
                            </div>
                        </div>
                    </div>
            `;

            // Conector vertical para a camada imediatamente inferior (i - 1):
            // O conector representa o caminho entre camadas[i - 1] (base) e camadas[i] (topo).
            if (i > 0) {
                const prevCamada = camadas[i - 1];
                let connectorPercent = 0;

                if (userXp >= camada.xp_necessario) {
                    connectorPercent = 100;
                } else if (userXp <= prevCamada.xp_necessario) {
                    connectorPercent = 0;
                } else {
                    const range = camada.xp_necessario - prevCamada.xp_necessario;
                    if (range > 0) {
                        const progress = ((userXp - prevCamada.xp_necessario) / range) * 100;
                        connectorPercent = Math.max(0, Math.min(100, progress));
                    }
                }

                html += `
                    <div class="camada-connector">
                        <div class="connector-track">
                            <div class="connector-fill" style="height: ${connectorPercent.toFixed(1)}%;"></div>
                        </div>
                    </div>
                `;
            }

            html += `</div>`;
        }

        html += '</div>';

        camadasTreeContainer.innerHTML = html;

        // Auto-scroll suave até a camada atual para o usuário não precisar rolar manualmente
        setTimeout(() => {
            const currentEl = document.querySelector('.camada-card.current');
            if (currentEl) {
                currentEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 150);
    }

    function getCamadaSvg(id) {
        switch(Number(id)) {
            case 1: // Núcleo
                return `<svg viewBox="0 0 64 64" fill="none" class="camada-svg" aria-label="Núcleo">
                    <circle cx="32" cy="32" r="28" stroke="#FFAE00" stroke-width="1.5" stroke-dasharray="3 3" opacity="0.4" />
                    <circle cx="32" cy="32" r="22" fill="#E64A19" opacity="0.85" stroke="#FF7043" stroke-width="2" />
                    <circle cx="32" cy="32" r="14" fill="#FFB300" stroke="#FFE082" stroke-width="2" />
                    <circle cx="32" cy="32" r="7" fill="#FFF9C4" />
                </svg>`;
            case 2: // Manto
                return `<svg viewBox="0 0 64 64" fill="none" class="camada-svg" aria-label="Manto">
                    <circle cx="32" cy="32" r="27" fill="#2E0C06" stroke="#FF5722" stroke-width="1.5" opacity="0.9"/>
                    <path d="M16 26 C20 18, 26 22, 30 25 C34 28, 40 22, 46 25" stroke="#FF8A65" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                    <path d="M14 36 C18 42, 26 38, 30 35 C34 32, 42 40, 48 35" stroke="#FF7043" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                    <circle cx="32" cy="32" r="13" fill="#D84315" stroke="#FFAB91" stroke-width="1.5"/>
                    <circle cx="32" cy="32" r="6" fill="#FFCCBC"/>
                </svg>`;
            case 3: // Crosta
                return `<svg viewBox="0 0 64 64" fill="none" class="camada-svg" aria-label="Crosta">
                    <circle cx="32" cy="32" r="26" fill="#0E2328" stroke="#26A69A" stroke-width="2"/>
                    <path d="M16 35 L23 25 L29 31 L36 21 L43 29 L48 23 L53 31" stroke="#4DB6AC" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    <path d="M14 41 L22 41 L28 45 L38 43 L46 47 L54 41" stroke="#80CBC4" stroke-width="1.5" fill="none" opacity="0.6"/>
                    <polygon points="29,31 36,21 43,29" fill="rgba(38, 166, 154, 0.4)"/>
                    <polygon points="19,35 23,25 29,31" fill="rgba(0, 229, 255, 0.25)"/>
                </svg>`;
            case 4: // Atmosfera
                return `<svg viewBox="0 0 64 64" fill="none" class="camada-svg" aria-label="Atmosfera">
                    <circle cx="32" cy="32" r="29" stroke="#00E5FF" stroke-width="1" opacity="0.4" stroke-dasharray="3 3"/>
                    <circle cx="32" cy="32" r="25" fill="#00E5FF" fill-opacity="0.12" stroke="#00E5FF" stroke-width="1.5" opacity="0.7"/>
                    <circle cx="32" cy="32" r="18" fill="#0277BD" stroke="#29B6F6" stroke-width="1.5"/>
                    <path d="M18 28 C22 24, 28 26, 32 25 C36 24, 42 27, 45 30" stroke="#E1F5FE" stroke-width="2" stroke-linecap="round" opacity="0.8" fill="none"/>
                    <path d="M20 36 C24 38, 30 35, 34 37 C38 39, 42 36, 46 35" stroke="#E1F5FE" stroke-width="1.5" stroke-linecap="round" opacity="0.7" fill="none"/>
                </svg>`;
            case 5: // Órbita Lunar
                return `<svg viewBox="0 0 64 64" fill="none" class="camada-svg" aria-label="Órbita Lunar">
                    <ellipse cx="32" cy="32" rx="26" ry="14" stroke="#00E5FF" stroke-width="1.5" stroke-dasharray="3 3" opacity="0.75" transform="rotate(-25 32 32)"/>
                    <circle cx="30" cy="32" r="11" fill="#1565C0" stroke="#42A5F5" stroke-width="1.5"/>
                    <path d="M25 29 C27 27, 31 28, 33 30 C31 34, 27 34, 25 32 Z" fill="#81C784" opacity="0.85"/>
                    <circle cx="51" cy="22" r="5" fill="#ECEFF1" stroke="#B0BEC5" stroke-width="1"/>
                    <circle cx="50" cy="21" r="1" fill="#78909C"/>
                    <circle cx="52" cy="23" r="0.8" fill="#78909C"/>
                </svg>`;
            case 6: // Sistema Solar
                return `<svg viewBox="0 0 64 64" fill="none" class="camada-svg" aria-label="Sistema Solar">
                    <circle cx="32" cy="32" r="27" stroke="#7C4DFF" stroke-width="1" stroke-dasharray="2 3" opacity="0.5"/>
                    <circle cx="32" cy="32" r="20" stroke="#00E5FF" stroke-width="1" stroke-dasharray="3 2" opacity="0.6"/>
                    <circle cx="32" cy="32" r="14" stroke="#FFAE00" stroke-width="1" stroke-dasharray="2 2" opacity="0.5"/>
                    <circle cx="32" cy="32" r="8" fill="#FFAE00"/>
                    <circle cx="32" cy="32" r="4" fill="#FFF9C4"/>
                    <circle cx="43" cy="24" r="2.5" fill="#4DB6AC"/>
                    <circle cx="16" cy="25" r="3.5" fill="#FFA726"/>
                    <circle cx="48" cy="42" r="4" fill="#AB47BC"/>
                    <circle cx="20" cy="41" r="2" fill="#42A5F5"/>
                </svg>`;
            case 7: // Via Láctea
                return `<svg viewBox="0 0 64 64" fill="none" class="camada-svg" aria-label="Via Láctea">
                    <path d="M32 32 C38 24, 48 24, 54 28 C58 32, 56 42, 48 48 C42 54, 30 54, 22 48" stroke="#E040FB" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                    <path d="M32 32 C26 40, 16 40, 10 36 C6 32, 8 22, 16 16 C22 10, 34 10, 42 16" stroke="#00E5FF" stroke-width="2.5" stroke-linecap="round" fill="none"/>
                    <circle cx="32" cy="32" r="14" fill="#7C4DFF" fill-opacity="0.3"/>
                    <circle cx="32" cy="32" r="6" fill="#FFF8E1"/>
                    <circle cx="46" cy="20" r="1.5" fill="#FFF"/>
                    <circle cx="18" cy="44" r="1.5" fill="#00E5FF"/>
                    <circle cx="50" cy="38" r="1" fill="#FFF"/>
                </svg>`;
            case 8: // Aglomerado Galáctico
                return `<svg viewBox="0 0 64 64" fill="none" class="camada-svg" aria-label="Aglomerado Galáctico">
                    <path d="M12 24 C14 14, 26 8, 36 8" stroke="#7C4DFF" stroke-width="1" stroke-dasharray="2 3" opacity="0.6"/>
                    <path d="M52 40 C50 50, 38 56, 28 56" stroke="#00E5FF" stroke-width="1" stroke-dasharray="2 3" opacity="0.6"/>
                    <ellipse cx="32" cy="30" rx="10" ry="6" fill="#651FFF" opacity="0.75" transform="rotate(-15 32 30)"/>
                    <circle cx="32" cy="30" r="3" fill="#FFF"/>
                    <ellipse cx="18" cy="22" rx="7" ry="3.5" fill="#BA68C8" opacity="0.85" transform="rotate(25 18 22)"/>
                    <circle cx="18" cy="22" r="1.5" fill="#FFF"/>
                    <ellipse cx="46" cy="20" rx="5" ry="3" fill="#4DD0E1" opacity="0.85" transform="rotate(-30 46 20)"/>
                    <ellipse cx="22" cy="44" rx="6" ry="3" fill="#9575CD" opacity="0.85" transform="rotate(45 22 44)"/>
                    <ellipse cx="44" cy="42" rx="7" ry="4" fill="#CE93D8" opacity="0.85" transform="rotate(-20 44 42)"/>
                    <circle cx="44" cy="42" r="1.5" fill="#FFF"/>
                </svg>`;
            case 9: // Teia Cósmica
                return `<svg viewBox="0 0 64 64" fill="none" class="camada-svg" aria-label="Teia Cósmica">
                    <line x1="14" y1="18" x2="32" y2="14" stroke="#00E5FF" stroke-width="1.5" opacity="0.7"/>
                    <line x1="32" y1="14" x2="50" y2="20" stroke="#7C4DFF" stroke-width="1.5" opacity="0.7"/>
                    <line x1="14" y1="18" x2="24" y2="34" stroke="#7C4DFF" stroke-width="2" opacity="0.8"/>
                    <line x1="32" y1="14" x2="40" y2="32" stroke="#00E5FF" stroke-width="2" opacity="0.9"/>
                    <line x1="50" y1="20" x2="40" y2="32" stroke="#E040FB" stroke-width="1.5" opacity="0.7"/>
                    <line x1="24" y1="34" x2="40" y2="32" stroke="#00E5FF" stroke-width="2.5" opacity="0.9"/>
                    <line x1="24" y1="34" x2="18" y2="48" stroke="#E040FB" stroke-width="1.5" opacity="0.7"/>
                    <line x1="40" y1="32" x2="46" y2="48" stroke="#7C4DFF" stroke-width="2" opacity="0.8"/>
                    <line x1="18" y1="48" x2="34" y2="52" stroke="#00E5FF" stroke-width="1.5" opacity="0.7"/>
                    <line x1="46" y1="48" x2="34" y2="52" stroke="#E040FB" stroke-width="2" opacity="0.8"/>
                    <circle cx="24" cy="34" r="4.5" fill="#00E5FF"/>
                    <circle cx="40" cy="32" r="5" fill="#E040FB"/>
                    <circle cx="32" cy="14" r="3.5" fill="#FFF"/>
                    <circle cx="14" cy="18" r="2.5" fill="#80D8FF"/>
                    <circle cx="50" cy="20" r="2.5" fill="#EA80FC"/>
                    <circle cx="18" cy="48" r="3" fill="#B388FF"/>
                    <circle cx="46" cy="48" r="3" fill="#80D8FF"/>
                    <circle cx="34" cy="52" r="3.5" fill="#FFF"/>
                </svg>`;
            case 10: // Universo Observável
                return `<svg viewBox="0 0 64 64" fill="none" class="camada-svg" aria-label="Universo Observável">
                    <circle cx="32" cy="32" r="28" fill="#1A0E3D" stroke="#FFD700" stroke-width="2"/>
                    <circle cx="32" cy="32" r="22" stroke="#00E5FF" stroke-width="1" stroke-dasharray="3 3" opacity="0.6"/>
                    <circle cx="32" cy="32" r="15" stroke="#E040FB" stroke-width="1.5" opacity="0.7"/>
                    <line x1="32" y1="4" x2="32" y2="60" stroke="#FFD700" stroke-width="0.75" opacity="0.4"/>
                    <line x1="4" y1="32" x2="60" y2="32" stroke="#FFD700" stroke-width="0.75" opacity="0.4"/>
                    <line x1="12" y1="12" x2="52" y2="52" stroke="#00E5FF" stroke-width="0.75" opacity="0.3"/>
                    <line x1="12" y1="52" x2="52" y2="12" stroke="#00E5FF" stroke-width="0.75" opacity="0.3"/>
                    <circle cx="32" cy="32" r="7" fill="#00E5FF"/>
                    <circle cx="32" cy="32" r="3" fill="#FFF"/>
                </svg>`;
            default:
                return `<svg viewBox="0 0 64 64" fill="none" class="camada-svg"><circle cx="32" cy="32" r="20" fill="#7C4DFF"/><circle cx="32" cy="32" r="8" fill="#FFF"/></svg>`;
        }
    }

    // ==========================================
    // 3. AÇÃO UNIVERSAL (SEGUIR/REMOVER/UNFOLLOW)
    // ==========================================
    function handleAction(action, targetUsername, element, currentType) {
        if (element.disabled) return;
        element.disabled = true;
        const originalText = element.innerText;
        element.innerText = '...';

        fetch('../api/api-follow-action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, targetUsername })
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
                    element.setAttribute('onclick', `handleAction('follow', '${targetUsername}', this, '${currentType}')`);
                    
                    // Se foi pelo hover, atualiza o data-attribute para o próximo mouseover
                    if(currentType === 'hover') updateHoverTriggerData(targetUsername, '0');
                } 
                else if (action === 'follow' && element.classList.contains('btn-action')) {
                    element.innerText = 'Seguindo';
                    element.classList.remove('btn-seguir');
                    element.classList.add('btn-seguindo');
                    element.setAttribute('onclick', `handleAction('unfollow', '${targetUsername}', this, '${currentType}')`);
                    
                    if(currentType === 'hover') updateHoverTriggerData(targetUsername, '1');
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
    function updateHoverTriggerData(username, isFollowingStr) {
        const trigger = document.querySelector(`.hover-trigger[data-username="${username}"]`);
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
            document.getElementById('hc-level').innerText = (trigger.dataset.level && trigger.dataset.level !== 'undefined') ? trigger.dataset.level : '1';
            document.getElementById('hc-xp').innerText = formatXP(trigger.dataset.xp);
            document.getElementById('hc-followers').innerText = (trigger.dataset.followers && trigger.dataset.followers !== 'undefined') ? trigger.dataset.followers : '0';
            document.getElementById('hc-following').innerText = (trigger.dataset.following && trigger.dataset.following !== 'undefined') ? trigger.dataset.following : '0';
            
            // Lógica do botão de Seguir no Hover
            const hcFollowBtn = document.getElementById('hc-follow-btn');
            const isMe = trigger.dataset.isme === 'true';
            
            if (isMe) {
                // Esconde o botão se a pessoa for você mesmo
                hcFollowBtn.style.display = 'none';
            } else {
                hcFollowBtn.style.display = 'inline-block';
                const isFollowing = trigger.dataset.isfollowing === '1';
                const userUsername = trigger.dataset.username;

                if (isFollowing) {
                    hcFollowBtn.className = 'btn-action btn-seguindo';
                    hcFollowBtn.innerText = 'Seguindo';
                    hcFollowBtn.setAttribute('onclick', `handleAction('unfollow', '${userUsername}', this, 'hover')`);
                } else {
                    hcFollowBtn.className = 'btn-action btn-seguir';
                    hcFollowBtn.innerText = 'Seguir';
                    hcFollowBtn.setAttribute('onclick', `handleAction('follow', '${userUsername}', this, 'hover')`);
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

    // ==========================================
    // 4.1 CLICK NO USERNAME → PERFIL PÚBLICO
    // ==========================================
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('.hover-trigger');
        if (trigger) {
            const username = trigger.dataset.username;
            if (username && trigger.dataset.isme !== 'true') {
                window.location.href = `/userScreen/profile.php?user=${encodeURIComponent(username)}`;
            }
        }
    });


    // Abre um dropdown quando clica na foto de perfil
    function toggleProfileDropdown(e) {
        e.stopPropagation();
        document.getElementById('profile-dropdown-content').classList.toggle('show');
    }
    window.addEventListener('click', function(event) {
        var dropdown = document.getElementById('profile-dropdown-content');
        if (dropdown && dropdown.classList.contains('show') && !event.target.matches('#login-icon')) {
            dropdown.classList.remove('show');
        }
    });

    // ==========================================
    // 5. NAVBAR SCROLL FUNCIONALIDADE
    // ==========================================
    const btnInicio = document.getElementById('nav-inicio');
    if (btnInicio) {
        btnInicio.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    const btnMissoes = document.getElementById('nav-missoes');
    if (btnMissoes) {
        btnMissoes.addEventListener('click', () => {
            const missoesSection = document.querySelector('.dashboard-row-2');
            if (missoesSection) {
                missoesSection.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }

    const btnTopicos = document.getElementById('nav-topicos');
    if (btnTopicos) {
        btnTopicos.addEventListener('click', () => {
            const topicosSection = document.querySelector('.topics');
            if (topicosSection) {
                topicosSection.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }