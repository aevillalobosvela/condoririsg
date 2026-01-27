<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-12">
                <h1 class="mb-4"><?= $title ?></h1>

                <!-- Filtro de días -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Configurar Alerta</h5>
                    </div>
                    <div class="card-body">
                        <form method="get" action="/productos/proximosAVencer" class="row align-items-end">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="dias" class="form-label">Mostrar productos que vencen en los próximos:</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="dias" name="dias" 
                                               value="<?= $dias ?>" min="1" max="365">
                                        <span class="input-group-text">días</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">Actualizar</button>
                                <a href="/productos" class="btn btn-secondary">Volver al Listado</a>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (empty($productos)): ?>
                    <div class="alert alert-info">
                        No hay productos que venzan en los próximos <?= $dias ?> días.
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        Se encontraron <strong><?= count($productos) ?></strong> productos que vencen en los próximos <?= $dias ?> días.
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead class="table-warning">
                                <tr>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th>Stock</th>
                                    <th>Fecha Vencimiento</th>
                                    <th>Días Restantes</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $producto): 
                                    $fechaVencimiento = new DateTime($producto['fecha_vencimiento']);
                                    $hoy = new DateTime();
                                    $intervalo = $hoy->diff($fechaVencimiento);
                                    $diasRestantes = (int)$intervalo->format('%r%a');
                                ?>
                                    <tr>
                                        <td><?= esc($producto['nombre']) ?></td>
                                        <td><?= esc($producto['categoria']) ?></td>
                                        <td><?= esc($producto['stock']) ?></td>
                                        <td><?= esc($producto['fecha_vencimiento']) ?></td>
                                        <td>
                                            <?php if ($diasRestantes < 0): ?>
                                                <span class="badge bg-danger">Vencido</span>
                                            <?php elseif ($diasRestantes === 0): ?>
                                                <span class="badge bg-warning text-dark">Vence hoy</span>
                                            <?php else: ?>
                                                <span class="badge bg-success"><?= $diasRestantes ?> días</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="/productos/edit/<?= $producto['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil-square"></i> Editar
                                            </a>
                                            <a href="/productos/delete/<?= $producto['id'] ?>" class="btn btn-sm btn-danger" 
                                               onclick="return confirm('¿Está seguro de que desea eliminar este producto?');">
                                                <i class="bi bi-trash"></i> Eliminar
                                            </a>
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