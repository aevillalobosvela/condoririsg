<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($title) ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
  /* Sistema de colores para stock */
  .stock-critical { background-color: #dc3545 !important; color: white !important; }
  .stock-low { background-color: #ffc107 !important; color: #000 !important; }
  .stock-medium { background-color: #17a2b8 !important; color: white !important; }
  .stock-good { background-color: #28a745 !important; color: white !important; }
  
  /* Mejoras visuales */
  .product-image {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
  }
  
  .table-hover tbody tr:hover {
    background-color: #f8f9fa;
    cursor: pointer;
  }
  
  .search-box {
    max-width: 400px;
  }
  
  .badge-stock {
    font-size: 0.9rem;
    padding: 0.5rem 0.75rem;
    font-weight: 600;
  }
  
  .card-header {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
  }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

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
                <h5 class="card-title mb-0">Listado de Productos</h5>
                <div class="flex-shrink-0">
                    <a href="<?= base_url('productos/register') ?>" class="btn btn-primary">
                        <i class="ri-add-line align-bottom me-1"></i> Nuevo Producto
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (session()->getFlashdata('message')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="ri-checkbox-circle-fill me-2"></i><?= session()->getFlashdata('message') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="ri-error-warning-fill me-2"></i><?= session()->getFlashdata('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Herramientas de búsqueda y filtros -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="search-box">
                            <input type="text" id="searchInput" class="form-control" placeholder="🔍 Buscar por nombre o descripción...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-outline-secondary" id="filterAll">Todos</button>
                            <button type="button" class="btn btn-outline-danger" id="filterCritical">Stock Crítico</button>
                            <button type="button" class="btn btn-outline-warning" id="filterLow">Stock Bajo</button>
                            <button type="button" class="btn btn-outline-success" id="filterGood">Stock Bueno</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-outline-primary" id="sortRecent">Más Recientes</button>
                            <button type="button" class="btn btn-outline-primary" id="sortOldest">Más Antiguos</button>
                            <button type="button" class="btn btn-outline-secondary" id="sortDefault">Por Defecto</button>
                        </div>
                    </div>
                </div>

                <!-- Leyenda de colores -->
                <div class="alert alert-light border mb-3" role="alert">
                    <strong>Leyenda de Stock:</strong>
                    <span class="badge stock-critical ms-2">0-5 unidades (Crítico)</span>
                    <span class="badge stock-low ms-2">6-20 unidades (Bajo)</span>
                    <span class="badge stock-medium ms-2">21-50 unidades (Medio)</span>
                    <span class="badge stock-good ms-2">51+ unidades (Bueno)</span>
                </div>

                <?php if (empty($productos)): ?>
                    <div class="alert alert-info text-center" role="alert">
                        <i class="ri-information-line me-2"></i>No hay productos registrados.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle table-sm" id="productosTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Precio Contado</th>
                                    <th>Precio Crédito</th>
                                    <th style="width: 120px;">Stock</th>
                                    <th style="width: 150px;">Fecha de Creación</th>
                                    <th style="width: 80px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $producto): 
                                    // Determinar clase de stock
                                    $stock = (int)$producto->stock;
                                    if ($stock <= 5) {
                                        $stockClass = 'stock-critical';
                                        $stockLevel = 'critical';
                                    } elseif ($stock <= 20) {
                                        $stockClass = 'stock-low';
                                        $stockLevel = 'low';
                                    } elseif ($stock <= 50) {
                                        $stockClass = 'stock-medium';
                                        $stockLevel = 'medium';
                                    } else {
                                        $stockClass = 'stock-good';
                                        $stockLevel = 'good';
                                    }
                                ?>
                                    <tr data-stock-level="<?= $stockLevel ?>" data-created="<?= strtotime($producto->created_at ?? 'now') ?>">
                                        <td>
                                            <strong><?= esc($producto->nombre) ?></strong>
                                            <?php if (!empty($producto->descripcion)): ?>
                                                <br><small class="text-muted"><?= esc($producto->descripcion) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>Bs. <?= number_format($producto->precio_contado, 2) ?></td>
                                        <td>Bs. <?= number_format($producto->precio_credito, 2) ?></td>
                                        <td>
                                            <span class="badge badge-stock <?= $stockClass ?>">
                                                <?= $stock ?> unidades
                                            </span>
                                        </td>
                                        <td>
                                            <small><?= date('d/m/Y H:i', strtotime($producto->created_at ?? 'now')) ?></small>
                                        </td>
                                        <td>
                                            <a href="<?= base_url('productos/edit/' . $producto->id) ?>" 
                                                class="btn btn-soft-warning btn-sm" title="Editar">
                                                <i class="ri-pencil-fill"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Resumen de productos -->
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="alert alert-secondary mb-0">
                                <strong>Total de productos:</strong> <span id="totalProductos"><?= count($productos) ?></span> |
                                <strong>Productos visibles:</strong> <span id="productosVisibles"><?= count($productos) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const table = document.getElementById('productosTable');
    const tbody = table ? table.querySelector('tbody') : null;
    const rows = tbody ? Array.from(tbody.querySelectorAll('tr')) : [];
    const totalProductos = rows.length;
    const productosVisiblesSpan = document.getElementById('productosVisibles');
    
    let currentStockFilter = null;
    let currentSortOrder = 'default';
    
    // Función para actualizar contador
    function updateCounter() {
        const visibleRows = rows.filter(row => row.style.display !== 'none');
        if (productosVisiblesSpan) {
            productosVisiblesSpan.textContent = visibleRows.length;
        }
    }
    
    // Función para aplicar filtros y ordenamiento
    function applyFiltersAndSort() {
        // Primero aplicar filtro de stock
        rows.forEach(row => {
            const stockLevel = row.getAttribute('data-stock-level');
            const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
            const text = row.textContent.toLowerCase();
            
            let showByStock = true;
            let showBySearch = text.includes(searchTerm);
            
            if (currentStockFilter === 'critical') {
                showByStock = stockLevel === 'critical';
            } else if (currentStockFilter === 'low') {
                showByStock = stockLevel === 'low';
            } else if (currentStockFilter === 'good') {
                showByStock = ['medium', 'good'].includes(stockLevel);
            }
            
            row.style.display = (showByStock && showBySearch) ? '' : 'none';
        });
        
        // Luego aplicar ordenamiento
        if (currentSortOrder !== 'default') {
            const visibleRows = rows.filter(row => row.style.display !== 'none');
            const hiddenRows = rows.filter(row => row.style.display === 'none');
            
            visibleRows.sort((a, b) => {
                const dateA = parseInt(a.getAttribute('data-created'));
                const dateB = parseInt(b.getAttribute('data-created'));
                
                if (currentSortOrder === 'recent') {
                    return dateB - dateA; // Más recientes primero
                } else if (currentSortOrder === 'oldest') {
                    return dateA - dateB; // Más antiguos primero
                }
                return 0;
            });
            
            // Reordenar en el DOM
            visibleRows.forEach(row => tbody.appendChild(row));
            hiddenRows.forEach(row => tbody.appendChild(row));
        }
        
        updateCounter();
    }
    
    // Búsqueda en tiempo real
    if (searchInput) {
        searchInput.addEventListener('input', applyFiltersAndSort);
    }
    
    // Filtros por nivel de stock
    const stockFilterButtons = {
        'filterAll': null,
        'filterCritical': 'critical',
        'filterLow': 'low',
        'filterGood': 'good'
    };
    
    Object.keys(stockFilterButtons).forEach(buttonId => {
        const button = document.getElementById(buttonId);
        if (button) {
            button.addEventListener('click', function() {
                // Remover clase activa de todos los botones de stock
                Object.keys(stockFilterButtons).forEach(id => {
                    const btn = document.getElementById(id);
                    if (btn) btn.classList.remove('active');
                });
                
                // Agregar clase activa al botón clickeado
                this.classList.add('active');
                
                currentStockFilter = stockFilterButtons[buttonId];
                applyFiltersAndSort();
            });
        }
    });
    
    // Botones de ordenamiento por fecha
    const sortButtons = {
        'sortRecent': 'recent',
        'sortOldest': 'oldest',
        'sortDefault': 'default'
    };
    
    Object.keys(sortButtons).forEach(buttonId => {
        const button = document.getElementById(buttonId);
        if (button) {
            button.addEventListener('click', function() {
                // Remover clase activa de todos los botones de ordenamiento
                Object.keys(sortButtons).forEach(id => {
                    const btn = document.getElementById(id);
                    if (btn) btn.classList.remove('active');
                });
                
                // Agregar clase activa al botón clickeado
                this.classList.add('active');
                
                currentSortOrder = sortButtons[buttonId];
                
                // Si es orden por defecto, restaurar orden original
                if (currentSortOrder === 'default') {
                    rows.forEach(row => tbody.appendChild(row));
                }
                
                applyFiltersAndSort();
            });
        }
    });
    
    // Activar filtros por defecto
    const filterAllBtn = document.getElementById('filterAll');
    if (filterAllBtn) filterAllBtn.classList.add('active');
    
    const sortDefaultBtn = document.getElementById('sortDefault');
    if (sortDefaultBtn) sortDefaultBtn.classList.add('active');
});
</script>
<?= $this->endSection() ?>