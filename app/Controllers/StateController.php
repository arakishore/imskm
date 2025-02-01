<?php namespace App\Controllers;

use App\Models\StateModel;
use CodeIgniter\RESTful\ResourceController;

class StateController extends ResourceController
{
    protected $stateModel;
    protected $format = 'json';

    public function __construct()
    {
        $this->stateModel = new StateModel();
    }

    public function index()
    {

        $query = clone $this->stateModel;

        // Get the limit, ids, and search keyword from the query parameters
        $limit = (int) $this->request->getGet('limit') ?: 0; // Default limit is 0
        $ids = $this->request->getGet('ids'); // Expecting a comma-separated list of IDs
        $search = $this->request->getGet('search'); // Search keyword

// Clone the model to create a base query
        $query = $this->stateModel->select('com_state_mst_t.*, com_country_mst_t.country_short_name'); // Select specific fields
        $query->join('com_country_mst_t', 'com_state_mst_t.country_id = com_country_mst_t.country_id', 'inner'); // Add a join with the item_groups table

// Filter by IDs if provided
        if ($ids) {
            $idArray = explode(',', $ids);
            $query->whereIn('com_state_mst_t.state_id', $idArray);
        }

// Filter by search keyword if provided
        if ($search) {
            $query->groupStart() // Start a group for OR conditions
                ->like('com_state_mst_t.state_short_name', $search)
                ->orLike('com_state_mst_t.state_name', $search)
                ->groupEnd(); // End the group
        }

// Apply the limit
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

        $transformData = array_map([$this->stateModel, 'transformData'], $items);

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
        //$item = $this->stateModel->where('uuid', $uuid)->first();
        $item = $this->stateModel
            ->select('com_state_mst_t.*, com_country_mst_t.country_short_name')
            ->join('com_country_mst_t', 'com_country_mst_t.country_id = com_state_mst_t.country_id')
            ->where('com_state_mst_t.uuid', $uuid)
            ->first();

        if (empty($item)) {
            return $this->failNotFound('Data not found');
        }

        if ($item) {

            $item = $this->stateModel->transformData($item);
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
        $input = $this->request->getJSON(true); // Associative array
        if (!$input) {
            return $this->fail('Invalid JSON format or no data provided');
        }

        $data = [
            'country_id' => $input['country_id'] ?? '',
            'state_short_name' => $input['state_short_name'] ?? '',
            'state_name' => $input['state_name'] ?? '',
            'state_sort_index' => $input['state_sort_index'] ?? '',
            'status_flag' => $input['status_flag'] ?? 'Active',
        ];

        if (!$this->stateModel->validate($data)) {
            return $this->failValidationErrors($this->stateModel->errors());
        }

        $existingItem = $this->stateModel
            ->groupStart()
            ->where('state_short_name', $data['state_short_name'])
            ->orWhere('state_name', $data['state_name'])
            ->groupEnd()
            ->where('deleted_at', null)
            ->first();

        if ($existingItem) {
            return $this->fail([
                'message' => 'Data with the same short name or name already exists.',
                'existing_data' => $existingItem,
            ]);
        }

        $data['uuid'] = \Ramsey\Uuid\Uuid::uuid4()->toString();
        helper('jwt_helper');
        $data['entered_by'] = getUserInfoFromToken($this->request);

        if ($this->stateModel->insert($data)) {
            return $this->respondCreated([
                'status' => 201,
                'error' => null,
                'message' => 'Data inserted successfully',
                'data' => $data,
            ]);
        }

        $db = \Config\Database::connect();
        $error = $db->error(); // Get the last database error
        return $this->fail([
            'status' => 500,
            'error' => $error['code'],
            'message' => $error['message'],
        ]);
    }

    public function update($uuid = null)
    {
        if ($uuid === null) {
            return $this->failValidationErrors('Id is required');
        }
// Parse the incoming PUT request body

// Validation Rules with Custom Messages
        $validationRules = [

            'state_short_name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Short name is required.',
                ],
            ],
            'state_name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Name is required.',
                ],
            ],

            'country_id' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Country id is required.',
                ],
            ],

        ];

// Validate the input data
        if (!$this->validate($validationRules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

// Find the user by UUID
        $item = $this->stateModel->where('uuid', $uuid)->first();

        if (!$item) {
            return $this->failNotFound('Data not found');
        }

        $input = $this->request->getJSON();
        if (($input->state_short_name != $item['state_short_name']) || ($input->state_name != $item['state_name'])) {

            $duplicateCheck = $this->stateModel
                ->groupStart()
                ->where('state_short_name', $input->state_short_name)
                ->orWhere('state_name', $input->state_name)
                ->groupEnd()
                ->where('state_id !=', $item['state_id'])
                ->first();

            if ($duplicateCheck) {
                return $this->fail([
                    'message' => 'Data  with the same short name or name already exists.',
                    'existing_data' => $duplicateCheck,
                ]);
            }

        }

        helper('jwt_helper');
        $enteredBy = getUserInfoFromToken($this->request);
// Prepare the data for updating
        $updateData = [
            'country_id' => $input->country_id ?? null,
            'state_short_name' => $input->state_short_name ?? null,
            'state_name' => $input->state_name ?? null,
            'modified_by' => $enteredBy ?? null,
        ];

// Perform the update
        if ($this->stateModel->update($item['state_id'], $updateData)) {
            helper('activity_helper');

            $userAgent = $this->request->getUserAgent()->getAgentString();
            $ipAddress = $this->request->getIPAddress();

            logActivity([
                'user_id' => $item['state_id'],
                'uuid' => $uuid,
                'action' => 'update',
                'section_tbl' => 'com_state_mst_t',
                'description' => "State master with UUID $uuid was updated.",
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_by' => $enteredBy,
            ]);

            return $this->respond([
                'status' => 200,
                'message' => 'Item updated successfully',
                'data' => $updateData,
            ]);
        }

// Return failure if the update was unsuccessful
        return $this->fail('Failed to update item group');
    }

    // Delete a user (soft delete is not used, so this will remove the user)
    public function delete($uuid = null)
    {
        if ($uuid === null) {
            return $this->failValidationErrors('ID is required', 404);
        }

// Find the user by UUID
        $item = $this->stateModel->where('uuid', $uuid)->first();

        if (!$item) {
            return $this->failNotFound('Data not found');
        }

// Perform a soft delete using the primary key (user_id)
        if ($this->stateModel->delete($item['state_id'])) {
            helper('activity_helper');
            helper('jwt_helper');
            $userId = getUserInfoFromToken($this->request);
            $userAgent = $this->request->getUserAgent()->getAgentString();
            $ipAddress = $this->request->getIPAddress();

            logActivity([
                'user_id' => $item['state_id'],
                'uuid' => $uuid,
                'action' => 'delete',
                'section_tbl' => 'com_state_mst_t',
                'description' => "State master with UUID $uuid was soft-deleted.",
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_by' => $userId,
            ]);

            return $this->respondDeleted(['status' => 'Data soft-deleted successfully']);
        }
        return $this->fail('Data deletion failed');
    }
}
