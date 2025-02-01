<?php
namespace App\Models;

use CodeIgniter\Model;

class PartyModel extends Model
{
    protected $table            = 'com_party_mst_t';
    protected $primaryKey       = 'party_id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'uuid',
        'party_name',
        'party_shortname',
        'party_type',
        'party_from',
        'party_orgination_type',
        'party_group_name',
        'party_register_no',
        'party_pan_no',
        'party_website',
        'party_email',
        'party_contact_no',
        'party_tax_no',
        'party_exim_id',
        'entered_by',
        'created_at',
        'modified_by',
        'updated_at',
        'record_modified_count',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'party_name'       => 'required|min_length[3]',
        'party_shortname'       => 'required|min_length[3]',
        
        // Add more rules as needed
    ];

    protected $validationMessages = [
        'party_name' => [
            'required'    => 'Party name is required.',
            'min_length'  => 'Party name must be at least 3 characters long.',
        ],
         'party_shortname' => [
            'required'    => 'Party name is required.',
            'min_length'  => 'Party name must be at least 3 characters long.',
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
        'party_id' => $data['party_id'],
        'party_name' => $data['party_name'],
        'party_shortname' => $data['party_shortname'],
        'party_type' => $data['party_type'],
        'party_from' => $data['party_from'],
        'party_orgination_type' => $data['party_orgination_type'],
        'party_group_name' => $data['party_group_name'],
        'party_register_no' => $data['party_register_no'],
        'party_pan_no' => $data['party_pan_no'],
        'party_website' => $data['party_website'],
        'party_email' => $data['party_email'],
        'party_contact_no' => $data['party_contact_no'],
        'party_tax_no' => $data['party_tax_no'],
        'party_exim_id' => $data['party_exim_id'],
        'entered_by' => $data['entered_by'],
        'created_at' => $data['created_at'],
        'modified_by' => $data['modified_by'],
        'updated_at' => $data['updated_at'],
        'record_modified_count' => $data['record_modified_count'],
    ];
}

}
