<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <style>
        /* Estilos base de la página, invisible bajo el modal */
        body {
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 9px;
            color: #000;
        }

        /* --- ESTILOS DEL MODAL (Overlay de pantalla completa) --- */
        #print-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        /* --- CONTENEDOR DEL RECIBO (Simulando 80mm de ancho) --- */
        #receipt-container {
            background-color: #fff;
            width: 300px; 
            padding: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.5);
            border-radius: 4px;
            max-height: 95vh;
            overflow-y: auto;
        }

        /* --- ESTILOS DEL RECIBO INTERNO --- */
        .header, .footer {
            text-align: center;
            margin-bottom: 8px;
        }
        .header h1 {
            font-size: 14px;
            margin: 0 0 3px 0;
            font-weight: bold;
        }
        .info p {
            margin: 1px 0;
            line-height: 1.2;
        }
        .details table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .details th, .details td {
            text-align: left;
            padding: 2px 0;
            border-bottom: 1px dashed #777;
        }
        .details th {
            font-weight: bold;
            font-size: 9px;
        }
        .total {
            text-align: right;
            margin-top: 10px;
            border-top: 1px solid #000; 
            padding-top: 5px;
        }
        .total p {
            font-size: 11px;
            font-weight: bold;
            margin: 0;
        }
        .cliente-info {
            margin: 8px 0;
            padding: 5px 0;
            border-top: 1px dashed #000; 
            border-bottom: 1px dashed #000;
        }
        .gracias {
            margin-top: 12px;
            font-style: italic;
            font-size: 10px;
        }

        /* --- ESTILOS DE CONTROL DEL MODAL (Botones) --- */
        .modal-controls {
            text-align: center;
            padding: 10px 0 0;
            margin-top: 10px;
        }
        .modal-controls button {
            padding: 6px 12px;
            margin: 0 5px;
            cursor: pointer;
            border: none;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
        }
        #print-button {
            background-color: #007bff;
            color: white;
        }
        #close-button {
            background-color: #6c757d;
            color: white;
        }
        
        @media print {
            body > * {
                visibility: hidden;
                display: none;
            }
            
            #print-modal, #receipt-container, #receipt-container * {
                visibility: visible;
                display: block;
            }
            
            #print-modal {
                position: absolute;
                left: 0;
                top: 0;
                background: none;
            }
            #receipt-container {
                width: 100%;
                box-shadow: none;
                margin: 0;
                padding: 0;
                max-height: none;
                overflow-y: visible;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<?php
$currentPath = $_SERVER['REQUEST_URI'];
$userRoleName = session()->get('rol_nombre');
$userSucursalName = session()->get('sucursal_nombre');

?>
<body>

    <div id="print-modal">
        <div id="receipt-container">
            <div class="header">
                <h1><?= esc($userSucursalName) ?></h1>
                <p><?= esc($sucursal['direccion']) ?></p>
                <p>Tel: <?= esc($sucursal['telefono']) ?> | Recibo</p>
                <p>-----------------------------------</p>
            </div>

            <div class="info">
                <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($venta->created_at)) ?></p>
                <p><strong>Venta ID:</strong> <?= esc($venta->id) ?></p>
                <p><strong>Código:</strong> <?= esc($venta->code) ?></p>
                <p><strong>Tipo Pago:</strong> <?= esc(ucfirst($venta->tipo_pago)) ?></p>
            </div>
            
          
            <div class="cliente-info">
                <?php if (!empty($venta->personal_uto_id) && !empty($personal)): ?>
                  
                    <p><strong>Personal UTO:</strong> <?= esc($personal['nombre'] ?? 'N/A') ?></p>
                    <p><strong>DIP:</strong> <?= esc($personal['dip'] ?? 'N/A') ?></p>
                    <p><strong>Cargo:</strong> <?= esc($personal['cargo'] ?? 'Sin cargo') ?></p>
                    <p><strong>Sección:</strong> <?= esc($personal['seccion'] ?? 'Sin sección') ?></p>
                <?php elseif (!empty($cliente)): ?>
                    
                    <p><strong>Cliente:</strong> <?= esc($cliente['nombre_completo'] ?? 'N/A') ?></p>
                    <p><strong>Documento:</strong> <?= esc($cliente['ci_nit'] ?? 'N/A') ?></p>
                <?php else: ?>
                   
                    <p><strong>Cliente:</strong> Consumidor Final</p>
                <?php endif; ?>
            </div>

            <div class="details">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 15%;">Cant.</th>
                            <th style="width: 45%;">Producto</th>
                            <th style="width: 20%; text-align: right;">P.U.</th>
                            <th style="width: 20%; text-align: right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalles as $item): ?>
                            <tr>
                                <td><?= esc($item['cantidad']) ?></td>
                                <td><?= esc($item['producto'] ?? 'Producto Desconocido') ?></td>
                                <td style="text-align: right;"><?= number_format($item['precio_unitario'], 2) ?></td>
                                <td style="text-align: right;"><?= number_format($item['subtotal'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="total">
                <p>TOTAL PAGADO: <?= number_format($venta->monto_total, 2) ?> Bs</p>
            </div>

            <div class="footer">
                <p>-----------------------------------</p>
                <p class="gracias">¡Gracias por su compra!</p>
                <?php if (!empty($venta->personal_uto_id)): ?>
                    <p style="font-size:9px; margin-top:5px;">Venta a crédito para personal UTO.</p>
                <?php endif; ?>
            </div>

            <div class="modal-controls no-print">
                <button id="print-button">Imprimir Recibo</button>
                <button id="close-button">Cerrar</button>
            </div>

        </div>
    </div>

    <script>
        window.onload = function() {
            const printButton = document.getElementById('print-button');
            const closeButton = document.getElementById('close-button');

            printButton.addEventListener('click', function() {
                window.print();
            });

            closeButton.addEventListener('click', function() {
                window.location.href = '/productosagro/ventas'; 
            });
        };
    </script>
</body>
</html>