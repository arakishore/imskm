<?php namespace App\Controllers;

use App\Models\CountryModel;
use App\Models\StateModel;

use CodeIgniter\RESTful\ResourceController;

class CountryController extends ResourceController
{
    protected $countryModel;
    protected $stateModel;
    protected $format = 'json';

    public function __construct()
    {
        $this->countryModel = new CountryModel();
        $this->stateModel = new StateModel();
    }

    public function index()
    {

        $limit = (int) $this->request->getGet('limit') ?: 0; // Default limit is 10
        $ids = $this->request->getGet('ids'); // Expecting a comma-separated list of IDs
        $search = $this->request->getGet('search'); // Search keyword

        $query = clone $this->countryModel;
        // Filter by IDs if provided
        if ($ids) {
            $idArray = explode(',', $ids);
            $query = $this->countryModel->whereIn('country_id', $idArray);
        }

        // Filter by search keyword if provided
        if ($search) {
            $query = $this->countryModel->groupStart() // Start a group for OR conditions
                ->like('country_short_name', $search)
                ->orLike('country_name', $search)
                ->groupEnd(); // End the group
        }
        if ($limit > 0) {
            $items = $query->limit($limit)->findAll();
        } else {
            $items = $query->findAll(); // Fetch all records if limit is 0
        }
        if (empty($items)) {
            $response = [
                'status' => 200,
                'error' => 404,
                'message' => 'No data found.',
                'data' => [],
            ];
            return $this->respond($response);
        }
        foreach ($items as &$item) {
            $item['states'] = $this->stateModel->getValuesByParent($item['country_id']);
        }

        $transformData = array_map([$this->countryModel, 'transformData'], $items);

        $response = [
            'status' => 200,
            'error' => null,
            'message' => null,
            'data' => $transformData,

        ];
        return $this->respond($response);

    }
// Method to retrieve a single user
    public function show($uuid = null)
    {
        if ($uuid === null) {
            return $this->failValidationErrors('Id is required', 404);
        }
// Find the user by UUID
        $item = $this->countryModel->where('uuid', $uuid)->first();
         

        if (empty($item)) {
            return $this->failNotFound('Data not found');
        }

        if ($item) {
            $item['states'] = $this->stateModel->getValuesByParent($item['country_id']);
            
            $item = $this->countryModel->transformData($item);
            $response = [
                'status' => 200,
                'error' => null,
                'message' => null,
                'data' => $item,
            ];
            return $this->respond($response);
        }

//return $this->failNotFound('User not found');
    }

    public function create()
    {
// Get the JSON data from the request
        $input = $this->request->getJSON();
        if ($input) {
            // Prepare the data for insertion
            $data = [
                'country_short_name' => $input->country_short_name ?? '', // Default to empty string if not provided
                'country_name' => $input->country_name ?? '', // Default to empty string if not provided
                'status_flag' => $input->status_flag ?? 'Active', // Default to 'Active' if not provided

            ];

            // Validate the data before inserting
            if (!$this->countryModel->validate($data)) {
                // If validation fails, return the validation errors
                return $this->fail($this->countryModel->errors());
            }

            // Check if item_short_name or item_name already exists
            $existingItem = $this->countryModel->where('country_short_name', $data['country_short_name'])
                ->orWhere('country_name', $data['country_name'])
                ->first();

            if ($existingItem) {
                return $this->fail([
                    'message' => 'An item with the same short name or name already exists.',
                    'existing_data' => $existingItem,
                ]);
            }
            // Insert the data into the database
            $data['uuid'] = \Ramsey\Uuid\Uuid::uuid4()->toString(); // Generate a UUID
            helper('jwt_helper');
            $enteredBy = getUserInfoFromToken($this->request);
            $data['entered_by'] = $enteredBy;
            if ($this->countryModel->insert($data)) {
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
    }

    public function update($uuid = null)
    {
        if ($uuid === null) {
            return $this->failValidationErrors('Id is required');
        }
// Parse the incoming PUT request body

// Validation Rules with Custom Messages
        $validationRules = [

            'country_short_name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Short name is required.',
                ],
            ],
            'country_name' => [
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
        $item = $this->countryModel->where('uuid', $uuid)->first();

        if (!$item) {
            return $this->failNotFound('Data not found');
        }

        $input = $this->request->getJSON();
        if (($input->country_short_name != $item['country_short_name']) || ($input->country_name != $item['country_name'])) {

            $duplicateCheck = $this->countryModel
                ->groupStart()
                ->where('country_short_name', $input->country_short_name)
                ->orWhere('country_name', $input->country_name)
                ->groupEnd()
                ->where('country_id !=', $item['country_id'])
                ->first();

            if ($duplicateCheck) {
                return $this->fail([
                    'message' => 'An Data  with the same short name or name already exists.',
                    'existing_data' => $duplicateCheck,
                ]);
            }

        }

        helper('jwt_helper');
        $enteredBy = getUserInfoFromToken($this->request);
// Prepare the data for updating
        $updateData = [
            'country_short_name' => $input->country_short_name ?? null,
            'country_name' => $input->country_name ?? null,
            'modified_by' => $enteredBy ?? null,
        ];

// Perform the update
        if ($this->countryModel->update($item['country_id'], $updateData)) {
            helper('activity_helper');

            $userAgent = $this->request->getUserAgent()->getAgentString();
            $ipAddress = $this->request->getIPAddress();

            logActivity([
                'user_id' => $item['country_id'],
                'uuid' => $uuid,
                'action' => 'update',
                'section_tbl' => 'com_country_mst_t',
                'description' => "Country master with UUID $uuid was updated.",
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_by' => $enteredBy,
            ]);

            return $this->respond([
                'status' => 200,
                'message' => 'Country updated successfully',
                'data' => $updateData,
            ]);
        }

// Return failure if the update was unsuccessful
        return $this->fail('Failed to update country');
    }

    // Delete a user (soft delete is not used, so this will remove the user)
    public function delete($uuid = null)
    {
        if ($uuid === null) {
            return $this->failValidationErrors('ID is required', 404);
        }

// Find the user by UUID
        $item = $this->countryModel->where('uuid', $uuid)->first();

        if (!$item) {
            return $this->failNotFound('Data not found');
        }

// Perform a soft delete using the primary key (user_id)
        if ($this->countryModel->delete($item['country_id'])) {
            helper('activity_helper');
            helper('jwt_helper');
            $userId = getUserInfoFromToken($this->request);
            $userAgent = $this->request->getUserAgent()->getAgentString();
            $ipAddress = $this->request->getIPAddress();

            logActivity([
                'user_id' => $item['country_id'],
                'uuid' => $uuid,
                'action' => 'delete',
                'section_tbl' => 'com_country_mst_t',
                'description' => "country  master with UUID $uuid was soft-deleted.",
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_by' => $userId,
            ]);

            return $this->respondDeleted(['status' => 'Data soft-deleted successfully']);
        }
        return $this->fail('Data deletion failed');
    }
}
