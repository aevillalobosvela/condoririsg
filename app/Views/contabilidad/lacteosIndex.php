<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>
Reportes de Ventas - <?= esc($sucursal['nombre'] ?? 'Sucursal') ?>
<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  :root {
    --primary: #28a745;
    --primary-dark: #1e7e34;
    --primary-light: #e8f5ec;
    --gray-700: #495057;
    --gray-500: #6c757d;
    --gray-300: #dee2e6;
    --gray-200: #e9ecef;
    --white: #ffffff;
    --shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  }

  .sucursal-header {
    background: linear-gradient(135deg, #f9fdfb 0%, #edf7f2 100%);
    height: 240px;
    padding: 1.25rem;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    flex-direction: column;
  }

  .header-top {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    flex: 1;
  }

  .sucursal-logo {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(40, 167, 69, 0.2);
  }

  .header-text {
    flex: 1;
    min-width: 0;
  }

  .sucursal-title {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--gray-700);
    margin: 0 0 0.25rem;
    line-height: 1.2;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .sucursal-subtitle {
    font-size: 1rem;
    color: var(--gray-500);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.35rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    background: var(--white);
    color: var(--primary);
    border: 1px solid var(--primary);
  }

  .header-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 0.75rem;
    margin-top: 1rem;
  }

  .info-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem;
    background: white;
    border-radius: 10px;
    font-size: 0.9rem;
    box-shadow: var(--shadow);
  }

  .info-icon {
    width: 32px;
    height: 32px;
    background: rgba(40, 167, 69, 0.1);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    flex-shrink: 0;
  }

  .info-label {
    font-weight: 600;
    color: var(--gray-700);
  }

  .btn-back {
    margin-top: 0.75rem;
    padding: 0.5rem 1.25rem;
    background: white;
    color: var(--primary);
    border: 1px solid var(--primary);
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
    box-shadow: var(--shadow);
  }

  .btn-back:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-2px);
  }

  .report-filters {
    background: white;
    padding: 1.25rem;
    border-radius: 12px;
    box-shadow: var(--shadow);
    margin-bottom: 1.5rem;
  }

  .filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: center;
  }

  .date-inputs {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-wrap: wrap;
  }

  .date-inputs input,
  .date-inputs button {
    padding: 0.4rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 8px;
    border: 1px solid var(--gray-300);
  }

  .date-inputs input[type="date"] {
    min-width: 140px;
  }

  .pdf-buttons {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    align-items: center;
  }

  .btn-pdf {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 1rem;
    font-weight: 600;
    border-radius: 8px;
    text-decoration: none;
  }

  .chart-section {
    background: white;
    border-radius: 12px;
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-bottom: 1.5rem;
  }

  .chart-header {
    padding: 1rem 1.25rem;
    background: #fbfcfd;
    border-bottom: 1px solid var(--gray-200);
    font-weight: 600;
    color: var(--gray-700);
  }

  .chart-body {
    padding: 1rem;
    min-height: 250px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .chart-container {
    width: 100%;
    height: 220px;
  }

  /* Estilos específicos para sucursal_id == 2 */
  .bg-soft-total {
    background-color: #d4edda;
    color: #155724;
  }

  .bg-soft-stock {
    background-color: #f8fdfa !important;
    color: #28a745 !important;
  }

  .card {
    border: 1px solid #e0f0e9;
    box-shadow: 0 0.125rem 0.25rem rgba(40, 167, 69, 0.08);
    border-radius: 8px;
  }

  .card-header {
    background-color: #f8fdfa;
    border-bottom: 1px solid #e0f0e9;
    font-weight: 600;
    color: #28a745;
  }

  .btn-success {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
  }

  .btn-success:hover {
    background-color: #218838 !important;
    border-color: #1e7e34 !important;
  }

  .badge-finalizada {
    background-color: #d4edda;
    color: #155724;
    font-weight: 600;
    padding: 0.35em 0.6em;
    border-radius: 6px;
  }

  .badge-cancelada {
    background-color: #f8d7da;
    color: #721c24;
    font-weight: 600;
    padding: 0.35em 0.6em;
    border-radius: 6px;
  }

  .table-light {
    background-color: #f8fdfa !important;
    border-color: #e0f0e9 !important;
  }

  .table {
    border-color: #e0f0e9 !important;
  }

  .filter-section {
    background-color: #f8fdfa;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
  }

  .btn-cierre-caja,
  .btn-cierre-caja:focus {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #343a40;
  }

  .btn-cierre-caja:hover {
    background-color: #e0a800;
    border-color: #d39e00;
    color: #343a40;
  }

  .card-cierre-caja {
    border: 3px solid #ffc107;
  }

  @media (max-width: 767.98px) {
    .sucursal-header { height: auto; min-height: 200px; padding: 1rem; }
    .header-top { flex-direction: column; text-align: center; gap: 1rem; }
    .sucursal-title, .sucursal-subtitle { white-space: normal; }
    .header-info { grid-template-columns: 1fr; }
    .filter-row { flex-direction: column; align-items: stretch; }
    .date-inputs { width: 100%; }
    .date-inputs input { flex: 1; }
    .pdf-buttons { justify-content: center; }
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
date_default_timezone_set('America/La_Paz');
$hoy = date('Y-m-d');

$fecha_desde = $_GET['fecha_inicio'] ?? $hoy;
$fecha_hasta = $_GET['fecha_fin'] ?? $hoy;

$fecha_desde = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_desde) ? $fecha_desde : $hoy;
$fecha_hasta = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_hasta) ? $fecha_hasta : $hoy;

