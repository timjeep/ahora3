<?php

namespace App\Http\Controllers;

use App\Models\Trace;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminTraceController extends Controller
{
    public function traceData(Request $request)
    {

        $query = Trace::query();

        // Apply filters
        if ($request->has('name') && $request->name) {
            // $query->where('name', 'like', '%'.$request->name.'%');
        }

        // Get total count after filters
        $total = $query->count();

        // Apply ordering
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'asc');

        $sortFieldMap = [
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];

        $sortField = $sortFieldMap[$sortBy] ?? 'created_at';

        return $this->smartData($request, $query, $total);
    }

    public function traceList(Request $request)
    {
        return Inertia::render('admin/trace/List');
    }
}
