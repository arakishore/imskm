<?php namespace App\Controllers;

use App\Models\ComLookupModel;
use App\Models\ComLookupValuesModel;
use CodeIgniter\RESTful\ResourceController;

class ComLookupController extends ResourceController
{
    protected $format = 'json';

    protected $lookupModel;
    protected $lookupvalueModel;

    public function __construct()
    {
        $this->lookupModel = new ComLookupModel();
        $this->lookupvalueModel = new ComLookupValuesModel();
    }

    // Fetch all lookup with their values
    public function index()
    {
        $categories = $this->lookupModel->findAll();
        foreach ($categories as &$category) {
            $category['values'] = $this->lookupvalueModel->getValuesByCategory($category['lookup_id']);
        }

        if (empty($categories)) {
            $response = [
                'status' => 200,
                'error' => 404,
                'message' => 'No data found.',
                'data' => [],
            ];
            return $this->respond($response);
        }
        if (!empty($categories)) {
            //print_r($categories);exit;

            $response = [
                'status' => 200,
                'error' => null,
                'message' => null,
                'data' => $categories,

            ];
            return $this->respond($response);
        }

    }
    // Fetch all categories with their values
    public function show($uuid = null)
    {
        if ($uuid === null) {
            return $this->failValidationErrors('Id is required', 404);
        }

        $categories = $this->lookupModel->where('uuid', $uuid)->first();

        if (empty($categories)) {
            return $this->failNotFound('Data not found');
        }
        $categories['values'] = $this->lookupvalueModel->getValuesByCategory($categories['lookup_id']);

        if (!empty($categories)) {
            //print_r($categories);exit;

            $response = [
                'status' => 200,
                'error' => null,
                'message' => null,
                'data' => $categories,

            ];
            return $this->respond($response);
        }

        if (empty($categories)) {
            $response = [
                'status' => 200,
                'error' => 404,
                'message' => 'No data found.',
                'data' => [],
            ];
            return $this->respond($response);
        }

    }
    // Add a new category
    public function addLookup()
    {
        $input = $this->request->getJSON();

        if ($input) {
            $data = [
                'lookup_name' => $input->lookup_name ?? '',
                'lookup_editable_status' => $input->lookup_editable_status ?? '',
                'module_id' => $input->module_id ?? '',
                'lookup_length' => $input->lookup_length ?? '',
                'lookup_module_name' => $input->lookup_module_name ?? '',
                'status_flag' => $input->status_flag ?? 'Active',

            ];

            // Validate the data before inserting
            if (!$this->lookupModel->validate($data)) {
                // If validation fails, return the validation errors
                return $this->fail($this->lookupModel->errors());
            }

            $existingData = $this->lookupModel->where('lookup_name', $input->lookup_name)
                ->first();
            if ($existingData) {

                return $this->fail([
                    'message' => 'Data with the same name already exists.',
                    'existing_data' => $existingData,
                ]);
            }

            $data['uuid'] = \Ramsey\Uuid\Uuid::uuid4()->toString(); // Generate a UUID
            helper('jwt_helper');
            $enteredBy = getUserInfoFromToken($this->request);
            $data['entered_by'] = $enteredBy;
            if ($this->lookupModel->insert($data)) {
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
        }

        return $this->fail('Failed to add Lookup');
    }

    // Update a category
    public function updateLookup($uuid)
    {
        if ($uuid === null) {
            return $this->failValidationErrors('Id is required');
        }
        $input = $this->request->getJSON();
        if ($input) {
            // Find the user by UUID
            $item = $this->lookupModel->where('uuid', $uuid)->first();

            if (!$item) {
                return $this->failNotFound('Data not found');
            }

            $data = [
                'lookup_name' => $input->lookup_name ?? '',
                'lookup_editable_status' => $input->lookup_editable_status ?? '',
                'module_id' => $input->module_id ?? '',
                'lookup_length' => $input->lookup_length ?? '',
                'lookup_module_name' => $input->lookup_module_name ?? '',
                'status_flag' => $input->status_flag ?? 'Active',

            ];

            // Validate the data before inserting
            if (!$this->lookupModel->validate($data)) {
                // If validation fails, return the validation errors
                return $this->fail($this->lookupModel->errors());
            }
            if ($input->lookup_name != $item['lookup_name']) {
                $duplicateCheck = $this->lookupModel
                    ->groupStart()
                    ->where('lookup_name', $input->lookup_name)
                    ->groupEnd()
                    ->where('lookup_id !=', $item['lookup_id'])
                    ->first();

                if ($duplicateCheck) {
                    return $this->fail([
                        'message' => 'Lookup with the same  name already exists.',
                        'existing_data' => $duplicateCheck,
                    ]);
                }
            }

            helper('jwt_helper');
            $enteredBy = getUserInfoFromToken($this->request);
            $data['modified_by'] = $enteredBy;

            if ($this->lookupModel->update($item['lookup_id'], $data)) {
                helper('activity_helper');

                $userAgent = $this->request->getUserAgent()->getAgentString();
                $ipAddress = $this->request->getIPAddress();

                logActivity([
                    'user_id' => $item['lookup_id'],
                    'uuid' => $uuid,
                    'action' => 'update',
                    'section_tbl' => 'com_lookup_mst_t',
                    'description' => "Lookup Master with UUID $uuid was updated.",
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'created_by' => $enteredBy,
                ]);

                return $this->respond([
                    'status' => 200,
                    'message' => 'Lookup updated successfully',
                ]);
            }
        }

        return $this->fail('Failed to update Lookup');
    }

    // Delete a category
    public function deleteLookup($uuid)
    {
        if ($uuid === null) {
            return $this->failValidationErrors('ID is required', 404);
        }
        // Find the user by UUID
        $item = $this->lookupModel->where('uuid', $uuid)->first();

        if (!$item) {
            return $this->failNotFound('Data not found');
        }

        // Perform a soft delete using the primary key (user_id)
        if ($this->lookupModel->delete($item['lookup_id'])) {
            helper('activity_helper');
            helper('jwt_helper');
            $enteredBy = getUserInfoFromToken($this->request);
            $userAgent = $this->request->getUserAgent()->getAgentString();
            $ipAddress = $this->request->getIPAddress();

            logActivity([
                'user_id' => $item['lookup_id'],
                'uuid' => $uuid,
                'action' => 'delete',
                'section_tbl' => 'com_lookup_mst_t',
                'description' => "Lookup master  with UUID $uuid was soft-deleted.",
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_by' => $enteredBy,
            ]);

            return $this->respondDeleted(['status' => 'Lookup deleted successfully']);
        }

        return $this->fail('Failed to delete Lookup');
    }
    //// lookupp values
    // Fetch all lookup with their values
    public function showlookupvalues()
    {
        $lookupvalues = $this->lookupvalueModel
        ->select('com_lookup_values_mst_t.*, com_lookup_mst_t.lookup_name')
        ->join('com_lookup_mst_t', 'com_lookup_values_mst_t.lookup_id = com_lookup_mst_t.lookup_id')
        ->findAll();
            

        if (empty($lookupvalues)) {
            $response = [
                'status' => 200,
                'error' => 404,
                'message' => 'No data found.',
                'data' => [],
            ];
            return $this->respond($response);
        }

        $lookupvalues = array_map([$this->lookupvalueModel, 'transformData'], $lookupvalues);


        if (!empty($lookupvalues)) {
            //print_r($lookupvalues);exit;

            $response = [
                'status' => 200,
                'error' => null,
                'message' => null,
                'data' => $lookupvalues,

            ];
            return $this->respond($response);
        }

    }

    // Fetch all categories with their values
    public function showlookupvaluesingle($uuid = null)
    {
        if ($uuid === null) {
            return $this->failValidationErrors('Id is required', 404);
        }

        $lookupvalueData = $this->lookupvalueModel
        ->select('com_lookup_values_mst_t.*, com_lookup_mst_t.lookup_name')
        ->join('com_lookup_mst_t', 'com_lookup_values_mst_t.lookup_id = com_lookup_mst_t.lookup_id')
        ->where('com_lookup_values_mst_t.uuid', $uuid)
        ->first();


        if (empty($lookupvalueData)) {
            return $this->failNotFound('Data not found');
        }
 
        if (!empty($lookupvalueData)) {
            //print_r($categories);exit;
            $lookupvalueData = $this->lookupvalueModel->transformData($lookupvalueData);
            $response = [
                'status' => 200,
                'error' => null,
                'message' => null,
                'data' => $lookupvalueData,

            ];
            return $this->respond($response);
        }

        if (empty($categories)) {
            $response = [
                'status' => 200,
                'error' => 404,
                'message' => 'No data found.',
                'data' => [],
            ];
            return $this->respond($response);
        }

    }

    // Add a new value to a category
    public function addLookupValue()
    {
        $input = $this->request->getJSON();

        if ($input) {
            $data = [
                'lookup_value' => $input->lookup_value ?? '',
                'lookup_value_order' => $input->lookup_value_order ?? '',
                'lookup_id' => $input->lookup_id ?? '',
                'status_flag' => $input->status_flag ?? 'Active',

            ];

            // Validate the data before inserting
            if (!$this->lookupvalueModel->validate($data)) {
                // If validation fails, return the validation errors
                return $this->fail($this->lookupvalueModel->errors());
            }

            $existingData = $this->lookupvalueModel->where('lookup_value', $input->lookup_value)
                ->first();
            if ($existingData) {

                return $this->fail([
                    'message' => 'Data with the same name already exists.',
                    'existing_data' => $existingData,
                ]);
            }

            $data['uuid'] = \Ramsey\Uuid\Uuid::uuid4()->toString(); // Generate a UUID
            helper('jwt_helper');
            $enteredBy = getUserInfoFromToken($this->request);
            $data['entered_by'] = $enteredBy;
            if ($this->lookupvalueModel->insert($data)) {
                // Return success response
                return $this->respond([
                    'status' => 201,
                    'error' => null,
                    'message' => 'Data inserted successfully',
                    'data' => $data,
                ]);
            } else {
                $db = \Config\Database::connect();
                $error = $db->error(); // Get the last database error
                return $this->fail([
                    'status' => 500,
                    'error' => $error['code'],
                    'message' => $error['message'],
                    'data' => $data,
                ]);
                // // Return failure response if insert fails
                return $this->fail('Failed to insert data');
            }
        }

        return $this->fail('Failed to add insert data');
    }

    // Update a value
    public function updateLookupValue($uuid)
    {
        if ($uuid === null) {
            return $this->failValidationErrors('Id is required');
        }
        $input = $this->request->getJSON();
        if ($input) {
            // Find the user by UUID
            $item = $this->lookupvalueModel->where('uuid', $uuid)->first();

            if (!$item) {
                return $this->failNotFound('Data not found');
            }
            $data = [
                'lookup_value' => $input->lookup_value ?? '',
                'lookup_value_order' => $input->lookup_value_order ?? '',
                'lookup_id' => $input->lookup_id ?? '',
                'status_flag' => $input->status_flag ?? 'Active',
               
            ];

            // Validate the data before inserting
            if (!$this->lookupvalueModel->validate($data)) {
                // If validation fails, return the validation errors
                return $this->fail($this->lookupvalueModel->errors());
            }
            if ($input->lookup_value != $item['lookup_value']) {
                $duplicateCheck = $this->lookupvalueModel
                    ->groupStart()
                    ->where('lookup_value', $input->lookup_value)
                    ->groupEnd()
                    ->where('lookup_value_id !=', $item['lookup_value_id'])
                    ->first();

                if ($duplicateCheck) {
                    return $this->fail([
                        'message' => 'Lookup with the same  name already exists.',
                        'existing_data' => $duplicateCheck,
                    ]);
                }
            }

            helper('jwt_helper');
            $enteredBy = getUserInfoFromToken($this->request);
            $data['modified_by'] = $enteredBy;

            if ($this->lookupvalueModel->update($item['lookup_value_id'], $data)) {
                helper('activity_helper');

                $userAgent = $this->request->getUserAgent()->getAgentString();
                $ipAddress = $this->request->getIPAddress();

                logActivity([
                    'user_id' => $item['lookup_value_id'],
                    'uuid' => $uuid,
                    'action' => 'update',
                    'section_tbl' => 'com_lookup_values_mst_t',
                    'description' => "Lookup value with UUID $uuid was updated.",
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'created_by' => $enteredBy,
                ]);

                return $this->respond([
                    'status' => 200,
                    'message' => 'Lookup values updated successfully',
                ]);
            }
        }

        return $this->fail('Failed to update Lookup values');
        
    }

    // Delete a value
    public function deleteLookupValue($uuid)
    {
         
        if ($uuid === null) {
            return $this->failValidationErrors('ID is required', 404);
        }

// Find the user by UUID
        $item = $this->lookupvalueModel->where('uuid', $uuid)->first();

        if (!$item) {
            return $this->failNotFound('Data not found');
        }

// Perform a soft delete using the primary key (user_id)
        if ($this->lookupvalueModel->delete($item['lookup_value_id'])) {
            helper('activity_helper');
            helper('jwt_helper');
            $enteredBy = getUserInfoFromToken($this->request);
            $userAgent = $this->request->getUserAgent()->getAgentString();
            $ipAddress = $this->request->getIPAddress();

            logActivity([
                'user_id' => $item['lookup_value_id'],
                'uuid' => $uuid,
                'action' => 'delete',
                'section_tbl' => 'com_lookup_values_mst_t',
                'description' => "Lookup value with UUID $uuid was soft-deleted.",
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_by' => $enteredBy,
            ]);

            return $this->respondDeleted(['status' => 'Data soft-deleted successfully']);
        }
        return $this->fail('Data deletion failed');
    }
}
