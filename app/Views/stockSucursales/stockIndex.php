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

  /* Grid de tarjetas */
  .sucursales-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 1.75rem;
    margin-top: 1rem;
  }

  /* Tarjeta individual */
  .sucursal-card {
    background: white;
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: all 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    display: flex;
    flex-direction: column;
    height: 100%;
    border: 1px solid var(--border);
  }

  .sucursal-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-lg);
    border-color: var(--primary);
  }

  /* Header de tarjeta */
  .card-head {
    padding: 1.5rem 1.5rem 1rem;
    border-bottom: 1px solid var(--border);
    background: var(--bg-light);
  }

  .card-id {
    display: inline-block;
    background: var(--primary-light);
    color: var(--primary-dark);
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    margin-bottom: 0.75rem;
  }

  .card-title {
    font-size: 1.375rem;
    font-weight: 800;
    color: var(--text);
    margin: 0 0 0.5rem;
    line-height: 1.2;
    display: flex;
    align-items: center;
    gap: 0.625rem;
  }

  .card-title i {
    color: var(--primary);
    font-size: 1.5rem;
  }

  /* Cuerpo */
  .card-body {
    padding: 1.5rem;
    flex: 1;
  }

  .info-row {
    display: flex;
    margin-bottom: 1.125rem;
    align-items: flex-start;
  }

  .info-label {
    flex: 0 0 100px;
    font-size: 0.8125rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-top: 0.125rem;
  }

  .info-value {
    flex: 1;
    font-size: 1rem;
    color: var(--text);
    line-height: 1.5;
    word-break: break-word;
  }

  .info-value.empty {
    color: var(--text-muted);
    font-style: italic;
  }

  /* Footer */
  .card-footer {
    padding: 1rem 1.5rem;
    background-color: #fafafa;
    border-top: 1px solid var(--border);
    text-align: right;
  }

  .btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    font-weight: 600;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s;
    font-size: 0.95rem;
  }

  .btn-edit {
    background-color: #e2f0ff;
    color: #0d6efd;
    border: 1px solid #cce5ff;
  }
  .btn-edit:hover {
    background-color: #cce5ff;
    transform: scale(1.03);
  }

  /* Empty state */
  .empty-state {
    text-align: center;
    padding: 3.5rem 2rem;
    background: white;
    border-radius: var(--radius);
    border: 2px dashed var(--border);
    max-width: 800px;
    margin: 2rem auto;
  }

  .empty-icon {
    font-size: 4rem;
    color: var(--primary-light);
    margin-bottom: 1.25rem;
  }

  .empty-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 0.75rem;
  }

  .empty-text {
    color: var(--text-muted);
    font-size: 1.05rem;
    max-width: 600px;
    margin: 0 auto 1.75rem;
    line-height: 1.6;
  }

  /* Responsive */
  @media (max-width: 575.98px) {
    .sucursales-grid { grid-template-columns: 1fr; }
    .page-title { font-size: 1.5rem; }
    .btn-primary { width: 100%; }
    .card-title { font-size: 1.25rem; }
    .info-label { flex: 0 0 80px; font-size: 0.75rem; }
    .card-footer { text-align: center; }
  }

  @media (min-width: 576px) and (max-width: 991.98px) {
    .sucursales-grid { grid-template-columns: repeat(2, 1fr); }
  }

  @media (min-width: 992px) {
    .sucursales-grid { grid-template-columns: repeat(3, 1fr); }
  }

  /* Animaciones suaves */
  @keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .sucursal-card {
    animation: fadeInUp 0.6s ease forwards;
    opacity: 0;
  }

  .sucursal-card:nth-child(1) { animation-delay: 0.1s; }
  .sucursal-card:nth-child(2) { animation-delay: 0.15s; }
  .sucursal-card:nth-child(3) { animation-delay: 0.2s; }
  .sucursal-card:nth-child(4) { animation-delay: 0.25s; }
  .sucursal-card:nth-child(5) { animation-delay: 0.3s; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
  <!-- Header -->
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

  <!-- Flash Messages -->
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


      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header border-0">
              <div class="d-flex align-items-center">
                <h5 class="card-title mb-0 flex-grow-1">Datos Estadísticos de Stock</h5>
              </div>
            </div>
            <div class="card-body">
              <!-- Date Filter Form -->
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
                <!-- Keep tab active -->
                <input type="hidden" name="tab" value="estadisticas">
              </form>

              <!-- Summary Cards -->
              <div class="row">
                <!-- Total Products & Stock -->
                <div class="col-xl-6 col-md-6">
                  <div class="card card-animate bg-primary">
                    <div class="card-body">
                      <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                          <p class="text-uppercase fw-medium text-white-50 text-truncate mb-0">
                            Total Productos en Stock
                          </p>
                        </div>
                        <div class="flex-shrink-0">
                          <h5 class="text-white fs-14 mb-0">
                            <i class="ri-shopping-bag-3-line fs-24 text-white-50"></i>
                          </h5>
                        </div>
                      </div>
                      <div class="d-flex align-items-end justify-content-between mt-4">
                        <div>
                          <h4 class="fs-22 fw-semibold ff-secondary mb-4 text-white">
                            <?= esc($resumenStock->total_productos ?? 0) ?>
                          </h4>
                          <p class="text-white-50 mb-0">
                            Total Stock: <span class="text-white fw-bold"><?= esc($resumenStock->total_stock ?? 0) ?></span>
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Total Value -->
                <!-- <div class="col-xl-6 col-md-6">
                  <div class="card card-animate bg-success">
                    <div class="card-body">
                      <div class="d-flex align-items-center">
                        <div class="flex-grow-1 overflow-hidden">
                          <p class="text-uppercase fw-medium text-white-50 text-truncate mb-0">
                            Valor Total del Stock
                          </p>
                        </div>
                        <div class="flex-shrink-0">
                          <h5 class="text-white fs-14 mb-0">
                            <i class="ri-money-dollar-circle-line fs-24 text-white-50"></i>
                          </h5>
                        </div>
                      </div>
                      <div class="mt-4">
                        <h4 class="fs-22 fw-semibold ff-secondary mb-3 text-white">
                          Bs. <?= number_format($resumenStock->total_valor_contado ?? 0, 2) ?>
                        </h4>
                        <div class="mt-3 pt-3 border-top border-top-dashed border-white-50">
                          <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-white-50">Precio Contado</span>
                            <span class="text-white fw-bold">Bs. <?= number_format($resumenStock->total_valor_contado ?? 0, 2) ?></span>
                          </div>
                          <div class="d-flex justify-content-between align-items-center">
                            <span class="text-white-50">Precio Crédito</span>
                            <span class="text-white fw-bold">Bs. <?= number_format($resumenStock->total_valor_credito ?? 0, 2) ?></span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div> -->
              </div>

              <!-- Product Detail Table -->
              <div class="row mt-4">
                <div class="col-12">
                  <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                      <h5 class="card-title mb-0">Detalle por Producto</h5>
                      <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success" onclick="
                          const fInicio = document.getElementById('stats_fecha_inicio').value;
                          const fFin = document.getElementById('stats_fecha_fin').value;
                          window.location.href = '<?= base_url('inventariosucursales/exportarExcelStock') ?>?fecha_inicio=' + fInicio + '&fecha_fin=' + fFin;
                        ">
                          <i class="ri-file-excel-2-line me-1"></i> Generar Excel
                        </button>
                        <button type="button" class="btn btn-danger" onclick="
                          const fInicio = document.getElementById('stats_fecha_inicio').value;
                          const fFin = document.getElementById('stats_fecha_fin').value;
                          window.location.href = '<?= base_url('inventariosucursales/reporteStock') ?>?fecha_inicio=' + fInicio + '&fecha_fin=' + fFin;
                        ">
                          <i class="ri-file-pdf-line me-1"></i> Generar PDF
                        </button>
                      </div>
                    </div>
                    <div class="card-body">
                      <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                          <thead class="table-light">
                            <tr>
                              <th>Producto</th>
                              <th class="text-end">Inventario</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php if (!empty($resumenPorProducto['lista_productos'])): ?>
                              <?php foreach ($resumenPorProducto['lista_productos'] as $item): ?>
                                <tr>
                                  <td><?= esc($item->producto) ?></td>
                                  <td class="text-end">
                                    <span class="badge <?= ($item->total_stock > 0) ? 'bg-success' : 'bg-danger' ?>">
                                      <?= esc($item->total_stock ?? 0) ?>
                                    </span>
                                  </td>
                                </tr>
                              <?php endforeach; ?>
                            <?php else: ?>
                              <tr>
                                <td colspan="2" class="text-center text-muted">No hay datos disponibles</td>
                              </tr>
                            <?php endif; ?>
                          </tbody>
                          <tfoot class="table-light">
                            <tr class="fw-bold">
                              <td>TOTALES</td>
                              <td class="text-end"><?= esc($resumenPorProducto['totales_generales']['total_stock'] ?? 0) ?></td>
                            </tr>
                          </tfoot>
                        </table>
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
<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Animación extra al cargar (opcional)
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'fadeInUp 0.6s ease forwards';
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.sucursal-card').forEach(card => {
        card.style.opacity = '0';
        observer.observe(card);
    });
});
</script>
<?= $this->endSection() ?>