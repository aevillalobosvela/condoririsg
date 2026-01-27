<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  :root {
    --primary: #28a745;
    --primary-dark: #218838;
    --primary-light: #e8f5ec;
    --bg-light: #f9fdfb;
    --border: #e0f0e9;
    --text: #343a40;
    --text-muted: #6c757d;
    --shadow-sm: 0 2px 6px rgba(0,0,0,0.05);
    --shadow: 0 4px 16px rgba(0,0,0,0.08);
    --shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
    --radius: 14px;
  }

  /* Alertas mejoradas */
  .alert {
    border: none;
    border-left: 4px solid transparent;
    padding: 1rem 1.25rem;
    border-radius: var(--radius);
    margin-bottom: 1.5rem;
    font-weight: 500;
  }
  .alert-success { background-color: #d4edda; color: #155724; border-left-color: var(--primary); }
  .alert-danger  { background-color: #f8d7da; color: #721c24; border-left-color: #e74c3c; }
  .alert-info    { background-color: #d1ecf1; color: #0c5460; border-left-color: #17a2b8; }

  /* Header */
  .page-header {
    margin-bottom: 2.25rem;
  }
  .page-title {
    font-size: 1.875rem;
    font-weight: 800;
    color: var(--text);
    letter-spacing: -0.5px;
  }
  .breadcrumb-item a { color: var(--primary); text-decoration: none; }
  .breadcrumb-item.active { color: var(--text-muted); }

  /* Botón principal */
  .btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    border: none;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    border-radius: 50px;
    box-shadow: 0 4px 10px rgba(40, 167, 69, 0.3);
    transition: all 0.3s ease;
  }
  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(40, 167, 69, 0.45);
    background: linear-gradient(135deg, var(--primary-dark), #1e7e34);
  }
  .btn-primary i { margin-right: 8px; font-size: 1.1rem; }

  /* Tabs Custom (Aún se mantiene aunque solo queda un tab visible) */
  .nav-tabs {
    border-bottom: 2px solid var(--border);
    margin-bottom: 1.5rem;
  }
  .nav-tabs .nav-link {
    color: var(--text-muted);
    font-weight: 600;
    border: none;
    border-bottom: 2px solid transparent;
    padding: 0.75rem 1.25rem;
    margin-bottom: -2px; 
    transition: all 0.3s;
  }
  .nav-tabs .nav-link:hover {
    border-color: var(--primary-light);
  }
  .nav-tabs .nav-link.active {
    color: var(--primary-dark);
    border-bottom-color: var(--primary);
    background-color: transparent;
  }
  .card-animate {
    border-radius: var(--radius);
    box-shadow: var(--shadow-sm);
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
  <div class="row mb-3">
    <div class="col-12">
      <div class="page-header d-sm-flex align-items-center justify-content-between">
        <div>
          <h1 class="page-title"><?= esc($title) ?></h1>
        </div>
          <div>
            <a href="<?= base_url('/') ?>" class="btn btn-primary">
              <i class="ri-arrow-left-line"></i> Volver
            </a>
          </div>
      </div>
    </div>
  </div>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="row">
      <div class="col-12">
        <div class="alert alert-success d-flex align-items-center">
          <i class="ri-checkbox-circle-fill fs-4 me-2"></i>
          <div><?= session()->getFlashdata('success') ?></div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="row">
      <div class="col-12">
        <div class="alert alert-danger d-flex align-items-center">
          <i class="ri-error-warning-fill fs-4 me-2"></i>
          <div><?= session()->getFlashdata('error') ?></div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Solo mantenemos el tab activo con el nuevo enfoque -->
  <ul class="nav nav-tabs" id="stockTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <a class="nav-link active" id="grouped-stock-tab" 
         href="#grouped-stock" data-bs-toggle="tab"
         role="tab" aria-selected="true">
        Resumen Global Agrupado
      </a>
    </li>
  </ul>

  <div class="tab-content" id="stockTabsContent">
    
    <!-- Contenido del tab: Resumen Global Agrupado (anteriormente 'estadisticas') -->
    <div class="tab-pane fade show active" id="grouped-stock" role="tabpanel" aria-labelledby="grouped-stock-tab">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header border-0">
              <div class="d-flex align-items-center">
                <h5 class="card-title mb-0 flex-grow-1">Filtro de Período y Estadísticas Clave</h5>
              </div>
            </div>
            <div class="card-body">
              <!-- Formulario de filtro por fecha -->
              <form method="get" class="mb-4">
                <div class="row g-3 align-items-end">
                  <div class="col-md-4">
                    <label for="stats_fecha_inicio" class="form-label">Fecha Inicio</label>
                    <input type="date" class="form-control" id="stats_fecha_inicio" 
                           name="fecha_inicio" value="<?= esc($filters['fecha_inicio'] ?? '') ?>">
                  </div>
                  <div class="col-md-4">
                    <label for="stats_fecha_fin" class="form-label">Fecha Fin</label>
                    <input type="date" class="form-control" id="stats_fecha_fin" 
                           name="fecha_fin" value="<?= esc($filters['fecha_fin'] ?? '') ?>">
                  </div>
                  <div class="col-md-4">
                    <div class="d-grid gap-2 d-md-flex">
                      <button type="submit" class="btn btn-primary">
                        <i class="ri-filter-3-line align-bottom me-1"></i> Filtrar
                      </button>
                      <a href="<?= base_url('inventariosucursales') ?>" class="btn btn-soft-secondary">
                        <i class="ri-refresh-line align-bottom me-1"></i> Limpiar
                      </a>
                    </div>
                  </div>
                </div>
              </form>
              <!-- Fin Formulario de filtro por fecha -->

              <div class="row">
                <!-- Tarjeta 1: Total de Registros (Lotes) -->
                <div class="col-xl-6 col-md-6">
                  <div class="card card-animate bg-primary">
                    <div class="card-body">
                      <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                          <p class="text-uppercase fw-medium text-white-50 text-truncate mb-0">
                            Total de Registros de Lotes
                          </p>
                        </div>
                        <div class="flex-shrink-0">
                          <h5 class="text-white fs-14 mb-0">
                            <i class="ri-box-3-line fs-24 text-white-50"></i>
                          </h5>
                        </div>
                      </div>
                      <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                          <h4 class="fs-22 fw-semibold ff-secondary mb-0 text-white">
                            <?= esc($resumenGeneral->total_productos ?? 0) ?>
                          </h4>
                          <p class="text-white-50 mb-0 mt-2">
                            Lotes de productos registrados
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Tarjeta 2: Stock Físico Total -->
                <div class="col-xl-6 col-md-6">
                  <div class="card card-animate bg-success">
                    <div class="card-body">
                      <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                          <p class="text-uppercase fw-medium text-white-50 text-truncate mb-0">
                            Suma Total de Stock Actual
                          </p>
                        </div>
                        <div class="flex-shrink-0">
                          <h5 class="text-white fs-14 mb-0">
                            <i class="ri-shopping-basket-line fs-24 text-white-50"></i>
                          </h5>
                        </div>
                      </div>
                      <div class="mt-4">
                        <h4 class="fs-22 fw-semibold ff-secondary mb-0 text-white">
                          <?= esc($resumenGeneral->total_stock_actual ?? 0) ?> Unidades
                        </h4>
                        <p class="text-white-50 mb-0 mt-2">
                            Stock físico de todos los productos
                          </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Tabla de Stock Agrupado por Nombre -->
              <div class="row mt-4">
                <div class="col-12">
                  <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h5 class="card-title mb-0">Detalle Agrupado por Producto</h5>
                      <!-- Botón de Reporte PDF -->
                      <button type="button" class="btn btn-primary" onclick="
                        const fInicio = document.getElementById('stats_fecha_inicio').value;
                        const fFin = document.getElementById('stats_fecha_fin').value;
                        window.location.href = '<?= base_url('stockinventario/reporteStock') ?>?fecha_inicio=' + fInicio + '&fecha_fin=' + fFin;
                      ">
                        <i class="ri-file-pdf-line me-1"></i> Generar Reporte PDF
                      </button>
                    </div>
                    <div class="card-body">
                      <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                          <thead class="table-light">
                            <tr>
                              <th>Producto</th>
                              <th class="text-end">Cantidad de Lotes</th>
                              <th class="text-end">Suma Total de Stock</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php if (!empty($stockAgrupado)): ?>
                              <?php foreach ($stockAgrupado as $item): ?>
                                <tr>
                                  <td><?= esc($item->nombre) ?></td>
                                  <td class="text-end">
                                    <?= esc($item->cantidad_registros ?? 0) ?>
                                  </td>
                                  <td class="text-end">
                                    <span class="badge <?= ($item->suma_stock_inve > 0) ? 'bg-success' : 'bg-danger' ?>">
                                      <?= esc($item->suma_stock_inve ?? 0) ?>
                                    </span>
                                  </td>
                                </tr>
                              <?php endforeach; ?>
                            <?php else: ?>
                              <tr>
                                <td colspan="3" class="text-center text-muted">No hay stock agrupado disponible para el período.</td>
                              </tr>
                            <?php endif; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Fin Tabla de Stock Agrupado por Nombre -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Código de animación (mantenido)
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'fadeInUp 0.6s ease forwards';
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.card-animate').forEach(card => {
        card.style.opacity = '0';
        // Solo observamos las tarjetas de resumen
        if (card.classList.contains('bg-primary') || card.classList.contains('bg-success')) {
             observer.observe(card);
        }
    });

    // Código para mantener el tab activo al cargar (aunque solo hay uno, es buena práctica)
    const activeTab = new URLSearchParams(window.location.search).get('tab') || 'grouped-stock';
    const tabElement = document.getElementById(activeTab + '-tab');
    if (tabElement) {
        new bootstrap.Tab(tabElement).show();
    }
});
</script>
<?= $this->endSection() ?>