
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container-fluid {
            padding-top: 2rem;
        }
        .nav-tabs .nav-link {
            border: 1px solid transparent;
            border-bottom: none;
            background-color: #e9ecef;
            color: #495057;
            border-top-left-radius: .25rem;
            border-top-right-radius: .25rem;
        }
        .nav-tabs .nav-link.active {
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
        .badge-success {
            color: #155724;
            background-color: #d4edda;
        }
        .badge-warning {
            color: #856404;
            background-color: #fff3cd;
        }
        .badge-danger {
            color: #721c24;
            background-color: #f8d7da;
        }
        .progress {
            height: 10px;
        }
        .table-product-image {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 5px;
        }
        .product-item {
            display: flex;
            align-items: center;
        }
        .product-item img {
            margin-right: 10px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .progress-bar-primary {
            background-color: #0d6efd;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Gestión de Productos</h1>
            <p class="text-muted">Administra tu inventario de productos de manera eficiente</p>
        </div>
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link" href="#">Panel de control</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">Gestión de envíos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="#">Nuevo Producto</a>
            </li>
        </ul>
    </div>

    <div class="card p-3 mb-4">
        <div class="d-flex align-items-center">
            <div class="me-auto">
                <input type="text" class="form-control" placeholder="Buscar productos...">
            </div>
            <div class="d-flex align-items-center me-3">
                <label for="categoria" class="form-label mb-0 me-2">Categoría:</label>
                <select class="form-select" id="categoria" style="min-width: 150px;">
                    <option selected>Todas</option>
                    <option>Lácteos</option>
                    <option>Carnes</option>
                    <option>Granos</option>
                    <option>Bebidas</option>
                </select>
            </div>
            <div class="d-flex align-items-center me-3">
                <label for="estado" class="form-label mb-0 me-2">Estado:</label>
                <select class="form-select" id="estado" style="min-width: 150px;">
                    <option selected>Todos</option>
                    <option>En stock</option>
                    <option>Bajo stock</option>
                    <option>Agotado</option>
                </select>
            </div>
            <div>
                <button class="btn btn-outline-primary">B</button>
                <button class="btn btn-outline-danger">U</button>
            </div>
        </div>
    </div>

    <div class="card p-3 mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col">Producto</th>
                        <th scope="col">Categoría</th>
                        <th scope="col">SKU</th>
                        <th scope="col">Stock</th>
                        <th scope="col">Precio</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="URL_IMAGEN_QUESO" class="table-product-image me-2" alt="Queso Fresco">
                                <div>
                                    <div>Queso Fresco Artesanal</div>
                                    <small class="text-muted">500g</small>
                                </div>
                            </div>
                        </td>
                        <td>Lácteos</td>
                        <td>LAC-QF-001</td>
                        <td>
                            42
                            <div class="progress mt-1">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 42%" aria-valuenow="42" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </td>
                        <td>Bs. 25.50</td>
                        <td><span class="badge bg-success">En stock</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary">B</button>
                            <button class="btn btn-sm btn-outline-danger">U</button>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="URL_IMAGEN_LOMO" class="table-product-image me-2" alt="Lomo de Res">
                                <div>
                                    <div>Lomo de Res Premium</div>
                                    <small class="text-muted">1kg</small>
                                </div>
                            </div>
                        </td>
                        <td>Carnes</td>
                        <td>CAR-LR-023</td>
                        <td>
                            8
                            <div class="progress mt-1">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: 8%" aria-valuenow="8" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </td>
                        <td>Bs. 85.00</td>
                        <td><span class="badge bg-warning text-dark">Bajo stock</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary">B</button>
                            <button class="btn btn-sm btn-outline-danger">U</button>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="URL_IMAGEN_QUINUA" class="table-product-image me-2" alt="Quinua Orgánica">
                                <div>
                                    <div>Quinua Orgánica</div>
                                    <small class="text-muted">500g</small>
                                </div>
                            </div>
                        </td>
                        <td>Granos</td>
                        <td>GRA-QO-105</td>
                        <td>
                            120
                            <div class="progress mt-1">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </td>
                        <td>Bs. 18.75</td>
                        <td><span class="badge bg-success">En stock</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary">B</button>
                            <button class="btn btn-sm btn-outline-danger">U</button>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="URL_IMAGEN_CHICHA" class="table-product-image me-2" alt="Chicha Morada">
                                <div>
                                    <div>Chicha Morada Tradicional</div>
                                    <small class="text-muted">1L</small>
                                </div>
                            </div>
                        </td>
                        <td>Bebidas</td>
                        <td>BEB-CM-078</td>
                        <td>
                            0
                            <div class="progress mt-1">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </td>
                        <td>Bs. 12.00</td>
                        <td><span class="badge bg-danger">Agotado</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary">B</button>
                            <button class="btn btn-sm btn-outline-danger">U</button>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="URL_IMAGEN_PALMITO" class="table-product-image me-2" alt="Palmito en Conserva">
                                <div>
                                    <div>Palmito en Conserva</div>
                                    <small class="text-muted">400g</small>
                                </div>
                            </div>
                        </td>
                        <td>Enlatados</td>
                        <td>ENL-PC-056</td>
                        <td>
                            35
                            <div class="progress mt-1">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 35%" aria-valuenow="35" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </td>
                        <td>Bs. 22.30</td>
                        <td><span class="badge bg-success">En stock</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary">B</button>
                            <button class="btn btn-sm btn-outline-danger">U</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mt-3">
            <small class="text-muted">Mostrando 5 de 42 productos</small>
            <nav aria-label="Paginación de productos">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item"><a class="page-link" href="#">B</a></li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">B</a></li>
                </ul>
            </nav>
            <div class="d-flex align-items-center">
                <label for="mostrar" class="form-label mb-0 me-2">Mostrar:</label>
                <select class="form-select form-select-sm" id="mostrar">
                    <option selected>5</option>
                    <option>10</option>
                    <option>20</option>
                </select>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card p-3 mb-4">
                <h5 class="card-title">Estadísticas de Inventario</h5>
                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <h6 class="mb-0">Total Productos</h6>
                        <span class="h4">42</span>
                    </div>
                    <div>
                        <h6 class="mb-0">Bajo Stock</h6>
                        <span class="h4">8</span>
                    </div>
                    <div>
                        <h6 class="mb-0">Agotados</h6>
                        <span class="h4">3</span>
                    </div>
                </div>
                <div class="progress-chart">
                    <div class="product-item my-2 d-flex align-items-center">
                        <span class="me-2 text-muted" style="width: 100px;">Queso Fresco</span>
                        <div class="progress w-100">
                            <div class="progress-bar progress-bar-primary" role="progressbar" style="width: 80%" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="product-item my-2 d-flex align-items-center">
                        <span class="me-2 text-muted" style="width: 100px;">Lomo de Res</span>
                        <div class="progress w-100">
                            <div class="progress-bar progress-bar-primary" role="progressbar" style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card p-3 mb-4">
                <h5 class="card-title">Productos Más Vendidos</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="product-item">
                            <img src="URL_IMAGEN_QUESO" class="table-product-image me-2" alt="Queso Fresco">
                            <span>Queso Fresco Artesanal</span>
                        </div>
                        <span class="text-muted">245 unid.</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="product-item">
                            <img src="URL_IMAGEN_QUINUA" class="table-product-image me-2" alt="Quinua Orgánica">
                            <span>Quinua Orgánica</span>
                        </div>
                        <span class="text-muted">198 unid.</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="product-item">
                            <img src="URL_IMAGEN_PALMITO" class="table-product-image me-2" alt="Palmito en Conserva">
                            <span>Palmito en Conserva</span>
                        </div>
                        <span class="text-muted">156 unid.</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="product-item">
                            <img src="URL_IMAGEN_CHICHA" class="table-product-image me-2" alt="Chicha Morada">
                            <span>Chicha Morada Tradicional</span>
                        </div>
                        <span class="text-muted">132 unid.</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>