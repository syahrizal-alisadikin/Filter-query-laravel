<?php

namespace App\Http\Controllers;

use App\Models\DynamicModel;
use App\Models\Scope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    public function filterData(Request $request)
    {
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
        $relations = $validatedData['relations'] ?? []; // Ambil relasi dinamis dari request
        $limit = $validatedData['limit'] ?? 10;
        $page = $validatedData['page'] ?? 0;

        // Buat instance dari DynamicModel dan set nama tabel
        $scope = Scope::where('doctype', $doctype)->with('details')->first();
        $relations =  $scope->details()->pluck('name')->toArray();
        if (!$scope) {
            return response()->json([
                'message' => [
                    'code' => 404,
                    'data' => 'Doctype tidak ditemukan'
                ]
            ], 500);
        }
        // Ubah doctype (nama tabel) menjadi nama model dengan konvensi CamelCase
        $modelClass = 'App\\Models\\' . Str::studly(Str::singular($scope->table));
        // Mulai query dengan model yang sesuai
        $query = $modelClass::query();
        // Terapkan filter
        foreach ($filters as $field => $filter) {
            $operator = $filter['operator'];
            $value = $filter['value'];

            if (strtolower($operator) == 'like') {
                // Tambahkan wildcard (%) jika menggunakan operator LIKE
                $value = '%' . $value . '%';
            }

            $query->where($field, $operator, $value);
        }

        // Terapkan relasi dinamis jika ada
        // Terapkan relasi dinamis jika ada
        if (!empty($relations)) {
            foreach ($relations as $relation) {
                if (method_exists($modelClass, $relation)) {
                    $query->with($relation);
                } else {
                    return response()->json(['error' => "Relasi {$relation} tidak ditemukan pada model."], 400);
                }
            }
        }
        // Hitung total rows
        $totalRows = $query->count();
        // Paginasi dengan limit dan offset (untuk halaman)
        $results = $query->limit($limit)->offset($page * $limit)->get();
        return response()->json([
            'message' => [
                'code' => 200,
                'data' => $results,
                'total_rows' => $totalRows
            ]
        ]);
    }

    public function storeData(Request $request)
    {
        // jadikan array
        $data = $request->all();
        foreach ($data as $key => $value) {
            if ($key === 'doctype') { // Ubah = menjadi === untuk perbandingan
                // Ambil scope berdasarkan doctype
                $scope = Scope::where('doctype', $value)->with('details')->first();

                // Pastikan scope ditemukan
                if ($scope) {
                    $modelClass = 'App\\Models\\' . Str::studly(Str::singular($scope->table));

                    // Periksa apakah model class ada
                    if (class_exists($modelClass)) {
                        // Siapkan data untuk updateOrCreate
                        $searchCriteria = ['id' => $data['id'] ?? null]; // Ambil ID jika ada
                        $record = $modelClass::updateOrCreate(
                            $searchCriteria, // Kriteria pencarian
                            $data // Data untuk disimpan
                        );

                        // Mengembalikan response sukses
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Data berhasil disimpan.',
                            'data' => $record // Kembalikan data record yang baru
                        ], 200);
                    } else {
                        return response()->json(['error' => 'Model tidak ditemukan.'], 404);
                    }
                } else {
                    return response()->json(['error' => 'Scope tidak ditemukan.'], 404);
                }
            }
        }

        // Jika 'doctype' tidak ditemukan dalam data
        return response()->json(['error' => 'Doctype tidak ditemukan.'], 400);
    }
}
