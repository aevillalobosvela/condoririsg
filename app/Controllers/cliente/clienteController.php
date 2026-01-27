<?php

namespace App\Controllers\cliente;

use App\Controllers\BaseController;
use App\Models\Cliente\ClienteModel;
use CodeIgniter\HTTP\RedirectResponse;

class clienteController extends BaseController
{
    protected $clienteModel;

    public function __construct()
    {
        $this->clienteModel = new ClienteModel();
    }

    /**
     * Muestra la lista de todos los clientes.
     */
    public function index()
    {
        $search = $this->request->getGet('search');
        
        if ($search) {
            $clientes = $this->clienteModel->like('nombre_completo', $search)
                                           ->orLike('ci_nit', $search)
                                           ->findAll();
        } else {
            $clientes = $this->clienteModel->findAll();
        }

        $data = [
            'title'    => 'Gestión de Clientes',
            'clientes' => $clientes,
            'search'   => $search
        ];

        return view('cliente/index', $data);
    }

    /**
     * Obtiene los datos de un cliente por ID (AJAX).
     */
    public function get($id)
    {
        $cliente = $this->clienteModel->find($id);

        if ($cliente) {
            return $this->response->setJSON(['success' => true, 'cliente' => $cliente]);
        } else {
            return $this->response->setJSON(['success' => false, 'error' => 'Cliente no encontrado']);
        }
    }

    /**
     * Actualiza los datos de un cliente.
     */
    public function update()
    {
        $id = $this->request->getPost('id');
        $nombreCompleto = $this->request->getPost('nombre_completo');
        $ciNit = $this->request->getPost('ci_nit');

        if (empty($id)) {
            return $this->response->setJSON(['success' => false, 'error' => 'ID de cliente no proporcionado']);
        }

        $data = [
            'nombre_completo' => !empty($nombreCompleto) ? strtoupper(trim($nombreCompleto)) : '',
            'ci_nit'          => !empty($ciNit) ? strtoupper(trim($ciNit)) : '',
        ];

        if (empty($data['nombre_completo']) || empty($data['ci_nit'])) {
            return $this->response->setJSON(['success' => false, 'error' => 'El nombre completo y CI/NIT son obligatorios.']);
        }

        if ($this->clienteModel->update($id, $data)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Cliente actualizado exitosamente.']);
        } else {
            return $this->response->setJSON(['success' => false, 'error' => 'Error al actualizar el cliente.']);
        }
    }

    /**
     * Crea un nuevo cliente.
     */
    public function create()
    {
        $nombreCompleto = $this->request->getPost('nombre_completo');
        $ciNit = $this->request->getPost('ci_nit');

        $data = [
            'nombre_completo' => !empty($nombreCompleto) ? strtoupper(trim($nombreCompleto)) : '',
            'ci_nit'          => !empty($ciNit) ? strtoupper(trim($ciNit)) : '',
            'estado'          => true,
            'user_id'         => session()->get('id'),
        ];

        if (empty($data['nombre_completo']) || empty($data['ci_nit'])) {
            return redirect()->back()->withInput()->with('error', 'El nombre completo y CI/NIT son obligatorios.');
        }

        if ($this->clienteModel->save($data)) {
            return redirect()->to('/ventas/register')->with('success', 'Cliente creado exitosamente.');
        } else {
            $errors = $this->clienteModel->errors();
            return redirect()->back()->withInput()->with('error', 'Error al crear el cliente: ' . implode(', ', $errors));
        }
    }

    public function registerCliente()
    {
        $nombreCompleto = $this->request->getPost('nombre_completo');
        $ciNit = $this->request->getPost('ci_nit');

        $data = [
            'nombre_completo' => !empty($nombreCompleto) ? strtoupper(trim($nombreCompleto)) : '',
            'ci_nit'          => !empty($ciNit) ? strtoupper(trim($ciNit)) : '',
            'estado'          => true,
            'user_id'         => session()->get('id'),
        ];

        if (empty($data['nombre_completo']) || empty($data['ci_nit'])) {
            return redirect()->back()->withInput()->with('error', 'El nombre completo y CI/NIT son obligatorios.');
        }

        if ($this->clienteModel->save($data)) {
            return redirect()->to('/productosagro/registerVentas')->with('success', 'Cliente creado exitosamente.');
        } else {
            $errors = $this->clienteModel->errors();
            return redirect()->back()->withInput()->with('error', 'Error al crear el cliente: ' . implode(', ', $errors));
        }
    }

    public function registerClienteInve()
    {
        $nombreCompleto = $this->request->getPost('nombre_completo');
        $ciNit = $this->request->getPost('ci_nit');

        $data = [
            'nombre_completo' => !empty($nombreCompleto) ? strtoupper(trim($nombreCompleto)) : '',
            'ci_nit'          => !empty($ciNit) ? strtoupper(trim($ciNit)) : '',
            'estado'          => true,
            'user_id'         => session()->get('id'),
        ];

        if (empty($data['nombre_completo']) || empty($data['ci_nit'])) {
            return redirect()->back()->withInput()->with('error', 'El nombre completo y CI/NIT son obligatorios.');
        }

        if ($this->clienteModel->save($data)) {
            return redirect()->to('/inventarios/registerVenta')->with('success', 'Cliente creado exitosamente.');
        } else {
            $errors = $this->clienteModel->errors();
            return redirect()->back()->withInput()->with('error', 'Error al crear el cliente: ' . implode(', ', $errors));
        }
    }
}
