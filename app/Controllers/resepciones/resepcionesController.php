<?php

namespace App\Controllers\resepciones;

use App\Controllers\BaseController;
use App\Models\Envios\EnviosModel;
use App\Models\Sucursal\SucursalModel;
use App\Models\UsuarioModel;
use App\Models\Rol\RolModel;
use App\Models\Estado\EstadoModel;
use App\Models\Producto\ProductoModel;
use App\Models\TransferirProducto\TransferirProductosModel;
use App\Models\StockSucursal\StockSucursalModel;
use CodeIgniter\HTTP\RedirectResponse;

class resepcionesController extends BaseController
{
    protected $envioModel;
    protected $sucursalModel;
    protected $transferenciasModel;
    protected $userModel;
    protected $rolesModel;
    protected $detallesEnvioModel;
    protected $estadoModel;
    protected $productosModel;
    protected $stockSucursalModel;

    public function __construct()
    {
        $this->envioModel = new EnviosModel();
        $this->sucursalModel = new SucursalModel();
        $this->userModel = new UsuarioModel();
        $this->rolesModel = new RolModel();
        $this->estadoModel = new EstadoModel();
        $this->productosModel = new ProductoModel();
        $this->transferenciasModel = new TransferirProductosModel();
        $this->stockSucursalModel = new StockSucursalModel();
    }

    public function index()
    {
        $userSucursal = session()->get('sucursal_id');
        
        // Obtener filtros
        $estadoId = $this->request->getGet('estado');
        $fechaInicio = $this->request->getGet('fecha_inicio');
        $fechaFin = $this->request->getGet('fecha_fin');

        // Query base
        $builder = $this->envioModel->where('sucursal_destino_id', $userSucursal);

        // Aplicar filtros
        if (empty($fechaInicio) && empty($fechaFin)) {
            $fechaInicio = date('Y-m-d');
            $fechaFin = date('Y-m-d');
        }

        if (!empty($estadoId)) {
            $builder->where('estado_id', $estadoId);
        }
        if (!empty($fechaInicio)) {
            $builder->where('fecha_envio >=', $fechaInicio . ' 00:00:00');
        }
        if (!empty($fechaFin)) {
            $builder->where('fecha_envio <=', $fechaFin . ' 23:59:59');
        }

        // Obtener resultados filtrados
        $envios = $builder->findAll();

        // Calcular KPIs (siempre sobre el total o sobre lo filtrado? 
        // El usuario pidió "que se filtre... y tambn su reporte". 
        // Los KPIs suelen ser globales, pero si filtramos, quizás quieran ver KPIs filtrados.
        // Mantendré los KPIs globales para dar contexto, o filtrados?
        // Mejor calculamos KPIs sobre TODO el historial de la sucursal para que sean útiles como dashboard
        // y la tabla muestra lo filtrado.
        
        $allEnvios = $this->envioModel->where('sucursal_destino_id', $userSucursal)->findAll();
        
        $totalRecibidos = count($allEnvios);
        $pendientes = 0;
        $aceptados = 0;
        $entregados = 0;

        foreach ($allEnvios as $envioItem) {
            switch ($envioItem['estado_id']) {
                case 1: 
                case 2: 
                    $pendientes++;
                    break;
                case 9: 
                    $aceptados++;
                    break;
                case 3: 
                    $entregados++;
                    break;
            }
        }

        $data = [
            'envios' => $envios,
            'title'  => 'Envios',
            'productos' => $this->productosModel->getAllProductosWithRelations(),
            'sucursales' => $this->sucursalModel->findAll(),
            'transferencias' => $this->transferenciasModel->findAll(),
            'totalRecibidos' => $totalRecibidos,
            'pendientes' => $pendientes,
            'aceptados' => $aceptados,
            'entregados' => $entregados,
            'filters' => [
                'estado' => $estadoId,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin
            ]
        ];

        echo view('resepciones/resepcionesIndex', $data);
    }

    public function reporteGeneral()
    {
        $userSucursal = session()->get('sucursal_id');
        
        $estadoId = $this->request->getGet('estado');
        $fechaInicio = $this->request->getGet('fecha_inicio');
        $fechaFin = $this->request->getGet('fecha_fin');

        $builder = $this->envioModel->where('sucursal_destino_id', $userSucursal);

        if (!empty($estadoId)) {
            $builder->where('estado_id', $estadoId);
        }
        if (!empty($fechaInicio)) {
            $builder->where('fecha_envio >=', $fechaInicio);
        }
        if (!empty($fechaFin)) {
            $builder->where('fecha_envio <=', $fechaFin);
        }

        $envios = $builder->findAll();
        $sucursales = $this->sucursalModel->findAll();
        
        // Mapear nombres de sucursales
        $sucursalesMap = [];
        foreach ($sucursales as $s) {
            $sucursalesMap[$s['id']] = $s['nombre'];
        }

        // Obtener usuario actual
        $user = $this->userModel->find(session()->get('id'));
        $nombreUsuario = trim(($user['nombre'] ?? '') . ' ' . ($user['apellidos'] ?? ''));

        $pdf = new \App\Libraries\ReporteRecepcion();
        $pdf->generarReporteGeneral($envios, $sucursalesMap, [
            'estado' => $estadoId,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'usuario' => $nombreUsuario
        ]);
    }

