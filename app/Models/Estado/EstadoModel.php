<?php

namespace App\Models\Estado;

use CodeIgniter\Model;

class EstadoModel extends Model
{
    protected $table = 'condoriri.estados';
    protected $primaryKey = 'id';
    protected $returnType = 'object'; 
    
    protected $allowedFields = [
      
        'nombre', 
        'descripcion',
        'user_id'
    ];
 
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at'; 

    protected $validationRules = [
       
        'nombre'    => 'required|min_length[3]|max_length[50]|is_unique[estados.nombre,id,{id}]',
        'descripcion' => 'max_length[255]',
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;
}