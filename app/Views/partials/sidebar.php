<?php
$currentPath = $_SERVER['REQUEST_URI'];
$userRoleName = session()->get('rol_nombre');
?>

<style>
  .app-menu {
    background-color: #e8f5ee !important;
    border-right: 1px solid #a8d9bc;
    box-shadow: 2px 0 8px rgba(40, 167, 69, 0.08);
  }

  .logo-dark span img,
  .logo-light span img {
    filter: none;
  }

  /* Force logo sizing in collapsed state */
  .navbar-brand-box .logo-sm img {
    max-height: 50px !important;
    max-width: 50px !important;
    object-fit: contain !important;
  }

  /* Remove blue background from navbar-brand-box */
  .navbar-brand-box {
    background: transparent !important;
  }

  /* Hide large logo when sidebar is narrow */
  @media (max-width: 991px) {
    .logo-lg {
      display: none !important;
    }
  }

  .menu-title {
    color: #1e7e34 !important;
    font-weight: 700;
    padding-left: 1.25rem;
    margin: 1.25rem 0 0.75rem;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 1px;
  }

  .nav-link {
    color: #495057 !important;
    font-weight: 500;
    padding: 0.65rem 1.5rem;
    margin: 0.15rem 0.75rem;
    border-radius: 6px;
    transition: all 0.2s ease;
  }

  .nav-link:hover,
  .nav-link:focus {
    color: #1e7e34 !important;
    background-color: #f0f9f4 !important;
  }

  .nav-link i {
    color: #28a745 !important;
    font-size: 1.1rem;
    width: 1.4rem;
    text-align: center;
    margin-right: 0.8rem;
  }

  .menu-dropdown .nav-link {
    padding-left: 3rem !important;
    color: #555 !important;
    margin: 0.1rem 0.75rem;
    font-size: 0.95rem;
  }

  .menu-dropdown .nav-link:hover {
    background-color: #e8f5ec !important;
    color: #28a745 !important;
  }

  .nav-item.active>.nav-link,
  .nav-link.active {
    color: #155724 !important;
    background-color: #e8f5ec !important;
    font-weight: 600;
    border-left: 3px solid #28a745;
  }

  .vertical-overlay {
    background-color: rgba(40, 167, 69, 0.1);
  }
</style>