    public function reporteDetallado($id)
    {
        $envio = $this->envioModel->find($id);
        if (!$envio) {
            return redirect()->back()->with('error', 'Envío no encontrado');
        }

        $sucursalOrigen = $this->sucursalModel->find($envio['sucursal_origen_id']);
        $sucursalDestino = $this->sucursalModel->find($envio['sucursal_destino_id']);
        
        // Obtener Creador (Enviado por)
        $userCreador = $this->userModel->find($envio['user_id']);
        $creadorNombre = $userCreador ? trim(($userCreador['nombre'] ?? '') . ' ' . ($userCreador['apellidos'] ?? '')) : 'N/A';
        $creadorCI = $userCreador['ci'] ?? 'N/A';

        // Obtener Transporte
        $userTransporte = $this->userModel->find($envio['user_transporte_id']);
        $transporteNombre = $userTransporte ? trim(($userTransporte['nombre'] ?? '') . ' ' . ($userTransporte['apellidos'] ?? '')) : 'N/A';
        $transporteCI = $userTransporte['ci'] ?? 'N/A';
        
        $transferencias = $this->transferenciasModel->where('envio_id', $id)->findAll();
        
        // Enriquecer transferencias con nombres de productos
        foreach ($transferencias as $key => $transfer) {
            $producto = $this->productosModel->find($transfer->producto_id);
            $transferencias[$key]->producto_nombre = $producto->nombre ?? 'Desconocido';
            $transferencias[$key]->producto_code = $producto->code ?? 'N/A';
            $transferencias[$key]->unidad = $producto->unidad ?? 'U';
        }

        // Obtener usuario actual (quien genera el reporte)
        $user = $this->userModel->find(session()->get('id'));
        $nombreUsuario = trim(($user['nombre'] ?? '') . ' ' . ($user['apellidos'] ?? ''));

        // Obtener usuario que recepcionó (si existe)
        $userRecepcion = null;
        if (!empty($envio['user_recepcion_id'])) {
            $userRecepcion = $this->userModel->find($envio['user_recepcion_id']);
        }
        
        $recepcionNombre = $userRecepcion ? trim(($userRecepcion['nombre'] ?? '') . ' ' . ($userRecepcion['apellidos'] ?? '')) : 'N/A';
        $recepcionCI = $userRecepcion['ci'] ?? 'N/A';

        $pdf = new \App\Libraries\ReporteRecepcion();
        $pdf->generarReporteDetallado($envio, $transferencias, [
            'sucursal_origen' => $sucursalOrigen['nombre'] ?? 'N/A',
            'sucursal_destino' => $sucursalDestino['nombre'] ?? 'N/A',
            'creador_nombre' => $creadorNombre,
            'creador_ci' => $creadorCI,
            'transporte_nombre' => $transporteNombre,
            'transporte_ci' => $transporteCI,
            'usuario' => $nombreUsuario,
            'recepcion_nombre' => $recepcionNombre,
            'recepcion_ci' => $recepcionCI
        ]);
    }

