/**
 * SportsHub - Tournament Management Controller
 */

function openCreateTournamentModal() {
    const modal = document.getElementById('createTournamentModal');
    if (modal) {
        modal.classList.add('open');
    }
}

function closeCreateTournamentModal() {
    const modal = document.getElementById('createTournamentModal');
    if (modal) {
        modal.classList.remove('open');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Sport Filter Pills logic
    const filterPills = document.querySelectorAll('.filter-pill-btn');
    const tournamentCards = document.querySelectorAll('.tournament-card');

    filterPills.forEach(pill => {
        pill.addEventListener('click', () => {
            filterPills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');

            const sport = pill.getAttribute('data-sport');

            tournamentCards.forEach(card => {
                const cardSport = card.getAttribute('data-sport');
                if (sport === 'all' || cardSport === sport) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
});