<div class="app-menu navbar-menu">
  <div class="navbar-brand-box mt-3">
    <a href="<?= base_url('/') ?>" class="logo logo-dark">
      <span class="logo-sm">
        <img src="/assets/img/condoriri1.png" alt="Condoriri" height="90">
      </span>
      <span class="logo-lg">
        <img src="/assets/img/condoriri1.png" alt="Condoriri" height="90">
      </span>
    </a>
    <a href="<?= base_url('/') ?>" class="logo logo-light">
      <span class="logo-sm">
        <img src="/assets/img/condoriri1.png" alt="Condoriri" height="90">
      </span>
      <span class="logo-lg">
        <img src="/assets/img/condoriri1.png" alt="Condoriri" height="90">
      </span>
    </a>
    <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover">
      <i class="ri-record-circle-line"></i>
    </button>
  </div>

  <div id="scrollbar">
    <div class="container-fluid">
      <div id="two-column-menu"></div>
      <ul class="navbar-nav" id="navbar-nav">

        <?php if (in_array($userRoleName, ['admin', 'dev'])): ?>
          <li class="menu-title"><span>Administración</span></li>

          <li class="nav-item">
            <a class="nav-link menu-link" href="<?= base_url('usuarios') ?>">
              <i class="ri-group-line"></i> <span>Gestión de Usuarios</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link menu-link" href="#sidebarInventario" data-bs-toggle="collapse">
              <i class="ri-fridge-line"></i> <span>Inventario Lácteo</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarInventario">
              <ul class="nav nav-sm flex-column">
                <li class="nav-item"><a href="<?= base_url('inventarios') ?>" class="nav-link">Registro de Leche</a></li>
                <li class="nav-item"><a href="<?= base_url('categorias') ?>" class="nav-link">Categorías</a></li>
                <li class="nav-item"><a href="<?= base_url('unidades') ?>" class="nav-link">Unidades</a></li>
              </ul>
            </div>
          </li>

          <li class="nav-item">
            <a class="nav-link menu-link" href="#sidebarEnvios" data-bs-toggle="collapse">
              <i class="ri-truck-line"></i> <span>Envíos</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarEnvios">
              <ul class="nav nav-sm flex-column">
                <li class="nav-item"><a href="<?= base_url('envios') ?>" class="nav-link">Gestionar Envíos</a></li>
                <li class="nav-item"><a href="<?= base_url('resepciones') ?>" class="nav-link">Recepcionar Envíos</a></li>
              </ul>
            </div>
          </li>

          <li class="nav-item">
            <a class="nav-link menu-link" href="<?= base_url('sucursales') ?>">
              <i class="ri-store-3-line"></i> <span>Gestión Sucursales</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link menu-link" href="#sidebarVentas" data-bs-toggle="collapse">
              <i class="ri-shopping-bag-2-line"></i> <span>Ventas</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarVentas">
              <ul class="nav nav-sm flex-column">
                <li class="nav-item"><a href="<?= base_url('ventas') ?>" class="nav-link">Gestionar Ventas</a></li>
              </ul>
            </div>
          </li>
        <?php endif; ?>


        <?php if ($userRoleName === 'vendedor'): ?>
          <li class="menu-title"><span>Ventas</span></li>
          <li class="nav-item">
            <a class="nav-link menu-link" href="#sidebarVentas" data-bs-toggle="collapse">
              <i class="ri-shopping-bag-2-line"></i> <span>Ventas</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarVentas">
              <ul class="nav nav-sm flex-column">
                <li class="nav-item"><a href="<?= base_url('ventas') ?>" class="nav-link">Gestionar Ventas</a></li>
                <li class="nav-item"><a href="<?= base_url('ventas/register') ?>" class="nav-link">Realizar Venta</a></li>
                <li class="nav-item"><a href="<?= base_url('cliente/lista') ?>" class="nav-link">Clientes</a></li>
              </ul>
            </div>
          </li>

          <li class="nav-item">
            <a class="nav-link menu-link" href="#sidebarRecepciones" data-bs-toggle="collapse">
              <i class="ri-truck-line"></i> <span>Recepciones</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarRecepciones">
              <ul class="nav nav-sm flex-column">
                <li class="nav-item"><a href="<?= base_url('resepciones') ?>" class="nav-link">Recepcionar Envíos</a></li>
              </ul>
            </div>
            <div class="collapse menu-dropdown" id="sidebarRecepciones">
              <ul class="nav nav-sm flex-column">
                <li class="nav-item"><a href="<?= base_url('envios?tipo=devolucion') ?>" class="nav-link">Devoluciones</a>
                </li>
              </ul>
            </div>

          </li>
          <li class="nav-item">
            <a class="nav-link menu-link" href="#sidebarAlmacen" data-bs-toggle="collapse">
              <i class="ri-truck-line"></i> <span>Inventario</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarAlmacen">
              <ul class="nav nav-sm flex-column">
                <li class="nav-item"><a href="<?= base_url('inventariosucursales') ?>" class="nav-link">Inventario
                    Stock</a></li>
              </ul>
            </div>
          </li>
        <?php endif; ?>


        <?php if ($userRoleName === 'almacen'): ?>
          <li class="menu-title"><span>Inventario</span></li>
          
          <li class="nav-item">
            <a class="nav-link menu-link" href="<?= base_url('usuarios') ?>">
              <i class="ri-group-line"></i> <span>Gestión de Usuarios</span>
            </a>
          </li>

          <li class="nav-item">
            <a class="nav-link menu-link" href="#sidebarInventario" data-bs-toggle="collapse">
              <i class="ri-fridge-line"></i> <span>Inventario Lácteo</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarInventario">
              <ul class="nav nav-sm flex-column">
                <li class="nav-item"><a href="<?= base_url('inventarios') ?>" class="nav-link">Registro de Leche</a></li>
                <li class="nav-item"><a href="<?= base_url('stockinventario') ?>" class="nav-link">Inventario Stock</a>
                </li>
                <li class="nav-item"><a href="<?= base_url('categorias') ?>" class="nav-link">Categorías</a></li>
                <li class="nav-item"><a href="<?= base_url('unidades') ?>" class="nav-link">Unidades</a></li>
              </ul>
            </div>
          </li>

         <!--  <li class="nav-item">
            <a class="nav-link menu-link" href="<?= base_url('productos') ?>">
              <i class="ri-store-3-line"></i> <span>Productos</span>
            </a>
          </li> -->

          <li class="nav-item">
            <a class="nav-link menu-link" href="#sidebarEnvios" data-bs-toggle="collapse">
              <i class="ri-truck-line"></i> <span>Envíos</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarEnvios">
              <ul class="nav nav-sm flex-column">
                <li class="nav-item"><a href="<?= base_url('envios') ?>" class="nav-link">Gestionar Envíos</a></li>
              </ul>
            </div>
            <div class="collapse menu-dropdown" id="sidebarEnvios">
              <ul class="nav nav-sm flex-column">
                <li class="nav-item"><a href="<?= base_url('resepciones') ?>" class="nav-link">Devoluciones</a></li>
              </ul>
            </div>
          </li>

          <li class="menu-title"><span>Ventas</span></li>
          <li class="nav-item">
            <a class="nav-link menu-link" href="#sidebarVentas" data-bs-toggle="collapse">
              <i class="ri-shopping-bag-2-line"></i> <span>Ventas</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarVentas">
              <ul class="nav nav-sm flex-column">
                <li class="nav-item"><a href="<?= base_url('inventarios/ventas') ?>" class="nav-link">Gestionar Ventas</a>
                </li>
              </ul>
            </div>
          </li>
          <li class="nav-item">
            <a class="nav-link menu-link" href="#sidebarBajas" data-bs-toggle="collapse">
              <i class="ri-shopping-bag-2-line"></i> <span>Bajas</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarBajas">
              <ul class="nav nav-sm flex-column">
                <li class="nav-item"><a href="<?= base_url('baja') ?>" class="nav-link">Gestionar Bajas</a></li>
              </ul>
            </div>
          </li>
        <?php endif; ?>


        <?php if (in_array($userRoleName, ['agropecuario', 'ganaderia'])): ?>
          <li class="menu-title"><span><?= $userRoleName === 'agropecuario' ? 'Agropecuario' : 'Ganadería' ?></span></li>

          <li class="nav-item">
            <a class="nav-link menu-link" href="#sidebarAgro" data-bs-toggle="collapse">
              <i class="ri-leaf-line"></i>
              <span><?= $userRoleName === 'agropecuario' ? 'Agropecuario' : 'Ganadería' ?></span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarAgro">
              <ul class="nav nav-sm flex-column">
                <li class="nav-item"><a href="<?= base_url('productosagro') ?>" class="nav-link">Registro Productos</a></li>
              </ul>
            </div>
          </li>

          <li class="nav-item">
            <a class="nav-link menu-link" href="#sidebarEnviosAgro" data-bs-toggle="collapse">
              <i class="ri-truck-line"></i> <span>Envíos</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarEnviosAgro">
              <ul class="nav nav-sm flex-column">
                <li class="nav-item"><a href="<?= base_url('envios') ?>" class="nav-link">Gestionar Envíos</a></li>
                <li class="nav-item"><a href="<?= base_url('envios/devoluciones') ?>" class="nav-link">Devoluciones</a></li>
              </ul>
            </div>
          </li>

          <li class="menu-title"><span>Ventas</span></li>
          <li class="nav-item">
            <a class="nav-link menu-link" href="#sidebarVentasAgro" data-bs-toggle="collapse">
              <i class="ri-shopping-bag-2-line"></i> <span>Ventas</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarVentasAgro">
              <ul class="nav nav-sm flex-column">
                <li class="nav-item"><a href="<?= base_url('productosagro/ventas') ?>" class="nav-link">Gestionar Ventas</a></li>
                <li class="nav-item"><a href="<?= base_url('productosagro/registerVentas') ?>" class="nav-link">Realizar Venta</a></li>
                <li class="nav-item"><a href="<?= base_url('cliente/lista') ?>" class="nav-link">Clientes</a></li>
              </ul>
            </div>
          </li>
        <?php endif; ?>


        <?php if ($userRoleName === 'contabilidad'): ?>
          <li class="menu-title"><span>Contabilidad</span></li>
          <li class="nav-item">
            <a class="nav-link menu-link" href="<?= base_url('/') ?>">
              <i class="ri-bank-line"></i> <span>Contabilidad</span>
            </a>
          </li>
        <?php endif; ?>

        <?php if ($userRoleName === 'admin'): ?>
          <li class="menu-title"><span>Contabilidad</span></li>
          <li class="nav-item">
            <a class="nav-link menu-link" href="<?= base_url('contabilidad') ?>">
              <i class="ri-bank-line"></i> <span>Contabilidad</span>
            </a>
          </li>
        <?php endif; ?>
        
        <?php if (in_array($userRoleName, ['admin', 'contabilidad'])): ?>

          <li class="menu-title"><span>Lacteos</span></li>
          <li class="nav-item">
            <a class="nav-link menu-link" href="#sidebarVentas" data-bs-toggle="collapse">
              <i class="ri-shopping-bag-2-line"></i> <span>Reportes</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarVentas">
              <ul class="nav nav-sm flex-column">
                <li class="nav-item"><a href="<?= base_url('inventarios/ventas') ?>" class="nav-link"> Ventas</a></li>
              </ul>
            </div>
          </li>
          <li class="menu-title"><span> Ventas Oruro</span></li>
          <li class="nav-item">
            <a class="nav-link menu-link" href="#sidebarVentasOruro" data-bs-toggle="collapse">
              <i class="ri-shopping-bag-2-line"></i> <span>Reportes</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarVentasOruro">
              <ul class="nav nav-sm flex-column">
                <li class="nav-item"><a href="<?= base_url('ventas') ?>" class="nav-link">Ventas</a></li>

              </ul>
            </div>
          </li>
          <li class="menu-title"><span>Agropecuario</span></li>
          <li class="nav-item">
            <a class="nav-link menu-link" href="#sidebarVentasAgro" data-bs-toggle="collapse">
              <i class="ri-shopping-bag-2-line"></i> <span>Reportes</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarVentasAgro">
              <ul class="nav nav-sm flex-column">
                <li class="nav-item"><a href="<?= base_url('productosagro/ventas') ?>" class="nav-link"> Ventas</a></li>
              </ul>
            </div>
          </li>
          <li class="menu-title"><span>Ganaderia</span></li>
          <li class="nav-item">
            <a class="nav-link menu-link" href="#sidebarVentasAgro1" data-bs-toggle="collapse">
              <i class="ri-shopping-bag-2-line"></i> <span>Reportes</span>
            </a>
            <div class="collapse menu-dropdown" id="sidebarVentasAgro1">
              <ul class="nav nav-sm flex-column">
                <li class="nav-item"><a href="<?= base_url('productosagro/ventas') ?>" class="nav-link">Ventas</a></li>
              </ul>
            </div>
          </li>


        <?php endif; ?>


        <?php if ($userRoleName === 'dev'): ?>
          <li class="menu-title"><i class="ri-code-s-line"></i> <span>Desarrollo</span></li>
          <li class="nav-item">
            <a class="nav-link menu-link" href="#sidebarUI" data-bs-toggle="collapse">
              <i class="ri-code-s-slash-line"></i> <span>Componentes UI</span>
            </a>
            <div class="collapse menu-dropdown mega-dropdown-menu" id="sidebarUI">
              <div class="row">
                <div class="col-lg-4">
                  <ul class="nav nav-sm flex-column">
                    <li class="nav-item"><a href="ui-alerts" class="nav-link">Alerts</a></li>
                    <li class="nav-item"><a href="ui-buttons" class="nav-link">Buttons</a></li>
                    <li class="nav-item"><a href="ui-cards" class="nav-link">Cards</a></li>
                  </ul>
                </div>
                <div class="col-lg-4">
                  <ul class="nav nav-sm flex-column">
                    <li class="nav-item"><a href="ui-modals" class="nav-link">Modals</a></li>
                    <li class="nav-item"><a href="ui-tabs" class="nav-link">Tabs</a></li>
                    <li class="nav-item"><a href="ui-typography" class="nav-link">Typography</a></li>
                  </ul>
                </div>
                <div class="col-lg-4">
                  <ul class="nav nav-sm flex-column">
                    <li class="nav-item"><a href="ui-grid" class="nav-link">Grid</a></li>
                    <li class="nav-item"><a href="ui-utilities" class="nav-link">Utilities</a></li>
                    <li class="nav-item"><a href="ui-colors" class="nav-link">Colors</a></li>
                  </ul>
                </div>
              </div>
            </div>
          </li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</div>

<div class="sidebar-background"></div>
<div class="vertical-overlay"></div>