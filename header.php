<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Thera Vita - Terapi Merkezi Yönetim Sistemi</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" rel="stylesheet">
<style>
    :root {
        --sidebar-width: 16rem;
        --header-height: 4rem;
        --primary-color: #4723D9;
        --gray-100: #f8f9fa;
        --gray-800: #1f2937;
        --gray-900: #111827;
    }

    body {
        min-height: 100vh;
        background-color: var(--gray-100);
        margin: 0;
        padding-left: var(--sidebar-width);
    }

    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: var(--sidebar-width);
        height: 100vh;
        background-color: var(--gray-800);
        padding: 1rem;
        transition: transform 0.3s ease;
        z-index: 50;
        overflow-y: auto;
    }

    .sidebar-header {
        padding: 1.5rem 1rem;
        color: white;
        font-size: 1.25rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .nav-section-header {
        cursor: pointer;
        color: #9ca3af;
        transition: all 0.2s;
    }

    .nav-section-header:hover {
        color: white;
    }

    .nav-section-header i {
        transition: transform 0.2s;
    }

    .nav-section-header[aria-expanded="true"] i {
        transform: rotate(-180deg);
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        color: #9ca3af;
        text-decoration: none;
        border-radius: 0.5rem;
        transition: all 0.2s;
    }

    .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .nav-link.active {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .nav-link i {
        font-size: 1.25rem;
    }

    .main-content {
        padding: 2rem;
        margin-top: var(--header-height);
    }

    .top-bar {
        position: fixed;
        top: 0;
        right: 0;
        left: var(--sidebar-width);
        height: var(--header-height);
        background-color: white;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 2rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        z-index: 40;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .user-avatar {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 50%;
        object-fit: cover;
    }

    .user-name {
        font-weight: 600;
        color: var(--gray-900);
    }

    .user-role {
        font-size: 0.875rem;
        color: #6b7280;
    }

    @media (max-width: 768px) {
        body {
            padding-left: 0;
        }

        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.show {
            transform: translateX(0);
        }

        .top-bar {
            left: 0;
        }
    }

    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .card {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        border: none;
    }
</style>