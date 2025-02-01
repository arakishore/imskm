<?php
namespace App\Models;

use CodeIgniter\Model;

class CountryModel extends Model
{
    protected $table = 'com_country_mst_t';
    protected $primaryKey = 'country_id';
    protected $useSoftDeletes = true; // Enable soft deletes

    protected $allowedFields = [
        'uuid',
        'country_name',
        'country_short_name',
        'machine_name',
        'entered_by',
        'created_at',
        'modified_by',
        'updated_at',
        'status_flag',
        'deleted_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Add validation rules here if necessary
    // Validation rules
    protected $validationRules = [

        'country_name' => 'required|max_length[255]',
        'country_short_name' => 'required|max_length[50]',
        // 'LOCK_STATUS'           => 'required|in_list[Y,N]',
    ];

    protected $validationMessages = [

        'country_short_name' => [
            'required' => 'Short name is required',
            'max_length' => 'Name cannot exceed 50 characters',
        ],
        'country_name' => [
            'required' => 'Name is required',
            'max_length' => 'Name cannot exceed 255 characters',
        ],

    ];

    // Private method to transform  data into custom format
    public function transformData($data)
    {

        if (!$data) {
            return null;
        }

        return [
            'uuid' => $data['uuid'],
            'country_id' => $data['country_id'],
            'country_short_name' => $data['country_short_name'],
            'country_name' => $data['country_name'],
            'status_flag' => $data['status_flag'],
            'created_at' => $data['created_at'],
            'states' => $data['states'],
        ];
    }

}