    public function exportarExcelDetallado($id)
    {
        $envio = $this->envioModel->find($id);
        if (!$envio) {
            return redirect()->back()->with('error', 'Envío no encontrado');
        }

        $sucursalOrigen = $this->sucursalModel->find($envio['sucursal_origen_id']);
        $sucursalDestino = $this->sucursalModel->find($envio['sucursal_destino_id']);
        $userCreador = $this->userModel->find($envio['user_id']);
        $userTransporte = $this->userModel->find($envio['user_transporte_id']);
        $userRecepcion = !empty($envio['user_recepcion_id']) ? $this->userModel->find($envio['user_recepcion_id']) : null;
        
        $transferencias = $this->transferenciasModel->where('envio_id', $id)->findAll();
        
        foreach ($transferencias as $key => $transfer) {
            $producto = $this->productosModel->find($transfer->producto_id);
            $transferencias[$key]->producto_nombre = $producto->nombre ?? 'Desconocido';
            $transferencias[$key]->producto_code = $producto->code ?? 'N/A';
        }

        $filename = 'recepcion_' . $envio['code'] . '_' . date('Ymd_His') . '.xls';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
        echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
        
        echo '<Styles>';
        echo '<Style ss:ID="titulo_uto"><Font ss:Bold="1" ss:Size="14" ss:Color="#1F4E78" ss:FontName="Calibri"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="subtitulo_uto"><Font ss:Bold="1" ss:Size="11" ss:Color="#1F4E78" ss:FontName="Calibri"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="info_uto"><Font ss:Size="9" ss:Color="#404040" ss:FontName="Calibri"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="header"><Font ss:Bold="1" ss:Size="11" ss:Color="#FFFFFF" ss:FontName="Calibri"/><Interior ss:Color="#2E5090" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#000000"/></Borders></Style>';
        echo '<Style ss:ID="subheader"><Font ss:Bold="1" ss:Size="10" ss:Color="#000000" ss:FontName="Calibri"/><Interior ss:Color="#D9E1F2" ss:Pattern="Solid"/><Alignment ss:Horizontal="Left" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="number"><NumberFormat ss:Format="#,##0.00"/><Alignment ss:Horizontal="Right" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="integer"><NumberFormat ss:Format="#,##0"/><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="center"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style>';
        echo '</Styles>';

        echo '<Worksheet ss:Name="Detalle Recepción">';
        echo '<Table>';
        echo '<Column ss:Width="250"/><Column ss:Width="200"/>';
        echo '<Row ss:Height="20"><Cell ss:MergeAcross="1" ss:StyleID="titulo_uto"><Data ss:Type="String">UNIVERSIDAD TÉCNICA DE ORURO</Data></Cell></Row>';
        echo '<Row ss:Height="18"><Cell ss:MergeAcross="1" ss:StyleID="subtitulo_uto"><Data ss:Type="String">FACULTAD DE CIENCIAS AGRARIAS Y NATURALES</Data></Cell></Row>';
        echo '<Row ss:Height="16"><Cell ss:MergeAcross="1" ss:StyleID="info_uto"><Data ss:Type="String">CONDORIRI - LABORATORIO DE INNOVACIÓN</Data></Cell></Row>';
        echo '<Row ss:Height="14"><Cell ss:MergeAcross="1" ss:StyleID="info_uto"><Data ss:Type="String">Telf.: 5281745 – Interno: 120 | FAX: 5242215 | Casilla 49</Data></Cell></Row>';
        echo '<Row ss:Height="14"><Cell ss:MergeAcross="1" ss:StyleID="info_uto"><Data ss:Type="String">Email: dpdi@uto.edu.bo | www.uto.edu.bo</Data></Cell></Row>';
        echo '<Row></Row>';
        echo '<Row ss:Height="22"><Cell ss:MergeAcross="1" ss:StyleID="header"><Data ss:Type="String">DETALLE DE RECEPCIÓN - ' . htmlspecialchars($envio['code'], ENT_XML1) . '</Data></Cell></Row>';
        echo '<Row></Row>';
        
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Código Envío:</Data></Cell><Cell><Data ss:Type="String">' . htmlspecialchars($envio['code'], ENT_XML1) . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Sucursal Origen:</Data></Cell><Cell><Data ss:Type="String">' . htmlspecialchars($sucursalOrigen['nombre'] ?? 'N/A', ENT_XML1) . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Sucursal Destino:</Data></Cell><Cell><Data ss:Type="String">' . htmlspecialchars($sucursalDestino['nombre'] ?? 'N/A', ENT_XML1) . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Enviado por:</Data></Cell><Cell><Data ss:Type="String">' . htmlspecialchars(trim(($userCreador['nombre'] ?? '') . ' ' . ($userCreador['apellidos'] ?? '')), ENT_XML1) . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Transporte:</Data></Cell><Cell><Data ss:Type="String">' . htmlspecialchars(trim(($userTransporte['nombre'] ?? '') . ' ' . ($userTransporte['apellidos'] ?? '')), ENT_XML1) . '</Data></Cell></Row>';
        echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Fecha Envío:</Data></Cell><Cell><Data ss:Type="String">' . date('d/m/Y H:i', strtotime($envio['fecha_envio'])) . '</Data></Cell></Row>';
        if ($userRecepcion) {
            echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Recepcionado por:</Data></Cell><Cell><Data ss:Type="String">' . htmlspecialchars(trim(($userRecepcion['nombre'] ?? '') . ' ' . ($userRecepcion['apellidos'] ?? '')), ENT_XML1) . '</Data></Cell></Row>';
            echo '<Row><Cell ss:StyleID="subheader"><Data ss:Type="String">Fecha Recepción:</Data></Cell><Cell><Data ss:Type="String">' . date('d/m/Y H:i', strtotime($envio['fecha_recepcion'])) . '</Data></Cell></Row>';
        }
        echo '<Row></Row>';

        echo '<Row>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Código Producto</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Producto</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Cantidad Enviada</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Cantidad Aceptada</Data></Cell>';
        echo '<Cell ss:StyleID="header"><Data ss:Type="String">Observaciones</Data></Cell>';
        echo '</Row>';

        foreach ($transferencias as $item) {
            echo '<Row>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($item->producto_code ?? 'N/A', ENT_XML1) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($item->producto_nombre, ENT_XML1) . '</Data></Cell>';
            echo '<Cell ss:StyleID="integer"><Data ss:Type="Number">' . ($item->cantidad ?? 0) . '</Data></Cell>';
            echo '<Cell ss:StyleID="integer"><Data ss:Type="Number">' . ($item->cantidad_acep ?? 0) . '</Data></Cell>';
            echo '<Cell><Data ss:Type="String">' . htmlspecialchars($item->observacion_destino ?? '', ENT_XML1) . '</Data></Cell>';
            echo '</Row>';
        }
        
        echo '</Table></Worksheet>';
        echo '</Workbook>';
        exit;
    }

