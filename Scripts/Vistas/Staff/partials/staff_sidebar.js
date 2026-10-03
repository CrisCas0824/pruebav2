const initializeStaffSidebar = () => {
        const sidebar = document.getElementById('staffSidebar');
        const overlay = document.getElementById('staffSidebarOverlay');
        const openBtn = document.getElementById('staffMenuToggle');
        const closeBtn = document.getElementById('staffSidebarClose');

        const open = () => {
            if (sidebar) {
                sidebar.classList.add('active');
                sidebar.classList.add('open');
            }
            if (overlay) {
                overlay.classList.add('active');
                overlay.classList.add('open');
            }
            document.body.style.overflow = 'hidden';
        };

        const close = () => {
            if (sidebar) {
                sidebar.classList.remove('active');
                sidebar.classList.remove('open');
            }
            if (overlay) {
                overlay.classList.remove('active');
                overlay.classList.remove('open');
            }
            document.body.style.overflow = '';
        };

        if (openBtn) {
            openBtn.removeEventListener('click', open);
            openBtn.addEventListener('click', open);
        }
        if (closeBtn) closeBtn.addEventListener('click', close);
        if (overlay) overlay.addEventListener('click', close);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') close();
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeStaffSidebar, { once: true });
    } else {
        initializeStaffSidebar();
    }
