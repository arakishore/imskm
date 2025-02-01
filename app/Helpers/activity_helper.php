<?php
if (!function_exists('logActivity')) {
    function logActivity($data)
    {
        $activityLogModel = new \App\Models\ActivityLogModel();
        $activityLogModel->insert($data);
    }
}
