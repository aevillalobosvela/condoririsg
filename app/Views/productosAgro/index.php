<?php $this->extend('layouts/main') ?>

<?php $this->section('title') ?><?= esc($title) ?><?php $this->endSection() ?>

<?php $this->section('styles') ?>
<style>
  :root {
    --color-contado: #16a34a;
    --color-contado-light: #dcfce7;
    --color-contado-dark: #15803d;
    --color-credito: #ca8a04;
    --color-credito-light: #fef9c3;
    --color-credito-dark: #a16207;
    --color-total: #65a30d;
    --color-total-light: #ecfccb;
    --color-total-dark: #4d7c0f;
    --color-cierre: #d97706;
    --color-cierre-light: #fef3c7;
    --color-cierre-dark: #b45309;
  }

  .card {
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-radius: 12px;
    transition: all 0.3s ease;
  }

  .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }

  .card-animate {
    overflow: hidden;
    position: relative;
  }

  .card-animate::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s;
  }

  .card-animate:hover::before {
    left: 100%;
  }

  .card-venta-contado {
    background: linear-gradient(135deg, var(--color-contado-light) 0%, #ffffff 100%);
    border-left: 4px solid var(--color-contado) !important;
  }

  .card-venta-contado .avatar-title {
    background: var(--color-contado-light) !important;
  }

  .card-venta-contado .text-primary-custom {
    color: var(--color-contado-dark) !important;
  }

  .card-venta-credito {
    background: linear-gradient(135deg, var(--color-credito-light) 0%, #ffffff 100%);
    border-left: 4px solid var(--color-credito) !important;
  }

  .card-venta-credito .avatar-title {
    background: var(--color-credito-light) !important;
  }

  .card-venta-credito .text-primary-custom {
    color: var(--color-credito-dark) !important;
  }

  .card-total-general {
    background: linear-gradient(135deg, var(--color-total-light) 0%, #ffffff 100%);
    border-left: 4px solid var(--color-total) !important;
  }

  .card-total-general .avatar-title {
    background: var(--color-total-light) !important;
  }

  .card-total-general .text-primary-custom {
    color: var(--color-total-dark) !important;
  }

  .card-cierre-caja {
    background: linear-gradient(135deg, var(--color-cierre-light) 0%, #ffffff 100%);
    border: 3px solid var(--color-cierre) !important;
    animation: pulse 2s infinite;
  }

  @keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(217, 119, 6, 0.4); }
    50%       { box-shadow: 0 0 0 10px rgba(217, 119, 6, 0); }
  }

  .card-cierre-caja .avatar-title {
    background: var(--color-cierre) !important;
    color: white !important;
  }

  .card-cierre-caja .text-primary-custom {
    color: var(--color-cierre-dark) !important;
  }

  .card-header {
    background-color: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    font-weight: 600;
    color: #374151;
  }

  .badge-finalizada {
    background-color: var(--color-contado-light);
    color: var(--color-contado-dark);
    font-weight: 600;
    padding: 0.35em 0.6em;
    border-radius: 6px;
  }

  .badge-cancelada {
    background-color: #fee2e2;
    color: #991b1b;
    font-weight: 600;
    padding: 0.35em 0.6em;
    border-radius: 6px;
  }

  .venta-contado {
    background-color: var(--color-contado-light) !important;
    border-left: 4px solid var(--color-contado) !important;
  }

  .venta-credito {
    background-color: var(--color-credito-light) !important;
    border-left: 4px solid var(--color-credito) !important;
  }

  .badge-tipo-contado {
    background-color: var(--color-contado);
    color: white;
    font-weight: 600;
    padding: 0.35em 0.6em;
    border-radius: 6px;
  }

  .badge-tipo-credito {
    background-color: var(--color-credito);
    color: white;
    font-weight: 600;
    padding: 0.35em 0.6em;
    border-radius: 6px;
  }

  .filter-section {
    background: linear-gradient(135deg, #f0fdf4 0%, #fefce8 100%);
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    border: 1px solid #d9f99d;
  }

  .avatar-sm {
    width: 48px;
    height: 48px;
  }

  .avatar-title {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    border-radius: 8px;
  }
</style>
<?php $this->endSection() ?>

<?php $this->section('content') ?>
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
          <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary me-3" title="Volver al inicio">
            <i class="ri-arrow-left-line"></i> Atrás
          </a>
          <div>
            <h4 class="mb-sm-0 me-3" style="color: var(--color-total-dark);">CONDORIRI AGROPECUARIO</h4>
            <h4 class="mb-sm-0"><?= esc($title) ?></h4>
          </div>
        </div>
        <div class="page-title-right">
          <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="<?= base_url('/') ?>">Dashboard</a></li>
            <li class="breadcrumb-item active"><?= esc($title) ?></li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <?php
  date_default_timezone_set('America/La_Paz');
  $hoy = date('Y-m-d');
  $fecha_desde = $fecha_desde ?? $hoy;
  $fecha_hasta = $fecha_hasta ?? $hoy;
  $per_page    = $per_page ?? 10;
  $totalVentas = $totalVentas ?? 0;

  $isSingleDay = ($fecha_desde === $fecha_hasta);
  $isToday     = ($fecha_desde === $hoy);
  $isPastDate  = ($fecha_desde < $hoy);

  $canSell         = $isToday;
  $showCloseBoxKPI = $isSingleDay && $isPastDate;
  ?>

  <div class="row">

    <div class="col-xl-3 col-md-6">
      <?php if ($canSell): ?>
        <a href="<?= base_url('productosagro/registerVentas') ?>" class="text-decoration-none">
          <div class="card card-animate card-venta-contado">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="flex-grow-1 overflow-hidden">
                  <p class="text-uppercase fw-medium text-muted text-truncate mb-0">VENTA ACTIVA (24 HORAS)</p>
                </div>
              </div>
              <div class="d-flex align-items-end justify-content-between mt-4">
                <div>
                  <h4 class="fs-22 fw-semibold ff-secondary mb-4 text-primary-custom">VENTAS AL CONTADO</h4>
                  <span class="text-decoration-underline text-primary-custom">Registrar Ahora</span>
                </div>
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title rounded fs-3">
                    <i class="ri-shopping-cart-line text-primary-custom"></i>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </a>
      <?php elseif ($showCloseBoxKPI): ?>
        <a href="<?= base_url('productosagro/ventas?fecha_desde=' . esc($fecha_desde) . '&fecha_hasta=' . esc($fecha_desde)) ?>" class="text-decoration-none">
          <div class="card card-animate card-cierre-caja">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <div class="flex-grow-1 overflow-hidden">
                  <p class="text-uppercase fw-medium text-muted text-truncate mb-0">ACCIÓN REQUERIDA</p>
                </div>
              </div>
              <div class="d-flex align-items-end justify-content-between mt-4">
                <div>
                  <h4 class="fs-22 fw-semibold ff-secondary mb-4 text-primary-custom">CIERRE DE CAJA</h4>
                  <span class="text-muted">Día: <?= date('d/m/Y', strtotime($fecha_desde)) ?></span>
                </div>
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title rounded fs-3">
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
                <h4 class="fs-22 fw-semibold ff-secondary mb-4 text-muted">Solo Consulta</h4>
                <span class="text-muted">No es día de hoy o es rango de fechas.</span>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title rounded fs-3" style="background-color: #e5e7eb;">
                  <i class="ri-history-line" style="color: #6b7280;"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="col-xl-3 col-md-6">
      <a href="<?= base_url('productosagro/credito') ?>" class="text-decoration-none">
        <div class="card card-animate card-venta-credito">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1 overflow-hidden">
                <p class="text-uppercase fw-medium text-muted text-truncate mb-0">VENTA ACTIVA (24 HORAS)</p>
              </div>
            </div>
            <div class="d-flex align-items-end justify-content-between mt-4">
              <div>
                <h4 class="fs-22 fw-semibold ff-secondary mb-4 text-primary-custom">VENTAS A CRÉDITO</h4>
                <span class="text-decoration-underline text-primary-custom">Registrar Ahora</span>
              </div>
              <div class="avatar-sm flex-shrink-0">
                <span class="avatar-title rounded fs-3">
                  <i class="ri-wallet-3-line text-primary-custom"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </a>
    </div>

    <div class="col-xl-3 col-md-6">
      <div class="card card-animate card-total-general">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1 overflow-hidden">
              <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total Ventas (Filtrado)</p>
            </div>
          </div>
          <div class="d-flex align-items-end justify-content-between mt-4">
            <div>
              <h4 class="fs-22 fw-semibold ff-secondary mb-4 text-primary-custom">
                Bs. <?= number_format($totalVentas, 2) ?>
              </h4>
              <span class="text-muted">
                <?= date('d/m/Y', strtotime($fecha_desde)) ?> - <?= date('d/m/Y', strtotime($fecha_hasta)) ?>
              </span>
            </div>
            <div class="avatar-sm flex-shrink-0">
              <span class="avatar-title rounded fs-3">
                <i class="ri-money-dollar-circle-line text-primary-custom"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6">
      <div class="card card-animate card-venta-contado">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1 overflow-hidden">
              <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total al Contado</p>
            </div>
          </div>
          <div class="d-flex align-items-end justify-content-between mt-4">
            <div>
              <h4 class="fs-22 fw-semibold ff-secondary mb-4 text-primary-custom">
                Bs. <?= number_format($totalContado, 2) ?>
              </h4>
              <span class="text-muted">
                <?= date('d/m/Y', strtotime($fecha_desde)) ?> - <?= date('d/m/Y', strtotime($fecha_hasta)) ?>
              </span>
            </div>
            <div class="avatar-sm flex-shrink-0">
              <span class="avatar-title rounded fs-3">
                <i class="ri-hand-coin-line text-primary-custom"></i>
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-3 col-md-6">
      <div class="card card-animate card-venta-credito">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1 overflow-hidden">
              <p class="text-uppercase fw-medium text-muted text-truncate mb-0">Total a Crédito</p>
            </div>
          </div>
          <div class="d-flex align-items-end justify-content-between mt-4">
            <div>
              <h4 class="fs-22 fw-semibold ff-secondary mb-4 text-primary-custom">
                Bs. <?= number_format($totalCredito, 2) ?>
              </h4>
              <span class="text-muted">
                <?= date('d/m/Y', strtotime($fecha_desde)) ?> - <?= date('d/m/Y', strtotime($fecha_hasta)) ?>
              </span>
            </div>
            <div class="avatar-sm flex-shrink-0">
              <span class="avatar-title rounded fs-3">
                <i class="ri-wallet-3-line text-primary-custom"></i>
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
          <div class="col-md-2">
            <label for="fecha_desde" class="form-label">Desde</label>
            <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="<?= esc($fecha_desde) ?>" required>
          </div>
          <div class="col-md-2">
            <label for="fecha_hasta" class="form-label">Hasta</label>
            <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="<?= esc($fecha_hasta) ?>" required>
          </div>
          <div class="col-md-2">
            <label for="per_page" class="form-label">Mostrar</label>
            <select name="per_page" id="per_page" class="form-select">
              <option value="10"  <?= $per_page == 10  ? 'selected' : '' ?>>10</option>
              <option value="20"  <?= $per_page == 20  ? 'selected' : '' ?>>20</option>
              <option value="30"  <?= $per_page == 30  ? 'selected' : '' ?>>30</option>
              <option value="50"  <?= $per_page == 50  ? 'selected' : '' ?>>50</option>
              <option value="100" <?= $per_page == 100 ? 'selected' : '' ?>>100</option>
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-success w-100">
              <i class="ri-filter-line me-1"></i> Filtrar
            </button>
          </div>
          <div class="col-md-2">
            <button type="button" class="btn btn-outline-secondary w-100" onclick="setTodayAndSubmit()">
              <i class="ri-calendar-line me-1"></i> Hoy
            </button>
          </div>
          <div class="col-md-2">
            <div class="btn-group w-100">
              <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
                <i class="ri-file-excel-2-line"></i> Excel
              </button>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?= base_url('productosagro/exportarExcelVentas?fecha_inicio=' . urlencode($fecha_desde) . '&fecha_fin=' . urlencode($fecha_hasta) . '&tipo=contado') ?>">
                  <i class="ri-hand-coin-line text-success me-2"></i>Contado
                </a></li>
                <li><a class="dropdown-item" href="<?= base_url('productosagro/exportarExcelVentas?fecha_inicio=' . urlencode($fecha_desde) . '&fecha_fin=' . urlencode($fecha_hasta) . '&tipo=credito') ?>">
                  <i class="ri-wallet-3-line text-warning me-2"></i>Crédito
                </a></li>
                <li><a class="dropdown-item" href="<?= base_url('productosagro/exportarExcelVentas?fecha_inicio=' . urlencode($fecha_desde) . '&fecha_fin=' . urlencode($fecha_hasta) . '&tipo=general') ?>">
                  <i class="ri-file-list-line text-info me-2"></i>General
                </a></li>
              </ul>
            </div>
          </div>
          <div class="col-md-2">
            <div class="btn-group w-100">
              <button type="button" class="btn btn-danger dropdown-toggle" data-bs-toggle="dropdown">
                <i class="ri-file-pdf-line"></i> PDF
              </button>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?= base_url('productosagro/exportarPdfVentas?fecha_inicio=' . urlencode($fecha_desde) . '&fecha_fin=' . urlencode($fecha_hasta) . '&tipo=contado') ?>" target="_blank">
                  <i class="ri-hand-coin-line text-success me-2"></i>Contado
                </a></li>
                <li><a class="dropdown-item" href="<?= base_url('productosagro/exportarPdfVentas?fecha_inicio=' . urlencode($fecha_desde) . '&fecha_fin=' . urlencode($fecha_hasta) . '&tipo=credito') ?>" target="_blank">
                  <i class="ri-wallet-3-line text-warning me-2"></i>Crédito
                </a></li>
                <li><a class="dropdown-item" href="<?= base_url('productosagro/exportarPdfVentas?fecha_inicio=' . urlencode($fecha_desde) . '&fecha_fin=' . urlencode($fecha_hasta) . '&tipo=general') ?>" target="_blank">
                  <i class="ri-file-list-line text-info me-2"></i>General
                </a></li>
              </ul>
            </div>
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
            <h5 class="card-title mb-0 flex-grow-1">Registros de Ventas</h5>
            <?php if (!empty($totalRegistros)): ?>
              <div class="text-muted small">Total Registros: <?= esc($totalRegistros) ?></div>
            <?php endif; ?>
          </div>
        </div>
        <div class="card-body pt-0">
          <?php if (empty($ventas)): ?>
            <div class="text-center py-5">
              <i class="ri-archive-line display-4 text-muted mb-2"></i>
              <p class="text-muted">No hay registros de ventas en el rango seleccionado.</p>
            </div>
          <?php else: ?>
            <div class="table-responsive table-card mb-1">
              <table class="table align-middle table-nowrap">
                <thead class="table-light text-muted">
                  <tr>
                    <th>Nro</th>
                    <th>Cliente / Personal UTO</th>
                    <th>Monto Total</th>
                    <th>Tipo Pago</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($ventas as $venta):
                    $tipoPago  = strtolower($venta->tipo_pago ?? '');
                    $claseRow  = ($tipoPago === 'contado') ? 'venta-contado' : 'venta-credito';
                    $claseBadge = ($tipoPago === 'contado') ? 'badge-tipo-contado' : 'badge-tipo-credito';
                  ?>
                    <tr class="<?= $claseRow ?>">
                      <td><strong><?= esc($venta->id ?? 'N/A') ?></strong></td>
                      <td>
                        <?php if (!empty($venta->personal_uto_id)): ?>
                          <strong><?= esc($venta->nombre_personal ?? 'Personal UTO') ?></strong><br>
                          <small>CI: <?= esc($venta->dip ?? 'N/A') ?> | <?= esc($venta->cargo ?? '') ?> - <?= esc($venta->seccion ?? '') ?></small>
                        <?php else: ?>
                          <?= esc($venta->cliente_nombre ?? 'Consumidor Final') ?>
                        <?php endif; ?>
                      </td>
                      <td><strong class="text-primary-custom" style="color: var(--color-contado-dark);">Bs. <?= esc(number_format($venta->monto_total, 2)) ?></strong></td>
                      <td><span class="<?= $claseBadge ?>"><?= esc(ucfirst($venta->tipo_pago ?? 'N/A')) ?></span></td>
                      <td><?= esc(date('d/m/Y H:i', strtotime($venta->created_at))) ?></td>
                      <td>
                        <?php if ($venta->estado == 1): ?>
                          <span class="badge-finalizada">Finalizada</span>
                        <?php else: ?>
                          <span class="badge-cancelada">Cancelada</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <a href="<?= base_url('productosagro/recibo/' . $venta->id) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Imprimir Recibo">
                          <i class="ri-printer-line"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <?php if (!empty($pagerLinks)): ?>
              <div class="mt-3"><?= $pagerLinks ?></div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
  function setTodayAndSubmit() {
    const hoy = new Date().toISOString().split('T')[0];
    document.getElementById('fecha_desde').value = hoy;
    document.getElementById('fecha_hasta').value = hoy;
    document.getElementById('ventas-filter-form').submit();
  }

  document.addEventListener('DOMContentLoaded', function() {
    const perPageSelect = document.getElementById('per_page');
    if (perPageSelect) {
      perPageSelect.addEventListener('change', function() {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', this.value);
        url.searchParams.delete('page');
        window.location.href = url.toString();
      });
    }
  });
</script>
<?php $this->endSection() ?>
