<?php
namespace App\Controllers;

use App\Models\PartyModel;
use CodeIgniter\RESTful\ResourceController;

class PartyController extends ResourceController
{
    protected $partyModel;
    protected $format = 'json';

    public function __construct()
    {
        $this->partyModel = new PartyModel();
    }

    public function index()
    {
        $parties = $this->partyModel->findAll();
        return $this->respond($parties);
    }

    public function show($uuid = null)
    {
        if ($uuid === null) {
            return $this->failValidationErrors('Id is required', 404);
        }
        $party = $this->partyModel->where('uuid', $uuid)->first();

        if (!$party) {
            return $this->failNotFound('Party not found');
        }

        return $this->respond($party);
    }

    public function create()
    {
        $input = $this->request->getJSON();
        if ($input) {
            // Prepare the data for insertion
            $data = [
                'party_prefix' => $input->party_prefix ?? '', 
                'party_name' => $input->party_name ?? '', 
                'party_shortname' => $input->party_shortname ?? '', 
                'party_type' => $input->party_type ?? '', 
                'party_from' => $input->party_from ?? '', 
                'party_orgination_type' => $input->party_orgination_type ?? '', 
                'party_group_name' => $input->party_group_name ?? '', 
                'party_register_no' => $input->party_register_no ?? '', 
                'party_pan_no' => $input->party_pan_no ?? '', 
                'party_website' => $input->party_website ?? '', 
                'party_email' => $input->party_email ?? '', 
                'party_contact_no' => $input->party_contact_no ?? '', 
                'party_tax_no' => $input->party_tax_no ?? '', 
                'party_exim_id' => $input->party_exim_id ?? '', 
                'status_flag' => $input->status_flag ?? 'Active', // Default to 'Active' if not provided
                
            ];

// Validate the data before inserting
            if (!$this->partyModel->validate($data)) {
                // If validation fails, return the validation errors
                return $this->fail($this->partyModel->errors());
            }

// Check if item_group_short_name or item_group_name already exists
            $existingItem = $this->partyModel->where('party_name', $data['party_name'])
                ->orWhere('party_shortname', $data['party_shortname'])
                ->first();

            if ($existingItem) {
                return $this->fail([
                    'message' => 'An party  with the same short name or name already exists.',
                    'existing_data' => $existingItem,
                ]);
            }
// Insert the data into the database
            $data['uuid'] = \Ramsey\Uuid\Uuid::uuid4()->toString(); // Generate a UUID
            helper('jwt_helper');
            $enteredBy = getUserInfoFromToken($this->request);
            $data['entered_by'] = $enteredBy;
            if ($this->partyModel->insert($data)) {
                // Return success response
                return $this->respond([
                    'status' => 201,
                    'error' => null,
                    'message' => 'Data inserted successfully',
                    'data' => $data,
                ]);
            } else {
                // $db = \Config\Database::connect();
                // $error = $db->error(); // Get the last database error
                // return $this->fail([
                //     'status' => 500,
                //     'error' => $error['code'],
                //     'message' => $error['message'],
                //     'data' => $data,
                // ]);
                // // Return failure response if insert fails
                return $this->fail('Failed to insert data');
            }
        } else {
            // Handle the case where no valid JSON data is received
            return $this->fail('Invalid JSON format');
        }

        return $this->failValidationErrors($this->partyModel->errors());
    }

    public function update($uuid = null)
    { 
        if ($uuid === null) {
            return $this->failValidationErrors('Id is required');
        }
 
// Validation Rules with Custom Messages
        $validationRules = [
            'party_shortname' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Short name is required.',
                ],
            ],
            'party_name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Name is required.',
                ],
            ],
 

        ];