$sucursal_id = $sucursal['id'] ?? null;
if (!$sucursal_id) {
    $uri = service('uri');
    $segments = $uri->getSegments();
    $sucursal_id = end($segments) ?: null;
}
?>

<div class="sucursal-header">
  <div class="header-top">
    <div class="sucursal-logo">
      <i class="ri-bank-line"></i>
    </div>
    <div class="header-text">
      <h1 class="sucursal-title"><?= esc($sucursal['nombre'] ?? 'Sucursal ID: ' . $sucursal_id) ?></h1>
      <p class="sucursal-subtitle">
        <i class="ri-map-pin-2-line"></i>
        <?= !empty($sucursal['direccion']) ? esc($sucursal['direccion']) : 'Sin dirección' ?>
      </p>
    </div>
    <div class="status-badge">
      <i class="<?= ($sucursal['estado'] ?? 1) ? 'ri-checkbox-circle-fill text-success' : 'ri-close-circle-fill text-danger' ?>"></i>
      <span><?= ($sucursal['estado'] ?? 1) ? 'Activa' : 'Inactiva' ?></span>
    </div>
  </div>

  <div class="header-info">
    <?php if (!empty($sucursal['telefono'])): ?>
      <div class="info-item">
        <div class="info-icon"><i class="ri-phone-line"></i></div>
        <span class="info-label"><?= esc($sucursal['telefono']) ?></span>
      </div>
    <?php endif; ?>
    <div class="info-item">
      <div class="info-icon"><i class="ri-time-line"></i></div>
      <span class="info-label">L-V: 08:00–18:00</span>
    </div>
  </div>

  <a href="<?= base_url('/') ?>" class="btn-back">
    <i class="ri-arrow-left-s-line"></i> Volver al inicio
  </a>
</div>

