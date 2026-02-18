<?php
$session = session();
$userRoleName = session()->get('rol_nombre');
$userNombre = session('nombre');
$userApellidos = session('apellidos');
?>

<header id="page-topbar">
    <div class="layout-width">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- LOGO -->
                <div class="navbar-brand-box horizontal-logo">
                    <a href="<?= base_url('/') ?>" class="logo logo-dark">
                        <span class="logo-sm">
                            <img src="/assets/img/condoriri.jpeg" alt="" height="50">
                        </span>
                        <span class="logo-lg">
                            <img src="/assets/img/condoriri.jpeg" alt="" height="50">
                        </span>
                    </a>
                    <a href="<?= base_url('/') ?>" class="logo logo-light">
                        <span class="logo-sm">
                            <img src="/assets/img/condoriri.jpeg" alt="" height="50">
                        </span>
                        <span class="logo-lg">
                            <img src="/assets/img/condoriri.jpeg" alt="" height="50">
                        </span>
                    </a>
                </div>

                <!-- Botón hamburguesa para móviles -->
                <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger"
                    id="topnav-hamburger-icon">
                    <span class="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>
                <div class="d-flex align-items-center">
                    <div class="ms-3 d-none d-md-block">
                        <h4 class="mb-0 fw-bold text-primary">Condoriri SG</h4>
                        <small class="text-muted">Sistema de Gestión - <?= esc(session('sucursal_nombre') ?? 'N/A') ?></small>
                        <small class="text-muted">N° - <?= esc(session('sucursal_id') ?? 'N/A') ?></small>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center">
                <!-- Fullscreen -->
                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle"
                        data-toggle="fullscreen">
                        <i class='bx bx-fullscreen fs-22'></i>
                    </button>
                </div>

                <!-- Menú de usuario -->
                <div class="dropdown ms-sm-3 header-item topbar-user">
                    <button type="button" class="btn" id="page-header-user-dropdown" data-bs-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <i class="mdi mdi-account-circle fs-24 text-primary me-2"></i>
                            <span class="text-start ms-xl-2">
                                <span
                                    class="d-none d-xl-inline-block ms-1 fw-medium user-name-text"><?= esc($userNombre . ' ' . $userApellidos) ?></span>
                                <span
                                    class="d-none d-xl-block ms-1 fs-12 user-name-sub-text"><?= esc($userRoleName) ?></span>
                            </span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <h6 class="dropdown-header"><?= esc($userNombre . ' ' . $userApellidos) ?></h6>
                        <a class="dropdown-item" href="<?= base_url('perfil') ?>">
                            <i class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i>
                            <span class="align-middle">Perfil</span>
                        </a>
                        <a class="dropdown-item" href="<?= base_url('logout') ?>">
                            <i class="mdi mdi-logout text-muted fs-16 align-middle me-1"></i>
                            <span class="align-middle" data-key="t-logout">Salir</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>