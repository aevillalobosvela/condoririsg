<?php

namespace App\Models\Rol;

use CodeIgniter\Model;

class RolModel extends Model
{
    protected $table = 'condoriri.roles';
    protected $primaryKey = 'id';
    protected $returnType = 'object'; 
    
    protected $allowedFields = [
      
        'nombre', 
        'descripcion'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at'; 

    protected $validationRules = [
       
        'nombre'    => 'required|min_length[3]|max_length[50]|is_unique[roles.nombre,id,{id}]',
        'descripcion' => 'max_length[255]',
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;
}