<div class="container-fluid">
  <div class="report-filters">
    <div class="filter-row">
      <h5 class="mb-0"><i class="ri-bar-chart-2-line text-success me-2"></i>Reportes de Ventas</h5>

      <form id="report-filter-form" method="get" class="date-inputs">
        <input type="hidden" name="sucursal_id" value="<?= esc($sucursal_id) ?>">

        <input type="date" id="fecha_desde" name="fecha_inicio" value="<?= esc($fecha_desde) ?>" required>
        <input type="date" id="fecha_hasta" name="fecha_fin" value="<?= esc($fecha_hasta) ?>" required>
        <button type="submit" class="btn btn-success">
          <i class="ri-search-line"></i> Filtrar
        </button>
        <button type="button" class="btn btn-outline-secondary" onclick="setToday()">
          <i class="ri-calendar-line"></i> Hoy
        </button>
      </form>

      <div class="pdf-buttons">
        <a href="<?= base_url('contabilidad/reporte?fecha_inicio=' . urlencode($fecha_desde) . '&fecha_fin=' . urlencode($fecha_hasta) . '&sucursal_id=' . urlencode($sucursal_id) . '&tipo=general') ?>"
           class="btn btn-info btn-pdf" target="_blank">
          <i class="ri-file-pdf-2-line"></i> General
        </a>
        <a href="<?= base_url('contabilidad/reporte?fecha_inicio=' . urlencode($fecha_desde) . '&fecha_fin=' . urlencode($fecha_hasta) . '&sucursal_id=' . urlencode($sucursal_id) . '&tipo=credito') ?>"
           class="btn btn-warning btn-pdf" target="_blank">
          <i class="ri-file-pdf-2-line"></i> Crédito
        </a>
        <a href="<?= base_url('contabilidad/reporte?fecha_inicio=' . urlencode($fecha_desde) . '&fecha_fin=' . urlencode($fecha_hasta) . '&sucursal_id=' . urlencode($sucursal_id) . '&tipo=contado') ?>"
           class="btn btn-primary btn-pdf" target="_blank">
          <i class="ri-file-pdf-2-line"></i> Contado
        </a>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-6 mb-4">
      <div class="chart-section">
        <div class="chart-header">
          Total de Ventas (Monto y Cantidad)
        </div>
        <div class="chart-body">
          <div class="chart-container">
            <canvas id="chartTotalVentas"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6 mb-4">
      <div class="chart-section">
        <div class="chart-header">
          Ventas por Tipo de Pago (Contado vs. Crédito)
        </div>
        <div class="chart-body">
          <div class="chart-container">
            <canvas id="chartDesgloseVentas"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12 mb-4">
      <div class="chart-section">
        <div class="chart-header">
          Producción por Producto
        </div>
        <div class="chart-body">
          <div class="chart-container" style="height: 300px;">
            <canvas id="chartProduccion"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if ($sucursal_id == 2): ?>
    <!-- ✅ CONTENIDO PARA SUCURSAL 2 - INSERTADO DEBAJO DE LOS GRÁFICOS -->
    <div class="row">
      <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
          <h4 class="mb-sm-0">Módulo Operativo Diario</h4>
          <div class="page-title-right">
            <ol class="breadcrumb m-0">
              <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Dashboard</a></li>
              <li class="breadcrumb-item active">Ventas Detalladas</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <?php
    // Valores por defecto para evitar errores si no se pasan desde el controlador
    $fecha_desde_kpi = $fecha_desde ?? $hoy;
    $fecha_hasta_kpi = $fecha_hasta ?? $hoy;
    $per_page_kpi = $per_page ?? 10;
    $totalVentas_kpi = $totalVentas ?? 0;
    $totalContado_kpi = $totalContado ?? 0;
    $totalCredito_kpi = $totalCredito ?? 0;
    $totalRegistros_kpi = $totalRegistros ?? 0;
    $ventas_kpi = $ventas ?? [];

    $isSingleDay = ($fecha_desde_kpi === $fecha_hasta_kpi);
    $currentTime = time();
    $startSellTime = strtotime($hoy . ' 08:00:00');
    $endSellTime = strtotime($hoy . ' 22:00:00');
    $isToday = ($fecha_desde_kpi === $hoy);
    $isPastDate = ($fecha_desde_kpi < $hoy);
    $isSellingTime = ($currentTime >= $startSellTime && $currentTime <= $endSellTime);
    $canSell = $isToday && $isSellingTime;
    $showCloseBoxKPI = $isSingleDay && ($isPastDate || ($isToday && !$isSellingTime));
    ?>

    <div class="row">
      <div class="col-xl-3 col-md-6">
        <?php if ($canSell): ?>
          <a href="<?= base_url('inventarios/registerVenta') ?>" class="text-decoration-none">
            <div class="card card-animate">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="flex-grow-1 overflow-hidden">
                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0">VENTA ACTIVA (08:00 - 16:00)</p>
                  </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                  <div>
                    <h4 class="fs-22 fw-semibold ff-secondary mb-4 text-success">VENTAS AL CONTADO</h4>
                    <span class="text-decoration-underline text-success">Registrar Ahora</span>
                  </div>
                  <div class="avatar-sm flex-shrink-0">
                    <span class="avatar-title bg-soft-total rounded fs-3">
                      <i class="ri-shopping-cart-line text-success"></i>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </a>
        <?php elseif ($showCloseBoxKPI): ?>
          <a href="<?= base_url('inventarios/cierre?fecha_inicio=' . esc($fecha_desde_kpi) . '&fecha_fin=' . esc($fecha_desde_kpi)) ?>" target="_blank" class="text-decoration-none">
            <div class="card card-animate card-cierre-caja">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <div class="flex-grow-1 overflow-hidden">
                    <p class="text-uppercase fw-medium text-muted text-truncate mb-0">ACCIÓN REQUERIDA</p>
                  </div>
                </div>
                <div class="d-flex align-items-end justify-content-between mt-4">
                  <div>
                    <h4 class="fs-22 fw-semibold ff-secondary mb-4 text-warning">CIERRE DE CAJA</h4>
                    <span class="text-muted">Día: <?= date('d/m/Y', strtotime($fecha_desde_kpi)) ?></span>
                  </div>
                  <div class="avatar-sm flex-shrink-0">
                    <span class="avatar-title btn-cierre-caja rounded fs-3 text-dark">
                      <i class="ri-lock-2-line"></i>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </a>
        <?php else: ?>
          <div class="card card-animate">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="flex-grow-1 overflow-hidden">
                  <p class="text-uppercase fw-medium text-muted text-truncate mb-0">VENTAS</p>
                </div>
              </div>
              <div class="d-flex align-items-end justify-content-between mt-4">
                <div>
                  <h4 class="fs-22 fw-semibold ff-secondary mb-4">Solo Consulta</h4>
                  <span class="text-muted">No es día de hoy o es rango de fechas.</span>
                </div>
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-soft-total rounded fs-3">
                    <i class="ri-history-line text-info"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <div class="col-xl-3 col-md-6">
        <a href="<?= base_url('inventarios/credito') ?>" class="text-decoration-none">
          <div class="card card-animate">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="flex-grow-1 overflow-hidden">
                  <p class="text-uppercase fw-medium text-muted text-truncate mb-0">VENTA ACTIVA (08:00 - 18:00)</p>
                </div>
              </div>
              <div class="d-flex align-items-end justify-content-between mt-4">
                <div>
                  <h4 class="fs-22 fw-semibold ff-secondary mb-4 text-success">VENTAS A CRÉDITO</h4>
                  <span class="text-decoration-underline text-success">Registrar Ahora</span>
                </div>
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-soft-total rounded fs-3">
                    <i class="ri-shopping-cart-line text-success"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </a>
      </div>

      <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1 overflow-hidden">
                <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Ventas (Filtrado)</p>
              </div>
            </div>
            <div class="d-flex align-items-end justify-content-between mt-4">
              <div>
                <h4 class="fs-22 fw-semibold ff-secondary mb-4 text-success">
                  Bs. <?= number_format($totalVentas_kpi, 2) ?>
                </h4>
                <span class="text-muted">
                  <?= date('d/m/Y', strtotime($fecha_desde_kpi)) ?> - <?= date('d/m/Y', strtotime($fecha_hasta_kpi)) ?>
                </span>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-soft-total rounded fs-3">
                  <i class="ri-money-dollar-circle-line text-success"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1 overflow-hidden">
                <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total al Contado</p>
              </div>
            </div>
            <div class="d-flex align-items-end justify-content-between mt-4">
              <div>
                <h4 class="fs-22 fw-semibold ff-secondary mb-4 text-primary">
                  Bs. <?= number_format($totalContado_kpi, 2) ?>
                </h4>
                <span class="text-muted">
                  <?= date('d/m/Y', strtotime($fecha_desde_kpi)) ?> - <?= date('d/m/Y', strtotime($fecha_hasta_kpi)) ?>
                </span>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-soft-primary rounded fs-3">
                  <i class="ri-hand-coin-line text-primary"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1 overflow-hidden">
                <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total a Crédito</p>
              </div>
            </div>
            <div class="d-flex align-items-end justify-content-between mt-4">
              <div>
                <h4 class="fs-22 fw-semibold ff-secondary mb-4 text-warning">
                  Bs. <?= number_format($totalCredito_kpi, 2) ?>
                </h4>
                <span class="text-muted">
                  <?= date('d/m/Y', strtotime($fecha_desde_kpi)) ?> - <?= date('d/m/Y', strtotime($fecha_hasta_kpi)) ?>
                </span>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title bg-soft-warning rounded fs-3">
                  <i class="ri-wallet-3-line text-warning"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="filter-section">
          <form id="ventas-filter-form" method="get" class="row g-3 align-items-end">
            <input type="hidden" name="sucursal_id" value="2">
            <div class="col-md-2">
              <label for="fecha_desde_kpi" class="form-label">Desde</label>
              <input type="date" class="form-control" id="fecha_desde_kpi" name="fecha_desde" value="<?= esc($fecha_desde_kpi) ?>" required>
            </div>
            <div class="col-md-2">
              <label for="fecha_hasta_kpi" class="form-label">Hasta</label>
              <input type="date" class="form-control" id="fecha_hasta_kpi" name="fecha_hasta" value="<?= esc($fecha_hasta_kpi) ?>" required>
            </div>
            <div class="col-md-2">
              <label for="per_page_kpi" class="form-label">Mostrar</label>
              <select name="per_page" id="per_page_kpi" class="form-select">
                <option value="10" <?= $per_page_kpi == 10 ? 'selected' : '' ?>>10</option>
                <option value="20" <?= $per_page_kpi == 20 ? 'selected' : '' ?>>20</option>
                <option value="30" <?= $per_page_kpi == 30 ? 'selected' : '' ?>>30</option>
                <option value="50" <?= $per_page_kpi == 50 ? 'selected' : '' ?>>50</option>
                <option value="100" <?= $per_page_kpi == 100 ? 'selected' : '' ?>>100</option>
              </select>
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-success w-100">
                <i class="ri-filter-line me-1"></i> Filtrar
              </button>
            </div>
            <div class="col-md-2">
              <button type="button" class="btn btn-outline-secondary w-100" onclick="setTodayAndSubmitKPI()">
                <i class="ri-calendar-line me-1"></i> Hoy
              </button>
            </div>
            <div class="col-md-2">
              <a href="<?= base_url('inventarios/cierre/rango?fecha_inicio=' . urlencode($fecha_desde_kpi) . '&fecha_fin=' . urlencode($fecha_hasta_kpi) . '&tipo=contado&sucursal_id=2') ?>"
                 class="btn btn-outline-primary w-100" target="_blank">
                <i class="ri-file-pdf-line me-1"></i> Contado
              </a>
            </div>
            <div class="col-md-2">
              <a href="<?= base_url('inventarios/cierre/rango?fecha_inicio=' . urlencode($fecha_desde_kpi) . '&fecha_fin=' . urlencode($fecha_hasta_kpi) . '&tipo=credito&sucursal_id=2') ?>"
                 class="btn btn-outline-warning w-100" target="_blank">
                <i class="ri-file-pdf-line me-1"></i> Crédito
              </a>
            </div>
            <div class="col-md-2">
              <a href="<?= base_url('inventarios/cierre/rango?fecha_inicio=' . urlencode($fecha_desde_kpi) . '&fecha_fin=' . urlencode($fecha_hasta_kpi) . '&tipo=general&sucursal_id=2') ?>"
                 class="btn btn-info w-100" target="_blank">
                <i class="ri-file-pdf-line me-1"></i> General
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="row mt-2">
      <div class="col-lg-12">
        <div class="card">
          <div class="card-header border-0">
            <div class="d-flex align-items-center">
              <h5 class="card-title mb-0 flex-grow-1">Registros de Ventas — <?= esc($sucursal['nombre'] ?? 'Sucursal 2') ?></h5>
              <?php if (!empty($totalRegistros_kpi)): ?>
                <div class="text-muted small">
                  Total Registros: <?= esc($totalRegistros_kpi) ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <div class="card-body pt-0">
            <?php if (empty($ventas_kpi)): ?>
              <div class="text-center py-5">
                <i class="ri-archive-line display-4 text-muted mb-2"></i>
                <p class="text-muted">No hay registros de ventas en el rango seleccionado.</p>
              </div>
            <?php else: ?>
              <div class="table-responsive table-card mb-1">
                <table class="table align-middle table-nowrap">
                  <thead class="table-light text-muted">
                    <tr>
                      <th>Código</th>
                      <th>Cliente / Personal UTO</th>
                      <th>Monto Total</th>
                      <th>Tipo Pago</th>
                      <th>Fecha</th>
                      <th>Estado</th>
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($ventas_kpi as $venta): ?>
                      <tr>
                        <td><strong><?= esc($venta->code ?? 'N/A') ?></strong></td>
                        <td>
                          <?php if (!empty($venta->personal_uto_id)): ?>
                            <strong><?= esc($venta->nombre_personal ?? 'Personal UTO') ?></strong><br>
                            <small>CI: <?= esc($venta->dip ?? 'N/A') ?> | <?= esc($venta->cargo ?? '') ?> - <?= esc($venta->seccion ?? '') ?></small>
                          <?php else: ?>
                            <?= esc($venta->cliente_nombre ?? 'Consumidor Final') ?>
                          <?php endif; ?>
                        </td>
                        <td><strong class="text-success">Bs. <?= esc(number_format($venta->monto_total, 2)) ?></strong></td>
                        <td><?= esc(ucfirst($venta->tipo_pago ?? 'N/A')) ?></td>
                        <td><?= esc(date('d/m/Y H:i', strtotime($venta->created_at))) ?></td>
                        <td>
                          <?php if ($venta->estado == 1): ?>
                            <span class="badge-finalizada">Finalizada</span>
                          <?php else: ?>
                            <span class="badge-cancelada">Cancelada</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <a href="<?= base_url('inventarios/recibo/' . $venta->id) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Imprimir Recibo">
                            <i class="ri-printer-line"></i>
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <?php if (!empty($pagerLinks)): ?>
                <div class="mt-3">
                  <?= $pagerLinks ?>
                </div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
