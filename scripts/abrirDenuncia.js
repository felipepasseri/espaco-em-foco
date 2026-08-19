const modal = document.getElementById("modalDenuncia");
const fecharModal = document.getElementById("fecharModal");

const botoesAnalisar = document.querySelectorAll(".analisar-denuncia");
const botaoRecusar = document.getElementById("recusarDenuncia");
let denunciaAtual = null;


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

        // Preenche o modal
        document.getElementById("modal-id").textContent = denuncia.id;

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
        console.error(erro);
        alert("Erro ao buscar a denúncia.");
    }
}


fecharModal.addEventListener("click", () => {
    modal.classList.remove("aberto");
});

modal.addEventListener("click", (event) => {
    if (event.target === modal) {
        modal.classList.remove("aberto");
    }
});

botoesAnalisar.forEach(botao => {
    botao.addEventListener("click", () => {
        const id = botao.dataset.id;
        abrirDenuncia(id);
    });
});

fecharModal.addEventListener("click", () => {
    modal.classList.remove("aberto");
});

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

        const dados = await resposta.json();
        if (!dados.sucesso) {
            alert(dados.erro);
            return;
        }

        alert("Denúncia recusada com sucesso!");
        modal.classList.remove("aberto");
        // Atualiza a lista de denúncias
        location.reload();

    } catch (erro) {
        console.error(erro);
        alert("Erro ao recusar a denúncia.");
    }

});