    public function show(int $id)
    {
        $envio = $this->envioModel->find($id);

        if (!$envio) {
            return redirect()->to('/envios')->with('error', 'Envío no encontrado.');
        }

        $sucursalOrigen = $this->sucursalModel->find($envio['sucursal_origen_id']);
        $sucursalDestino = $this->sucursalModel->find($envio['sucursal_destino_id']);
        $userTransporte = $this->userModel->find($envio['user_transporte_id']);
        $userCreador = $this->userModel->find($envio['user_id']);
        $userRecepcion = $envio['user_recepcion_id'] ? $this->userModel->find($envio['user_recepcion_id']) : null;
        $estado = $this->estadoModel->find($envio['estado_id']);

        $transferencias = $this->transferenciasModel
            ->where('envio_id', $id)
            ->findAll();

        foreach ($transferencias as $key => $transfer) {
            $producto = $this->productosModel->find($transfer->producto_id);
            $transferencias[$key]->producto_nombre = $producto->nombre ?? 'Desconocido';
            $transferencias[$key]->producto_code = $producto->code ?? 'N/A';
            $transferencias[$key] = (object) $transferencias[$key];
        }

        $data = [
            'envio'           => (object) $envio,
            'productos'       => $this->productosModel->getAllProductosWithRelations(),
            'transferencias'  => $transferencias,
            'sucursalOrigen'  => (object) $sucursalOrigen,
            'sucursalDestino' => (object) $sucursalDestino,
            'userTransporte'  => $userTransporte ? (object) $userTransporte : null,
            'userCreador'     => $userCreador ? (object) $userCreador : null,
            'userRecepcion'   => $userRecepcion ? (object) $userRecepcion : null,
            'estado'          => $estado ? (object) $estado : null,
            'title'           => 'Detalle del Envío',
        ];

        echo view('resepciones/resepcionesShow', $data);
    }

