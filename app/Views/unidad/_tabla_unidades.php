<?php
$tipo        = $tipo ?? 'lacteo';
$accentColor = ($tipo === 'agro') ? '#16a34a' : '#0d6efd';
?>
<?php if (empty($unidades)): ?>
  <div class="alert alert-info text-center">No hay unidades registradas.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" style="font-size:0.88rem;">
      <thead>
        <tr style="background:#f8fafc;">
          <th>Nombre</th>
          <th>Descripción</th>
          <th class="text-center">Estado</th>
          <th class="text-center">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($unidades as $u):
          $activa = ($u['estado'] === 't' || $u['estado'] === true);
        ?>
          <tr>
            <td class="fw-semibold"><?= esc($u['nombre']) ?></td>
            <td class="text-muted"><?= esc($u['descripcion'] ?? '—') ?></td>
            <td class="text-center">
              <?php if ($activa): ?>
                <span class="badge-activa">Activa</span>
              <?php else: ?>
                <span class="badge-inactiva">Inactiva</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <div class="d-flex justify-content-center gap-1">
                <a href="<?= base_url('unidades/edit/' . $u['id']) ?>"
                   class="btn btn-sm btn-outline-secondary" title="Editar">
                  <i class="ri-pencil-line"></i>
                </a>
                <button type="button"
                        class="btn btn-sm <?= $activa ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                        onclick="confirmarToggle(<?= $u['id'] ?>, '<?= esc($u['nombre']) ?>', <?= $activa ? 'false' : 'true' ?>)"
                        title="<?= $activa ? 'Desactivar' : 'Reactivar' ?>">
                  <i class="<?= $activa ? 'ri-pause-circle-line' : 'ri-play-circle-line' ?>"></i>
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
