<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table = 'condoriri.usuarios';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'nombre',
        'apellidos',
        'usuario',
        'correo',
        'password',
        'celular',
        'direccion',
        'rol_id',
        'sucursal_id',
        'estado',
        'ultima_sesion',
        'created_at',
        'updated_at',
        'deleted_at',
        'ci',
        
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];




    

    /**
     * Hashea la contraseña antes de guardar.
     */
    protected function hashPassword(array $data): array
    {
        if (!empty($data['data']['password'])) {
            // Solo hashear si la contraseña es nueva o fue cambiada
            if (!password_get_info($data['data']['password'])['algo']) {
                $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
            }
        }
        return $data;
    }

    /**
     * Obtiene un usuario por su ID con relaciones.
     */
    public function get(int $id): ?array
    {
        return $this->select('usuarios.*, roles.nombre as rol_nombre, sucursales.nombre as sucursal_nombre')
            ->join('roles', 'roles.id = usuarios.rol_id', 'left')
            ->join('sucursales', 'sucursales.id = usuarios.sucursal_id', 'left')
            ->where('usuarios.id', $id)
            ->where('usuarios.deleted_at', null)
            ->first();
    }

    /**
     * Obtiene todos los usuarios con relaciones y filtros.
     */
    public function getRegistrosFiltrados(array $filters = []): array
    {
        $builder = $this->select('usuarios.*, roles.nombre as rol_nombre, sucursales.nombre as sucursal_nombre')
            ->join('roles', 'roles.id = usuarios.rol_id', 'left')
            ->join('sucursales', 'sucursales.id = usuarios.sucursal_id', 'left')
            ->where('usuarios.deleted_at', null);
        
        // Filtro de búsqueda
        if (!empty($filters['search'])) {
            $searchTerm = "%{$filters['search']}%";
            $builder->groupStart()
                ->like('usuarios.nombre', $searchTerm)
                ->orLike('usuarios.apellidos', $searchTerm)
                ->orLike('usuarios.usuario', $searchTerm)
                ->orLike('usuarios.correo', $searchTerm)
                ->orLike('roles.nombre', $searchTerm)
                ->orLike('sucursales.nombre', $searchTerm)
                ->groupEnd();
        }
        
        // Filtro por rol
        if (!empty($filters['rol'])) {
            $builder->where('LOWER(roles.nombre)', strtolower($filters['rol']));
        }
        
        // Filtro por estado
        if (isset($filters['estado']) && $filters['estado'] !== '') {
            $estado = $filters['estado'] === 'activo' ? true : false;
            $builder->where('usuarios.estado', $estado);
        }
        
        return $builder->orderBy('usuarios.nombre, usuarios.apellidos')->findAll();
    }

    /**
     * Obtiene todos los usuarios con relaciones.
     */
    public function getRegistros(): array
    {
        return $this->select('usuarios.*, roles.nombre as rol_nombre, sucursales.nombre as sucursal_nombre')
            ->join('roles', 'roles.id = usuarios.rol_id', 'left')
            ->join('sucursales', 'sucursales.id = usuarios.sucursal_id', 'left')
            ->where('usuarios.deleted_at', null)
            ->orderBy('usuarios.nombre, usuarios.apellidos')
            ->findAll();
    }

    /**
     * Actualiza un registro.
     */
    public function actualizar(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /**
     * Elimina un registro (soft delete).
     */
  public function eliminar($id)
    {
        $data = [
            'estado' => false, // 0 o false, dependiendo de cómo manejes los booleanos en tu DB
            'updated_at' => date('Y-m-d H:i:s') // Opcional, si usas TimeStamps
        ];

        // CodeIgniter 4: Utiliza el método update para modificar el registro
        return $this->update($id, $data);
    }

    /**
     * Adiciona un nuevo registro.
     */
    public function adicionar(array $data): int
    {
        return $this->insert($data, true);
    }

    /**
     * Verifica credenciales de usuario.
     */
    public function verificarCredenciales(string $identificador, string $password): ?object
    {
        $user = $this->select('usuarios.id, usuarios.nombre, usuarios.apellidos, usuarios.usuario, usuarios.correo, usuarios.rol_id, roles.nombre as rol_nombre, sucursales.nombre as sucursal_nombre, sucursales.id as sucursal_id, usuarios.password')
            ->join('roles', 'roles.id = usuarios.rol_id', 'left')
            ->join('sucursales', 'sucursales.id = usuarios.sucursal_id', 'left')
            ->groupStart()
                ->where('usuarios.usuario', $identificador)
                ->orWhere('usuarios.correo', $identificador)
            ->groupEnd()
            ->where('usuarios.estado', true)
            ->where('usuarios.deleted_at', null)
            ->first();

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            return (object) $user;
        }
        return null;
    }

    /**
     * Obtiene usuarios por rol.
     */
    public function getUsuariosPorRol(int $rolId): array
    {
        return $this->select('usuarios.*, roles.nombre as rol_nombre, sucursales.nombre as sucursal_nombre')
            ->join('roles', 'roles.id = usuarios.rol_id', 'left')
            ->join('sucursales', 'sucursales.id = usuarios.sucursal_id', 'left')
            ->where('usuarios.rol_id', $rolId)
            ->where('usuarios.deleted_at', null)
            ->orderBy('usuarios.nombre, usuarios.apellidos')
            ->findAll();
    } 

    /**
     * Obtiene usuarios por sucursal.
     */
    public function getUsuariosPorSucursal(int $sucursalId): array
    {
        return $this->select('usuarios.*, roles.nombre as rol_nombre, sucursales.nombre as sucursal_nombre')
            ->join('roles', 'roles.id = usuarios.rol_id', 'left')
            ->join('sucursales', 'sucursales.id = usuarios.sucursal_id', 'left')
            ->where('usuarios.sucursal_id', $sucursalId)
            ->where('usuarios.deleted_at', null)
            ->orderBy('usuarios.nombre, usuarios.apellidos')
            ->findAll();
    }

    /**
     * Verifica si usuario o correo ya existen.
     */
    public function existeUsuarioOCorreo(string $usuario, string $correo, ?int $excluirId = null): bool
    {
        $builder = $this->builder();
        $builder->where('deleted_at', null)
            ->groupStart()
                ->where('usuario', $usuario)
                ->orWhere('correo', $correo)
            ->groupEnd();

        if ($excluirId) {
            $builder->where('id !=', $excluirId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Busca usuarios por término.
     */
    public function buscarUsuarios(string $termino): array
    {
        $searchTerm = "%{$termino}%";
        return $this->select('usuarios.*, roles.nombre as rol_nombre, sucursales.nombre as sucursal_nombre')
            ->join('roles', 'roles.id = usuarios.rol_id', 'left')
            ->join('sucursales', 'sucursales.id = usuarios.sucursal_id', 'left')
            ->groupStart()
                ->like('usuarios.nombre', $searchTerm)
                ->orLike('usuarios.apellidos', $searchTerm)
                ->orLike('usuarios.usuario', $searchTerm)
                ->orLike('usuarios.correo', $searchTerm)
                ->orLike('roles.nombre', $searchTerm)
                ->orLike('sucursales.nombre', $searchTerm)
            ->groupEnd()
            ->where('usuarios.deleted_at', null)
            ->orderBy('usuarios.nombre, usuarios.apellidos')
            ->findAll();
    }

    /**
     * Actualiza la última sesión del usuario.
     */
    public function actualizarUltimaSesion(int $usuarioId): bool
    {
        return $this->update($usuarioId, [
            'ultima_sesion' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Cambia el estado de un usuario.
     */
    public function cambiarEstado(int $usuarioId, bool $estado): bool
    {
        return $this->update($usuarioId, [
            'estado' => $estado
        ]);
    }

    //poner las funcones de usuarios 

    
}