 public function recepcionar(int $envioId)
{
    if (!$this->request->is('post')) {
        return redirect()->back()->with('error', 'Método no permitido.');
    }

    $productos = $this->request->getPost('productos');
    $userId = session()->get('id');

    if (empty($productos) || !is_array($productos)) {
        return redirect()->back()->with('error', 'No se recibieron productos para procesar.');
    }

    $envio = $this->envioModel->find($envioId);
    if (!$envio) {
        return redirect()->back()->with('error', 'Envío principal no encontrado.');
    }

    // ✅ Detectar si es sucursal especial (ID = 4)
    $destinoId = $envio['sucursal_destino_id'];
    $esSucursalEspecial = ($destinoId == 4);

    $this->transferenciasModel->db->transBegin();

    try {
        foreach ($productos as $transferenciaId => $data) {
            $cantidadRecibida = (int)($data['cantidad_recibida'] ?? 0);
            $aceptar = !empty($data['aceptar']) && $data['aceptar'] == 1 && $cantidadRecibida > 0;
            $observacion = $data['observacion'] ?? '';

            $transfer = $this->transferenciasModel->find($transferenciaId);
            if (!$transfer) {
                continue;
            }

            $productoId = $transfer->producto_id;
            $transferState = $aceptar ? 3 : 10;

            if ($aceptar) {
                $productDetails = $this->productosModel->find($productoId);
                if (!$productDetails) {
                    throw new \Exception("Detalles del producto ID {$productoId} no encontrados.");
                }

                // ✅ LÓGICA DIFERENCIADA SEGÚN SUCURSAL
                if ($esSucursalEspecial) {
                    // 🔹 SUCURSAL 4: Actualizar directamente en `productos`
                    $nuevoDevo = ($productDetails->cantidad_devo ?? 0) + $cantidadRecibida;
                    $nuevoStockInve = ($productDetails->stock_inve ?? 0) + $cantidadRecibida;

                    if ($this->productosModel->update($productoId, [
                        'cantidad_devo' => $nuevoDevo,
                        'stock_inve'    => $nuevoStockInve,
                        // Opcional: registrar quién y cuándo actualizó
                        'updated_at'    => date('Y-m-d H:i:s'),
                    ]) === false) {
                        $errors = $this->productosModel->errors();
                        throw new \Exception("Fallo al actualizar producto ID {$productoId}: " . print_r($errors, true));
                    }

                } else {
                    // 🔹 OTRAS SUCURSALES: Actualizar `stock_sucursales` (flujo original)
                    $existingStock = $this->stockSucursalModel
                        ->where('producto_id', $productoId)
                        ->where('sucursal_id', $destinoId)
                        ->first();

                    if ($existingStock) {
                        $newStock = $existingStock['stock'] + $cantidadRecibida;
                        $newCantidad = $existingStock['cantidad'] + $cantidadRecibida;

                        if ($this->stockSucursalModel->update($existingStock['id'], [
                            'stock' => $newStock,
                            'cantidad' => $newCantidad,
                            'user_id' => $userId,
                        ]) === false) {
                            $errors = $this->stockSucursalModel->errors();
                            throw new \Exception("Fallo al actualizar stock existente: " . print_r($errors, true));
                        }
                    } else {
                        $precioContado = $productDetails->precio_contado ?? 0.00;
                        $precioCredito = $productDetails->precio_credito ?? 0.00;
                        $nombreProducto = $productDetails->nombre ?? null;
                        $categoria = $productDetails->categoria ?? null;
                        $unidad = $productDetails->unidad ?? null;

                        $insertData = [
                            'producto_id'    => $productoId,
                            'sucursal_id'    => $destinoId,
                            'cantidad'       => $cantidadRecibida,
                            'stock'          => $cantidadRecibida,
                            'precio_contado' => $precioContado,
                            'precio_credito' => $precioCredito,
                            'user_id'        => $userId,
                            'producto'       => $nombreProducto,
                            'categoria'      => $categoria,
                            'unidad'         => $unidad,
                        ];

                        if ($this->stockSucursalModel->insert($insertData) === false) {
                            $dbError = $this->stockSucursalModel->db()->error();
                            $errorMessage = "Fallo al insertar stock. DB: ({$dbError['code']}) {$dbError['message']}";
                            throw new \Exception($errorMessage);
                        }
                    }
                }
            }

            // ✅ Actualizar transferencia (igual para todos)
            $updateData = [
                'cantidad_acep'       => $cantidadRecibida,
                'user_recepcion_id'   => $userId,
                'observacion_destino' => $observacion,
                'estado_id'           => $transferState,
            ];

            if ($this->transferenciasModel->update($transferenciaId, $updateData) === false) {
                $errors = $this->transferenciasModel->errors();
                throw new \Exception("Fallo al actualizar la transferencia ID {$transferenciaId}: " . print_r($errors, true));
            }
        }

        // ✅ Actualizar envío principal (igual para todos)
        $updateEnvioData = [
            'estado_id'          => 9,
            'user_recepcion_id'  => $userId,
            'fecha_recepcion'    => date('Y-m-d H:i:s'),
        ];

        if ($this->envioModel->skipValidation()->update($envioId, $updateEnvioData) === false) {
            $dbError = $this->envioModel->db()->error();
            throw new \Exception("Error al actualizar envío: ({$dbError['code']}) {$dbError['message']}");
        }

        $this->transferenciasModel->db->transCommit();

        return redirect()->to(base_url("resepciones/show/{$envioId}"))
            ->with('message', $esSucursalEspecial 
                ? 'Recepción confirmada. Stock maestro actualizado.'
                : 'Recepción confirmada y stock de sucursal actualizado.');

    } catch (\Exception $e) {
        $this->transferenciasModel->db->transRollback();
        log_message('error', '[Recepción] ' . $e->getMessage());
        return redirect()->back()->with('error', 'Error al procesar recepción: ' . $e->getMessage());
    }
}



}