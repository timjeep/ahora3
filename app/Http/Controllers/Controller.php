<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function orderBy($query, $request)
    {
        if ($request->sortBy) {
            $query->orderBy($request->sortBy, $request->sortOrder ?? 'asc');
        }
    }

    protected function paginate(int $total, int $filtered, int $page, int $perPage): array
    {
        return [
            'total' => $total,
            'filtered' => $filtered,
            'current_page' => $page,
            'per_page' => $perPage,
            'last_page' => (int) ceil($filtered / max(1, $perPage)),
        ];
    }

    protected function smartData($request, $query, int $total)
    {
        $this->orderBy($query, $request);
        $paginator = $query->paginate((int) $request->get('per_page', 20));

        return response()->json([
            'data' => $paginator->items(),
            'pagination' => $this->paginate($total, $paginator->total(), $paginator->currentPage(), $paginator->perPage()),
        ]);
    }
}
