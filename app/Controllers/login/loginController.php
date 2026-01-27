<?php


namespace App\Controllers\login;

use App\Controllers\BaseController;
use App\Models\UsuarioModel;

class loginController extends BaseController
{
    /**
     * Muestra la página de login.
     */
    public function index()
    {

        if (session()->get('isLoggedIn')) {
            return redirect()->to('/');
        }

        return view('login');
    }

    /**
     * Procesa el intento de inicio de sesión.
     */
    public function authenticate()
    {

        $rules = [
            'usuario'    => 'required',
            'password' => 'required'
        ];

        if (!$this->validate($rules)) {

            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }


        $usuario = $this->request->getPost('usuario');
        $password = $this->request->getPost('password');


        $usuarioModel = new UsuarioModel();
        $userData = $usuarioModel->verificarCredenciales($usuario, $password);


        if ($userData) {

            $this->crearSesion($userData);
            return redirect()->to('/')->with('message', '¡Bienvenido de nuevo!');
        } else {

            return redirect()->back()->with('error', 'Usuario o contraseña incorrectos.');
        }
    }

    /**
     * Establece los datos de la sesión del usuario.
     * Ahora acepta tanto arrays como objetos stdClass
     */
    private function crearSesion($userData)
    {
       
        if (is_object($userData)) {
            $userData = (array) $userData;
        }

        // $sessionData = [
        //     'userId'     => $userData['id'],
        //     'nombre'     => $userData['nombre'],
        //     'usuario'    => $userData['usuario'],
        //     'rol_nombre' => $userData['rol_nombre'],
        //     'sucursal_nombre' => $userData['sucursal_nombre'] ?? 'N/A',
        //     'sucursal_id' => $userData['sucursal_id'] ?? 'N/A',
        //     'isLoggedIn' => true,
        // ];

          $sessionData = [
        'id'          => $userData['id'], 
        'nombre'      => $userData['nombre'],
        'usuario'     => $userData['usuario'],
        'rol_id'      => $userData['rol_id'],
        'rol_nombre'  => $userData['rol_nombre'], 
        'sucursal_nombre' => $userData['sucursal_nombre'] ?? 'N/A',
        'sucursal_id' => $userData['sucursal_id'] ?? 'N/A',
        'isLoggedIn'  => true,
    ];

        session()->set($sessionData);
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login')->with('message', 'Has cerrado sesión correctamente.');
    }
}
