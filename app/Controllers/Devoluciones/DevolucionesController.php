<?php

namespace App\Controllers\Devoluciones;

use App\Controllers\BaseController;
use App\Models\Devoluciones\DevolucionesModel;
use App\Models\Sucursal\SucursalModel;
use App\Models\Producto\ProductoModel;
use App\Models\StockSucursal\StockSucursalModel;
use App\Models\UsuarioModel;
use CodeIgniter\HTTP\RedirectResponse;

class DevolucionesController extends BaseController
{
    protected $devolucionesModel;
    protected $sucursalModel;
    protected $productoModel;
    protected $stockSucursalModel;
    protected $userModel;

    public function __construct()
    {
        $this->devolucionesModel = new DevolucionesModel();
        $this->sucursalModel = new SucursalModel();
        $this->productoModel = new ProductoModel();
        $this->stockSucursalModel = new StockSucursalModel();
        $this->userModel = new UsuarioModel();
    }

    public function index()
    {
        $data = [
            'devoluciones' => $this->devolucionesModel->getDevolucionesWithDetails(),
            'title'        => 'Listado de Devoluciones'
        ];

        echo view('devoluciones/devolucionesIndex', $data);
    }

    public function register()
    {
        $sucursal_id = session()->get('sucursal_id');
        
        // Get products available in the current branch's stock
        $stockItems = $this->stockSucursalModel->where('sucursal_id', $sucursal_id)->where('stock >', 0)->findAll();
        
        $productos = [];
        foreach ($stockItems as $item) {
            $prod = $this->productoModel->find($item['producto_id']);
            if ($prod) {
                $prod->stock_actual = $item['stock']; 
                $productos[] = $prod;
            }
        }

        // Get all sucursales for destination selection, excluding current one
        $sucursales = $this->sucursalModel->where('id !=', $sucursal_id)->findAll();

        $data = [
            'title'       => 'Registrar Devolución',
            'sucursal_id' => $sucursal_id,
            'productos'   => $productos,
            'sucursales'  => $sucursales,
            'user_id'     => session()->get('id')
        ];

        echo view('devoluciones/devolucionesForm', $data);
    }

    public function create()
    {
        $rules = $this->devolucionesModel->getValidationRules();
        
        // Remove fields that are not in the form but set by system
        unset($rules['user_id']);
        unset($rules['estado_id']);

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $sucursal_id = $this->request->getPost('sucursales_id');
        $sucursal_destino_id = $this->request->getPost('sucursales_destino_id');
        $producto_id = $this->request->getPost('producto_id');
        $cantidad    = (int)$this->request->getPost('cantidad');
        $observacion = $this->request->getPost('observacion');
        $user_id     = session()->get('id');

        // Generate Code
        $code = 'DEV-' . date('Ymd') . '-' . rand(1000, 9999);

        $data = [
            'code'                  => $code,
            'sucursales_id'         => $sucursal_id,
            'sucursales_destino_id' => $sucursal_destino_id,
            'producto_id'           => $producto_id,
            'cantidad'              => $cantidad,
            'observacion'           => $observacion,
            'user_id'               => $user_id,
            'estado_id'             => 1, // Pendiente
            'stock_sucursales_id'   => null // Will be filled upon confirmation
        ];

        if ($this->devolucionesModel->save($data)) {
            return redirect()->to('/envios/devoluciones')->with('message', 'Devolución registrada exitosamente (Pendiente).');
        } else {
            return redirect()->back()->withInput()->with('error', 'Error al registrar la devolución.');
        }
    }

    public function show($id)
    {
        $devolucion = $this->devolucionesModel->getDevolucionWithDetails($id);

        if (!$devolucion) {
            return redirect()->to('/envios/devoluciones')->with('error', 'Devolución no encontrada.');
        }

        $data = [
            'devolucion' => $devolucion,
            'title'      => 'Detalle de Devolución'
        ];

        echo view('devoluciones/devolucionesShow', $data);
    }

    public function confirmarEnvio($id)
    {
        $devolucion = $this->devolucionesModel->find($id);

        if (!$devolucion) {
            return redirect()->back()->with('error', 'Devolución no encontrada.');
        }

        if ($devolucion['estado_id'] != 1) {
            return redirect()->back()->with('error', 'La devolución ya ha sido procesada.');
        }

        
        $stockItem = $this->stockSucursalModel
            ->where('sucursal_id', $devolucion['sucursales_id'])
            ->where('producto_id', $devolucion['producto_id'])
            ->first();

        if (!$stockItem || $stockItem['stock'] < $devolucion['cantidad']) {
            return redirect()->back()->with('error', 'Stock insuficiente para confirmar la devolución.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            
            $nuevoStock = $stockItem['stock'] - $devolucion['cantidad'];
            $this->stockSucursalModel->update($stockItem['id'], ['stock' => $nuevoStock]);

           
            $this->devolucionesModel->update($id, [
                'estado_id'           => 2, 
                'stock_sucursales_id' => $stockItem['id'], 
                'updated_at'          => date('Y-m-d H:i:s')
            ]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Error en la transacción.');
            }

            return redirect()->to("/envios/devoluciones/show/$id")->with('message', 'Devolución confirmada y stock descontado.');

        } catch (\Exception $e) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Error al confirmar: ' . $e->getMessage());
        }
    }
    
    public function generarRecibo($id)
    {
        $devolucion = $this->devolucionesModel->getDevolucionWithDetails($id);

        if (!$devolucion) {
            return redirect()->back()->with('error', 'Devolución no encontrada.');
        }

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => [80, 200],
            'margin_top' => 5,
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_bottom' => 5,
            'default_font_size' => 9,
            'default_font' => 'Arial'
        ]);

        $html = view('devoluciones/reciboDevolucion', ['devolucion' => $devolucion]);
        $mpdf->WriteHTML($html);
        
        $this->response->setHeader('Content-Type', 'application/pdf');
        $mpdf->Output('Recibo_Devolucion_' . $devolucion['id'] . '.pdf', 'I');
    }
}


