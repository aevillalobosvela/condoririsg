<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes de Ventas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome para íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f6f9; /* Color de fondo similar a AdminLTE */
            margin: 0;
            padding: 0;
        }

        .header-top {
            background-color: #f8f9fa;
            padding: 10px 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-top .left-info {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .header-top .nav-buttons .btn {
            margin-left: 10px;
            font-size: 0.9rem;
        }

        .reports-container {
            padding: 20px;
            max-width: 1400px; /* Ancho máximo para el contenedor principal */
            margin: 20px auto;
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }

        /* Filter Section */
        .filter-section {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            align-items: flex-end; /* Alinea los elementos en la parte inferior */
        }

        .filter-group {
            flex: 1; /* Permite que los grupos de filtros ocupen espacio */
            min-width: 180px; /* Ancho mínimo para cada filtro */
        }

        .filter-section .form-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .filter-section .form-control,
        .filter-section .form-select {
            border-radius: 0.5rem;
            font-size: 0.9rem;
        }

        .filter-section .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            border-radius: 0.5rem;
            padding: 8px 15px;
            font-size: 0.9rem;
        }

        /* KPI Cards */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); /* 3 columnas responsivas */
            gap: 20px;
            margin-bottom: 30px;
        }

        .kpi-card {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .kpi-card .icon-placeholder {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 2.5rem;
            color: rgba(0,0,0,0.1);
        }

        .kpi-card .kpi-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .kpi-card .kpi-value {
            font-size: 2.2rem;
            font-weight: bold;
            color: #343a40;
        }

        .kpi-card .kpi-sub {
            font-size: 0.8rem;
            color: #777;
            margin-top: 10px;
        }

        .kpi-card .kpi-sub .trend {
            font-weight: bold;
        }

        .kpi-card .kpi-sub .trend.up { color: #28a745; }
        .kpi-card .kpi-sub .trend.down { color: #dc3545; }

        /* Chart Sections */
        .chart-section {
            background-color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .chart-header .title {
            font-size: 1.1rem;
            font-weight: bold;
            color: #333;
        }

        .chart-actions .btn-group .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }

        .chart-placeholder {
            background-color: #e9ecef;
            border-radius: 0.3rem;
            height: 250px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #6c757d;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .chart-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Detailed Report Table */
        .table-controls {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 15px;
        }

        .table-controls .btn {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
            border-radius: 0.3rem;
        }

        .detailed-report-table thead th {
            background-color: #007bff;
            color: white;
            border-bottom: 2px solid #dee2e6;
            font-size: 0.9rem;
        }

        .detailed-report-table tbody tr:nth-of-type(odd) {
            background-color: rgba(0,0,0,.03);
        }

        .detailed-report-table tbody tr:hover {
            background-color: rgba(0,0,0,.07);
        }

        .detailed-report-table td, .detailed-report-table th {
            padding: 0.75rem;
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .table-pagination-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }

        .table-pagination-info small {
            color: #6c757d;
        }

        .table-pagination-info .pagination .page-link {
            font-size: 0.85rem;
            padding: 0.4rem 0.7rem;
            border-radius: 0.3rem;
        }

        /* Responsive adjustments */
        @media (max-width: 767.98px) { /* Small devices (phones) */
            .reports-container {
                padding: 10px;
                margin: 10px auto;
            }
            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-group {
                min-width: 100%;
            }
            .kpi-row {
                grid-template-columns: 1fr;
            }
            .chart-grid {
                grid-template-columns: 1fr;
            }
            .chart-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
<div>
<!-- Top Header Section -->
<div class="header-top">
    <div class="left-info">
        <strong>Reportes de Ventas</strong> <br>
        Análisis detallado de ventas por sucursal y producto
    </div>
    <div class="nav-buttons">
        <button class="btn btn-outline-secondary">Panel de control</button>
        <button class="btn btn-outline-secondary">Punto de venta</button>
        <button class="btn btn-primary">Reportes de ventas</button>
    </div>
</div>

<div class="reports-container">
    <h2 class="section-title">Análisis de Ventas</h2>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="filter-group">
            <label for="dateRangeStart" class="form-label">Rango de fechas</label>
            <div class="input-group">
                <input type="date" class="form-control" id="dateRangeStart" value="2023-01-01">
                <span class="input-group-text">-</span>
                <input type="date" class="form-control" id="dateRangeEnd" value="2023-03-31">
            </div>
        </div>
        <div class="filter-group">
            <label for="sucursalSelect" class="form-label">Sucursal</label>
            <select class="form-select" id="sucursalSelect">
                <option>Todas las sucursales</option>
                <option>La Paz Centro</option>
                <option>El Alto</option>
                <option>Cochabamba</option>
                <option>Santa Cruz</option>
            </select>
        </div>
        <div class="filter-group">
            <label for="categorySelect" class="form-label">Categoría de producto</label>
            <select class="form-select" id="categorySelect">
                <option>Todas las categorías</option>
                <option>Lácteos</option>
                <option>Embutidos</option>
                <option>Quesos</option>
            </select>
        </div>
        <div>
            <button class="btn btn-primary">Aplicar filtros</button>
        </div>
    </div>

    <!-- KPI Row -->
    <div class="kpi-row">
        <div class="kpi-card">
            <div class="icon-placeholder"><i class="fas fa-dollar-sign"></i></div>
            <div class="kpi-label">Ventas totales</div>
            <div class="kpi-value">Bs. 1,458,320</div>
            <div class="kpi-sub">
                <span class="trend down"><i class="fas fa-arrow-trend-down"></i> 5%</span> vs. trimestre anterior
            </div>
        </div>
        <div class="kpi-card">
            <div class="icon-placeholder"><i class="fas fa-box"></i></div>
            <div class="kpi-label">Productos vendidos</div>
            <div class="kpi-value">24,856</div>
            <div class="kpi-sub">
                <span class="trend up"><i class="fas fa-arrow-trend-up"></i> 3%</span> vs. trimestre anterior
            </div>
        </div>
        <div class="kpi-card">
            <div class="icon-placeholder"><i class="fas fa-receipt"></i></div>
            <div class="kpi-label">Ticket promedio</div>
            <div class="kpi-value">Bs. 58.67</div>
            <div class="kpi-sub">
                <span class="trend down"><i class="fas fa-arrow-trend-down"></i> 1%</span> vs. trimestre anterior
            </div>
        </div>
    </div>

    <!-- Chart Grid Section -->
    <div class="chart-grid mb-4">
        <!-- Sales by Branch Chart -->
        <div class="chart-section">
            <div class="chart-header">
                <div class="title">Ventas por sucursal</div>
                <div class="chart-actions btn-group">
                    <button type="button" class="btn btn-outline-secondary btn-sm"><i class="fas fa-chart-bar"></i></button>
                    <button type="button" class="btn btn-outline-secondary btn-sm"><i class="fas fa-cog"></i></button>
                </div>
            </div>
            <div class="chart-placeholder">
                            </div>
        </div>
        <!-- Sales Trend Chart -->
        <div class="chart-section">
            <div class="chart-header">
                <div class="title">Tendencia de ventas</div>
                <div class="chart-actions btn-group">
                    <button type="button" class="btn btn-outline-secondary btn-sm"><i class="fas fa-chart-line"></i></button>
                    <button type="button" class="btn btn-outline-secondary btn-sm"><i class="fas fa-cog"></i></button>
                </div>
            </div>
            <div class="chart-placeholder">
                            </div>
        </div>
    </div>

    <!-- Detailed Sales Report Table -->
    <div class="chart-section">
        <h2 class="section-title">Reporte detallado de Ventas</h2>
        <div class="table-controls">
            <button class="btn btn-success">Excel</button>
            <button class="btn btn-info">PDF</button>
            <button class="btn btn-secondary">CSV</button>
            <button class="btn btn-light"><i class="fas fa-print"></i> Imprimir</button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover detailed-report-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Sucursal</th>
                        <th>Unidades</th>
                        <th>Precio Unit.</th>
                        <th>Total</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Queso Cheddar 250g</td>
                        <td>Lácteos</td>
                        <td>La Paz Centro</td>
                        <td>245</td>
                        <td>Bs. 18.50</td>
                        <td>Bs. 4,532.50</td>
                        <td>15/03/2023</td>
                    </tr>
                    <tr>
                        <td>Yogurt Natural 1L</td>
                        <td>Lácteos</td>
                        <td>El Alto</td>
                        <td>189</td>
                        <td>Bs. 12.75</td>
                        <td>Bs. 2,409.75</td>
                        <td>14/03/2023</td>
                    </tr>
                    <tr>
                        <td>Chorizo Parrillero 500g</td>
                        <td>Embutidos</td>
                        <td>Cochabamba</td>
                        <td>156</td>
                        <td>Bs. 25.00</td>
                        <td>Bs. 3,900.00</td>
                        <td>13/03/2023</td>
                    </tr>
                    <tr>
                        <td>Leche Entera 1L</td>
                        <td>Lácteos</td>
                        <td>Santa Cruz</td>
                        <td>312</td>
                        <td>Bs. 8.50</td>
                        <td>Bs. 2,652.00</td>
                        <td>12/03/2023</td>
                    </tr>
                    <tr>
                        <td>Queso Gouda 300g</td>
                        <td>Quesos</td>
                        <td>La Paz Centro</td>
                        <td>98</td>
                        <td>Bs. 32.00</td>
                        <td>Bs. 3,136.00</td>
                        <td>11/03/2023</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="table-pagination-info">
            <small>Mostrando 5 de 1,248 registros</small>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item disabled"><a class="page-link" href="#">«</a></li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">»</a></li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- Bottom Chart Grid Section -->
    <div class="chart-grid mb-4">
        <!-- Most Sold Products Chart -->
        <div class="chart-section">
            <div class="chart-header">
                <div class="title">Productos más vendidos</div>
                <div class="chart-actions btn-group">
                    <button type="button" class="btn btn-outline-secondary btn-sm"><i class="fas fa-chart-pie"></i></button>
                    <button type="button" class="btn btn-outline-secondary btn-sm"><i class="fas fa-cog"></i></button>
                </div>
            </div>
            <div class="chart-placeholder">
                            </div>
        </div>
        <!-- Sales by Category Chart -->
        <div class="chart-section">
            <div class="chart-header">
                <div class="title">Ventas por categoría</div>
                <div class="chart-actions btn-group">
                    <button type="button" class="btn btn-outline-secondary btn-sm"><i class="fas fa-chart-bar"></i></button>
                    <button type="button" class="btn btn-outline-secondary btn-sm"><i class="fas fa-cog"></i></button>
                </div>
            </div>
            <div class="chart-placeholder">
                            </div>
        </div>
    </div>

</div>

</div>

<!-- Bootstrap Bundle JS (para componentes como dropdowns, modales, etc.) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
