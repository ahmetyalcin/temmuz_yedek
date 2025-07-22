<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        }

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                sidebar.classList.add('show');
            }
        });

        // Aktif menü grubunu aç
        const activeLink = document.querySelector('.nav-link.active');
        if (activeLink) {
            const parentCollapse = activeLink.closest('.collapse');
            if (parentCollapse) {
                const bsCollapse = new bootstrap.Collapse(parentCollapse, {
                    toggle: false
                });
                bsCollapse.show();
            }
        }

        // Menü ok ikonlarını yönet
        const collapsibleButtons = document.querySelectorAll('.nav-section-header');
        collapsibleButtons.forEach(button => {
            button.addEventListener('click', () => {
                const icon = button.querySelector('i');
                const isExpanded = button.getAttribute('aria-expanded') === 'true';
                icon.style.transform = isExpanded ? 'rotate(0deg)' : 'rotate(-180deg)';
            });
        });
    });
</script>