<?php

namespace App\Controllers\Usuarios;

use App\Controllers\BaseController;
use App\Models\UsuarioModel;
use App\Models\Rol\RolModel;
use App\Models\Sucursal\SucursalModel;
use App\Models\Cliente\ClienteModel;

class UsuariosController extends BaseController
{
    /**
     * Muestra la lista de usuarios en una tabla con nombres de rol y sucursal.
     */


    protected $usuarioModel;
    protected $rolModel;
    protected $sucursalModel;
    protected $clienteModel;

    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
        $this->rolModel = new RolModel();
        $this->sucursalModel = new SucursalModel();
        $this->clienteModel = new ClienteModel();
    }

    public function all()
    {
        $usuariosModel = new UsuarioModel();
        
        // Obtener filtros de la URL
        $filters = [
            'search' => $this->request->getGet('search'),
            'rol' => $this->request->getGet('rol'),
            'estado' => $this->request->getGet('estado')
        ];
        
        // Aplicar filtros
        $usuarios = $usuariosModel->getRegistrosFiltrados($filters);
        
        // Obtener todos los usuarios para estadísticas (sin filtros)
        $todosUsuarios = $usuariosModel->getRegistros();
        
        $totalUsuarios   = count($todosUsuarios);
        $totalAdmins     = 0;
        $totalVendedores = 0;
        $totalClientes   = $this->clienteModel->contarClientes();

        foreach ($todosUsuarios as $usuario) {
            if (isset($usuario['rol_nombre'])) {
                if (strtolower($usuario['rol_nombre']) === 'admin') {
                    $totalAdmins++;
                } elseif (strtolower($usuario['rol_nombre']) === 'vendedor') {
                    $totalVendedores++;
                }
            }
        }

        $data = [
            'usuarios'        => $usuarios,
            'filters'         => $filters,
            'totalUsuarios'   => $totalUsuarios,
            'totalAdmins'     => $totalAdmins,
            'totalVendedores' => $totalVendedores,
            'totalClientes'   => $totalClientes,
            'title'           => 'Gestión de Usuarios',
            'nombre'          => session()->get('nombre'),
            'rol_nombre'      => session()->get('rol_nombre'),
            'sucursal_nombre' => session()->get('sucursal_nombre'),
            'usuario'         => session()->get('usuario'),
        ];

        echo view('usuarios/usuariosTable', $data);
    }

    /**
     * Muestra el formulario de registro de usuario.
     */
    public function register()
    {
        $data = [
            'title'      => 'Registrar Usuario',
            'roles'      => $this->rolModel->findAll(),
            'sucursales' => $this->sucursalModel->findAll(),
        ];
        return view('usuarios/usuariosRegister', $data);
    }

 public function create()
{
    // Reglas SIN is_unique (lo manejamos manualmente para respetar soft deletes)
    $rules = [
        'nombre'      => 'required|min_length[3]|max_length[100]',
        'apellidos'   => 'required|min_length[3]|max_length[150]',
        'correo'      => 'required|valid_email|max_length[150]', // sin is_unique
        'celular'     => 'permit_empty|max_length[20]',
        'direccion'   => 'permit_empty',
        'rol_id'      => 'required|is_natural_no_zero',
        'sucursal_id' => 'required|is_natural_no_zero',
        'ci'          => 'required|numeric|min_length[4]|max_length[12]',
    ];

    if (!$this->validate($rules)) {
        return redirect()->back()->withInput()->with('validation', $this->validator);
    }

    $ci = trim($this->request->getPost('ci'));
    $correo = trim($this->request->getPost('correo'));

    if (!is_numeric($ci)) {
        return redirect()->back()->withInput()->with('error', 'El CI debe ser numérico.');
    }

    // 🔴 VALIDACIÓN: Verificar duplicados (respetando soft deletes)
    $existeCI = $this->usuarioModel
        ->where('ci', $ci)
        ->where('deleted_at', null)
        ->first();

    $existeCorreo = $this->usuarioModel
        ->where('correo', $correo)
        ->where('deleted_at', null)
        ->first();

    if ($existeCI && $existeCorreo) {
        return redirect()->back()->withInput()->with('error', 'El CI y el correo electrónico ya están registrados.');
    } elseif ($existeCI) {
        return redirect()->back()->withInput()->with('error', 'El CI ya está registrado.');
    } elseif ($existeCorreo) {
        return redirect()->back()->withInput()->with('error', 'El correo electrónico ya está registrado.');
    }

    // --- Generar usuario y contraseña ---
    $nombreCompleto = trim($this->request->getPost('nombre'));
    $apellidos = trim($this->request->getPost('apellidos'));

    $nombres = explode(' ', $nombreCompleto);
    $apellidosArr = explode(' ', $apellidos);

    $primerNombre = strtolower($nombres[0] ?? '');
    $segundoNombre = isset($nombres[1]) ? strtolower(substr($nombres[1], 0, 1)) : '';
    $primerApellido = isset($apellidosArr[0]) ? strtolower(substr($apellidosArr[0], 0, 1)) : '';
    $segundoApellido = isset($apellidosArr[1]) ? strtolower(substr($apellidosArr[1], 0, 1)) : '';

    $usuario = $primerNombre . $segundoNombre . $primerApellido . $segundoApellido;
    $password = $ci . ($primerNombre ? $primerNombre[0] : '') . $primerApellido . $segundoApellido;

    // --- Asegurar nombre de usuario único ---
    $intentos = 0;
    $usuarioBase = $usuario;
    while ($this->usuarioModel->where('usuario', $usuario)->where('deleted_at', null)->countAllResults() > 0) {
        $intentos++;
        if ($intentos > 10) {
            return redirect()->back()->with('error', 'No se pudo generar un nombre de usuario único.');
        }
        $usuario = $usuarioBase . $intentos;
    }

    // --- Guardar ---
    $data = [
        'nombre'      => $nombreCompleto,
        'apellidos'   => $apellidos,
        'usuario'     => $usuario,
        'correo'      => $correo,
        'password'    => $password,
        'celular'     => $this->request->getPost('celular'),
        'direccion'   => $this->request->getPost('direccion'),
        'rol_id'      => $this->request->getPost('rol_id'),
        'sucursal_id' => $this->request->getPost('sucursal_id'),
        'estado'      => true,
        'ci'          => $ci,
    ];

    if ($this->usuarioModel->adicionar($data)) {
        $mensaje = "Usuario creado exitosamente.<br><strong>Usuario:</strong> {$usuario}<br><strong>Nota:</strong> La contraseña temporal ha sido generada y debe ser cambiada en el primer acceso.";
        return redirect()->to('/usuarios')->with('message', $mensaje);
    } else {
        return redirect()->back()->withInput()->with('error', 'Error al crear el usuario.');
    }
}
    /**
     * Muestra el formulario para editar un usuario existente.
     */


    public function edit($id)
    {

        $usuario = $this->usuarioModel->get($id);


        if (empty($usuario)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }


        $data = [
            'title'      => 'Editar Usuario',
            'usuario'    => $usuario,
            'roles'      => $this->rolModel->findAll(),
            'sucursales' => $this->sucursalModel->findAll(),
        ];


        return view('usuarios/usuariosRegister', $data);
    }

    /**
     * Procesa la actualización de un usuario existente.
     */
    public function update()
    {
        $usuariosModel = new UsuarioModel();

        $id = $this->request->getPost('id');

        $rules = [
            'nombre'      => 'required|min_length[3]|max_length[100]',
            'apellidos'   => 'required|min_length[3]|max_length[150]',
            'usuario'     => 'required|min_length[3]|max_length[50]',
            'correo'      => 'required|min_length[6]|max_length[150]|valid_email',
            'celular'     => 'permit_empty|max_length[20]',
            'direccion'   => 'permit_empty',
            'rol_id'      => 'required|is_natural_no_zero',
            'sucursal_id' => 'required|is_natural_no_zero',
            'ci'          => 'required',
        ];

        if ($this->request->getPost('password')) {
            $rules['password'] = 'min_length[8]|max_length[255]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $usuario = $this->request->getPost('usuario');
        $correo = $this->request->getPost('correo');

        if ($usuariosModel->existeUsuarioOCorreo($usuario, $correo, $id)) {
            return redirect()->back()->withInput()->with('error', 'El nombre de usuario o correo electrónico ya existe.');
        }

        $data = [
            'nombre'      => $this->request->getPost('nombre'),
            'apellidos'   => $this->request->getPost('apellidos'),
            'usuario'     => $usuario,
            'correo'      => $correo,
            'celular'     => $this->request->getPost('celular'),
            'direccion'   => $this->request->getPost('direccion'),
            'rol_id'      => $this->request->getPost('rol_id'),
            'sucursal_id' => $this->request->getPost('sucursal_id'),
        ];

        if ($this->request->getPost('password')) {
            $data['password'] = $this->request->getPost('password');
        }

     
        if ($usuariosModel->actualizar($id, $data)) {
            return redirect()->to('/usuarios')->with('message', 'Usuario actualizado exitosamente.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Error al actualizar el usuario.');
        }
    }

    /**
     * Elimina un usuario.
     */
  public function delete($id = null)
    {
        $usuariosModel = new UsuarioModel();

        // 1. Verificar si se proporcionó un ID
        if (is_null($id)) {
            return redirect()->to('/usuarios')->with('error', 'ID de usuario no especificado.');
        }

        // 2. Verificar si el usuario existe
        $usuario = $usuariosModel->find($id); // Usar find() para CodeIgniter 4
        if (!$usuario) {
            return redirect()->to('/usuarios')->with('error', 'Usuario no encontrado.');
        }

        // 3. Verificar si ya está inactivo para evitar operaciones innecesarias
        if ($usuario['estado'] === 'f' || $usuario['estado'] === false) {
             return redirect()->to('/usuarios')->with('message', 'El usuario ya está inactivo.');
        }

        // 4. Realizar la eliminación lógica (establecer estado = false)
        // El método 'eliminar' debe estar definido en el UsuarioModel
        if ($usuariosModel->eliminar($id)) {
            // Se usa 'desactivado' o 'inactivado' para reflejar que es una eliminación lógica
            return redirect()->to('/usuarios')->with('success', 'Usuario desactivado exitosamente.');
        } else {
            return redirect()->back()->with('error', 'Error al desactivar el usuario.');
        }
    }

    /**
     * Activa un usuario (estado = true).
     */
    public function activate($id = null)
    {
        $usuariosModel = new UsuarioModel();

        if (is_null($id)) {
            return redirect()->to('/usuarios')->with('error', 'ID de usuario no especificado.');
        }

        $usuario = $usuariosModel->find($id);
        if (!$usuario) {
            return redirect()->to('/usuarios')->with('error', 'Usuario no encontrado.');
        }

        if ($usuario['estado'] === 't' || $usuario['estado'] === true) {
            return redirect()->to('/usuarios')->with('message', 'El usuario ya está activo.');
        }

        // Reutilizamos cambiarEstado o hacemos update directo
        if ($usuariosModel->cambiarEstado($id, true)) {
            return redirect()->to('/usuarios')->with('success', 'Usuario activado exitosamente.');
        } else {
            return redirect()->back()->with('error', 'Error al activar el usuario.');
        }
    }

    /**
     * Cambia el estado de un usuario (activar/desactivar)
     */
    /**
     * Cambia el estado de un usuario (activar/desactivar)
     * @param int|null $id ID del usuario
     * @param int|null $state Estado explícito (1 = activo, 0 = inactivo). Si es null, se invierte el estado actual.
     */
    public function cambiarEstado($id = null, $state = null)
    {
        $usuariosModel = new UsuarioModel();

        $usuario = $usuariosModel->get($id);

        if (!$usuario) {
            return redirect()->to('/usuarios')->with('error', 'Usuario no encontrado.');
        }

        // Si se pasa un estado explícito, usarlo. Si no, invertir el actual.
        if ($state !== null) {
            $nuevoEstado = (bool) $state;
        } else {
            // Convertir string de PostgreSQL a booleano para invertir
            $estadoActual = ($usuario['estado'] === 't');
            $nuevoEstado = !$estadoActual;
        }

        if ($usuariosModel->cambiarEstado($id, $nuevoEstado)) {
            $mensaje = $nuevoEstado ? 'Usuario activado exitosamente.' : 'Usuario desactivado exitosamente.';
            return redirect()->to('/usuarios')->with('message', $mensaje);
        } else {
            return redirect()->back()->with('error', 'Error al cambiar el estado del usuario.');
        }
    }

    /**
     * Buscar usuarios por término
     */
    public function buscar()
    {
        $usuariosModel = new UsuarioModel();

        $termino = $this->request->getGet('q');

        if (empty($termino)) {
            return redirect()->to('/usuarios');
        }

        $usuarios = $usuariosModel->buscarUsuarios($termino);

        $data = [
            'usuarios'        => $usuarios,
            'terminoBusqueda' => $termino,
            'totalUsuarios'   => count($usuarios),
            'title'           => 'Resultados de búsqueda: ' . $termino,
            'nombre'          => session()->get('nombre'),
            'apellidos'       => session()->get('apellidos'),
            'rol_nombre'      => session()->get('rol_nombre'),
            'sucursal_nombre' => session()->get('sucursal_nombre'),
            'usuario'         => session()->get('usuario'),
        ];


        echo view('usuarios/usuariosTable', $data);
    }
    /**
     * Muestra el perfil del usuario logueado.
     */
    public function profile()
    {
        $id = session()->get('id');
        $usuario = $this->usuarioModel->get($id);

        if (!$usuario) {
            return redirect()->to('/')->with('error', 'Usuario no encontrado.');
        }

        $data = [
            'title'   => 'Mi Perfil',
            'usuario' => $usuario,
        ];

        return view('usuarios/perfil', $data);
    }

    /**
     * Actualiza el perfil del usuario logueado.
     */
    public function updateProfile()
    {
        $id = session()->get('id');
        $usuariosModel = new UsuarioModel();

        $rules = [
            'usuario' => 'required|min_length[3]|max_length[50]',
            'correo'  => 'required|min_length[6]|max_length[150]|valid_email',
            'celular' => 'permit_empty|max_length[20]',
            'direccion' => 'permit_empty',
        ];

        if ($this->request->getPost('password')) {
            $rules['password'] = 'min_length[8]|max_length[255]';
            $rules['confirm_password'] = 'matches[password]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $usuario = $this->request->getPost('usuario');
        $correo = $this->request->getPost('correo');

        // Verificar duplicados excluyendo al usuario actual
        if ($usuariosModel->existeUsuarioOCorreo($usuario, $correo, $id)) {
            return redirect()->back()->withInput()->with('error', 'El nombre de usuario o correo electrónico ya existe.');
        }

        $data = [
            'usuario'   => $usuario,
            'correo'    => $correo,
            'celular'   => $this->request->getPost('celular'),
            'direccion' => $this->request->getPost('direccion'),
        ];

        if ($this->request->getPost('password')) {
            $data['password'] = $this->request->getPost('password');
        }

        if ($usuariosModel->actualizar($id, $data)) {
            // Actualizar datos de sesión si cambiaron
            $sessionData = session()->get();
            $sessionData['usuario'] = $usuario;
            session()->set($sessionData);

            return redirect()->to('/perfil')->with('message', 'Perfil actualizado exitosamente.');
        } else {
            return redirect()->back()->withInput()->with('error', 'Error al actualizar el perfil.');
        }
    }
}
