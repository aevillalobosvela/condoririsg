<?php

namespace App\Models\ClienteExterno;

use CodeIgniter\Model;

class ClienteExternoModel extends Model
{
    protected $table      = 'condoriri.clientes_externos';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'nombre',
        'dip',
        'segmento',
        'estado',
        'user_id',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $useSoftDeletes = true;
    protected $deletedField   = 'deleted_at';

    protected $skipValidation = true;
}
