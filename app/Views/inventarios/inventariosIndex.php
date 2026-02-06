<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  /* ... (Estilos CSS permanecen igual) ... */
  .bg-soft-activo {
    background-color: #d4edda !important;
    color: #155724 !important;
  }

  .bg-soft-inactivo {
    background-color: #f1f3f5 !important;
    color: #6c757d !important;
  }

  .bg-soft-stock {
    background-color: #d4edda !important;
    color: #155724 !important;
  }

  .bg-soft-no-stock {
    background-color: #f8d7da !important;
    color: #721c24 !important;
  }

  .alert-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
  }

  .alert-danger {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
  }

  .btn-primary,
  .btn-success {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
  }

  .btn-primary:hover,
  .btn-success:hover {
    background-color: #218838 !important;
    border-color: #1e7e34 !important;
  }

  .btn-soft-secondary {
    background-color: #f8f9fa !important;
    color: #6c757d !important;
    border: 1px solid #e9ecef;
  }

  .btn-soft-danger {
    background-color: #f8d7da !important;
    color: #721c24 !important;
    border: 1px solid #f1aeb5;
  }

  .btn-soft-danger:hover {
    background-color: #f1aeb5 !important;
    color: #721c24 !important;
  }

  .badge-stock,
  .badge-estado {
    font-weight: 600;
    padding: 0.35em 0.6em;
    border-radius: 6px;
  }

  .per-page-selector {
    width: auto;
    display: inline-block;
  }

  @media (max-width: 767.98px) {
    .table-responsive {
      border: 1px solid #e9ecef;
    }

    .table-nowrap {
      white-space: nowrap;
    }
  }

  #inventarioList .flex-shrink-0 a {
    margin-bottom: 8px !important;
  }

  @media (max-width: 575.98px) {

    #inventarioList .flex-shrink-0,
    .card-header .flex-shrink-0 {
      display: flex;
      flex-direction: column;
      width: 100%;
      margin-top: 10px;
    }

    #inventarioList .flex-shrink-0 a,
    .card-header .flex-shrink-0 a {
      width: 100%;
      margin-bottom: 8px !important;
      margin-right: 0 !important;
    }
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-sm-flex align-items-center justify-content-between">
        <h4 class="mb-sm-0"><?= esc($title) ?></h4>
        <div class="page-title-right">
          <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="javascript: void(0);">Inventario</a></li>
            <li class="breadcrumb-item active">Listado</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= session()->getFlashdata('success') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?= session()->getFlashdata('error') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Nav tabs -->
  <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="inventarios-tab" data-bs-toggle="tab" data-bs-target="#inventarios" type="button" role="tab" aria-controls="inventarios" aria-selected="true">Registro Inventarios</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="resumen-tab" data-bs-toggle="tab" data-bs-target="#resumen" type="button" role="tab" aria-controls="resumen" aria-selected="false">Datos Estadisticos</button>
    </li>
  </ul>

  <!-- Tab panes -->
  <div class="tab-content" id="myTabContent">
    <div class="tab-pane fade show active" id="inventarios" role="tabpanel" aria-labelledby="inventarios-tab">
      <div class="row">
        <div class="col-lg-12">
          <div class="card" id="inventarioList">
            <div class="card-header border-0">
              <div class="d-flex align-items-center flex-wrap">
                <h5 class="card-title mb-0 flex-grow-1">Filtros de Búsqueda</h5>
                <div class="flex-shrink-0 d-flex flex-wrap justify-content-end">
                  <a href="<?= base_url('inventarios') ?>" class="btn btn-soft-secondary me-2">
                    <i class="ri-refresh-line align-bottom me-1"></i> Recargar Hoy
                  </a>
                  <a href="<?= base_url('inventarios?mostrar_todos=1') ?>" class="btn btn-soft-secondary">
                    <i class="ri-list-check align-bottom me-1"></i> Mostrar Todos
                  </a>
                </div>
              </div>
            </div>
            <div class="card-body border-dashed border-end-0 border-start-0">
              <form action="<?= base_url('inventarios/filtered') ?>" method="get" id="filterForm">
                <div class="row g-3">
                  <div class="col-xxl-4 col-sm-6">
                    <label for="nombre" class="form-label">Nombre del Inventario</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Buscar por nombre..." value="<?= esc($filters['nombre'] ?? '') ?>">
                  </div>
                  <div class="col-xxl-3 col-sm-6">
                    <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= esc($filters['fecha_inicio'] ?? '') ?>">
                  </div>
                  <div class="col-xxl-3 col-sm-6">
                    <label for="fecha_fin" class="form-label">Fecha de Fin</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?= esc($filters['fecha_fin'] ?? '') ?>">
                  </div>
                  <div class="col-xxl-2 col-sm-6 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">
                      <i class="ri-search-line me-1 align-bottom"></i> Buscar
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-6">
          <label for="perPageSelect" class="form-label">Mostrar por página:</label>
          <select id="perPageSelect" class="form-select per-page-selector">
            <option value="10" <?= (isset($per_page) && $per_page == 10) ? 'selected' : '' ?>>10</option>
            <option value="20" <?= (isset($per_page) && $per_page == 20) ? 'selected' : '' ?>>20</option>
            <option value="40" <?= (isset($per_page) && $per_page == 40) ? 'selected' : '' ?>>40</option>
            <option value="50" <?= (isset($per_page) && $per_page == 50) ? 'selected' : '' ?>>50</option>
            <option value="100" <?= (isset($per_page) && $per_page == 100) ? 'selected' : '' ?>>100</option>
            <option value="all" <?= (isset($per_page) && $per_page == 'all') ? 'selected' : '' ?>>Todos</option>
          </select>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-header border-0">
              <div class="d-flex align-items-center flex-wrap">
                <h5 class="card-title mb-0 flex-grow-1">Lista de Inventarios</h5>
                <div class="flex-shrink-0 d-flex flex-wrap justify-content-end">
                  <a href="<?= base_url('inventarios/register') ?>" class="btn btn-success add-btn me-1">
                    <i class="ri-add-line align-bottom me-1"></i> Nuevo Inventario
                  </a>
                  <a href="<?= base_url('inventarios/exportarPdf?' . http_build_query(array_merge($filters, ['per_page' => $per_page ?? 20]))) ?>" class="btn btn-soft-danger">
                    <i class="ri-file-pdf-line align-bottom me-1"></i> Exportar a PDF
                  </a>
                </div>
              </div>
            </div>
            <div class="card-body pt-0">
              <?php 
                $hasCustomFilters = !empty($filters['nombre']) || (!empty($filters['fecha_inicio']) && !empty($filters['fecha_fin']));
              ?>
              <?php if (!$mostrar_todos && !$hasCustomFilters): ?>
                <div class="alert alert-info d-flex align-items-center mb-3" style="background-color: #e3f2fd; border-color: #2196f3; color: #1976d2;">
                  <i class="ri-information-line fs-5 me-2"></i>
                  <div>Mostrando inventarios del día de hoy. Use "Mostrar Todos" para ver todos los registros.</div>
                </div>
              <?php endif; ?>
              <?php if (empty($inventarios)): ?>
                <div class="alert alert-info text-center mt-3" style="background-color: #f8fdfa; border-color: #e0f0e9; color: #28a745;">
                  No se encontraron inventarios con los filtros aplicados.
                </div>
              <?php else: ?>
                <div class="table-responsive table-card mb-1">
                  <table class="table align-middle table-nowrap" id="tablaInventarios">
                    <thead class="table-light text-muted">
                      <tr>
                        <th>Código</th>
                        <th>Materia Prima</th>
                        <th>Cantidad (Litros)</th>
                        <th>Turno</th>
                        <th>Creado</th>
                        <th class="text-end">Acciones</th>
                      </tr>
                    </thead>
                    <tbody class="list form-check-all">
                      <?php foreach ($inventarios as $inventario): ?>
                        <tr>
                          <td><?= esc($inventario->code) ?></td>
                          <td class="inventario-nombre"><?= esc($inventario->nombre) ?></td>
                          <td>
                            <span class="badge <?= $inventario->stock > 0 ? 'bg-soft-stock' : 'bg-soft-no-stock' ?> badge-stock">
                              <?= esc($inventario->stock) ?>
                            </span>
                          </td>
                          <td><?= esc($inventario->turno) ?></td>
                          <td><?= date('d/m/Y H:i', strtotime($inventario->created_at)) ?></td>
                          <td class="text-end">
                            <div class="btn-group">
                              <a href="<?= base_url('inventarios/show/' . $inventario->id) ?>" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Ver">
                                <i class="ri-eye-fill align-bottom"> Ver</i>
                              </a>





                              <?php if (!isset($inventario->user_cali) || is_null($inventario->user_cali)): ?>
                                <button type="button"
                                  class="btn btn-sm btn-outline-primary btn-control-calidad"
                                  data-bs-toggle="modal"
                                  data-bs-target="#modalCalidad"
                                  data-id="<?= $inventario->id ?>"
                                  data-code="<?= esc($inventario->code) ?>"
                                  title="Control de Calidad">
                                  <i class="ri-shield-check-line align-bottom me-1"></i> Control Calidad
                                </button>
                              <?php endif; ?>



                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>

                <?php if (isset($pager) && $per_page !== 'all'): ?>
                  <div class="d-flex justify-content-end">
                    <?= $pager->links() ?>
                  </div>
                <?php endif; ?>

              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="tab-pane fade" id="resumen" role="tabpanel" aria-labelledby="resumen-tab">
      <div class="row">
        <div class="col-12">
           <div class="card">
               <div class="card-header border-0">
                   <div class="d-flex align-items-center">
                       <h5 class="card-title mb-0 flex-grow-1">Datos Estadísticos</h5>
                   </div>
               </div>
               <div class="card-body">
                   <!-- Filtro de Fechas para Estadísticas -->
                   <form method="get" class="mb-4">
                       <div class="row g-3 align-items-end">
                           <div class="col-md-4">
                               <label for="stats_fecha_inicio" class="form-label">Fecha Inicio</label>
                               <input type="date" class="form-control" id="stats_fecha_inicio" name="fecha_inicio" value="<?= esc($filters['fecha_inicio'] ?? '') ?>">
                           </div>
                           <div class="col-md-4">
                               <label for="stats_fecha_fin" class="form-label">Fecha Fin</label>
                               <input type="date" class="form-control" id="stats_fecha_fin" name="fecha_fin" value="<?= esc($filters['fecha_fin'] ?? '') ?>">
                           </div>
                           <div class="col-md-4">
                               <div class="d-grid gap-2 d-md-flex">
                                   <button type="submit" class="btn btn-primary">
                                       <i class="ri-filter-3-line align-bottom me-1"></i> Filtrar
                                   </button>
                                   <a href="<?= base_url('inventarios') ?>" class="btn btn-soft-secondary">
                                       <i class="ri-refresh-line align-bottom me-1"></i> Limpiar
                                   </a>
                               </div>
                           </div>
                       </div>
                       <!-- Mantener tab activo -->
                       <input type="hidden" name="tab" value="resumen">
                   </form>

                   <div class="row">
                       <!-- Resumen de Inventario -->
                       <div class="col-xl-6 col-md-6">
                           <div class="card card-animate bg-primary">
                               <div class="card-body">
                                   <div class="d-flex align-items-center">
                                       <div class="flex-grow-1 overflow-hidden">
                                           <p class="text-uppercase fw-medium text-white-50 text-truncate mb-0">Total Registros Inventario</p>
                                       </div>
                                       <div class="flex-shrink-0">
                                           <h5 class="text-white fs-14 mb-0">
                                               <i class="ri-file-list-3-line fs-24 text-white-50"></i>
                                           </h5>
                                       </div>
                                   </div>
                                   <div class="d-flex align-items-end justify-content-between mt-4">
                                       <div>
                                           <h4 class="fs-22 fw-semibold ff-secondary mb-4 text-white">
                                               <?= esc($resumenInventario->total_registros ?? 0) ?>
                                           </h4>
                                           <p class="text-white-50 mb-0">Total Stock Producido: <span class="text-white fw-bold"><?= esc($resumenInventario->total_stock_producido ?? 0) ?></span></p>
                                       </div>
                                   </div>
                               </div>
                           </div>
                       </div>

                       <!-- Resumen de Productos -->
                       <div class="col-xl-6 col-md-6">
                           <div class="card card-animate bg-success">
                               <div class="card-body">
                                   <div class="d-flex align-items-center">
                                       <div class="flex-grow-1 overflow-hidden">
                                           <p class="text-uppercase fw-medium text-white-50 text-truncate mb-0">Total Productos</p>
                                       </div>
                                       <div class="flex-shrink-0">
                                           <h5 class="text-white fs-14 mb-0">
                                               <i class="ri-shopping-bag-3-line fs-24 text-white-50"></i>
                                           </h5>
                                       </div>
                                   </div>
                                   <div class="mt-4">
                                       <h4 class="fs-22 fw-semibold ff-secondary mb-3 text-white">
                                           <?= esc($resumenPorNombre['totales_generales']['total_tipos_productos'] ?? 0)  ?>
                                       </h4>
                                       <div class="mt-3 pt-3 border-top border-top-dashed border-white-50">
                                           
                                          
                                           <div class="d-flex justify-content-between align-items-center mb-2">
                                               <span class="text-white-50">Total Producido</span>
                                               <span class="text-white fw-bold"><?= esc($resumenPorNombre['totales_generales']['gran_total_producido'] ?? 0) ?></span>
                                           </div>
                                           <div class="d-flex justify-content-between align-items-center">
                                               <span class="text-white-50">Total Inventario</span>
                                               <span class="text-white fw-bold"><?= esc($resumenPorNombre['totales_generales']['gran_total_stock'] ?? 0) ?></span>
                                           </div>
                                       </div>
                                   </div>
                               </div>
                           </div>
                       </div>
                   </div>

                   <!-- Tabla de Resumen por Nombre -->
                   <div class="row mt-4">
                       <div class="col-12">
                           <div class="card">
                               <div class="card-header">
                                   <h5 class="card-title mb-0">Detalle por Producto</h5>
                                   <button type="button" class="btn btn-primary" onclick="
                                       const fInicio = document.getElementById('stats_fecha_inicio').value;
                                       const fFin = document.getElementById('stats_fecha_fin').value;
                                       window.location.href = '<?= base_url('inventarios/reporteInventario') ?>?fecha_inicio=' + fInicio + '&fecha_fin=' + fFin;
                                   ">
                                       Generar Reporte
                                   </button>
                               </div>

                               <div class="card-body">
                                   <div class="table-responsive">
                                       <table class="table table-striped table-hover align-middle">
                                           <thead class="table-light">
                                               <tr>
                                                   <th>Nombre Producto</th>
                                                    <th>Total lote</th>
                                                   <th class="text-end">Total Producido</th>
                                                   <th class="text-end">Total Inventario</th>
                                               </tr>
                                           </thead>
                                           <tbody>
                                           <tbody>
                                               <?php if (!empty($resumenPorNombre['lista_productos'])): ?>
                                                   <?php foreach ($resumenPorNombre['lista_productos'] as $item): ?>
                                                       <tr>
                                                           <td><?= esc($item->nombre) ?></td>
                                                           <td class="text-center"><?= esc($item->cantidad) ?></td>
                                                           <td class="text-end"><?= esc($item->total_producido) ?></td>
                                                           <td class="text-end">
                                                               <span class="badge <?= ($item->total_stock_actual > 0) ? 'bg-success' : 'bg-danger' ?>">
                                                                   <?= esc($item->total_stock_actual ?? 0) ?>
                                                               </span>
                                                           </td>
                                                       </tr>
                                                   <?php endforeach; ?>
                                               <?php else: ?>
                                                   <tr>
                                                       <td colspan="4" class="text-center text-muted">No hay datos disponibles</td>
                                                   </tr>
                                               <?php endif; ?>
                                           </tbody>
                                       </table>
                                   </div>
                                   <div class="card-footer">
                                       <div class="d-flex align-items-center justify-content-between">
                                           <div>
                                               
                                               <p class="mb-0">Total Producido: <span class="fw-bold"><?= esc($resumenPorNombre['totales_generales']['gran_total_producido'] ?? 0) ?></span></p>
                                               <p class="mb-0">Total Inventario: <span class="fw-bold"><?= esc($resumenPorNombre['totales_generales']['gran_total_stock'] ?? 0) ?></span></p>
                                           </div>
                                       </div>
                                   </div>
                               </div>
                           </div>
                       </div>
                   </div>
               </div>
           </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalCalidad" tabindex="-1" aria-labelledby="modalCalidadLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-soft-activo">
        <h5 class="modal-title" id="modalCalidadLabel">Registrar Control de Calidad - <span id="modalCodigoInventario"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formCalidad" method="POST">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="grasa" class="form-label">Grasa (%)</label>
              <input type="number" step="0.01" class="form-control" id="grasa" name="grasa" required placeholder="0.00">
            </div>

            <div class="col-md-6">
              <label for="sng" class="form-label">SNG (Sólidos No Grasos)</label>
              <input type="number" step="0.01" class="form-control" id="sng" name="sng" required placeholder="0.00">
            </div>

            <div class="col-md-6">
              <label for="densidad" class="form-label">Densidad</label>
              <input type="number" step="0.001" class="form-control" id="densidad" name="densidad" required placeholder="1.030">
            </div>

            <div class="col-md-6">
              <label for="lactosa" class="form-label">Lactosa (%)</label>
              <input type="number" step="0.01" class="form-control" id="lactosa" name="lactosa" required placeholder="0.00">
            </div>

            <div class="col-md-6">
              <label for="solidos" class="form-label">Sólidos Totales (%)</label>
              <input type="number" step="0.01" class="form-control" id="solidos" name="solidos" required placeholder="0.00">
            </div>

            <div class="col-md-6">
              <label for="proteina" class="form-label">Proteína (%)</label>
              <input type="number" step="0.01" class="form-control" id="proteina" name="proteina" required placeholder="0.00">
            </div>

            <div class="col-md-6">
              <label for="agua" class="form-label">Agua Agregada (%)</label>
              <input type="number" step="0.01" class="form-control" id="agua" name="agua" required placeholder="0.00">
            </div>

            <div class="col-md-6">
              <label for="temperatura" class="form-label">Temperatura (°C)</label>
              <input type="number" step="0.1" class="form-control" id="temperatura" name="temperatura" required placeholder="0.0">
            </div>

            <div class="col-md-6">
              <label for="congelacion" class="form-label">Punto de Congelación</label>
              <input type="number" step="0.001" class="form-control" id="congelacion" name="congelacion" required placeholder="-0.500">
            </div>

            <div class="col-md-6">
              <label for="ph" class="form-label">pH</label>
              <input type="number" step="0.01" class="form-control" id="ph" name="ph" required placeholder="6.60">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-success">Guardar Control</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Lógica existente del selector de páginas
    const perPageSelect = document.getElementById('perPageSelect');
    if (perPageSelect) {
      perPageSelect.addEventListener('change', function() {
        const url = new URL(window.location.href);
        const selectedValue = this.value;
        if (selectedValue === 'all') {
          url.searchParams.set('per_page', 'all');
        } else {
          url.searchParams.set('per_page', selectedValue);
        }
        url.searchParams.delete('page');
        window.location.href = url.toString();
      });
    }

    // LÓGICA CORREGIDA PARA EL MODAL DE CALIDAD
    const modalCalidad = document.getElementById('modalCalidad');
    const formCalidad = document.getElementById('formCalidad');
    const baseUrl = '<?= base_url('inventarios/guardar-calidad') ?>'; // Base de la URL de la ruta

    if (modalCalidad) {
      modalCalidad.addEventListener('show.bs.modal', function(event) {
        // Botón que activó el modal
        const button = event.relatedTarget;

        // Extraer info de los atributos data-*
        const inventarioId = button.getAttribute('data-id');
        const inventarioCode = button.getAttribute('data-code');

        // 1. ACTUALIZAR EL TÍTULO DEL MODAL
        const modalTitleSpan = modalCalidad.querySelector('#modalCodigoInventario');
        modalTitleSpan.textContent = inventarioCode;

        // 2. ACTUALIZAR EL ACTION DEL FORMULARIO
        // Configura el action para que sea: /inventarios/guardar-calidad/{ID}
        formCalidad.action = `${baseUrl}/${inventarioId}`;
      });

      // Opcional: Limpiar el formulario cada vez que se oculte el modal
      modalCalidad.addEventListener('hidden.bs.modal', function() {
        formCalidad.reset();
      });
    }
    // Activar tab basado en URL
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab');
    if (activeTab === 'resumen') {
        const tabTrigger = new bootstrap.Tab(document.querySelector('#resumen-tab'));
        tabTrigger.show();
    }
  });
</script>
<?= $this->endSection() ?>