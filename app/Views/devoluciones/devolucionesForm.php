<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Registrar Devolución</h3>
            </div>
            
            <form action="<?= base_url('envios/createDevoluciones') ?>" method="post">
                <div class="card-body">
                    
                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger">
                            <?= session()->getFlashdata('error') ?>
                        </div>
                    <?php endif; ?>

                    <?php if (session()->getFlashdata('validation')): ?>
                        <div class="alert alert-danger">
                            <?= session()->getFlashdata('validation')->listErrors() ?>
                        </div>
                    <?php endif; ?>

                    <input type="hidden" name="sucursales_id" value="<?= $sucursal_id ?>">

                    <div class="form-group">
                        <label for="sucursales_destino_id">Sucursal Destino</label>
                        <select name="sucursales_destino_id" id="sucursales_destino_id" class="form-control select2" required>
                            <option value="">Seleccione una sucursal</option>
                            <?php foreach ($sucursales as $sucursal): ?>
                                <option value="<?= $sucursal['id'] ?>"><?= $sucursal['nombre'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="producto_id">Producto</label>
                        <select name="producto_id" id="producto_id" class="form-control select2" required>
                            <option value="">Seleccione un producto</option>
                            <?php foreach ($productos as $producto): ?>
                                <option value="<?= $producto->id ?>" data-stock="<?= $producto->stock_actual ?>">
                                    <?= $producto->nombre ?> - Stock: <?= $producto->stock_actual ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="cantidad">Cantidad a Devolver</label>
                        <input type="number" name="cantidad" id="cantidad" class="form-control" min="1" required>
                        <small id="stockHelp" class="form-text text-muted">Seleccione un producto para ver el stock disponible.</small>
                    </div>

                    <div class="form-group">
                        <label for="observacion">Observación</label>
                        <textarea name="observacion" id="observacion" class="form-control" rows="3" placeholder="Ingrese la observación de la devolución..."></textarea>
                    </div>

                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Registrar Devolución</button>
                    <a href="<?= base_url('envios/devoluciones') ?>" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const productoSelect = document.getElementById('producto_id');
        const cantidadInput = document.getElementById('cantidad');
        const stockHelp = document.getElementById('stockHelp');

        productoSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const stock = selectedOption.getAttribute('data-stock');
            
            if (stock) {
                cantidadInput.max = stock;
                stockHelp.textContent = `Stock disponible: ${stock}`;
            } else {
                cantidadInput.removeAttribute('max');
                stockHelp.textContent = 'Seleccione un producto para ver el stock disponible.';
            }
        });
    });
</script>

<?= $this->endSection() ?>
