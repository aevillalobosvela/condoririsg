<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  :root {
    --primary: #28a745;
    --primary-dark: #218838;
    --primary-light: #e8f5ec;
    --secondary: #6c757d;
    --success: #28a745;
    --warning: #ffc106;
    --danger: #dc3545;
    --info: #17a2b8;
    --observado: #ff6b6b;
    --light: #f8f9fa;
    --dark: #343a40;
    --border: #e0f0e9;
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
    --shadow: 0 4px 12px rgba(40,167,69,0.1);
    --shadow-lg: 0 10px 25px rgba(40,167,69,0.15);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  /* Layout */
  .dashboard-layout {
    padding: 0 16px;
    max-width: 1400px;
    margin: 0 auto;
  }
  @media (min-width: 768px) {
    .dashboard-layout { padding: 0 24px; }
  }

  /* Page header */
  .page-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--border);
  }
  .page-title {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--dark);
    margin: 0;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  /* KPI Cards */
  .kpi-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
  }

  .kpi-card {
    background: white;
    border-radius: 16px;
    box-shadow: var(--shadow);
    overflow: hidden;
    transition: var(--transition);
    text-decoration: none;
    display: block;
    position: relative;
    cursor: pointer;
  }
  .kpi-card:hover {
    transform: translateY(-6px) scale(1.02);
    box-shadow: var(--shadow-lg);
  }
  .kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: var(--primary);
    transition: var(--transition);
  }
  .kpi-card:hover::before {
    height: 6px;
  }
  .kpi-card.pendiente::before { background: linear-gradient(90deg, #ffc106 0%, #ff9800 100%); }
  .kpi-card.transito::before { background: linear-gradient(90deg, #17a2b8 0%, #138496 100%); }
  .kpi-card.entregado::before { background: linear-gradient(90deg, #28a745 0%, #20c997 100%); }
  .kpi-card.observado::before { background: linear-gradient(90deg, #ff6b6b 0%, #ee5a6f 100%); }

  .kpi-body {
    padding: 24px;
    position: relative;
  }
  .kpi-title {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--secondary);
    margin: 0 0 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .kpi-value {
    font-size: 2.25rem;
    font-weight: 800;
    color: var(--dark);
    margin: 0 0 8px;
    font-family: 'Segoe UI', 'Arial', sans-serif;
    line-height: 1;
  }
  .kpi-subtitle {
    font-size: 0.8rem;
    color: var(--secondary);
    margin: 0;
    font-weight: 500;
  }

  .kpi-icon {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 56px;
    height: 56px;
    background: var(--primary-light);
    color: var(--primary);
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    transition: var(--transition);
  }
  .kpi-card:hover .kpi-icon {
    transform: rotate(10deg) scale(1.1);
  }
  .kpi-card.pendiente .kpi-icon { background: #fff3cd; color: #856404; }
  .kpi-card.transito .kpi-icon { background: #d1ecf1; color: #0c5460; }
  .kpi-card.entregado .kpi-icon { background: #d4edda; color: #155724; }
  .kpi-card.observado .kpi-icon { background: #ffe5e5; color: #c92a2a; }

  /* Filters */
  .filters-section {
    background: white;
    border-radius: 16px;
    box-shadow: var(--shadow);
    margin-bottom: 24px;
  }
  .filters-header {
    padding: 18px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border);
    cursor: pointer;
    transition: var(--transition);
  }
  .filters-header:hover {
    background: var(--primary-light);
  }
  .filters-header h3 {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0;
    color: var(--dark);
  }
  .filters-body {
    padding: 24px;
  }
  .filter-row {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
  }
  @media (min-width: 768px) {
    .filter-row {
      grid-template-columns: repeat(5, 1fr);
    }
  }

  .filter-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: flex-end;
  }
  .btn-filter {
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: var(--transition);
  }
  .btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
  }

  /* Tabs */
  .tabs-container {
    background: white;
    border-radius: 16px;
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-bottom: 24px;
  }
  .nav-tabs-custom {
    display: flex;
    border-bottom: 2px solid var(--border);
    background: var(--light);
    padding: 0;
    margin: 0;
    overflow-x: auto;
    scrollbar-width: thin;
  }
  .nav-tabs-custom::-webkit-scrollbar {
    height: 4px;
  }
  .nav-tabs-custom::-webkit-scrollbar-thumb {
    background: var(--primary);
    border-radius: 4px;
  }
  .nav-tab {
    flex: 1;
    min-width: 140px;
    padding: 16px 20px;
    text-align: center;
    cursor: pointer;
    border: none;
    background: transparent;
    color: var(--secondary);
    font-weight: 600;
    font-size: 0.9rem;
    transition: var(--transition);
    position: relative;
    border-bottom: 3px solid transparent;
  }
  .nav-tab:hover {
    background: rgba(40, 167, 69, 0.05);
    color: var(--primary);
  }
  .nav-tab.active {
    background: white;
    color: var(--primary);
    border-bottom-color: var(--primary);
  }
  .nav-tab .tab-count {
    display: inline-block;
    margin-left: 6px;
    padding: 2px 8px;
    background: var(--primary-light);
    color: var(--primary);
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 700;
  }
  .nav-tab.active .tab-count {
    background: var(--primary);
    color: white;
  }

  .tab-content {
    display: none;
    padding: 0;
  }
  .tab-content.active {
    display: block;
    animation: fadeIn 0.3s ease;
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* Table */
  .table-header {
    padding: 20px 24px;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    background: var(--light);
  }
  .table-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--dark);
    margin: 0;
  }

  .table-responsive {
    overflow-x: auto;
  }
  .table-envios {
    min-width: 800px;
    margin: 0;
  }
  .table-envios thead th {
    background-color: var(--primary-light);
    color: var(--dark);
    font-weight: 700;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    padding: 14px 12px;
    border: none;
  }
  .table-envios tbody td {
    padding: 14px 12px;
    vertical-align: middle;
    border-bottom: 1px solid #f0f0f0;
  }
  .table-envios tbody tr {
    transition: var(--transition);
  }
  .table-envios tbody tr:hover {
    background-color: var(--primary-light);
    transform: scale(1.005);
  }

  /* Row colors by estado_id */
  .table-envios tbody tr.estado-1 { background-color: #ffebee; }
  .table-envios tbody tr.estado-2 { background-color: #fff9e6; }
  .table-envios tbody tr.estado-10 { background-color: #fff3cd; }
  .table-envios tbody tr.estado-11 { background-color: #ffe0b2; }
  .table-envios tbody tr.estado-9 { background-color: #e8f5e9; }
  .table-envios tbody tr.estado-1:hover { background-color: #ffcdd2; }
  .table-envios tbody tr.estado-2:hover { background-color: #fff59d; }
  .table-envios tbody tr.estado-10:hover { background-color: #ffe082; }
  .table-envios tbody tr.estado-11:hover { background-color: #ffcc80; }
  .table-envios tbody tr.estado-9:hover { background-color: #c8e6c9; }

  /* Badges */
  .badge-custom {
    font-weight: 600;
    padding: 0.45em 0.9em;
    border-radius: 20px;
    font-size: 0.8rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  .badge-estado-1 {
    background: linear-gradient(135deg, #ef5350 0%, #e53935 100%);
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(239, 83, 80, 0.3);
  }
  .badge-estado-2 {
    background: linear-gradient(135deg, #fff176 0%, #ffee58 100%);
    color: #f57f17;
    box-shadow: 0 2px 4px rgba(255, 241, 118, 0.3);
  }
  .badge-estado-10 {
    background: linear-gradient(135deg, #ffb74d 0%, #ffa726 100%);
    color: #e65100;
    box-shadow: 0 2px 4px rgba(255, 183, 77, 0.3);
  }
  .badge-estado-11 {
    background: linear-gradient(135deg, #ff8a65 0%, #ff7043 100%);
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(255, 138, 101, 0.3);
  }
  .badge-estado-9 {
    background: linear-gradient(135deg, #66bb6a 0%, #4caf50 100%);
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(102, 187, 106, 0.3);
  }
  /* Legacy badges for compatibility */
  .badge-pendiente { background: linear-gradient(135deg, #ef5350 0%, #e53935 100%); color: #ffffff; box-shadow: 0 2px 4px rgba(239, 83, 80, 0.3); }
  .badge-enviado { background: linear-gradient(135deg, #fff176 0%, #ffee58 100%); color: #f57f17; box-shadow: 0 2px 4px rgba(255, 241, 118, 0.3); }
  .badge-entregado { background: linear-gradient(135deg, #66bb6a 0%, #4caf50 100%); color: #ffffff; box-shadow: 0 2px 4px rgba(102, 187, 106, 0.3); }
  .badge-observado { background: linear-gradient(135deg, #ff8a65 0%, #ff7043 100%); color: #ffffff; box-shadow: 0 2px 4px rgba(255, 138, 101, 0.3); }

  /* Empty state */
  .empty-state {
    text-align: center;
    padding: 60px 20px;
  }
  .empty-icon {
    font-size: 4rem;
    color: var(--secondary);
    margin-bottom: 20px;
    opacity: 0.5;
  }
  .empty-text {
    color: var(--secondary);
    margin: 0;
    font-size: 1.2rem;
    font-weight: 600;
  }

  /* Responsive */
  @media (max-width: 767.98px) {
    .page-title {
      font-size: 1.4rem;
    }
    .kpi-cards {
      grid-template-columns: repeat(2, 1fr);
    }
    .kpi-value {
      font-size: 1.8rem;
    }
    .kpi-icon {
      width: 44px;
      height: 44px;
      font-size: 1.3rem;
    }
    .filter-row {
      grid-template-columns: 1fr !important;
    }
    .nav-tab {
      min-width: 100px;
      padding: 12px 10px;
      font-size: 0.8rem;
    }
  }

  /* Sort controls */
  .sort-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }
  .sort-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--secondary);
    white-space: nowrap;
  }
  .btn-sort {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    border: 2px solid transparent;
    background: var(--light);
    color: var(--secondary);
    cursor: pointer;
    transition: var(--transition);
    white-space: nowrap;
  }
  .btn-sort:hover {
    background: var(--primary-light);
    color: var(--primary);
    border-color: var(--primary);
    transform: translateY(-1px);
  }
  .btn-sort.active-asc,
  .btn-sort.active-desc {
    background: var(--primary);
    color: #fff;
    border-color: var(--primary-dark);
    box-shadow: 0 3px 8px rgba(40,167,69,0.35);
  }
  .btn-sort .sort-icon {
    font-size: 1rem;
    line-height: 1;
    transition: transform 0.2s ease;
  }
  .btn-sort.active-desc .sort-icon {
    transform: rotate(180deg);
  }
  .sort-divider {
    width: 1px;
    height: 20px;
    background: var(--border);
  }

  /* Animations */
  @keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .animate-fade-in-up {
    animation: fadeInUp 0.5s ease forwards;
  }

  /* ── Botón Reporte Excel (inline en barra de filtros) ── */
  .excel-fab-inline {
    display: inline-flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 2px;
    padding: 10px 18px 9px 16px;
    background: linear-gradient(135deg, #1a7a34 0%, #28a745 60%, #20c997 100%);
    color: #fff;
    border: none;
    border-radius: 14px;
    cursor: pointer;
    transition: var(--transition);
    min-width: 210px;
    text-align: left;
    filter: drop-shadow(0 4px 10px rgba(33,136,56,0.30));
    margin-left: auto;   /* empuja el botón al extremo derecho de la barra */
  }
  .excel-fab-inline:hover {
    transform: translateY(-3px) scale(1.02);
    filter: brightness(1.08) drop-shadow(0 8px 18px rgba(33,136,56,0.40));
  }
  .excel-fab-inline:active {
    transform: translateY(-1px) scale(0.99);
  }
  .excel-fab-icon {
    font-size: 1.4rem;
    margin-bottom: 1px;
  }
  .excel-fab-label {
    font-size: 0.88rem;
    font-weight: 700;
    line-height: 1.2;
  }
  .excel-fab-hint {
    font-size: 0.70rem;
    font-weight: 400;
    opacity: 0.85;
    line-height: 1.3;
  }
  @media (max-width: 767.98px) {
    .excel-fab-inline {
      margin-left: 0;
      width: 100%;
    }
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
// 🔑 🔑 🔑 FILTRADO POR ROL DEL USUARIO 🔑 🔑 🔑
$userRolId = session()->get('rol_id'); 
$enviosFiltrados = [];

foreach ($envios ?? [] as $envio) {
    $esDevolucion = (strtolower($envio['tipo'] ?? '') === 'devolucion');
    $esEnvio = (strtolower($envio['tipo'] ?? '') === 'envio');
    
    if ($userRolId == 1) {
        // Admin ve todo
        $enviosFiltrados[] = $envio;
    } elseif ($userRolId == 2 && $esDevolucion) {
        // Rol 2 solo ve devoluciones
        $enviosFiltrados[] = $envio;
    } elseif ($userRolId == 3 && $esEnvio) {
        // Rol 3 solo ve envíos
        $enviosFiltrados[] = $envio;
    }
}

// Calculate dynamic KPIs from the FILTRADOS data
$totalEnvios = count($enviosFiltrados);
$enviosPendientes = 0;
$enviosEnTransito = 0;
$enviosEntregados = 0;
$enviosObservados = 0;

// Arrays to hold envios by status
$enviosPorEstado = [
  'todos' => $enviosFiltrados,
  'pendientes' => [],
  'transito' => [],
  'entregados' => [],
  'observados' => []
];

foreach ($enviosFiltrados as $envio) {
  switch ($envio['estado_id']) {
    case 1:
      $enviosPendientes++;
      $enviosPorEstado['pendientes'][] = $envio;
      break;
    case 2:
      $enviosEnTransito++;
      $enviosPorEstado['transito'][] = $envio;
      break;
    case 9:
      $enviosEntregados++;
      $enviosPorEstado['entregados'][] = $envio;
      break;
    case 11:
      $enviosObservados++;
      $enviosPorEstado['observados'][] = $envio;
      break;
  }
}

// Estado definitions
$estados = [
  1 => 'Pendiente',
  2 => 'En Tránsito',
  9 => 'Entregado',
  11 => 'Observado'
];
?>

<div class="dashboard-layout">
  <!-- Page Header -->
  <div class="page-header animate-fade-in-up">
    <h1 class="page-title">
      <?php if ($userRolId == 2): ?>
        🔄 Devoluciones
      <?php elseif ($userRolId == 3): ?>
        📦 Envíos
      <?php else: ?>
        📦 Gestión de Envíos y Devoluciones
      <?php endif; ?>
    </h1>
    <?php if ($userRolId == 2): ?>
      <a href="<?= base_url('envios/register') ?>" class="btn btn-success btn-filter">
        <i class="ri-add-line"></i> Nueva Devolución
      </a>
    <?php elseif ($userRolId == 3): ?>
      <a href="<?= base_url('envios/register') ?>" class="btn btn-success btn-filter">
        <i class="ri-add-line"></i> Nuevo Envío
      </a>
    <?php else: ?>
       <!-- Admin: Generic button or maybe dropdown? For now, generic link which defaults to Envio or lets user choose in form -->
       <a href="<?= base_url('envios/register') ?>" class="btn btn-success btn-filter">
        <i class="ri-add-line"></i> Nuevo Registro
      </a>
    <?php endif; ?>
  </div>

  <!-- KPI Cards -->
  <div class="kpi-cards animate-fade-in-up">
    <div class="kpi-card" onclick="showAllTabs()">
      <div class="kpi-body">
        <h3 class="kpi-title">
            <?php 
                if ($userRolId == 2) echo 'Total Devoluciones';
                elseif ($userRolId == 3) echo 'Total Envíos';
                else echo 'Total Registros';
            ?>
        </h3>
        <h2 class="kpi-value"><?= $totalEnvios ?></h2>
        <p class="kpi-subtitle">Registros activos</p>
      </div>
      <div class="kpi-icon">
        <i class="ri-archive-line"></i>
      </div>
    </div>

    <div class="kpi-card pendiente" onclick="switchTab('pendientes')">
      <div class="kpi-body">
        <h3 class="kpi-title">Pendientes</h3>
        <h2 class="kpi-value"><?= $enviosPendientes ?></h2>
        <p class="kpi-subtitle">Requieren acción</p>
      </div>
      <div class="kpi-icon">
        <i class="ri-time-line"></i>
      </div>
    </div>

    <div class="kpi-card transito" onclick="switchTab('transito')">
      <div class="kpi-body">
        <h3 class="kpi-title">En Tránsito</h3>
        <h2 class="kpi-value"><?= $enviosEnTransito ?></h2>
        <p class="kpi-subtitle">En proceso</p>
      </div>
      <div class="kpi-icon">
        <i class="ri-truck-line"></i>
      </div>
    </div>

    <div class="kpi-card entregado" onclick="switchTab('entregados')">
      <div class="kpi-body">
        <h3 class="kpi-title">Finalizados</h3>
        <h2 class="kpi-value"><?= $enviosEntregados ?></h2>
        <p class="kpi-subtitle">Confirmados</p>
      </div>
      <div class="kpi-icon">
        <i class="ri-checkbox-circle-line"></i>
      </div>
    </div>

    <div class="kpi-card observado" onclick="switchTab('observados')">
      <div class="kpi-body">
        <h3 class="kpi-title">Observados</h3>
        <h2 class="kpi-value"><?= $enviosObservados ?></h2>
        <p class="kpi-subtitle">Requieren revisión</p>
      </div>
      <div class="kpi-icon">
        <i class="ri-alert-line"></i>
      </div>
    </div>
  </div>

  <!-- Filters -->
  <div class="filters-section animate-fade-in-up">
    <div class="filters-header" id="filtersToggle">
      <h3>🔍 Filtros de Búsqueda</h3>
      <i class="ri-filter-3-line"></i>
    </div>
    <div class="filters-body" id="filtersBody">
      <form id="filterForm" method="GET" action="<?= base_url('envios') ?>">
        <div class="filter-row">
          <div>
            <label for="filter_sucursal_origen" class="form-label fw-medium">Sucursal Origen</label>
            <select class="form-select" id="filter_sucursal_origen" name="sucursal_origen_id">
              <option value="">Todas las sucursales</option>
              <?php foreach ($sucursales ?? [] as $sucursal): ?>
                <option value="<?= $sucursal['id'] ?>" <?= (isset($_GET['sucursal_origen_id']) && $_GET['sucursal_origen_id'] == $sucursal['id']) ? 'selected' : '' ?>>
                  <?= esc($sucursal['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label for="filter_sucursal_destino" class="form-label fw-medium">Sucursal Destino</label>
            <select class="form-select" id="filter_sucursal_destino" name="sucursal_destino_id">
              <option value="">Todas las sucursales</option>
              <?php foreach ($sucursales ?? [] as $sucursal): ?>
                <option value="<?= $sucursal['id'] ?>" <?= (isset($_GET['sucursal_destino_id']) && $_GET['sucursal_destino_id'] == $sucursal['id']) ? 'selected' : '' ?>>
                  <?= esc($sucursal['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label for="filter_estado_id" class="form-label fw-medium">Estado</label>
            <select class="form-select" id="filter_estado_id" name="estado_id">
              <option value="">Todos los estados</option>
              <?php foreach ($estados as $id => $nombre): ?>
                <option value="<?= $id ?>" <?= (isset($_GET['estado_id']) && $_GET['estado_id'] == $id) ? 'selected' : '' ?>>
                  <?= esc($nombre) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div>
            <label for="filter_fecha_inicio" class="form-label fw-medium">Fecha Inicio</label>
            <input type="date" class="form-control" id="filter_fecha_inicio" 
                   name="fecha_inicio" value="<?= $_GET['fecha_inicio'] ?? '' ?>">
          </div>

          <div>
            <label for="filter_fecha_fin" class="form-label fw-medium">Fecha Fin</label>
            <input type="date" class="form-control" id="filter_fecha_fin" 
                   name="fecha_fin" value="<?= $_GET['fecha_fin'] ?? '' ?>">
          </div>
        </div>

        <div class="filter-actions mt-3">
          <button type="submit" class="btn btn-success btn-filter">
            <i class="ri-search-line"></i> Aplicar Filtros
          </button>
          <!-- <button type="submit" formaction="<?= base_url('envios/reporte') ?>" formtarget="_blank" class="btn btn-danger btn-filter">
            <i class="ri-file-pdf-line"></i> Generar Reporte PDF
          </button> -->
          <a href="<?= base_url('envios') ?>" class="btn btn-outline-secondary btn-filter">
            <i class="ri-refresh-line"></i> Limpiar
          </a>

          <button type="button" id="btnExportarExcel" class="btn btn-success btn-filter">
            <span class="excel-fab-label">Emitir reporte general</span>
          </button>
                      <span class="excel-fab-hint">Usa los filtros y el orden activo para personalizar la salida</span>

        </div>
      </form>
    </div>
  </div>

  <!-- Tabs Container -->
  <div class="tabs-container animate-fade-in-up">
    <div class="nav-tabs-custom">
      <button class="nav-tab active" data-tab="todos">
        Todos <span class="tab-count"><?= $totalEnvios ?></span>
      </button>
      <button class="nav-tab" data-tab="pendientes">
        Pendientes <span class="tab-count"><?= $enviosPendientes ?></span>
      </button>
      <button class="nav-tab" data-tab="transito">
        En Tránsito <span class="tab-count"><?= $enviosEnTransito ?></span>
      </button>
      <button class="nav-tab" data-tab="entregados">
        Finalizados <span class="tab-count"><?= $enviosEntregados ?></span>
      </button>
      <button class="nav-tab" data-tab="observados">
        Observados <span class="tab-count"><?= $enviosObservados ?></span>
      </button>
    </div>

    <?php foreach ($enviosPorEstado as $tabName => $enviosTab): ?>
      <div class="tab-content <?= $tabName === 'todos' ? 'active' : '' ?>" id="tab-<?= $tabName ?>">
        <!-- Sort bar -->
        <div class="table-header">
          <span class="sort-label"><i class="ri-sort-asc"></i> Ordenar por:</span>
          <div class="sort-bar">
            <button class="btn-sort active-desc" data-sort="fecha" data-tab="<?= $tabName ?>">
              <i class="ri-calendar-line"></i> Fecha
              <span class="sort-icon">↑</span>
            </button>
            <div class="sort-divider"></div>
            <button class="btn-sort" data-sort="codigo" data-tab="<?= $tabName ?>">
              <i class="ri-barcode-line"></i> Código
              <span class="sort-icon">↑</span>
            </button>
            <div class="sort-divider"></div>
            <button class="btn-sort" data-sort="estado" data-tab="<?= $tabName ?>">
              <i class="ri-flag-line"></i> Estado
              <span class="sort-icon">↑</span>
            </button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-envios table-hover mb-0" id="table-<?= $tabName ?>">
            <thead>
              <tr>
                <th>Código</th>
                <th>Sucursal Origen</th>
                <th>Sucursal Destino</th>
                <th>Fecha de Envío</th>
                <th>Estado</th>
                <th>Observación</th>
                <th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($enviosTab)): ?>
                <?php foreach ($enviosTab as $envio): ?>
                  <?php
                    $nombre_origen  = '';
                    $nombre_destino = '';
                    foreach ($sucursales ?? [] as $sucursal) {
                      if ($sucursal['id'] == $envio['sucursal_origen_id'])  $nombre_origen  = $sucursal['nombre'];
                      if ($sucursal['id'] == $envio['sucursal_destino_id']) $nombre_destino = $sucursal['nombre'];
                    }
                    $fechaRaw = $envio['fecha_envio'] ? date('Y-m-d H:i:s', strtotime($envio['fecha_envio'])) : '0000-00-00';
                  ?>
                  <tr class="estado-<?= $envio['estado_id'] ?>"
                      data-fecha="<?= esc($fechaRaw) ?>"
                      data-codigo="<?= esc($envio['code']) ?>"
                      data-estado="<?= (int)$envio['estado_id'] ?>">
                    <td><strong><?= esc($envio['code']) ?></strong></td>
                    <td><?= esc($nombre_origen  ?: '—') ?></td>
                    <td><?= esc($nombre_destino ?: '—') ?></td>
                    <td><?= esc($envio['fecha_envio'] ? date('d/m/Y H:i', strtotime($envio['fecha_envio'])) : '—') ?></td>
                    <td>
                      <?php 
                        $estado_id     = $envio['estado_id'];
                        $estado_nombre = $estados[$estado_id] ?? 'Desconocido';
                        $badge_class   = 'badge-estado-' . $estado_id;
                        $icon = match($estado_id) {
                          1  => 'ri-time-line',
                          2  => 'ri-truck-line',
                          10 => 'ri-loader-line',
                          11 => 'ri-alert-line',
                          9  => 'ri-checkbox-circle-line',
                          default => 'ri-question-line'
                        };
                      ?>
                      <span class="badge-custom <?= $badge_class ?>">
                        <i class="<?= $icon ?>"></i> <?= esc($estado_nombre) ?>
                      </span>
                    </td>
                    <td>
                      <?php 
                        $obs_origen  = $envio['observacion_origen']  ?? '';
                        $obs_destino = $envio['observacion_destino'] ?? '';
                        $observacion = $obs_destino ?: $obs_origen;
                        if ($observacion) {
                          echo '<small class="text-muted">' . esc(substr($observacion, 0, 40)) . (strlen($observacion) > 40 ? '...' : '') . '</small>';
                        } else {
                          echo '<span class="text-muted">—</span>';
                        }
                      ?>
                    </td>
                    <td class="text-end">
                      <div class="btn-group" role="group">
                        <a href="<?= base_url('envios/show/' . $envio['id']) ?>" 
                           class="btn btn-sm btn-outline-success" title="Ver Detalles">
                          <i class="ri-eye-fill"></i>
                        </a>
                        <?php if ($envio['estado_id'] == 1): ?>
                        <a href="<?= base_url('envios/edit/' . $envio['id']) ?>" 
                           class="btn btn-sm btn-outline-primary" title="Editar">
                          <i class="ri-pencil-fill"></i>
                        </a>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7">
                    <div class="empty-state">
                      <div class="empty-icon"><i class="ri-inbox-line"></i></div>
                      <h3 class="empty-text">No hay registros en esta categoría</h3>
                      <p class="text-muted mt-2">Los registros aparecerán aquí cuando estén disponibles.</p>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {

  // ── Estado de ordenamiento por tab ───────────────────────────────────────
  // Cada tab recuerda su columna activa y dirección
  const sortState = {};

  // ── Ordenar filas de una tabla ────────────────────────────────────────────
  function sortTable(tabName, column) {
    const table  = document.getElementById('table-' + tabName);
    if (!table) return;
    const tbody  = table.querySelector('tbody');
    const rows   = Array.from(tbody.querySelectorAll('tr[data-fecha]')); // solo filas de datos reales
    if (rows.length === 0) return;

    // Determinar dirección: si ya estaba activo en esta columna, invertir; si no, desc por defecto
    const prev = sortState[tabName] || {};
    let dir = 'desc';
    if (prev.column === column) {
      dir = prev.dir === 'desc' ? 'asc' : 'desc';
    }
    sortState[tabName] = { column, dir };

    rows.sort((a, b) => {
      let valA, valB;
      if (column === 'fecha') {
        valA = a.dataset.fecha;
        valB = b.dataset.fecha;
      } else if (column === 'codigo') {
        valA = a.dataset.codigo;
        valB = b.dataset.codigo;
      } else if (column === 'estado') {
        valA = parseInt(a.dataset.estado, 10);
        valB = parseInt(b.dataset.estado, 10);
      }
      if (valA < valB) return dir === 'asc' ? -1 :  1;
      if (valA > valB) return dir === 'asc' ?  1 : -1;
      return 0;
    });

    rows.forEach(row => tbody.appendChild(row));
    updateSortButtons(tabName, column, dir);
  }

  // ── Actualizar apariencia de botones ─────────────────────────────────────
  function updateSortButtons(tabName, activeColumn, dir) {
    const tabEl = document.getElementById('tab-' + tabName);
    if (!tabEl) return;
    tabEl.querySelectorAll('.btn-sort').forEach(btn => {
      btn.classList.remove('active-asc', 'active-desc');
      if (btn.dataset.sort === activeColumn) {
        btn.classList.add(dir === 'asc' ? 'active-asc' : 'active-desc');
      }
    });
  }

  // ── Adjuntar listeners a todos los botones de sort ───────────────────────
  document.querySelectorAll('.btn-sort').forEach(btn => {
    btn.addEventListener('click', function () {
      sortTable(this.dataset.tab, this.dataset.sort);
    });
  });

  // Ordenar por fecha desc al cargar (más reciente primero) en todos los tabs
  ['todos', 'pendientes', 'transito', 'entregados', 'observados'].forEach(tab => {
    sortState[tab] = { column: 'fecha', dir: 'asc' }; // forzar que el primer click quede en desc
    sortTable(tab, 'fecha');
  });

  // ── Botón Exportar Excel ─────────────────────────────────────────────────
  document.getElementById('btnExportarExcel')?.addEventListener('click', function () {
    // Recoger filtros del formulario
    const form    = document.getElementById('filterForm');
    const params  = new URLSearchParams(new FormData(form));

    // Detectar el tab activo y su estado de ordenamiento
    const activeTab = document.querySelector('.nav-tab.active')?.dataset?.tab ?? 'todos';
    const state     = sortState[activeTab] ?? { column: 'fecha', dir: 'desc' };
    params.set('sort_by',  state.column);
    params.set('sort_dir', state.dir);

    window.location.href = '<?= base_url('envios/exportarExcelEnvios') ?>?' + params.toString();
  });

  // ── Tab switching ─────────────────────────────────────────────────────────
  const tabs        = document.querySelectorAll('.nav-tab');
  const tabContents = document.querySelectorAll('.tab-content');

  window.switchTab = function (tabName) {
    tabs.forEach(tab => { if (tab.dataset.tab === tabName) tab.click(); });
  };
  window.showAllTabs = function () { switchTab('todos'); };

  tabs.forEach(tab => {
    tab.addEventListener('click', function () {
      tabs.forEach(t => t.classList.remove('active'));
      tabContents.forEach(c => c.classList.remove('active'));
      this.classList.add('active');
      document.getElementById('tab-' + this.dataset.tab).classList.add('active');
    });
  });

  // ── Toggle filtros en móvil ───────────────────────────────────────────────
  const filtersToggle = document.getElementById('filtersToggle');
  const filtersBody   = document.getElementById('filtersBody');
  if (window.innerWidth < 768) filtersBody.style.display = 'none';
  filtersToggle.addEventListener('click', () => {
    filtersBody.style.display = filtersBody.style.display === 'none' ? 'block' : 'none';
  });

  // ── Validación de fechas ──────────────────────────────────────────────────
  document.getElementById('filterForm')?.addEventListener('submit', function (e) {
    const inicio = document.getElementById('filter_fecha_inicio')?.value;
    const fin    = document.getElementById('filter_fecha_fin')?.value;
    if (inicio && fin && inicio > fin) {
      e.preventDefault();
      alert('⚠️ La fecha de inicio no puede ser posterior a la fecha de fin.');
    }
  });

  // ── Auto-switch tab por parámetro URL ────────────────────────────────────
  const urlParams = new URLSearchParams(window.location.search);
  const estadoId  = urlParams.get('estado_id');
  if (estadoId) {
    const tabMap = { '1': 'pendientes', '2': 'transito', '9': 'entregados', '11': 'observados' };
    if (tabMap[estadoId]) switchTab(tabMap[estadoId]);
  }
});
</script>
<?= $this->endSection() ?>