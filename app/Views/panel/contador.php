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

  .quick-item {
    background: var(--white);
    border-radius: 12px;
    padding: 1.25rem;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
  }

  .quick-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.1);
    border-color: var(--primary-green);
  }

  .qi-icon {
    width: 56px;
    height: 56px;
    background-color: var(--primary-green-light);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: var(--primary-green);
    font-size: 1.5rem;
  }

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
  <div class="page-title">Panel de Control</div>
  <div class="subtitle">Bienvenido de nuevo, Carlos. Aquí tienes un resumen de las operaciones actuales.</div>

  <!-- KPIs -->
  <section class="kpis">
    <div class="card kpi">
      <div class="label">Inventario Total</div>
      <div class="value">2,547</div>
      <div class="sub">
        <span class="trend up"><i class="fa-solid fa-arrow-trend-up"></i> 3.2%</span>
        <span>desde el mes pasado</span>
      </div>
    </div>

    <div class="card kpi">
      <div class="label">Envíos Pendientes</div>
      <div class="value">18</div>
      <div class="sub">
        <span class="trend down"><i class="fa-solid fa-arrow-trend-down"></i> 8%</span>
        <span>desde la semana pasada</span>
      </div>
    </div>

    <div class="card kpi">
      <div class="label">Ventas del Día</div>
      <div class="value">Bs. 12,845</div>
      <div class="sub">
        <span>42 transacciones</span>
        <span class="trend up"><i class="fa-solid fa-arrow-trend-up"></i> 8.3%</span>
        <span>vs ayer</span>
      </div>
    </div>

    <div class="card kpi">
      <div class="label">Alertas de Stock</div>
      <div class="value">7</div>
      <div class="sub">
        <span class="trend down"><i class="fa-solid fa-minus"></i> 2</span>
        <span>menos que ayer</span>
      </div>
    </div>
  </section> 

  <!-- Acceso rápido -->
  <section class="quick">
    <div class="quick-title">Acceso Rápido</div>
    <div class="quick-grid">
      <div class="quick-item">
        <div class="qi-icon"><i class="fa-solid fa-warehouse"></i></div>
        <div class="qi-title">Gestión de Inventario</div>
        <div class="qi-sub">Administra productos y stock</div>
      </div>

      <div class="quick-item">
        <div class="qi-icon"><i class="fa-solid fa-truck-fast"></i></div>
        <div class="qi-title">Gestión de Envíos</div>
        <div class="qi-sub">Programa y monitorea envíos</div>
      </div>

      <div class="quick-item">
        <div class="qi-icon"><i class="fa-solid fa-clipboard-check"></i></div>
        <div class="qi-title">Aceptación de Envíos</div>
        <div class="qi-sub">Recibe y verifica mercancía</div>
      </div>

      <div class="quick-item">
        <div class="qi-icon"><i class="fa-solid fa-cash-register"></i></div>
        <div class="qi-title">Punto de Venta</div>
        <div class="qi-sub">Registra ventas y pagos</div>
      </div>

      <div class="quick-item">
        <div class="qi-icon"><i class="fa-solid fa-chart-simple"></i></div>
        <div class="qi-title">Reportes de Ventas</div>
        <div class="qi-sub">Analiza rendimiento comercial</div>
      </div>
    </div>
  </section>
</main>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Si usas Chart.js en el futuro, aquí irían los scripts -->
<?= $this->endSection() ?>