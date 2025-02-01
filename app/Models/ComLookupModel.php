<?php

namespace App\Models;

use CodeIgniter\Model;

class ComLookupModel extends Model
{
    protected $table = 'com_lookup_mst_t';
    protected $primaryKey = 'lookup_id';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'uuid',
        'lookup_name',
        'lookup_editable_status',
        'module_id',
        'lookup_length',
        'lookup_module_name',
        'status_flag',
        'entered_by',
        'created_at',
        'modified_by',
        'updated_at',
        'deleted_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation rules
    protected $validationRules = [

        'lookup_name' => 'required|max_length[255]',
        'lookup_editable_status' => 'required|max_length[1]',
        'module_id' => 'required|integer',
        'lookup_length' => 'required|integer',
        'lookup_module_name' => 'required|max_length[255]',

    ];

    protected $validationMessages = [

        'lookup_name' => [
            'required' => 'Lookup name is required',
            'max_length' => 'Lookup  name cannot exceed 255 characters',
        ],
        'lookup_editable_status' => [
            'required' => 'Lookup editable status is required',
            'max_length' => 'Lookup editable status cannot exceed 1 characters',
        ],
        'module_id' => [
            'required' => 'Module id is required',

        ],
        'lookup_length' => [
            'required' => 'Module id is required',

        ],
        'lookup_module_name' => [
            'required' => 'Lookup module name is required',
            'max_length' => 'Lookup module name cannot exceed 255 characters',
        ],

    ];

    // Example function to get active lookups
    public function getActiveLookups()
    {
        return $this->where('status_flag', 'Active')->findAll();
    }

    // Private method to transform  data into custom format
    public function transformData($data)
    {

        if (!$data) {
            return null;
        }

        return [
            'uuid' => $data['uuid'],
            'lookup_id' => $data['lookup_id'],
            'lookup_name' => $data['lookup_name'],
            'lookup_editable_status' => $data['lookup_editable_status'],
            'module_id' => $data['module_id'],
            'lookup_length' => $data['lookup_length'],
            'lookup_module_name' => $data['lookup_module_name'],
            'machine_name' => $data['machine_name'],
            'status_flag' => $data['status_flag'],
            'created_at' => $data['created_at'],

        ];
    }
}
