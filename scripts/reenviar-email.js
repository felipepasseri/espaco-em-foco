function reenviarEmail({ 
    btnId, 
    feedbackId, 
    linkId, 
    spinnerId, 
    textId, 
    apiPath 
} = {}) {
    const btn = btnId ? document.getElementById(btnId) : null;
    const feedback = feedbackId ? document.getElementById(feedbackId) : null;
    const link = linkId ? document.getElementById(linkId) : null;
    const spinner = spinnerId ? document.getElementById(spinnerId) : null;
    const text = textId ? document.getElementById(textId) : null;

    if (link) link.style.display = 'none';
    if (btn) btn.disabled = true;
    if (spinner) spinner.style.display = 'inline-block';
    
    if (text) text.textContent = 'Enviando...';
    else if (btn && !spinner) btn.textContent = 'Enviando...';

    if (feedback) {
        // Se o elemento de link disparou, mudamos o texto pra Enviando...
        if (link) feedback.textContent = 'Enviando...';
        else feedback.textContent = ''; // Limpa pra não acavalar
        
        feedback.classList.remove('text-success', 'text-danger');
        feedback.style.color = '#333';
    }

    fetch(apiPath, { method: 'POST' })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            if (feedback) {
                feedback.textContent = 'E-mail reenviado com sucesso! Verifique sua caixa de entrada.';
                feedback.style.color = '#28a745';
                feedback.classList.add('text-success'); 
            }
            if (btn && !spinner) btn.style.display = 'none'; 
        } else {
            if (feedback) {
                feedback.textContent = data.message || 'Erro ao reenviar o e-mail.';
                feedback.style.color = '#dc3545';
                feedback.classList.add('text-danger');
            }
            if (link) link.style.display = 'inline';
            if (btn) btn.disabled = false;
            if (text) text.textContent = 'Reenviar E-mail';
            else if (btn && !spinner) btn.textContent = 'Reenviar E-mail';
        }
    })
    .catch(() => {
        if (feedback) {
            feedback.textContent = 'Erro de conexão. Tente novamente.';
            feedback.style.color = '#dc3545';
            feedback.classList.add('text-danger');
        }
        if (link) link.style.display = 'inline';
        if (btn) btn.disabled = false;
        if (text) text.textContent = 'Reenviar E-mail';
        else if (btn && !spinner) btn.textContent = 'Reenviar E-mail';
    })
    .finally(() => {
        if (spinner) spinner.style.display = 'none';
        
        // Cooldown para o botão de cadastro se deu sucesso
        if (btn && spinner && feedback && (feedback.classList.contains('text-success') || feedback.style.color === 'rgb(40, 167, 69)')) {
            setTimeout(() => { btn.disabled = false; }, 30000);
        }
    });
}
