<?php

namespace App\Controllers\Bmg;

use App\Controllers\DashboardController as BaseDashboardController;

/**
 * BMG Module Dashboard.
 *
 * This controller extends the main DashboardController so that the
 * /bmg/dashboard route delegates to the BMG dashboard view.
 * It exists primarily to keep BMG routes in the Bmg namespace.
 */
class DashboardController extends BaseDashboardController
{
    /**
     * Render the BMG dashboard. Inherits all logic from the parent.
     */
    public function index()
    {
        return parent::bmg();
    }

    /**
     * Alias for /bmg/dashboard/facility-operations
     */
    public function facilityOperations()
    {
        return parent::facilityOperations();
    }
}