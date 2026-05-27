<?php

namespace App\Controllers\cliente;

use App\Controllers\BaseController;
use App\Models\Cliente\ClienteModel;
use App\Models\ClienteExterno\ClienteExternoModel;
use CodeIgniter\HTTP\RedirectResponse;

class clienteController extends BaseController
{
    protected $clienteModel;
    protected $clienteExternoModel;

    public function __construct()
    {
        $this->clienteModel        = new ClienteModel();
        $this->clienteExternoModel = new ClienteExternoModel();
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
     * Actualiza los datos de un cliente (último registrado por el usuario, mismo día).
     */
    public function update()
    {
        $id     = $this->request->getPost('id');
        $ciNit  = $this->request->getPost('ci_nit');
        $userId = session()->get('id');

        if (empty($id)) {
            return $this->response->setJSON(['success' => false, 'error' => 'ID de cliente no proporcionado.']);
        }

        $apellidoPaterno = strtoupper(trim($this->request->getPost('apellido_paterno') ?? ''));
        $apellidoMaterno = strtoupper(trim($this->request->getPost('apellido_materno') ?? ''));
        $nombres         = strtoupper(trim($this->request->getPost('nombres') ?? ''));

        if (empty($apellidoPaterno) || empty($nombres)) {
            return $this->response->setJSON(['success' => false, 'error' => 'El apellido paterno y el nombre son obligatorios.']);
        }

        if (empty($ciNit)) {
            return $this->response->setJSON(['success' => false, 'error' => 'El CI/NIT es obligatorio.']);
        }

        $nombreCompleto = trim(implode(' ', array_filter([$apellidoPaterno, $apellidoMaterno, $nombres])));

        // Verificar que sea el último cliente registrado por este usuario hoy
        $ultimo = $this->clienteModel
            ->where('user_id', $userId)
            ->where('deleted_at', null)
            ->orderBy('id', 'DESC')
            ->first();

        if (!$ultimo || (int)$ultimo['id'] !== (int)$id) {
            return $this->response->setJSON(['success' => false, 'error' => 'Solo puede editar el último cliente que usted registró.']);
        }

        if (date('Y-m-d', strtotime($ultimo['created_at'])) !== date('Y-m-d')) {
            return $this->response->setJSON(['success' => false, 'error' => 'Solo puede editar clientes registrados el día de hoy.']);
        }

        $data = [
            'nombre_completo' => $nombreCompleto,
            'ci_nit'          => strtoupper(trim($ciNit)),
        ];

        if ($this->clienteModel->update($id, $data)) {
            return $this->response->setJSON([
                'success'         => true,
                'message'         => 'Cliente actualizado exitosamente.',
                'nombre_completo' => $data['nombre_completo'],
                'ci_nit'          => $data['ci_nit'],
            ]);
        }

        return $this->response->setJSON(['success' => false, 'error' => 'Error al actualizar el cliente.']);
    }

    /**
     * Actualiza los datos de un cliente externo (último registrado por el usuario, mismo día).
     */
    public function updateExterno()
    {
        $id       = $this->request->getPost('id');
        $nombre   = $this->request->getPost('nombre');
        $dip      = $this->request->getPost('dip');
        $segmento = $this->request->getPost('segmento');
        $userId   = session()->get('id');

        if (empty($id)) {
            return $this->response->setJSON(['success' => false, 'error' => 'ID de cliente no proporcionado.']);
        }

        $ultimo = $this->clienteExternoModel
            ->where('user_id', $userId)
            ->where('deleted_at', null)
            ->orderBy('id', 'DESC')
            ->first();

        if (!$ultimo || (int)$ultimo->id !== (int)$id) {
            return $this->response->setJSON(['success' => false, 'error' => 'Solo puede editar el último cliente externo que usted registró.']);
        }

        if (date('Y-m-d', strtotime($ultimo->created_at)) !== date('Y-m-d')) {
            return $this->response->setJSON(['success' => false, 'error' => 'Solo puede editar clientes registrados el día de hoy.']);
        }

        $data = [
            'nombre'   => !empty($nombre)   ? strtoupper(trim($nombre))  : '',
            'dip'      => !empty($dip)       ? trim($dip)                 : '',
            'segmento' => !empty($segmento)  ? trim($segmento)            : '',
        ];

        if (empty($data['nombre']) || empty($data['dip']) || empty($data['segmento'])) {
            return $this->response->setJSON(['success' => false, 'error' => 'Nombre, DIP y segmento son obligatorios.']);
        }

        if ($this->clienteExternoModel->update($id, $data)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Cliente externo actualizado exitosamente.',
                'nombre'  => $data['nombre'],
                'dip'     => $data['dip'],
            ]);
        }

