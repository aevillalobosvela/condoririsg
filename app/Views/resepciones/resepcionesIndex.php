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
  .kpi-card.aceptado::before { background: linear-gradient(90deg, #17a2b8 0%, #138496 100%); }
  .kpi-card.entregado::before { background: linear-gradient(90deg, #28a745 0%, #20c997 100%); }

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
  .kpi-card.aceptado .kpi-icon { background: #d1ecf1; color: #0c5460; }
  .kpi-card.entregado .kpi-icon { background: #d4edda; color: #155724; }

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
    display: block; /* Default visible */
  }
  .filter-row {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
  }
  @media (min-width: 768px) {
    .filter-row {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  .filter-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
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
  .table-envios tbody tr.estado-3 { background-color: #d4edda; }
  .table-envios tbody tr.estado-1:hover { background-color: #ffcdd2; }
  .table-envios tbody tr.estado-2:hover { background-color: #fff59d; }
  .table-envios tbody tr.estado-10:hover { background-color: #ffe082; }
  .table-envios tbody tr.estado-11:hover { background-color: #ffcc80; }
  .table-envios tbody tr.estado-9:hover { background-color: #c8e6c9; }
  .table-envios tbody tr.estado-3:hover { background-color: #a5d6a7; }

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
  .badge-estado-3 {
    background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(76, 175, 80, 0.3);
  }
  /* Legacy badges for compatibility */
  .badge-pendiente { background: linear-gradient(135deg, #ef5350 0%, #e53935 100%); color: #ffffff; box-shadow: 0 2px 4px rgba(239, 83, 80, 0.3); }
  .badge-aceptado { background: linear-gradient(135deg, #66bb6a 0%, #4caf50 100%); color: #ffffff; box-shadow: 0 2px 4px rgba(102, 187, 106, 0.3); }
  .badge-entregado { background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%); color: #ffffff; box-shadow: 0 2px 4px rgba(76, 175, 80, 0.3); }
  .badge-enviado { background: linear-gradient(135deg, #fff176 0%, #ffee58 100%); color: #f57f17; box-shadow: 0 2px 4px rgba(255, 241, 118, 0.3); }

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

  /* Animations */
  @keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .animate-fade-in-up {
    animation: fadeInUp 0.5s ease forwards;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
// Calculate dynamic KPIs from the envios data
// Note: The controller passes $totalRecibidos, $pendientes, $aceptados, $entregados
// But we also need to split the $envios array for the tabs.

$enviosPorEstado = [
  'todos' => $envios ?? [],
  'pendientes' => [],
  'aceptados' => [],
  'entregados' => []
];

foreach ($envios ?? [] as $envio) {
  if ($envio['estado_id'] == 1 || $envio['estado_id'] == 2) {
      $enviosPorEstado['pendientes'][] = $envio;
  } elseif ($envio['estado_id'] == 9) {
      $enviosPorEstado['aceptados'][] = $envio;
  } elseif ($envio['estado_id'] == 3) {
      $enviosPorEstado['entregados'][] = $envio;
  }
}
?>

<div class="dashboard-layout">
  <!-- Page Header -->
  <div class="page-header animate-fade-in-up">
    <h1 class="page-title">📦 Gestión de Recepciones</h1>
    <!-- No "Nueva Devolución" button here -->
  </div>

  <!-- KPI Cards -->
  <div class="kpi-cards animate-fade-in-up">
    <div class="kpi-card" onclick="showAllTabs()">
      <div class="kpi-body">
        <h3 class="kpi-title">Total Recibidos</h3>
        <h2 class="kpi-value"><?= $totalRecibidos ?? 0 ?></h2>
        <p class="kpi-subtitle">Todos los registros</p>
      </div>
      <div class="kpi-icon">
        <i class="ri-inbox-line"></i>
      </div>
    </div>

    <div class="kpi-card pendiente" onclick="switchTab('pendientes')">
      <div class="kpi-body">
        <h3 class="kpi-title">Pendientes</h3>
        <h2 class="kpi-value"><?= $pendientes ?? 0 ?></h2>
        <p class="kpi-subtitle">Por aceptar</p>
      </div>
      <div class="kpi-icon">
        <i class="ri-time-line"></i>
      </div>
    </div>

    <div class="kpi-card aceptado" onclick="switchTab('aceptados')">
      <div class="kpi-body">
        <h3 class="kpi-title">Aceptados</h3>
        <h2 class="kpi-value"><?= $aceptados ?? 0 ?></h2>
        <p class="kpi-subtitle">Confirmados</p>
      </div>
      <div class="kpi-icon">
        <i class="ri-check-double-line"></i>
      </div>
    </div>

    <div class="kpi-card entregado" onclick="switchTab('entregados')">
      <div class="kpi-body">
        <h3 class="kpi-title">Entregados</h3>
        <h2 class="kpi-value"><?= $entregados ?? 0 ?></h2>
        <p class="kpi-subtitle">Finalizados</p>
      </div>
      <div class="kpi-icon">
        <i class="ri-checkbox-circle-line"></i>
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
      <form id="filterForm" method="GET" action="<?= base_url('resepciones') ?>">
        <div class="filter-row">
          <div>
            <label for="estado" class="form-label fw-medium">Estado</label>
            <select class="form-select" id="estado" name="estado">
              <option value="">Todos</option>
              <option value="1" <?= ($filters['estado'] ?? '') == '1' ? 'selected' : '' ?>>Pendiente</option>
              <option value="2" <?= ($filters['estado'] ?? '') == '2' ? 'selected' : '' ?>>Enviado</option>
              <option value="9" <?= ($filters['estado'] ?? '') == '9' ? 'selected' : '' ?>>Aceptado</option>
              <option value="3" <?= ($filters['estado'] ?? '') == '3' ? 'selected' : '' ?>>Entregado</option>
            </select>
          </div>
          
          <div>
            <label for="fecha_inicio" class="form-label fw-medium">Fecha Inicio</label>
            <input type="date" class="form-control" id="fecha_inicio" 
                   name="fecha_inicio" value="<?= esc($filters['fecha_inicio'] ?? '') ?>">
          </div>

          <div>
            <label for="fecha_fin" class="form-label fw-medium">Fecha Fin</label>
            <input type="date" class="form-control" id="fecha_fin" 
                   name="fecha_fin" value="<?= esc($filters['fecha_fin'] ?? '') ?>">
          </div>
        </div>

        <div class="filter-actions mt-3">
          <button type="submit" class="btn btn-success btn-filter">
            <i class="ri-search-line"></i> Aplicar Filtros
          </button>
          <a href="<?= base_url('resepciones') ?>" class="btn btn-outline-secondary btn-filter">
            <i class="ri-refresh-line"></i> Limpiar
          </a>
          <button type="button" class="btn btn-danger btn-filter" onclick="generarReporte()">
            <i class="ri-file-pdf-line"></i> Reporte
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabs Container -->
  <div class="tabs-container animate-fade-in-up">
    <div class="nav-tabs-custom">
      <button class="nav-tab active" data-tab="todos">
        Todos <span class="tab-count"><?= count($enviosPorEstado['todos']) ?></span>
      </button>
      <button class="nav-tab" data-tab="pendientes">
        Pendientes <span class="tab-count"><?= count($enviosPorEstado['pendientes']) ?></span>
      </button>
      <button class="nav-tab" data-tab="aceptados">
        Aceptados <span class="tab-count"><?= count($enviosPorEstado['aceptados']) ?></span>
      </button>
      <button class="nav-tab" data-tab="entregados">
        Entregados <span class="tab-count"><?= count($enviosPorEstado['entregados']) ?></span>
      </button>
    </div>

    <?php foreach ($enviosPorEstado as $tabName => $enviosTab): ?>
      <div class="tab-content <?= $tabName === 'todos' ? 'active' : '' ?>" id="tab-<?= $tabName ?>">
        <div class="table-responsive">
          <table class="table table-envios table-hover mb-0">
            <thead>
              <tr>
                <th>Código</th>
                <th>Sucursal Origen</th>
                <th>Fecha de Envío</th>
                <th>Estado</th>
                <th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($enviosTab)): ?>
                <?php foreach ($enviosTab as $envio): ?>
                  <tr class="estado-<?= $envio['estado_id'] ?>">
                    <td><strong><?= esc($envio['code']) ?></strong></td>
                    <td>
                      <?php
                        $nombre_origen = '';
                        foreach ($sucursales as $sucursal) {
                          if ($sucursal['id'] == $envio['sucursal_origen_id']) {
                            $nombre_origen = $sucursal['nombre'];
                            break;
                          }
                        }
                        echo esc($nombre_origen ?: '—');
                      ?>
                    </td>
                    <td><?= esc($envio['fecha_envio'] ? date('d/m/Y', strtotime($envio['fecha_envio'])) : '—') ?></td>
                    <td>
                      <?php 
                        $estado_id = $envio['estado_id'];
                        $badge_class = 'badge-estado-' . $estado_id;
                        $estado_nombre = '';
                        $icon = '';

                        if ($estado_id == 1) {
                            $estado_nombre = 'Pendiente';
                            $icon = 'ri-time-line';
                        } elseif ($estado_id == 2) {
                            $estado_nombre = 'Enviado';
                            $icon = 'ri-truck-line';
                        } elseif ($estado_id == 9) {
                            $estado_nombre = 'Aceptado';
                            $icon = 'ri-check-double-line';
                        } elseif ($estado_id == 3) {
                            $estado_nombre = 'Entregado';
                            $icon = 'ri-checkbox-circle-line';
                        } elseif ($estado_id == 10) {
                            $estado_nombre = 'Proceso';
                            $icon = 'ri-loader-line';
                        } elseif ($estado_id == 11) {
                            $estado_nombre = 'Observado';
                            $icon = 'ri-alert-line';
                        } else {
                            $estado_nombre = 'Desconocido';
                            $icon = 'ri-question-line';
                        }
                      ?>
                      <span class="badge-custom <?= $badge_class ?>">
                        <i class="<?= $icon ?>"></i> <?= esc($estado_nombre) ?>
                      </span>
                    </td>
                    <td class="text-end">
                      <div class="btn-group" role="group">
                        <a href="<?= base_url('resepciones/show/' . $envio['id']) ?>" 
                           class="btn btn-sm btn-outline-success" 
                           title="Ver Detalles">
                          <i class="ri-eye-fill"> Ver Detalle</i>
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5">
                    <div class="empty-state">
                      <div class="empty-icon">
                        <i class="ri-inbox-line"></i>
                      </div>
                      <h3 class="empty-text">No hay recepciones en esta categoría</h3>
                      <p class="text-muted mt-2">Las recepciones aparecerán aquí cuando estén disponibles.</p>
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
document.addEventListener('DOMContentLoaded', function() {
  // Tab switching functionality
  const tabs = document.querySelectorAll('.nav-tab');
  const tabContents = document.querySelectorAll('.tab-content');

  window.switchTab = function(tabName) {
    tabs.forEach(tab => {
      if (tab.dataset.tab === tabName) {
        tab.click();
      }
    });
  };

  window.showAllTabs = function() {
    switchTab('todos');
  };

  tabs.forEach(tab => {
    tab.addEventListener('click', function() {
      const targetTab = this.dataset.tab;
      
      // Remove active class from all tabs and contents
      tabs.forEach(t => t.classList.remove('active'));
      tabContents.forEach(c => c.classList.remove('active'));
      
      // Add active class to clicked tab and corresponding content
      this.classList.add('active');
      document.getElementById('tab-' + targetTab).classList.add('active');
    });
  });

  // Toggle filters on mobile
  const filtersToggle = document.getElementById('filtersToggle');
  const filtersBody = document.getElementById('filtersBody');
  
  if (window.innerWidth < 768) {
    filtersBody.style.display = 'none';
  }
  
  filtersToggle.addEventListener('click', () => {
    const isHidden = filtersBody.style.display === 'none';
    filtersBody.style.display = isHidden ? 'block' : 'none';
  });

  // Form validation
  document.getElementById('filterForm')?.addEventListener('submit', function(e) {
    const inicio = document.getElementById('fecha_inicio')?.value;
    const fin = document.getElementById('fecha_fin')?.value;
    
    if (inicio && fin && inicio > fin) {
      e.preventDefault();
      alert('⚠️ La fecha de inicio no puede ser posterior a la fecha de fin.');
    }
  });

  // Report generation function
  window.generarReporte = function() {
    const estado = document.getElementById('estado').value;
    const fInicio = document.getElementById('fecha_inicio').value;
    const fFin = document.getElementById('fecha_fin').value;
    window.location.href = '<?= base_url('resepciones/reporteGeneral') ?>?estado=' + estado + '&fecha_inicio=' + fInicio + '&fecha_fin=' + fFin;
  };
});
</script>
<?= $this->endSection() ?>