<?php

namespace App\Controllers;

namespace App\Controllers\sucursales;

use App\Controllers\BaseController;
use App\Models\Sucursal\SucursalModel;
use CodeIgniter\HTTP\RedirectResponse;

class sucursalesController extends BaseController

{
    /**
     * @var SucursalModel 
     */
    protected $sucursalModel;

    /**
     * Constructor del controlador.
     */
    public function __construct()
    {
        $this->sucursalModel = new SucursalModel();
    }

    /**
     * Muestra la lista de todas las unidades (operación READ - todas).
     *
     * @return string
     */
    public function index()
    {
        $data = [
            'sucursales' => $this->sucursalModel->findAll(),
            'title'    => 'Listado de sucursales',
        ];


        echo view('sucursales/sucursalesIndex', $data);
        // echo view('panel/contabilidad',$data);
    }


    /**
     * Muestra el formulario para crear una nueva unidad o procesa la creación (operación CREATE).
     *
     * @return string|RedirectResponse
     */
    public function register()
    {

        $data = [
            'title' => 'Crear Nueva Sucursal',
            'errors' => [],
            'unidad' => [
                'id' => null,
                'nombre' => '',
                'descripcion' => '',
                'direccion' => '',
                'telefono' => '',
                'user_id' => ''
            ]
        ];



        echo view('sucursales/sucursalesform', $data);
    }
    public function create()
    {
        $sucursalModel = new SucursalModel();


        $rules = [
            'nombre'           => 'required|min_length[1]|max_length[100]',
            'descripcion'        => 'max_length[100]',
            'direccion'        => 'max_length[100]',
            'telefono'        => 'max_length[15]',
            'user_id'          => 'required|integer|min_length[1]|max_length[11]',

        ];

        if (!$this->validate($rules)) {

            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $data = [
            'nombre'    => $this->request->getPost('nombre'),
            'descripcion' => $this->request->getPost('descripcion'),
            'direccion' => $this->request->getPost('direccion'),
            'telefono' => $this->request->getPost('telefono'),
            'user_id'   => $this->request->getPost('user_id'),
        ];


        if ($sucursalModel->save($data)) {
            return redirect()->to('/sucursales')->with('message', 'Sucursal creado exitosamente.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Error al crear la sucursal.');
        }
    }
    /**
     * Muestra el formulario para editar una unidad existente o procesa la actualización (operación UPDATE).
     *
     * @param int $id ID de la unidad a editar.
     * @return string|RedirectResponse
     */
    public function edit(int $id)
    {
        $sucursalModel = new SucursalModel();
        $sucursal = $sucursalModel->find($id);

        if (!$sucursal) {
            return redirect()->to('/sucursal')->with('error', 'Sucursal no encontrado.');
        }

        $data = [
            'sucursal' => $sucursal,
            'title'   => 'Editar Sucursal',
        ];


        echo view('sucursales/sucursalesform', $data);
    }

    public function update(int $id): RedirectResponse
    {
        $sucursalModel = new SucursalModel();

        $rules = [
            'nombre'           => 'required|min_length[3]|max_length[100]',
            'descripcion'        => 'permit_empty|max_length[100]',
            'direccion'        => 'permit_empty|max_length[100]',
            'telefono'        => 'permit_empty|max_length[15]',
            'user_id'          => 'required|integer|min_length[1]|max_length[11]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $data = [
            'id'        => $id,
            'nombre'    => $this->request->getPost('nombre'),
            'descripcion' => $this->request->getPost('descripcion'),
            'direccion' => $this->request->getPost('direccion'),
            'telefono' => $this->request->getPost('telefono'),
            'user_id'   => $this->request->getPost('user_id'),
        ];

        if ($sucursalModel->save($data)) {
            return redirect()->to('/sucursales')->with('message', 'Sucursal actualizada exitosamente.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Error al actualizar la sucursal.');
        }
    }

    /**
     * Elimina una unidad (operación DELETE - suave).
     *
     * @param int $id ID de la unidad a eliminar.
     * @return RedirectResponse
     */
    public function delete(int $id): RedirectResponse
    {
        if ($this->sucursalModel->delete($id)) {
            session()->setFlashdata('success', 'Sucursal eliminada exitosamente (movida a la papelera).');
        } else {
            session()->setFlashdata('error', 'No se pudo eliminar la Sucursal.');
        }

        return redirect()->to('/sucursales');
    }

    public function toggleEstado(int $id): RedirectResponse
    {
        $sucursal = $this->sucursalModel->find($id);
        
        if (!$sucursal) {
            session()->setFlashdata('error', 'Sucursal no encontrada.');
            return redirect()->to('/sucursales');
        }

        $estadoActual = ($sucursal['estado'] === 't' || $sucursal['estado'] === true);
        $nuevoEstado = !$estadoActual;
        $accion = $nuevoEstado ? 'reactivada' : 'desactivada';
        
        if ($this->sucursalModel->update($id, ['estado' => $nuevoEstado])) {
            session()->setFlashdata('success', "Sucursal {$accion} exitosamente.");
        } else {
            session()->setFlashdata('error', 'Error al cambiar el estado de la sucursal.');
        }

        return redirect()->to('/sucursales');
    }


    public function show(int $id)
    {
        $sucursal = $this->sucursalModel->find($id);

        if (!$sucursal) {
            return redirect()->to('/sucursales')->with('error', 'Sucursal no encontrada.');
        }

        $data = [
            'sucursal' => $sucursal,
            'title'    => 'Detalle de Sucursal: ' . esc($sucursal['nombre']),
        ];
        return view('contabilidad/lacteosIndex', $data);

        // switch ($id) {
        //     case 4:

        //         return view('contabilidad/lacteosIndex', $data);

        //     case 6:

        //         return view('contabilidad/ventasOruro', $data);

        //     case 7:

        //         return view('contabilidad/agropecuarios', $data);

        //     case 8:

        //         return view('contabilidad/ganaderia', $data);

        //     default:

        //         return view('contabilidad/lacteosIndex', $data);
        // }

    }


    public function ventas()
    {
        $data = [
            'title' => 'Ventas Sucursal Ventas',
        ];

        echo view('sucursales/ventas', $data);
    }




}
