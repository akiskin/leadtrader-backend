<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Cache;


class ToolsController extends BaseController
{
    public function releaseLock()
    {
        Cache::lock('process_financials')->forceRelease();

        return response()->noContent();
    }
}
