document.addEventListener("click", async (e) => {
    if (e.target.classList.contains("delete")) {
        const id = e.target.dataset.id;

        if (!confirm("Deseja realmente excluir este card?")) {
            return;
        }
        const response = await fetch("excluiCard.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: `id=${encodeURIComponent(id)}`
        });

        const resultado = await response.json();

        if (resultado.sucesso) {
            alert("Card excluído!");
            // Remove o card da tela ou recarrega a página
            e.target.closest(".topic-card").remove();
        } else {
            alert(resultado.erro);
        }
    }
});