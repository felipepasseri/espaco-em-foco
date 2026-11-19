/**
 * home-adm.js - Lógica interativa do Painel Administrativo
 * Gerenciamento de abas, filtros, denúncias, banimentos, moderação e tópicos
 */

document.addEventListener("DOMContentLoaded", () => {
    let denunciaEmAnalise = null;

    // =========================================================================
    // 1. NAVEGAÇÃO POR ABAS E KPIS
    // =========================================================================
    const tabButtons = document.querySelectorAll(".adm-tab-btn");
    const tabContents = document.querySelectorAll(".adm-tab-content");
    const kpiCards = document.querySelectorAll(".adm-kpi-card[data-tab-target]");

    function ativarAba(targetId) {
        tabButtons.forEach(btn => {
            btn.classList.toggle("active", btn.dataset.tab === targetId);
        });

        tabContents.forEach(content => {
            content.classList.toggle("active", content.id === targetId);
        });

        // Atualiza URL hash de forma limpa
        history.replaceState(null, null, `#${targetId}`);
    }

    tabButtons.forEach(btn => {
        btn.addEventListener("click", () => {
            ativarAba(btn.dataset.tab);
        });
    });

    kpiCards.forEach(card => {
        card.addEventListener("click", () => {
            ativarAba(card.dataset.tabTarget);
        });
    });

    // Se houver hash na URL, ativa a aba correspondente
    if (window.location.hash) {
        const hashTarget = window.location.hash.substring(1);
        if (document.getElementById(hashTarget)) {
            ativarAba(hashTarget);
        }
    }

    // =========================================================================
    // 2. FILTRAGEM POR PILLS EM CADA SEÇÃO
    // =========================================================================
    const filterGroups = document.querySelectorAll("[data-filter-group]");

    filterGroups.forEach(group => {
        const pills = group.querySelectorAll(".filter-pill");
        const groupName = group.dataset.filterGroup;
        const section = document.getElementById(`tab-${groupName}`);
        if (!section) return;

        pills.forEach(pill => {
            pill.addEventListener("click", () => {
                pills.forEach(p => p.classList.remove("active"));
                pill.classList.add("active");

                const filterVal = pill.dataset.filter;
                const items = section.querySelectorAll(".adm-item-card");

                items.forEach(item => {
                    const status = item.dataset.status;
                    if (filterVal === "all" || status === filterVal) {
                        item.style.display = "flex";
                    } else {
                        item.style.display = "none";
                    }
                });
            });
        });
    });

    // =========================================================================
    // 3. GERENCIADOR DE MODAIS
    // =========================================================================
    function abrirModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove("hidden");
            document.body.style.overflow = "hidden";
        }
    }

    function fecharModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add("hidden");
            document.body.style.overflow = "";
        }
    }

    // Fechar ao clicar fora ou no botão X
    document.querySelectorAll(".modal-overlay").forEach(overlay => {
        overlay.addEventListener("click", (e) => {
            if (e.target === overlay) {
                fecharModal(overlay.id);
            }
        });

        const closeBtn = overlay.querySelector(".close-btn");
        if (closeBtn) {
            closeBtn.addEventListener("click", () => {
                fecharModal(overlay.id);
            });
        }
    });

    // Fechar com ESC
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") {
            document.querySelectorAll(".modal-overlay:not(.hidden)").forEach(m => {
                fecharModal(m.id);
            });
        }
    });

    // =========================================================================
    // 4. DENÚNCIAS: ANALISAR E RESOLVER
    // =========================================================================
    const modalDenuncia = document.getElementById("modalDenuncia");
    const modalResolucao = document.getElementById("modalResolucao");

    // Botões Analisar Denúncia
    document.querySelectorAll(".btn-analisar-denuncia, .btn-ver-detalhes-denuncia").forEach(btn => {
        btn.addEventListener("click", async () => {
            const id = btn.dataset.id;
            try {
                const res = await fetch(`buscarDenuncia.php?id=${id}`);
                const data = await res.json();

                if (!data.sucesso) {
                    alert(data.erro || "Erro ao carregar denúncia.");
                    return;
                }

                const d = data.denuncia;
                denunciaEmAnalise = d;

                document.getElementById("modal-denuncia-header-title").textContent = `Denúncia #${d.id}`;
                document.getElementById("modal-denunciado-val").textContent = `@${d.nome_usuario_alvo}`;
                document.getElementById("modal-denunciado-email").textContent = d.email_alvo || "";
                document.getElementById("modal-denunciante-val").textContent = `@${d.nome_usuario_denunciante}`;
                document.getElementById("modal-categoria-val").textContent = d.categoria_denuncia || "Geral";
                document.getElementById("modal-tipoalvo-val").textContent = d.tipo_alvo || "usuario";
                document.getElementById("modal-motivo-val").textContent = d.motivo || "Sem motivo informado.";

                // Se já estiver resolvida ou recusada, ajusta botões do rodapé
                const btnRecusar = document.getElementById("btn-recusar-denuncia");
                const btnAbrirRes = document.getElementById("btn-abrir-resolucao");
                if (d.status !== "Em Analise") {
                    btnRecusar.style.display = "none";
                    btnAbrirRes.textContent = "Reavaliar Resolução";
                } else {
                    btnRecusar.style.display = "inline-flex";
                    btnAbrirRes.textContent = "⚖️ Resolver Denúncia";
                }

                abrirModal("modalDenuncia");
            } catch (err) {
                console.error(err);
                alert("Erro ao se comunicar com o servidor.");
            }
        });
    });

    // Recusar Denúncia
    const btnRecusar = document.getElementById("btn-recusar-denuncia");
    if (btnRecusar) {
        btnRecusar.addEventListener("click", async () => {
            if (!denunciaEmAnalise) return;

            const motivo = prompt("Motivo da recusa (opcional):", "Denúncia improcedente ou sem infração identificada.");
            if (motivo === null) return; // cancelou o prompt

            if (!confirm(`Tem certeza que deseja RECUSAR a Denúncia #${denunciaEmAnalise.id}?`)) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append("id", denunciaEmAnalise.id);
                formData.append("motivo_recusa", motivo);

                const res = await fetch("recusarDenuncia.php", {
                    method: "POST",
                    body: formData
                });
                const data = await res.json();

                if (!data.sucesso) {
                    alert("Erro: " + data.erro);
                    return;
                }

                alert(data.mensagem || "Denúncia recusada com sucesso.");
                fecharModal("modalDenuncia");
                location.reload();
            } catch (err) {
                console.error(err);
                alert("Erro na requisição.");
            }
        });
    }

    // Abrir Modal de Resolução
    const btnAbrirResolucao = document.getElementById("btn-abrir-resolucao");
    if (btnAbrirResolucao) {
        btnAbrirResolucao.addEventListener("click", () => {
            if (!denunciaEmAnalise) return;

            fecharModal("modalDenuncia");

            document.getElementById("res-modal-id").textContent = denunciaEmAnalise.id;
            document.getElementById("res-target-user").textContent = `@${denunciaEmAnalise.nome_usuario_alvo}`;
            document.getElementById("observacaoResolucao").value = "";

            // Reseta radio para aviso_email e esconde campo de data
            const radioAviso = document.querySelector('input[name="acaoResolucao"][value="aviso_email"]');
            if (radioAviso) radioAviso.checked = true;
            document.getElementById("campo-data-personalizada").classList.add("hidden");

            // Configura data mínima para amanhã
            const inputData = document.getElementById("dataFimResolucao");
            const agora = new Date();
            agora.setDate(agora.getDate() + 1);
            inputData.min = agora.toISOString().slice(0, 16);

            abrirModal("modalResolucao");
        });
    }

    // Toggle de exibição do campo de data no modal de resolução
    document.querySelectorAll('input[name="acaoResolucao"]').forEach(radio => {
        radio.addEventListener("change", () => {
            const campoData = document.getElementById("campo-data-personalizada");
            if (radio.value === "banir_personalizado") {
                campoData.classList.remove("hidden");
            } else {
                campoData.classList.add("hidden");
            }
        });
    });

    document.getElementById("btn-cancelar-resolucao").addEventListener("click", () => {
        fecharModal("modalResolucao");
    });

    // Confirmar Resolução
    const btnConfirmarResolucao = document.getElementById("btn-confirmar-resolucao");
    if (btnConfirmarResolucao) {
        btnConfirmarResolucao.addEventListener("click", async () => {
            if (!denunciaEmAnalise) return;

            const opcao = document.querySelector('input[name="acaoResolucao"]:checked');
            if (!opcao) {
                alert("Por favor, selecione uma ação de resolução.");
                return;
            }

            const acao = opcao.value;
            const observacao = document.getElementById("observacaoResolucao").value.trim();
            const dataFim = document.getElementById("dataFimResolucao").value;

            if (!observacao) {
                alert("É obrigatório informar uma observação / justificativa para a decisão.");
                document.getElementById("observacaoResolucao").focus();
                return;
            }

            if (acao === "banir_personalizado") {
                if (!dataFim) {
                    alert("Informe a data e horário de término do banimento.");
                    document.getElementById("dataFimResolucao").focus();
                    return;
                }
                const dataSelecionada = new Date(dataFim);
                if (dataSelecionada <= new Date()) {
                    alert("A data de término do banimento deve ser uma data futura.");
                    return;
                }
            }

            let msgConfirmacao = "";
            if (acao === "aviso_email") {
                msgConfirmacao = `Confirmar envio de aviso por e-mail para @${denunciaEmAnalise.nome_usuario_alvo}? Nenhum banimento será aplicado.`;
            } else if (acao === "banir_permanente") {
                msgConfirmacao = `ATENÇÃO: Confirma o BANIMENTO PERMANENTE do usuário @${denunciaEmAnalise.nome_usuario_alvo}?`;
            } else if (acao === "banir_personalizado") {
                const dataFormatada = new Date(dataFim).toLocaleString("pt-BR");
                msgConfirmacao = `Confirma o BANIMENTO TEMPORÁRIO de @${denunciaEmAnalise.nome_usuario_alvo} até ${dataFormatada}? O usuário será desbanido automaticamente após essa data.`;
            }

            if (!confirm(msgConfirmacao)) {
                return;
            }

            try {
                btnConfirmarResolucao.disabled = true;
                btnConfirmarResolucao.textContent = "Processando...";

                const formData = new FormData();
                formData.append("id", denunciaEmAnalise.id);
                formData.append("acao", acao);
                formData.append("observacao", observacao);
                if (acao === "banir_personalizado") {
                    formData.append("data_fim", dataFim);
                }

                const res = await fetch("resolverDenuncia.php", {
                    method: "POST",
                    body: formData
                });
                const data = await res.json();

                if (!data.sucesso) {
                    alert("Erro: " + data.erro);
                    btnConfirmarResolucao.disabled = false;
                    btnConfirmarResolucao.textContent = "Confirmar e Aplicar";
                    return;
                }

                alert(data.mensagem || "Denúncia resolvida com sucesso!");
                fecharModal("modalResolucao");
                location.reload();
            } catch (err) {
                console.error(err);
                alert("Erro de comunicação ao resolver denúncia.");
                btnConfirmarResolucao.disabled = false;
                btnConfirmarResolucao.textContent = "Confirmar e Aplicar";
            }
        });
    }

    // =========================================================================
    // 5. BANIMENTOS: REVOGAR E MANUAL
    // =========================================================================
    // Revogar Banimento (Desbanir)
    document.querySelectorAll(".btn-revogar-banimento").forEach(btn => {
        btn.addEventListener("click", async () => {
            const id = btn.dataset.id;
            const user = btn.dataset.user;

            if (!confirm(`Deseja revogar o banimento de ${user}? O usuário recuperará o acesso ao sistema imediatamente.`)) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append("acao", "revogar");
                formData.append("id", id);

                const res = await fetch("gerenciarBanimento.php", {
                    method: "POST",
                    body: formData
                });
                const data = await res.json();

                if (!data.sucesso) {
                    alert("Erro: " + data.erro);
                    return;
                }

                alert(data.mensagem || "Banimento revogado com sucesso!");
                location.reload();
            } catch (err) {
                console.error(err);
                alert("Erro ao revogar banimento.");
            }
        });
    });

    // Abrir Modal de Banimento Manual
    const btnNovoBanHeader = document.getElementById("btn-abrir-banimento-manual");
    const btnNovoBanTab = document.getElementById("btn-novo-banimento-tab");

    [btnNovoBanHeader, btnNovoBanTab].forEach(b => {
        if (b) {
            b.addEventListener("click", () => {
                document.getElementById("manualTargetUser").value = "";
                document.getElementById("manualMotivo").value = "";
                document.getElementById("manualDataFim").value = "";
                document.querySelector('input[name="manualTipoBan"][value="permanente"]').checked = true;
                document.getElementById("campo-data-manual").classList.add("hidden");

                const agora = new Date();
                agora.setDate(agora.getDate() + 1);
                document.getElementById("manualDataFim").min = agora.toISOString().slice(0, 16);

                abrirModal("modalBanimentoManual");
            });
        }
    });

    // Toggle campo data no banimento manual
    document.querySelectorAll('input[name="manualTipoBan"]').forEach(radio => {
        radio.addEventListener("change", () => {
            const campo = document.getElementById("campo-data-manual");
            if (radio.value === "temporario") {
                campo.classList.remove("hidden");
            } else {
                campo.classList.add("hidden");
            }
        });
    });

    document.getElementById("btn-cancelar-ban-manual").addEventListener("click", () => {
        fecharModal("modalBanimentoManual");
    });

    // Confirmar Banimento Manual
    const btnConfirmarBanManual = document.getElementById("btn-confirmar-ban-manual");
    if (btnConfirmarBanManual) {
        btnConfirmarBanManual.addEventListener("click", async () => {
            const identificador = document.getElementById("manualTargetUser").value.trim();
            const tipoBan = document.querySelector('input[name="manualTipoBan"]:checked').value;
            const motivo = document.getElementById("manualMotivo").value.trim();
            const dataFim = document.getElementById("manualDataFim").value;

            if (!identificador) {
                alert("Informe o e-mail ou @nomeDeUsuario do usuário.");
                document.getElementById("manualTargetUser").focus();
                return;
            }

            if (!motivo) {
                alert("Informe o motivo do banimento.");
                document.getElementById("manualMotivo").focus();
                return;
            }

            if (tipoBan === "temporario") {
                if (!dataFim) {
                    alert("Informe a data de término do banimento temporário.");
                    document.getElementById("manualDataFim").focus();
                    return;
                }
                if (new Date(dataFim) <= new Date()) {
                    alert("A data de término deve ser uma data futura.");
                    return;
                }
            }

            const tipoTexto = (tipoBan === "permanente") ? "PERMANENTE" : "TEMPORÁRIO";
            if (!confirm(`Confirma a aplicação do BANIMENTO ${tipoTexto} para ${identificador}?`)) {
                return;
            }

            try {
                btnConfirmarBanManual.disabled = true;
                btnConfirmarBanManual.textContent = "Aplicando...";

                const formData = new FormData();
                formData.append("acao", "aplicar_manual");
                formData.append("identificador", identificador);
                formData.append("tipo_banimento", tipoBan);
                formData.append("motivo", motivo);
                if (tipoBan === "temporario") {
                    formData.append("data_fim", dataFim);
                }

                const res = await fetch("gerenciarBanimento.php", {
                    method: "POST",
                    body: formData
                });
                const data = await res.json();

                if (!data.sucesso) {
                    alert("Erro: " + data.erro);
                    btnConfirmarBanManual.disabled = false;
                    btnConfirmarBanManual.textContent = "Aplicar Banimento";
                    return;
                }

                alert(data.mensagem || "Banimento aplicado com sucesso!");
                fecharModal("modalBanimentoManual");
                location.reload();
            } catch (err) {
                console.error(err);
                alert("Erro ao aplicar banimento manual.");
                btnConfirmarBanManual.disabled = false;
                btnConfirmarBanManual.textContent = "Aplicar Banimento";
            }
        });
    }

    // =========================================================================
    // 6. MODERAÇÃO DE POSTS DO FÓRUM
    // =========================================================================
    document.querySelectorAll(".btn-ver-conteudo-post").forEach(btn => {
        btn.addEventListener("click", () => {
            const titulo = btn.dataset.titulo;
            const autor = btn.dataset.autor;
            const topico = btn.dataset.topico;
            const conteudo = btn.dataset.conteudo;

            document.getElementById("vis-modal-title").textContent = titulo;
            document.getElementById("vis-modal-meta").textContent = `Por @${autor} • Tópico: ${topico}`;
            document.getElementById("vis-modal-body-content").innerHTML = conteudo;

            abrirModal("modalVisualizacao");
        });
    });

    document.querySelectorAll(".btn-aprovar-post").forEach(btn => {
        btn.addEventListener("click", async () => {
            const id = btn.dataset.id;
            if (!confirm("Deseja APROVAR a publicação deste post no fórum?")) return;

            await moderarItem("moderarPost.php", id, "aprovar");
        });
    });

    document.querySelectorAll(".btn-reprovar-post").forEach(btn => {
        btn.addEventListener("click", async () => {
            const id = btn.dataset.id;
            if (!confirm("Deseja REPROVAR este post? Ele não será exibido na comunidade.")) return;

            await moderarItem("moderarPost.php", id, "reprovar");
        });
    });

    // =========================================================================
    // 7. MODERAÇÃO DE ARTIGOS
    // =========================================================================
    document.querySelectorAll(".btn-ver-conteudo-artigo").forEach(btn => {
        btn.addEventListener("click", () => {
            const titulo = btn.dataset.titulo;
            const topico = btn.dataset.topico;
            const conteudo = btn.dataset.conteudo;

            document.getElementById("vis-modal-title").textContent = titulo;
            document.getElementById("vis-modal-meta").textContent = `Tópico: ${topico}`;
            document.getElementById("vis-modal-body-content").textContent = conteudo;

            abrirModal("modalVisualizacao");
        });
    });

    document.querySelectorAll(".btn-aprovar-artigo").forEach(btn => {
        btn.addEventListener("click", async () => {
            const id = btn.dataset.id;
            if (!confirm("Deseja APROVAR a publicação deste artigo no catálogo?")) return;

            await moderarItem("moderarArtigo.php", id, "aprovar");
        });
    });

    document.querySelectorAll(".btn-reprovar-artigo").forEach(btn => {
        btn.addEventListener("click", async () => {
            const id = btn.dataset.id;
            if (!confirm("Deseja REPROVAR este artigo?")) return;

            await moderarItem("moderarArtigo.php", id, "reprovar");
        });
    });

    async function moderarItem(endpoint, id, acao) {
        try {
            const formData = new FormData();
            formData.append("id", id);
            formData.append("acao", acao);

            const res = await fetch(endpoint, {
                method: "POST",
                body: formData
            });
            const data = await res.json();

            if (!data.sucesso) {
                alert("Erro: " + data.erro);
                return;
            }

            alert(data.mensagem || "Ação executada com sucesso!");
            location.reload();
        } catch (err) {
            console.error(err);
            alert("Erro de comunicação com o servidor.");
        }
    }

    document.getElementById("btn-fechar-vis").addEventListener("click", () => {
        fecharModal("modalVisualizacao");
    });

    // =========================================================================
    // 8. GERENCIAR TÓPICOS
    // =========================================================================
    const btnNovoTopicoHero = document.getElementById("btn-abrir-novo-topico");
    const btnNovoTopicoTab = document.getElementById("btn-novo-topico-tab");

    [btnNovoTopicoHero, btnNovoTopicoTab].forEach(b => {
        if (b) {
            b.addEventListener("click", () => {
                abrirModal("modalNovoTopico");
            });
        }
    });

    document.getElementById("btn-cancelar-novo-topico").addEventListener("click", () => {
        fecharModal("modalNovoTopico");
    });

    // Excluir tópico
    document.querySelectorAll(".btn-excluir-topico").forEach(btn => {
        btn.addEventListener("click", async () => {
            const id = btn.dataset.id;
            const nome = btn.dataset.nome;

            if (!confirm(`Tem certeza que deseja excluir o tópico '${nome}'? Essa ação apagará a imagem e o registro do banco de dados.`)) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append("id", id);

                const res = await fetch("excluiCard.php", {
                    method: "POST",
                    body: formData
                });
                const data = await res.json();

                if (!data.sucesso) {
                    alert("Erro: " + data.erro);
                    return;
                }

                alert(data.mensagem || "Tópico excluído com sucesso!");
                location.reload();
            } catch (err) {
                console.error(err);
                alert("Erro ao excluir tópico.");
            }
        });
    });
});

