<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FilterController extends Controller
{
    public function getData(Request $request)
    {
        // Validasi input
        $validatedData = $request->validate([
            'doctype' => 'required|string',
            'filters' => 'nullable|array',
            'filters.*.operator' => 'required_with:filters|string',
            'filters.*.value' => 'required_with:filters|string',
            'limit' => 'nullable|integer',
            'page' => 'nullable|integer',
        ]);

        // Ambil data dari request
        $doctype = $validatedData['doctype'];
        $filters = $validatedData['filters'] ?? [];
        $limit = $validatedData['limit'] ?? 10;
        $page = $validatedData['page'] ?? 0;

        // Mulai query builder
        $query = DB::table($doctype);

        // Terapkan filter
        foreach ($filters as $field => $filter) {
            $operator = $filter['operator'];
            $value = $filter['value'];
            if (strtolower($operator) == 'like') {
                // Tambahkan wildcard (%) jika menggunakan operator LIKE
                $value = '%' . $value . '%';
            }

            $query->where($field, $operator, $value);
            $query->where($field, $operator, $value);
        }

        // Hitung total rows
        $totalRows = $query->count();

        // Pagination
        $query->limit($limit)->offset($page * $limit);

        // Dapatkan hasilnya
        $data = $query->get();

        // Kembalikan response dalam format JSON dengan format yang diinginkan
        return response()->json([
            'message' => [
                'code' => 200,
                'data' => $data,
                'total_rows' => $totalRows
            ]
        ]);
    }
}
