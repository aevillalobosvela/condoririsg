<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
    




    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0"><?= esc($title) ?></h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                            <li class="breadcrumb-item active"><?= esc($title) ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">Listado de Unidades</h5>
                        <div class="flex-shrink-0">
                            <a href="/unidades/register" class="btn btn-primary">
                                <i class="ri-add-line align-bottom me-1"></i> Crear Nueva Unidad
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
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

                        <?php if (empty($unidades)): ?>
                            <div class="alert alert-info text-center" role="alert">
                                No hay unidades registradas.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-nowrap align-middle table-sm">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Tipo</th>
                                            <th>Estado</th>
                                            <th>Creado</th>
                                            <th>Actualizado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($unidades as $unidad): ?>
                                            <?php $isActive = ($unidad['estado'] === 't' || $unidad['estado'] === true); ?>
                                            <tr class="<?= !$isActive ? 'table-secondary' : '' ?>">
                                                <td><?= $unidad['id'] ?></td>
                                                <td><?= esc($unidad['nombre']) ?></td>
                                                <td>
                                                    <?php $tipo = $unidad['tipo'] ?? 'lacteo'; ?>
                                                    <?php if ($tipo === 'agro'): ?>
                                                        <span class="badge" style="background:#dcfce7; color:#15803d; border:1px solid #86efac;">Agropecuario</span>
                                                    <?php else: ?>
                                                        <span class="badge" style="background:#dbeafe; color:#1d4ed8; border:1px solid #93c5fd;">Lácteos</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $isActive ? 'bg-success' : 'bg-danger' ?>">
                                                        <?= $isActive ? 'Activa' : 'Inactiva' ?>
                                                    </span>
                                                </td>
                                                <td><?= $unidad['created_at'] ?></td>
                                                <td><?= $unidad['updated_at'] ?></td>
                                                <td>
                                                    <a href="/unidades/edit/<?= $unidad['id'] ?>" class="btn btn-sm btn-warning">
                                                        <i class="ri-edit-line"></i> Editar
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm <?= $isActive ? 'btn-outline-danger' : 'btn-outline-success' ?>" 
                                                            onclick="confirmarToggle(<?= $unidad['id'] ?>, '<?= esc($unidad['nombre']) ?>', <?= $isActive ? 'false' : 'true' ?>)">
                                                        <i class="<?= $isActive ? 'ri-pause-circle-line' : 'ri-play-circle-line' ?>"></i> 
                                                        <?= $isActive ? 'Desactivar' : 'Reactivar' ?>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
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
function confirmarToggle(id, nombre, activar) {
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    const accion = activar ? 'reactivar' : 'desactivar';
    const color = activar ? 'success' : 'warning';
    
    document.getElementById('modalTitle').textContent = `${accion.charAt(0).toUpperCase() + accion.slice(1)} Unidad`;
    document.getElementById('modalMessage').innerHTML = `¿Está seguro que desea <strong>${accion}</strong> la unidad <strong>"${nombre}"</strong>?`;
    
    const confirmBtn = document.getElementById('confirmButton');
    confirmBtn.className = `btn btn-${color}`;
    confirmBtn.textContent = accion.charAt(0).toUpperCase() + accion.slice(1);
    
    confirmBtn.onclick = () => {
        window.location.href = `/unidades/delete/${id}`;
    };
    
    modal.show();
}
</script>
<?= $this->endSection() ?>
