<?php $this->extend('layouts/main') ?>

<?php $this->section('title') ?><?= esc($title) ?><?php $this->endSection() ?>

<?php $this->section('styles') ?>
<style>
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
            <h4 class="mb-sm-0 text-primary me-3">ORURO VENTAS</h4>
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
  // Obtener el rol de la sesión
  $rol_nombre = session()->get('rol_nombre');
  $isContabilidad = ($rol_nombre === 'contabilidad');

  date_default_timezone_set('America/La_Paz');
  $hoy = date('Y-m-d');
  $fecha_desde = $fecha_desde ?? $hoy;
  $fecha_hasta = $fecha_hasta ?? $hoy;
  $per_page = $per_page ?? 10;
  $totalVentas = $totalVentas ?? 0;

  $isSingleDay = ($fecha_desde === $fecha_hasta);
  $currentTime = time();
  $startSellTime = strtotime($hoy . ' 08:00:00');
  $endSellTime = strtotime($hoy . ' 22:00:00');

  $isToday = ($fecha_desde === $hoy);
  $isPastDate = ($fecha_desde < $hoy);
  $isSellingTime = ($currentTime >= $startSellTime && $currentTime <= $endSellTime);

  $canSell = $isToday && $isSellingTime;
  $showCloseBoxKPI = $isSingleDay && ($isPastDate || ($isToday && !$isSellingTime));
  ?>

  <div class="row">

    <?php if (!$isContabilidad): ?>
      <div class="col-xl-3 col-md-6">
        <?php if ($canSell): ?>
          <a href="<?= base_url('ventas/register') ?>" class="text-decoration-none">
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
          <a href="<?= base_url('ventas/cierre?fecha_inicio=' . esc($fecha_desde) . '&fecha_fin=' . esc($fecha_desde)) ?>" target="_blank" class="text-decoration-none">
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
                    <span class="text-muted">Día: <?= date('d/m/Y', strtotime($fecha_desde)) ?></span>
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
    <?php endif; ?>
    <?php if (!$isContabilidad): ?>
      <div class="col-xl-3 col-md-6">
        <a href="<?= base_url('ventas/credito') ?>" class="text-decoration-none">
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
    <?php endif; ?>
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
                Bs. <?= number_format($totalVentas, 2) ?>
              </h4>
              <span class="text-muted">
                <?= date('d/m/Y', strtotime($fecha_desde)) ?> - <?= date('d/m/Y', strtotime($fecha_hasta)) ?>
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
                Bs. <?= number_format($totalContado, 2) ?>
              </h4>
              <span class="text-muted">
                <?= date('d/m/Y', strtotime($fecha_desde)) ?> - <?= date('d/m/Y', strtotime($fecha_hasta)) ?>
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
                Bs. <?= number_format($totalCredito, 2) ?>
              </h4>
              <span class="text-muted">
                <?= date('d/m/Y', strtotime($fecha_desde)) ?> - <?= date('d/m/Y', strtotime($fecha_hasta)) ?>
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
            <select name="per_page" id="per_page" class="form-select" onchange="this.form.submit()">
              <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10</option>
              <option value="20" <?= $per_page == 20 ? 'selected' : '' ?>>20</option>
              <option value="30" <?= $per_page == 30 ? 'selected' : '' ?>>30</option>
              <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50</option>
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
          <!-- Grupo Excel -->
          <div class="col-md-2">
            <div class="btn-group w-100">
              <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
                <i class="ri-file-excel-2-line"></i> Excel
              </button>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?= base_url('ventas/exportarExcelVentas?fecha_inicio=' . urlencode($fecha_desde) . '&fecha_fin=' . urlencode($fecha_hasta) . '&tipo=contado') ?>">
                  <i class="ri-hand-coin-line text-primary me-2"></i>Contado
                </a></li>
                <li><a class="dropdown-item" href="<?= base_url('ventas/exportarExcelVentas?fecha_inicio=' . urlencode($fecha_desde) . '&fecha_fin=' . urlencode($fecha_hasta) . '&tipo=credito') ?>">
                  <i class="ri-wallet-3-line text-warning me-2"></i>Crédito
                </a></li>
                <li><a class="dropdown-item" href="<?= base_url('ventas/exportarExcelVentas?fecha_inicio=' . urlencode($fecha_desde) . '&fecha_fin=' . urlencode($fecha_hasta) . '&tipo=general') ?>">
                  <i class="ri-file-list-line text-info me-2"></i>General
                </a></li>
              </ul>
            </div>
          </div>
          <!-- Grupo PDF -->
          <div class="col-md-2">
            <div class="btn-group w-100">
              <button type="button" class="btn btn-danger dropdown-toggle" data-bs-toggle="dropdown">
                <i class="ri-file-pdf-line"></i> PDF
              </button>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?= base_url('ventas/cierre/rango?fecha_inicio=' . urlencode($fecha_desde) . '&fecha_fin=' . urlencode($fecha_hasta) . '&tipo=deposito_contado') ?>" target="_blank">
                  <i class="ri-bank-line text-primary me-2"></i>Contado
                </a></li>
                <li><a class="dropdown-item" href="<?= base_url('ventas/cierre/rango?fecha_inicio=' . urlencode($fecha_desde) . '&fecha_fin=' . urlencode($fecha_hasta) . '&tipo=contado') ?>" target="_blank">
                  <i class="ri-hand-coin-line text-primary me-2"></i>Contado Depósito
                </a></li>
                <li><a class="dropdown-item" href="<?= base_url('ventas/cierre/rango?fecha_inicio=' . urlencode($fecha_desde) . '&fecha_fin=' . urlencode($fecha_hasta) . '&tipo=credito') ?>" target="_blank">
                  <i class="ri-wallet-3-line text-warning me-2"></i>Crédito
                </a></li>
                <li><a class="dropdown-item" href="<?= base_url('ventas/cierre/rango?fecha_inicio=' . urlencode($fecha_desde) . '&fecha_fin=' . urlencode($fecha_hasta) . '&tipo=general') ?>" target="_blank">
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
              <div class="text-muted small">
                Total Registros: <?= esc($totalRegistros) ?>
              </div>
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
                  <?php foreach ($ventas as $venta): ?>
                    <tr>
                      <td><strong><?= esc($venta->id ?? 'N/A') ?></strong></td>
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

                        <a href="<?= base_url('ventas/recibo/' . $venta->id) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Imprimir Recibo">
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