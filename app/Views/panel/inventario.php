<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  :root {
    --primary-green: #28a745;
    --primary-green-light: #d4edda;
    --white: #ffffff;
    --gray-100: #f8f9fa;
    --gray-700: #495057;
    --gray-500: #adb5bd;
  }

  .main {
    padding: 1.5rem;
    background-color: #f5f7fb;
  }

  .page-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--gray-700);
    margin-bottom: 0.25rem;
  }

  .subtitle {
    color: var(--gray-500);
    margin-bottom: 1.5rem;
    font-size: 1rem;
  }

  /* KPI Cards */
  .kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.25rem;
    margin-bottom: 2rem;
  }

  .kpi {
    background: var(--white);
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border-left: 4px solid var(--primary-green);
    transition: transform 0.2s;
  }

  .kpi:hover {
    transform: translateY(-3px);
  }

  .kpi .label {
    font-size: 0.9rem;
    color: var(--gray-500);
    margin-bottom: 0.5rem;
  }

  .kpi .value {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--gray-700);
    margin-bottom: 0.5rem;
  }

  .kpi .sub {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: var(--gray-500);
  }

  .trend.up {
    color: var(--primary-green);
    font-weight: 600;
  }

  .trend.down {
    color: #e74c3c;
    font-weight: 600;
  }

  /* Quick Access */
  .quick-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 1rem;
  }

  .quick-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1.25rem;
  }

  .quick-link { text-decoration: none; color: inherit; display: block; }

  .quick-item {
    border-radius: 12px;
    padding: 1.25rem;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 1.5px solid transparent;
    cursor: pointer;
  }

  .quick-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    filter: brightness(0.96);
  }

  .quick-item.blue { background-color: #e8f0fe; border-color: #0d6efd; }
  .quick-item.cyan { background-color: #e0f5f7; border-color: #0097a7; }

  .qi-icon {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.5rem;
  }

  .quick-item.blue .qi-icon { background-color: #0d6efd; color: #fff; }
  .quick-item.cyan .qi-icon { background-color: #0097a7; color: #fff; }

  .qi-title {
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 0.25rem;
    font-size: 1rem;
  }

  .qi-sub {
    font-size: 0.85rem;
    color: var(--gray-500);
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<main class="main">
  <div class="page-title">Panel de Inventario</div>


  <!-- Acceso rápido -->
  <section class="quick">
    <div class="quick-title">Acceso Rápido</div>
    <div class="quick-grid">

      <a href="<?= base_url('inventarios') ?>" class="quick-link">
        <div class="quick-item" style="background:#e8f5e9; border-color:#28a745;">
          <div class="qi-icon" style="background:#28a745; color:#fff;"><i class="ri-drop-line"></i></div>
          <div class="qi-title">Registro de Leche</div>
          <div class="qi-sub">Registrar ingreso de materia prima</div>
        </div>
      </a>

      <a href="<?= base_url('stockinventario') ?>" class="quick-link">
        <div class="quick-item" style="background:#e8f0fe; border-color:#0d6efd;">
          <div class="qi-icon" style="background:#0d6efd; color:#fff;"><i class="ri-archive-stack-line"></i></div>
          <div class="qi-title">Inventario / Stock</div>
          <div class="qi-sub">Consultar y gestionar el stock de productos</div>
        </div>
      </a>

      <a href="<?= base_url('envios') ?>" class="quick-link">
        <div class="quick-item" style="background:#e0f5f7; border-color:#0097a7;">
          <div class="qi-icon" style="background:#0097a7; color:#fff;"><i class="ri-truck-line"></i></div>
          <div class="qi-title">Gestionar Envíos</div>
          <div class="qi-sub">Programar y monitorear envíos</div>
        </div>
      </a>

      <a href="<?= base_url('inventarios/ventas') ?>" class="quick-link">
        <div class="quick-item" style="background:#fff3e0; border-color:#f57c00;">
          <div class="qi-icon" style="background:#f57c00; color:#fff;"><i class="ri-shopping-cart-line"></i></div>
          <div class="qi-title">Ventas</div>
          <div class="qi-sub">Registrar y consultar ventas de planta</div>
        </div>
      </a>

    </div>
  </section>

</main>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Si usas Chart.js en el futuro, aquí irían los scripts -->
<?= $this->endSection() ?>
