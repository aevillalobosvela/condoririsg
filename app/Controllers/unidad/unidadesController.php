<?php

namespace App\Controllers;

namespace App\Controllers\unidad;

use App\Controllers\BaseController;
use App\Models\Unidad\UnidadModel;;

use CodeIgniter\HTTP\RedirectResponse;

class unidadesController extends BaseController
// class UnidadesController extends Controller 
{
    /**
     * @var UnidadModel 
     */
    protected $unidadModel;

    /**
     * Constructor del controlador.
     */
    public function __construct()
    {
        $this->unidadModel = new UnidadModel();
    }

    /**
     * Muestra la lista de todas las unidades (operación READ - todas).
     *
     * @return string
     */
    public function index()
    {
        $data = [
            'unidades' => $this->unidadModel->findAll(),
            'title'    => 'Listado de Unidades',
        ];

        
        echo view('unidad/unidadesIndex', $data);
       
    }


    /**
     * Muestra el formulario para crear una nueva unidad o procesa la creación (operación CREATE).
     *
     * @return string|RedirectResponse
     */
    public function register()
    {

        $data = [
            'title' => 'Crear Nueva Unidad',
            'errors' => [],
            'unidad' => [
                'id' => null,
                'nombre' => '',
                'descripcion' => '',
                'user_id' => ''
            ]
        ];

        
       
        echo view('unidad/unidadesform', $data);
       
    }
    public function create()
    {
        $unidadModel = new UnidadModel();


        $rules = [
            'nombre'           => 'required|min_length[1]|max_length[100]',
            'descripcion'        => 'permit_empty|max_length[100]',
            'user_id'          => 'required|integer|min_length[1]|max_length[11]',
            

        ]; 

        if (!$this->validate($rules)) {

            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $data = [
            'nombre'    => $this->request->getPost('nombre'),
            'descripcion' => $this->request->getPost('descripcion'),
            'user_id'   => $this->request->getPost('user_id'),
            'estado'   => true,
        ];


        if ($unidadModel->save($data)) {
            return redirect()->to('/unidades')->with('message', 'Unidad creado exitosamente.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Error al crear el usuario.');
        }
    }
    /**
    //  * Muestra el formulario para editar una unidad existente o procesa la actualización (operación UPDATE).
    //  *
    //  * @param int $id ID de la unidad a editar.
    //  * @return string|RedirectResponse
    //  */
    public function edit(int $id)
    {
        $unidadModel = new UnidadModel();
        $unidad = $unidadModel->find($id);

        if (!$unidad) {
            return redirect()->to('/unidad')->with('error', 'Unidad no encontrado.');
        }

        $data = [ 
            'unidad' => $unidad,
            'title'   => 'Editar Unidad',
        ];

        
        echo view('unidad/unidadesform', $data); // Asume que tienes una vista para editar
        
    }

    public function update(int $id): RedirectResponse
    {
        $unidadModel = new UnidadModel();

        $rules = [
            'nombre'           => 'required|min_length[1]|max_length[100]',
            'descripcion'        => 'permit_empty|max_length[100]',
            'user_id'          => 'required|integer|min_length[1]|max_length[11]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $data = [
            'id'        => $id,
            'nombre'    => $this->request->getPost('nombre'),
            'descripcion' => $this->request->getPost('descripcion'),
            'user_id'   => $this->request->getPost('user_id'),
            'estado'   => true,
        ];

        if ($unidadModel->save($data)) {
            return redirect()->to('/unidades')->with('message', 'Unidad actualizada exitosamente.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Error al actualizar la unidad.');
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
        $unidad = $this->unidadModel->find($id);
        
        if (!$unidad) {
            session()->setFlashdata('error', 'Unidad no encontrada.');
            return redirect()->to('/unidades');
        }

        $estadoActual = ($unidad['estado'] === 't' || $unidad['estado'] === true);
        $nuevoEstado = !$estadoActual;
        $accion = $nuevoEstado ? 'reactivada' : 'desactivada';
        
        if ($this->unidadModel->update($id, ['estado' => $nuevoEstado])) {
            session()->setFlashdata('success', "Unidad {$accion} exitosamente.");
        } else {
            session()->setFlashdata('error', 'Error al cambiar el estado de la unidad.');
        }

        return redirect()->to('/unidades');
    }
}
