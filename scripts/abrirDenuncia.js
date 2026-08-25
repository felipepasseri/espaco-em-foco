const modal = document.getElementById("modalDenuncia");
const fecharModal = document.getElementById("fecharModal");

const botoesAnalisar = document.querySelectorAll(".analisar-denuncia");

const botaoRecusar = document.getElementById("recusarDenuncia");
const botaoResolver = document.getElementById("resolverDenuncia");

const modalResolucao = document.getElementById("modalResolucao");
const fecharResolucao = document.getElementById("fecharResolucao");
const cancelarResolucao = document.getElementById("cancelarResolucao");
const confirmarResolucao = document.getElementById("confirmarResolucao");

let denunciaAtual = null;


// =====================================================
// ABRIR DENÚNCIA
// =====================================================

botoesAnalisar.forEach(botao => {

    botao.addEventListener("click", () => {

        const id = botao.dataset.id;

        abrirDenuncia(id);

    });

});


async function abrirDenuncia(id) {

    try {

        denunciaAtual = id;

        const resposta = await fetch(`buscarDenuncia.php?id=${id}`);

        const dados = await resposta.json();

        if (!dados.sucesso) {

            alert(dados.erro);

            return;

        }

        const denuncia = dados.denuncia;


        document.getElementById("modal-id").textContent =
            denuncia.id;

        document.getElementById("modal-denunciante").textContent =
            denuncia.nome_usuario_denunciante;

        document.getElementById("modal-denunciado").textContent =
            denuncia.nome_usuario_alvo;

        document.getElementById("modal-categoria").textContent =
            denuncia.categoria_denuncia;

        document.getElementById("modal-motivo").textContent =
            denuncia.motivo;


        modal.classList.add("aberto");

    } catch (erro) {

        console.error("Erro ao buscar denúncia:", erro);

        alert("Erro ao buscar a denúncia.");

    }

}


// =====================================================
// FECHAR MODAL PRINCIPAL
// =====================================================

fecharModal.addEventListener("click", () => {

    modal.classList.remove("aberto");

});


modal.addEventListener("click", (event) => {

    if (event.target === modal) {

        modal.classList.remove("aberto");

    }

});


// =====================================================
// ABRIR MODAL DE RESOLUÇÃO
// =====================================================

botaoResolver.addEventListener("click", () => {

    if (!denunciaAtual) {

        alert("Nenhuma denúncia selecionada.");

        return;

    }

    modalResolucao.classList.add("aberto");

});


// =====================================================
// FECHAR MODAL DE RESOLUÇÃO
// =====================================================

fecharResolucao.addEventListener("click", () => {

    modalResolucao.classList.remove("aberto");

});


cancelarResolucao.addEventListener("click", () => {

    modalResolucao.classList.remove("aberto");

});


// =====================================================
// CONFIRMAR RESOLUÇÃO
// =====================================================

confirmarResolucao.addEventListener("click", async () => {

    if (!denunciaAtual) {

        alert("Nenhuma denúncia selecionada.");

        return;

    }


    const opcaoSelecionada = document.querySelector(
        'input[name="acaoResolucao"]:checked'
    );


    if (!opcaoSelecionada) {

        alert("Escolha uma ação para a denúncia.");

        return;

    }


    const acao = opcaoSelecionada.value;


    const observacao = document
        .getElementById("observacaoResolucao")
        .value
        .trim();


    if (!observacao) {

        alert("Digite uma observação sobre a decisão.");

        return;

    }


    const confirmar = confirm(
        "Tem certeza que deseja confirmar essa ação?"
    );


    if (!confirmar) {

        return;

    }


    try {

        const formData = new FormData();

        formData.append("id", denunciaAtual);
        formData.append("acao", acao);
        formData.append("observacao", observacao);


        const resposta = await fetch("resolverDenuncia.php", {

            method: "POST",

            body: formData

        });


        /*
         * Primeiro pegamos como texto.
         * Isso permite descobrir caso o PHP
         * esteja retornando um erro ou resposta vazia.
         */

        const texto = await resposta.text();

        console.log("Resposta do resolverDenuncia.php:");
        console.log(texto);


        let dados;

        try {

            dados = JSON.parse(texto);

        } catch (erroJSON) {

            console.error(
                "O PHP não retornou JSON válido:",
                texto
            );

            alert(
                "O servidor retornou uma resposta inválida. " +
                "Abra o console (F12) para ver o erro."
            );

            return;

        }


        if (!dados.sucesso) {

            alert("Erro: " + dados.erro);

            return;

        }


        alert(dados.mensagem);


        modalResolucao.classList.remove("aberto");

        modal.classList.remove("aberto");


        location.reload();


    } catch (erro) {

        console.error(
            "Erro ao resolver denúncia:",
            erro
        );

        alert(
            "Erro de comunicação com o servidor."
        );

    }

});


// =====================================================
// RECUSAR DENÚNCIA
// =====================================================

botaoRecusar.addEventListener("click", async () => {

    if (!denunciaAtual) {

        alert("Nenhuma denúncia selecionada.");

        return;

    }


    const confirmar = confirm(
        "Tem certeza que deseja recusar esta denúncia?"
    );


    if (!confirmar) {

        return;

    }


    try {

        const formData = new FormData();

        formData.append("id", denunciaAtual);


        const resposta = await fetch("recusarDenuncia.php", {

            method: "POST",

            body: formData

        });


        const texto = await resposta.text();

        console.log("Resposta do recusarDenuncia.php:");
        console.log(texto);


        let dados;

        try {

            dados = JSON.parse(texto);

        } catch (erroJSON) {

            console.error(
                "O PHP não retornou JSON válido:",
                texto
            );

            alert(
                "O servidor retornou uma resposta inválida. " +
                "Abra o console (F12) para ver o erro."
            );

            return;

        }


        if (!dados.sucesso) {

            alert("Erro: " + dados.erro);

            return;

        }


        alert("Denúncia recusada com sucesso!");


        modal.classList.remove("aberto");


        location.reload();


    } catch (erro) {

        console.error(
            "Erro ao recusar denúncia:",
            erro
        );

        alert("Erro ao recusar a denúncia.");

    }

});