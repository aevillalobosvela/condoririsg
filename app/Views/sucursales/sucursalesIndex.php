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
    position: relative;
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

  .btn-toggle {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
    margin-left: 0.5rem;
  }
  .btn-toggle:hover {
    background-color: #ffeaa7;
    transform: scale(1.03);
  }

  .btn-activate {
    background-color: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
    margin-left: 0.5rem;
  }
  .btn-activate:hover {
    background-color: #bee5eb;
    transform: scale(1.03);
  }

  .sucursal-card.inactive {
    opacity: 0.6;
    border-color: #dc3545;
  }

  .sucursal-card.inactive .card-head {
    background: #f8d7da;
  }

  .estado-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
  }

  .estado-activo {
    background: #d4edda;
    color: #155724;
  }

  .estado-inactivo {
    background: #f8d7da;
    color: #721c24;
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
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb m-0">
              <li class="breadcrumb-item"><a href="<?= base_url('/') ?>"><i class="ri-home-4-line me-1"></i>Dashboard</a></li>
              <li class="breadcrumb-item active" aria-current="page"><?= esc($title) ?></li>
            </ol>
          </nav>
        </div>
        <div class="mt-3 mt-sm-0">
          <a href="<?= base_url('sucursales/register') ?>" class="btn btn-primary">
            <i class="ri-add-line"></i> Nueva Sucursal
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

  <!-- Contenido -->
  <?php if (empty($sucursales)): ?>
    <div class="row justify-content-center">
      <div class="col-12 col-md-10 col-lg-8">
        <div class="empty-state">
          <div class="empty-icon">
            <i class="ri-map-pin-5-line"></i>
          </div>
          <h2 class="empty-title">Aún no tienes sucursales registradas</h2>
          <p class="empty-text">
            Las sucursales te permiten gestionar inventario, ventas y usuarios por ubicación física. 
            Empieza creando tu primera sucursal hoy.
          </p>
          <a href="<?= base_url('sucursales/register') ?>" class="btn btn-primary">
            <i class="ri-add-line"></i> Crear Sucursal Ahora
          </a>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="row">
      <div class="col-12">
        <div class="d-flex align-items-center mb-3">
          <span class="badge bg-success rounded-pill px-3 py-2">
            <i class="ri-map-pin-2-line me-1"></i> <?= count($sucursales) ?> sucursales
          </span>
        </div>

        <div class="sucursales-grid">
          <?php foreach ($sucursales as $sucursal): ?>
            <?php $isActive = ($sucursal['estado'] === 't' || $sucursal['estado'] === true); ?>
            <div class="sucursal-card <?= !$isActive ? 'inactive' : '' ?>">
              <div class="card-head">
                <span class="estado-badge <?= $isActive ? 'estado-activo' : 'estado-inactivo' ?>">
                  <?= $isActive ? 'Activa' : 'Inactiva' ?>
                </span>
                <span class="card-id">#<?= esc($sucursal['id']) ?></span>
                <h3 class="card-title">
                  <i class="ri-store-3-fill"></i>
                  <?= esc($sucursal['nombre']) ?>
                </h3>
              </div>

              <div class="card-body">
                <div class="info-row">
                  <div class="info-label">Dirección</div>
                  <div class="info-value <?= empty($sucursal['direccion']) ? 'empty' : '' ?>">
                    <?= esc($sucursal['direccion'] ?? 'No especificada') ?>
                  </div>
                </div>

                <div class="info-row">
                  <div class="info-label">Teléfono</div>
                  <div class="info-value <?= empty($sucursal['telefono']) ? 'empty' : '' ?>">
                    <?= esc($sucursal['telefono'] ?? 'No disponible') ?>
                  </div>
                </div>

                <div class="info-row">
                  <div class="info-label">Descripción</div>
                  <div class="info-value <?= empty($sucursal['descripcion']) ? 'empty' : '' ?>">
                    <?= esc($sucursal['descripcion'] ?? 'Sin descripción') ?>
                  </div>
                </div>

                <div class="info-row">
                  <div class="info-label">Creada</div>
                  <div class="info-value">
                    <?= $sucursal['created_at'] 
                      ? date('d M Y \a \l\a\s H:i', strtotime($sucursal['created_at'])) 
                      : '—' ?>
                  </div>
                </div>
              </div>

              <div class="card-footer">
                <a href="<?= base_url('sucursales/edit/' . $sucursal['id']) ?>" 
                   class="btn-action btn-edit">
                  <i class="ri-pencil-line"></i> Editar
                </a>
                <button type="button" 
                        class="btn-action <?= $isActive ? 'btn-toggle' : 'btn-activate' ?>" 
                        onclick="confirmarCambioEstado(<?= $sucursal['id'] ?>, '<?= esc($sucursal['nombre']) ?>', <?= $isActive ? 'false' : 'true' ?>)">
                  <i class="<?= $isActive ? 'ri-pause-circle-line' : 'ri-play-circle-line' ?>"></i> 
                  <?= $isActive ? 'Desactivar' : 'Reactivar' ?>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<!-- Modal de Confirmación -->
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle">Confirmar Acción</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p id="modalMessage"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="confirmButton">Confirmar</button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function confirmarCambioEstado(id, nombre, activar) {
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    const accion = activar ? 'reactivar' : 'desactivar';
    const color = activar ? 'success' : 'warning';
    
    document.getElementById('modalTitle').textContent = `${accion.charAt(0).toUpperCase() + accion.slice(1)} Sucursal`;
    document.getElementById('modalMessage').innerHTML = `¿Está seguro que desea <strong>${accion}</strong> la sucursal <strong>"${nombre}"</strong>?`;
    
    const confirmBtn = document.getElementById('confirmButton');
    confirmBtn.className = `btn btn-${color}`;
    confirmBtn.textContent = accion.charAt(0).toUpperCase() + accion.slice(1);
    
    confirmBtn.onclick = () => {
        window.location.href = `<?= base_url('sucursales/toggle/') ?>${id}`;
    };
    
    modal.show();
}

document.addEventListener('DOMContentLoaded', () => {
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