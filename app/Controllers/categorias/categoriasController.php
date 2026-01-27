<?php

namespace App\Controllers;

namespace App\Controllers\categorias;

use App\Controllers\BaseController;
use App\Models\Categoria\CategoriaModel;
use CodeIgniter\HTTP\RedirectResponse;

class categoriasController extends BaseController

{
    /**
     * @var CategoriaModel 
     */
    protected $categoriaModel;

    /**
     * Constructor del controlador.
     */
    public function __construct()
    {
        $this->categoriaModel = new CategoriaModel();
    }

    /**
     * Muestra la lista de todas las unidades (operación READ - todas).
     *
     * @return string
     */
    public function index()
    {
        $data = [
            'categorias' => $this->categoriaModel->findAll(),
            'title'    => 'Listado de categorías',
        ];


        echo view('categorias/categoriasIndex', $data);
    }


    /**
     * Muestra el formulario para crear una nueva unidad o procesa la creación (operación CREATE).
     *
     * @return string|RedirectResponse
     */
    public function register()
    {

        $data = [
            'title' => 'Crear Nueva Categoria',
            'errors' => [],
            'unidad' => [
                'id' => null,
                'nombre' => '',
                'descripcion' => '',
                'user_id' => '',
                'rol' => '',
            ]
        ];




        echo view('categorias/categoriasform', $data);
    }
    public function create()
    {
        $categoriaModel = new CategoriaModel();


        $rules = [
            'nombre'           => 'required|min_length[3]|max_length[100]',
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
            'estado'    => true,
        ];


        if ($categoriaModel->save($data)) {
            return redirect()->to('/categorias')->with('message', 'Categorias creado exitosamente.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Error al crear el usuario.');
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
        $categoriaModel = new CategoriaModel();
        $categoria = $categoriaModel->find($id);

        if (!$categoria) {
            return redirect()->to('/categorias')->with('error', 'Categorias no encontrado.');
        }

        $data = [
            'categoria' => $categoria,
            'title'   => 'Editar Categoria',
        ];


        echo view('categorias/categoriasform', $data);
    }

    public function update(int $id): RedirectResponse
    {
        $categoriaModel = new CategoriaModel();

        $rules = [
            'nombre'           => 'required|min_length[3]|max_length[100]',
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
            'estado'    => true,
        ];

        if ($categoriaModel->save($data)) {
            return redirect()->to('/categorias')->with('message', 'Categoria actualizada exitosamente.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Error al actualizar la categoria.');
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
  
    if ($this->categoriaModel->update($id, ['estado' => 0])) { 
        session()->setFlashdata('success', 'Categoría eliminada (movida a la papelera) exitosamente.');
    } else {
        session()->setFlashdata('error', 'No se pudo eliminar (inactivar) la categoría.');
    }

    return redirect()->to('/categorias'); 
}
}
