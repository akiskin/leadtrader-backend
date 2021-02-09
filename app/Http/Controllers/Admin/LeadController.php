<?php

namespace App\Http\Controllers\Admin;

use App\Models\Lead;
use Illuminate\Routing\Controller as BaseController;


class LeadController extends BaseController
{
    public function inspect(Lead $lead)
    {
        return $lead->toJson();
    }
}