// Validate the input data
        if (!$this->validate($validationRules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

// Find the user by UUID
        $party = $this->partyModel->where('uuid', $uuid)->first();

        if (!$party) {
            return $this->failNotFound('Data not found');
        }
        $input = $this->request->getJSON();
// Prepare the data for updating
        $updateData = [
            'party_prefix' => $input->party_prefix ?? '', 
            'party_name' => $input->party_name ?? '', 
            'party_shortname' => $input->party_shortname ?? '', 
            'party_type' => $input->party_type ?? '', 
            'party_from' => $input->party_from ?? '', 
            'party_orgination_type' => $input->party_orgination_type ?? '', 
            'party_group_name' => $input->party_group_name ?? '', 
            'party_register_no' => $input->party_register_no ?? '', 
            'party_pan_no' => $input->party_pan_no ?? '', 
            'party_website' => $input->party_website ?? '', 
            'party_email' => $input->party_email ?? '', 
            'party_contact_no' => $input->party_contact_no ?? '', 
            'party_tax_no' => $input->party_tax_no ?? '', 
            'party_exim_id' => $input->party_exim_id ?? '', 
            'status_flag' => $input->status_flag ?? 'Active', // Default to 'Active' if not provided
        ];

        if(($input->party_shortname != $party['party_shortname']) || ($input->party_name != $party['party_name'])){
            $duplicateCheck = $this->partyModel
            ->groupStart()
            ->where('party_shortname', $input->party_shortname)
            ->orWhere('party_name', $input->party_name)
            ->groupEnd()
            ->where('party_id !=', $party['party_id'])
            ->first();

        if ($duplicateCheck) {
            return $this->fail([
                'message' => 'An Party with the same short name or name already exists.',
                'existing_data' => $duplicateCheck,
            ]);
        }
        }
  

// Perform the update
        helper('jwt_helper');
        $enteredBy = getUserInfoFromToken($this->request);
        $updateData['modified_by'] = $enteredBy;

        if ($this->partyModel->update($party['party_id'], $updateData)) {
            helper('activity_helper');

            $userAgent = $this->request->getUserAgent()->getAgentString();
            $ipAddress = $this->request->getIPAddress();

            logActivity([
                'user_id' => $party['party_id'],
                'uuid' => $uuid,
                'action' => 'update',
                'section_tbl' => 'com_party_mst_t',
                'description' => "Party master with UUID $uuid was updated.",
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_by' => $enteredBy,
            ]);

            return $this->respond([
                'status' => 200,
                'message' => 'Party updated successfully',
                'data' => $updateData,
            ]);
        }

// Return failure if the update was unsuccessful
        return $this->fail('Failed to update party');
    }

     // Delete a user (soft delete is not used, so this will remove the user)
     public function delete($uuid = null)
     {
         if ($uuid === null) {
             return $this->failValidationErrors('ID is required', 404);
         }
 
 // Find the user by UUID
         $item = $this->partyModel->where('uuid', $uuid)->first();
 
         if (!$item) {
             return $this->failNotFound('Data not found');
         }
 
 // Perform a soft delete using the primary key (user_id)
         if ($this->partyModel->delete($item['party_id'])) {
             helper('activity_helper');
             helper('jwt_helper');
             $userId = getUserInfoFromToken($this->request);
             $userAgent = $this->request->getUserAgent()->getAgentString();
             $ipAddress = $this->request->getIPAddress();
 
             logActivity([
                 'user_id' => $item['party_id'],
                 'uuid' => $uuid,
                 'action' => 'delete',
                 'section_tbl' => 'com_party_mst_t',
                 'description' => "Party with UUID $uuid was soft-deleted.",
                 'ip_address' => $ipAddress,
                 'user_agent' => $userAgent,
                 'created_by' => $userId,
             ]);
 
             return $this->respondDeleted(['status' => 'Data soft-deleted successfully']);
         }
         return $this->fail('Data deletion failed');
     }
    public function delete1($id = null)
    {
        if (!$this->partyModel->find($id)) {
            return $this->failNotFound('Party not found');
        }

        if ($this->partyModel->delete($id)) {
            return $this->respondDeleted(['id' => $id, 'message' => 'Party deleted']);
        }

        return $this->fail('Failed to delete party');
    }
}
