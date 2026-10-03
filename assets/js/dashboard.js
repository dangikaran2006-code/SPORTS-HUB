/**
 * SportsHub - Dashboard Controller
 * Handles live score ticker simulation and real-time UI updates
 */

document.addEventListener('DOMContentLoaded', () => {
    // Live match simulation ticker
    const liveScoreElements = document.querySelectorAll('.live-score-sim');
    
    if (liveScoreElements.length > 0) {
        setInterval(() => {
            liveScoreElements.forEach(el => {
                const sport = el.getAttribute('data-sport');
                if (sport === 'Cricket') {
                    let currentScore = el.textContent.split('/');
                    if (currentScore.length === 2) {
                        let runs = parseInt(currentScore[0]) + Math.floor(Math.random() * 4);
                        el.textContent = `${runs}/${currentScore[1]}`;
                    }
                } else if (sport === 'Football') {
                    // Random small flash update
                }
            });
        }, 15000);
    }
});
