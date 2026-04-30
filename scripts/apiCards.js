async function apiCards() {
    try {
        const response = await fetch('./api/apiCard.php');
        const data = await response.json();
        
        const tipoMap = {
            'planets': 0,
            'stars': 1,
            'galaxies': 2,
            'cosmology': 3,
            'others': 4
        };

        
        for (const card of data) {
            const listaIndex = tipoMap[card.tipoTopic];
            if (listaIndex === undefined) continue;
            const topicCard = document.createElement('li');   
            topicCard.classList.add('topic-card');
            
            topicCard.style.background = `url(${card.imgCard}) no-repeat`;
            topicCard.style.backgroundSize = 'cover';
            topicCard.style.backgroundPosition = 'center';
            
            topicCard.innerHTML = `
                <article>
                    <header>
                        <h3>${card.nameTopic}</h3>
                    </header>
                    <footer>
                        <p>${card.descTopic}</p>
                        <a href="#" class="button">Aprenda Agora</a>
                    </footer>
                </article>
            `;
            
            // ✅ CORREÇÃO 3: Seletor correto
            const lista = document.querySelectorAll('.topics-cards-list')[listaIndex].appendChild(topicCard);
        }
    } catch (error) {
        console.error('Erro ao carregar cards:', error);
    }
}

apiCards();