let chartTotalVentasInstance = null;
let chartDesgloseVentasInstance = null;
let chartProduccionInstance = null;

function setToday() {
  const hoy = new Date().toISOString().split('T')[0];
  document.getElementById('fecha_desde').value = hoy;
  document.getElementById('fecha_hasta').value = hoy;
  loadCharts();
}

function setTodayAndSubmitKPI() {
  const hoy = new Date().toISOString().split('T')[0];
  const desde = document.getElementById('fecha_desde_kpi');
  const hasta = document.getElementById('fecha_hasta_kpi');
  if (desde) desde.value = hoy;
  if (hasta) hasta.value = hoy;
  const form = document.getElementById('ventas-filter-form');
  if (form) form.submit();
}

async function loadCharts() {
  const fecha_inicio = document.getElementById('fecha_desde').value;
  const fecha_fin = document.getElementById('fecha_hasta').value;
  const sucursal_id = <?= json_encode((int)($sucursal_id ?? 0)) ?>;

  if (!sucursal_id || !fecha_inicio || !fecha_fin) {
    console.warn('⚠️ Faltan parámetros para cargar gráficos.');
    return;
  }

  const url = `<?= site_url('contabilidad/reportes/grafico') ?>?fecha_inicio=${encodeURIComponent(fecha_inicio)}&fecha_fin=${encodeURIComponent(fecha_fin)}&sucursal_id=${sucursal_id}`;

  if (chartTotalVentasInstance) chartTotalVentasInstance.destroy();
  if (chartDesgloseVentasInstance) chartDesgloseVentasInstance.destroy();
  if (chartProduccionInstance) chartProduccionInstance.destroy();

  try {
    const response = await fetch(url);
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    const data = await response.json();

    if (!data.ventasTotal || !data.ventasPorTipo || !data.produccionPorProducto) {
      throw new Error("Datos incompletos");
    }

    // Gráfico 1
    const ctx1 = document.getElementById('chartTotalVentas').getContext('2d');
    chartTotalVentasInstance = new Chart(ctx1, {
      type: 'bar',
      data: {
        labels: ['Ventas Totales'],
        datasets: [
          { label: 'Cantidad', data: [data.ventasTotal.cantidad], backgroundColor: '#28a745', borderRadius: 4, barPercentage: 0.5, categoryPercentage: 0.5 },
          { label: 'Monto (Bs)', data: [data.ventasTotal.monto], backgroundColor: '#17a2b8', borderRadius: 4, yAxisID: 'y1', barPercentage: 0.5, categoryPercentage: 0.5 }
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: {
          x: { grid: { drawOnChartArea: false } },
          y: { beginAtZero: true, title: { display: true, text: 'Cantidad' }, ticks: { precision: 0 } },
          y1: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, title: { display: true, text: 'Bs' } }
        }
      }
    });

    // Gráfico 2
    const ctx2 = document.getElementById('chartDesgloseVentas').getContext('2d');
    chartDesgloseVentasInstance = new Chart(ctx2, {
      type: 'pie',
      data: {
        labels: ['Contado', 'Crédito'],
        datasets: [{
          label: 'Monto (Bs)',
          data: [data.ventasPorTipo.contado.monto, data.ventasPorTipo.credito.monto],
          backgroundColor: ['#28a745', '#ffc107'],
          hoverOffset: 4
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } }
      }
    });

    // Gráfico 3
    const ctx3 = document.getElementById('chartProduccion').getContext('2d');
    const productos = data.produccionPorProducto.map(p => p.producto);
    const unidades = data.produccionPorProducto.map(p => p.unidades);
    const colores = ['#20c997', '#17a2b8', '#6f42c1', '#e83e8c', '#ffc107', '#dc3545', '#6c757d', '#28a745'];
    chartProduccionInstance = new Chart(ctx3, {
      type: 'bar',
      data: {
        labels: productos,
        datasets: [{
          label: 'Unidades',
          data: unidades,
          backgroundColor: colores.slice(0, unidades.length),
          borderRadius: 4,
          barPercentage: 0.8
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, title: { display: true, text: 'Unidades' } } }
      }
    });
  } catch (err) {
    console.error('❌ Error:', err);
    ['chartTotalVentas', 'chartDesgloseVentas', 'chartProduccion'].forEach(id => {
      const canvas = document.getElementById(id);
      if (canvas) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        if (canvas.width && canvas.height) {
          ctx.fillStyle = '#dc3545';
          ctx.font = 'bold 14px sans-serif';
          ctx.textAlign = 'center';
          ctx.fillText('⚠️ Error', canvas.width / 2, canvas.height / 2 - 10);
          ctx.fillStyle = '#6c757d';
          ctx.font = '12px sans-serif';
          ctx.fillText('Ver consola', canvas.width / 2, canvas.height / 2 + 10);
        }
      }
    });
  }
}

document.addEventListener('DOMContentLoaded', function () {
  loadCharts();

  document.getElementById('report-filter-form')?.addEventListener('submit', function(e) {
    e.preventDefault(); loadCharts();
  });

  document.getElementById('fecha_desde')?.addEventListener('change', loadCharts);
  document.getElementById('fecha_hasta')?.addEventListener('change', loadCharts);

  // Manejo del select de paginación en sucursal 2
  const perPageSelect = document.getElementById('per_page_kpi');
  if (perPageSelect) {
    perPageSelect.addEventListener('change', function() {
      const form = document.getElementById('ventas-filter-form');
      if (form) form.submit();
    });
  }
});
</script>
<?= $this->endSection() ?>