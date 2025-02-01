<?php

namespace App\Controllers;

use App\Models\LookupValuesModel;
use CodeIgniter\RESTful\ResourceController;

class LookupValuesController extends ResourceController
{
    protected $modelName = 'App\Models\LookupValuesModel';
    protected $format    = 'json';

    // Get all lookup values
    public function index()
    {
        $data = $this->model->findAll();
        return $this->respond($data);
    }

    // Create a new lookup value
    public function create()
    {
        $data = $this->request->getJSON(true);
        
        if (!$this->validate([
            'LOOKUP_VALUE' => 'required',
            'LOOKUP_ID' => 'required',
        ])) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $this->model->save($data);
        return $this->respondCreated($data);
    }

    // Get a specific lookup value by ID
    public function show($id = null)
    {
        $data = $this->model->find($id);
        if (!$data) {
            return $this->failNotFound('Lookup Value not found');
        }
        return $this->respond($data);
    }

    // Update an existing lookup value
    public function update($id = null)
    {
        $data = $this->request->getJSON(true);
        if (!$this->validate([
            'LOOKUP_VALUE' => 'required',
            'LOOKUP_ID' => 'required',
        ])) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        if (!$this->model->find($id)) {
            return $this->failNotFound('Lookup Value not found');
        }

        $this->model->update($id, $data);
        return $this->respond($data);
    }

    // Delete a lookup value (soft delete)
    public function delete($id = null)
    {
        $data = $this->model->find($id);
        if (!$data) {
            return $this->failNotFound('Lookup Value not found');
        }

        $this->model->delete($id);
        return $this->respondDeleted($data);
    }
}

