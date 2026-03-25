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
  }

  .quick-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    filter: brightness(0.96);
  }

  .quick-item.orange { background-color: #fff0e0; border-color: #fd7e14; }
  .quick-item.green  { background-color: #e6f4ea; border-color: #28a745; }

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

  .quick-item.orange .qi-icon { background-color: #fd7e14; color: #fff; }
  .quick-item.green  .qi-icon { background-color: #28a745; color: #fff; }

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
  
  <div class="subtitle">Bienvenido Agropecuario</div>
    <!-- Acceso rápido -->
  <section class="quick">
    <div class="quick-title">Acceso Rápido</div>

    <div class="quick-grid">

      <a href="<?= base_url('productosagro') ?>" class="quick-link" aria-label="Registro de Productos">
        <div class="quick-item orange">
          <div class="qi-icon"><i class="mdi mdi-leaf"></i></div>
          <div class="qi-title">Registro de Productos</div>
          <div class="qi-sub">Gestiona productos agropecuarios</div>
        </div>
      </a>

      <a href="<?= base_url('productosagro/ventas') ?>" class="quick-link" aria-label="Punto de Venta">
        <div class="quick-item green">
          <div class="qi-icon"><i class="mdi mdi-cash-register"></i></div>
          <div class="qi-title">Punto de Venta</div>
          <div class="qi-sub">Registra ventas agropecuarias</div>
        </div>
      </a>

      <!-- <div class="quick-item">
        <div class="qi-icon"><i class="fa-solid fa-chart-simple"></i></div>
        <div class="qi-title">Reportes de Ventas</div>
        <div class="qi-sub">Analiza rendimiento comercial</div>
      </div> -->
    </div>
  </section>

  <!-- Nota: si quieres que todo el bloque tenga el comportamiento visual de enlace, añade en tu CSS:
  .quick-link { text-decoration: none; color: inherit; display: block; }
  -->
</main>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Si usas Chart.js en el futuro, aquí irían los scripts -->
<?= $this->endSection() ?>