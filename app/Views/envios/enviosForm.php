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
    --danger: #dc3545;
    --light: #f8f9fa;
    --dark: #343a40;
    --border: #e0f0e9;
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
    --shadow: 0 4px 12px rgba(40,167,69,0.1);
    --transition: all 0.3s ease;
  }

  .form-envio {
    max-width: 900px;
    margin: 0 auto;
  }

  .card-envio {
    border-radius: 16px;
    box-shadow: var(--shadow);
    overflow: hidden;
  }

  .card-header {
    background: linear-gradient(135deg, #28a745 0%, #218838 100%);
    color: white;
    padding: 20px 24px;
    font-weight: 600;
    font-size: 1.1rem;
  }

  .form-section {
    padding: 24px;
    background: white;
  }

  .form-group {
    margin-bottom: 1.5rem;
  }

  .form-label {
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .form-label i {
    color: var(--primary);
    font-size: 1rem;
  }

  .form-control, .form-select {
    border-radius: 10px;
    border: 2px solid #e0f0e9;
    padding: 12px 16px;
    font-size: 1rem;
    transition: var(--transition);
  }
  .form-control:focus, .form-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(40,167,69,0.2);
    outline: none;
  }

  .form-control:disabled, .form-select:disabled {
    background-color: #f8fdfa;
    color: var(--secondary);
  }

  .btn-action {
    padding: 12px 24px;
    font-weight: 600;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    font-size: 1.05rem;
  }
  .btn-save {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    flex: 1;
  }
  .btn-save:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
  }
  .btn-cancel {
    background: white;
    color: var(--secondary);
    border: 1px solid var(--border);
    flex: 1;
  }
  .btn-cancel:hover {
    background: #f1f3f5;
    color: var(--dark);
  }

  .btn-group-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
  }

  /* Validation */
  .invalid-feedback {
    color: var(--danger);
    font-size: 0.875rem;
    margin-top: 0.25rem;
    display: block;
  }
  .form-control.is-invalid, .form-select.is-invalid {
    border-color: var(--danger);
  }

  /* Responsive */
  @media (max-width: 767.98px) {
    .btn-group-actions {
      flex-direction: column;
    }
    .btn-action {
      width: 100%;
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
$userId = session()->get('id');
$sucursalId = session()->get('sucursal_id');
?>

<div class="container-fluid">
  <div class="row">
    <div class="col-12">
      <div class="page-title-box d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h4 mb-sm-0 fw-bold">
          <i class="ri-truck-line text-success me-2"></i>
          <?= isset($envio['id']) ? 'Editar Envío' : 'Crear Nuevo Envío' ?>
        </h1>
        <div class="page-title-right">
          <a href="<?= base_url('envios') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="ri-arrow-left-s-line"></i> Volver a Envíos
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="row justify-content-center">
    <div class="col-12">
      <div class="card-envio animate-fade-in-up">
        <div class="card-header">
          <i class="ri-file-list-3-line me-2"></i>
          Información del Envío
        </div>
        <div class="form-section">
          
          <!-- Errores globales -->
          <?php if (isset($validation)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="ri-error-warning-line me-2"></i>
              <strong>Corrija los siguientes errores:</strong>
              <?= $validation->listErrors() ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <i class="ri-error-warning-line me-2"></i>
              <?= session()->getFlashdata('error') ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <form action="<?= isset($envio['id']) ? base_url('envios/update/' . $envio['id']) : base_url('envios/create') ?>" 
                method="post" class="needs-validation" novalidate>
            <?= csrf_field() ?>

            <input type="hidden" name="id" value="<?= $envio['id'] ?? '' ?>">
            <input type="hidden" name="user_id" value="<?= old('user_id', $userId ?? '') ?>">
            <input type="hidden" name="sucursal_origen_id" value="<?= old('sucursal_origen_id', $sucursalId ?? '') ?>">
            <input type="hidden" name="estado_id" value="1">
            <input type="hidden" name="tipo" value="<?= esc(($sucursalId == 2) ? 'devolucion' : ($tipo ?? 'envio')) ?>">

            <div class="row g-4">
              <!-- Sucursal Destino -->
              <div class="col-12">
                <label for="sucursal_destino_id" class="form-label">
                  <i class="ri-map-pin-line"></i> Sucursal de Destino
                </label>
                <select class="form-select <?= session('errors.sucursal_destino_id') ? 'is-invalid' : '' ?>" 
                        id="sucursal_destino_id" name="sucursal_destino_id" required>
                  <option value="">Seleccione una sucursal...</option>
                  <?php foreach ($sucursales as $sucursal): ?>
                    <?php if ($sucursal['id'] != $sucursalId): // Evitar enviar a la misma sucursal ?>
                      <option value="<?= $sucursal['id'] ?>" 
                              <?= old('sucursal_destino_id', $envio['sucursal_destino_id'] ?? '') == $sucursal['id'] ? 'selected' : '' ?>>
                        <?= esc($sucursal['nombre']) ?> 
                        <?php if (!empty($sucursal['direccion'])): ?>
                          — <?= esc(substr($sucursal['direccion'], 0, 30)) ?>...
                        <?php endif; ?>
                      </option>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </select>
                <?php if (session('errors.sucursal_destino_id')): ?>
                  <div class="invalid-feedback"><?= session('errors.sucursal_destino_id') ?></div>
                <?php endif; ?>
              </div>

              <!-- Usuario de Transporte -->
            <div class="col-md-6">
  <label for="user_transporte_id" class="form-label">
    <i class="ri-user-3-line"></i> Transportista
  </label>
  
  <select class="form-select <?= session('errors.user_transporte_id') ? 'is-invalid' : '' ?>" 
          id="user_transporte_id" 
          name="user_transporte_id" 
          required
          onchange="document.getElementById('hidden_trasporte_id').value = this.value">
    <option value="">Seleccione un transportista...</option>
    <?php foreach ($users as $user): ?>
      <option value="<?= $user['id'] ?>" 
              <?= old('user_transporte_id', $envio['user_transporte_id'] ?? '') == $user['id'] ? 'selected' : '' ?>>
        <?= esc($user['nombre']) ?>
        <?= !empty($user['telefono']) ? " • {$user['telefono']}" : '' ?>
      </option>
    <?php endforeach; ?>
  </select>

  <input type="hidden" 
         id="hidden_trasporte_id" 
         name="user_trasporte_id" 
         value="<?= old('user_transporte_id', $envio['user_transporte_id'] ?? '') ?>">

  <?php if (session('errors.user_transporte_id')): ?>
    <div class="invalid-feedback"><?= session('errors.user_transporte_id') ?></div>
  <?php endif; ?>
</div>

              <!-- Fecha de Envío -->
              <div class="col-md-6">
                <label for="fecha_envio" class="form-label">
                  <i class="ri-calendar-2-line"></i> Fecha de Envío
                </label>
                <input type="date" class="form-control <?= session('errors.fecha_envio') ? 'is-invalid' : '' ?>" 
                       id="fecha_envio" name="fecha_envio" 
                       value="<?= old('fecha_envio', $envio['fecha_envio'] ?? date('Y-m-d')) ?>" 
                       required>
                <?php if (session('errors.fecha_envio')): ?>
                  <div class="invalid-feedback"><?= session('errors.fecha_envio') ?></div>
                <?php endif; ?>
              </div>

              <!-- Observación -->
              <div class="col-12">
                <label for="observacion_origen" class="form-label">
                  <i class="ri-chat-1-line"></i> Observación de Origen (Opcional)
                </label>
                <textarea class="form-control" id="observacion_origen" name="observacion_origen" 
                          rows="3" placeholder="Ej: Productos frágiles, manejar con cuidado..."><?= old('observacion_origen', $envio['observacion_origen'] ?? '') ?></textarea>
              </div>
            </div>

            <!-- Botones -->
            <div class="btn-group-actions">
              <a href="<?= base_url('envios') ?>" class="btn btn-action btn-cancel">
                <i class="ri-close-line"></i> Cancelar
              </a>
              <button type="submit" class="btn btn-action btn-save">
                <i class="ri-save-2-line"></i>
                <?= isset($envio['id']) ? 'Actualizar Envío' : 'Registrar Envío' ?>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Validación HTML5 mejorada
  const forms = document.querySelectorAll('.needs-validation');
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', function(event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
        
        // Scroll al primer campo con error
        const firstError = form.querySelector(':invalid');
        if (firstError) {
          firstError.focus();
          firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      }
      form.classList.add('was-validated');
    }, false);
  });

  // ✅ Establecer fecha mínima: HOY EN ZONA HORARIA LOCAL (corrección clave)
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  const todayLocal = `${year}-${month}-${day}`;
  document.getElementById('fecha_envio').min = todayLocal;

  // Microinteracciones
  document.querySelectorAll('.btn-action').forEach(btn => {
    btn.addEventListener('touchstart', () => btn.style.transform = 'scale(0.98)');
    btn.addEventListener('touchend', () => btn.style.transform = '');
    btn.addEventListener('mousedown', () => btn.style.transform = 'scale(0.98)');
    btn.addEventListener('mouseup', () => btn.style.transform = '');
  });
});
</script>
<?= $this->endSection() ?>