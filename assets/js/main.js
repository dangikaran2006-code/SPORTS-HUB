/**
 * SportsHub - Multi-Sport Tournament Management Platform
 * Main Client-Side Controller
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Sidebar Toggle Logic
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
    const mainWrapper = document.getElementById('mainWrapper');
    const appSidebar = document.getElementById('appSidebar');

    if (sidebarToggleBtn && mainWrapper) {
        sidebarToggleBtn.addEventListener('click', () => {
            mainWrapper.classList.toggle('sidebar-collapsed');
            if (window.innerWidth <= 992 && appSidebar) {
                appSidebar.classList.toggle('mobile-open');
            }
        });
    }

    // 2. Notification Toast Generator
    window.showToast = function(title, message, type = 'info') {
        const container = document.getElementById('toastContainer') || createToastContainer();
        const toast = document.createElement('div');
        toast.className = `toast-item toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <strong>${title}</strong>
                <p>${message}</p>
            </div>
            <button onclick="this.parentElement.remove()" class="toast-close">&times;</button>
        `;
        container.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    };

    function createToastContainer() {
        const div = document.createElement('div');
        div.id = 'toastContainer';
        div.style.cssText = 'position: fixed; bottom: 24px; right: 24px; z-index: 999; display: flex; flex-direction: column; gap: 10px;';
        document.body.appendChild(div);
        return div;
    }

    // 3. Search Quick Filtering
    const searchInput = document.getElementById('globalSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase();
            const searchableCards = document.querySelectorAll('[data-searchable]');
            searchableCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                if (text.includes(query)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
});
