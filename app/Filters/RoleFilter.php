<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class RoleFilter implements FilterInterface
{
    /**
     * Valida si el usuario está logueado y si su rol tiene permisos.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments  Roles permitidos para la ruta.
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

      
        if (!$session->get('isLoggedIn')) {
            return redirect()->to('/login')->with('error', 'Debes iniciar sesión.');
        }

        
        if (!$arguments) {
            return;
        }

        
        $userRole = $session->get('rol_nombre');
        
        
       

       
        if (!in_array($userRole, $arguments)) {
          
            return redirect()->to('/')->with('error', 'No tienes permisos para acceder a esta sección.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
       
    }
}