        return $this->response->setJSON(['success' => false, 'error' => 'Error al actualizar el cliente externo.']);
    }

    /**
     * Crea un nuevo cliente.
     */
    public function create()
    {
        $apellidoPaterno = strtoupper(trim($this->request->getPost('apellido_paterno') ?? ''));
        $apellidoMaterno = strtoupper(trim($this->request->getPost('apellido_materno') ?? ''));
        $nombres         = strtoupper(trim($this->request->getPost('nombres') ?? ''));
        $ciNit           = strtoupper(trim($this->request->getPost('ci_nit') ?? ''));

        if (empty($apellidoPaterno) || empty($nombres)) {
            return redirect()->back()->withInput()->with('error', 'El apellido paterno y el nombre son obligatorios.');
        }

        if (empty($ciNit)) {
            return redirect()->back()->withInput()->with('error', 'El nombre completo y CI/NIT son obligatorios.');
        }

        $nombreCompleto = trim(implode(' ', array_filter([$apellidoPaterno, $apellidoMaterno, $nombres])));

        $data = [
            'nombre_completo' => $nombreCompleto,
            'ci_nit'          => $ciNit,
            'estado'          => true,
            'user_id'         => session()->get('id'),
        ];

        if ($this->clienteModel->save($data)) {
            return redirect()->to('/ventas/register')->with('success', 'Cliente creado exitosamente.');
        } else {
            $errors = $this->clienteModel->errors();
            return redirect()->back()->withInput()->with('error', 'Error al crear el cliente: ' . implode(', ', $errors));
        }
    }

    public function registerCliente()
    {
        $apellidoPaterno = strtoupper(trim($this->request->getPost('apellido_paterno') ?? ''));
        $apellidoMaterno = strtoupper(trim($this->request->getPost('apellido_materno') ?? ''));
        $nombres         = strtoupper(trim($this->request->getPost('nombres') ?? ''));
        $ciNit           = strtoupper(trim($this->request->getPost('ci_nit') ?? ''));

        if (empty($apellidoPaterno) || empty($nombres)) {
            return redirect()->back()->withInput()->with('error', 'El apellido paterno y el nombre son obligatorios.');
        }

        if (empty($ciNit)) {
            return redirect()->back()->withInput()->with('error', 'El nombre completo y CI/NIT son obligatorios.');
        }

        $nombreCompleto = trim(implode(' ', array_filter([$apellidoPaterno, $apellidoMaterno, $nombres])));

        $data = [
            'nombre_completo' => $nombreCompleto,
            'ci_nit'          => $ciNit,
            'estado'          => true,
            'user_id'         => session()->get('id'),
        ];

        if ($this->clienteModel->save($data)) {
            return redirect()->to('/productosagro/registerVentas')->with('success', 'Cliente creado exitosamente.');
        } else {
            $errors = $this->clienteModel->errors();
            return redirect()->back()->withInput()->with('error', 'Error al crear el cliente: ' . implode(', ', $errors));
        }
    }

    public function registerClienteInve()
    {
        $apellidoPaterno = strtoupper(trim($this->request->getPost('apellido_paterno') ?? ''));
        $apellidoMaterno = strtoupper(trim($this->request->getPost('apellido_materno') ?? ''));
        $nombres         = strtoupper(trim($this->request->getPost('nombres') ?? ''));
        $ciNit           = strtoupper(trim($this->request->getPost('ci_nit') ?? ''));

        if (empty($apellidoPaterno) || empty($nombres)) {
            return redirect()->back()->withInput()->with('error', 'El apellido paterno y el nombre son obligatorios.');
        }

        if (empty($ciNit)) {
            return redirect()->back()->withInput()->with('error', 'El nombre completo y CI/NIT son obligatorios.');
        }

        $nombreCompleto = trim(implode(' ', array_filter([$apellidoPaterno, $apellidoMaterno, $nombres])));

        $data = [
            'nombre_completo' => $nombreCompleto,
            'ci_nit'          => $ciNit,
            'estado'          => true,
            'user_id'         => session()->get('id'),
        ];

        if ($this->clienteModel->save($data)) {
            return redirect()->to('/inventarios/registerVenta')->with('success', 'Cliente creado exitosamente.');
        } else {
            $errors = $this->clienteModel->errors();
            return redirect()->back()->withInput()->with('error', 'Error al crear el cliente: ' . implode(', ', $errors));
        }
    }
}
