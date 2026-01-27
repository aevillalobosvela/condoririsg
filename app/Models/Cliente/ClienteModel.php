<?php

namespace App\Models\Cliente;

use CodeIgniter\Model;

class ClienteModel extends Model
{
   
    protected $table = 'condoriri.clientes';
    protected $primaryKey = 'id';

  
    protected $allowedFields = [
        'nombre_completo',
        'ci_nit',
        'estado',
        'user_id',
    ];

    
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    
  
    protected $useSoftDeletes = true;
    protected $deletedField  = 'deleted_at';

 
   
}
