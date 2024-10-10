<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DynamicModel extends Model
{
    use HasFactory;
    protected $table;

    public function setTableName($table)
    {
        $this->table = $table;
        return $this;
    }

    // Menangani relasi dinamis
    public function dynamicRelation($relation)
    {
        // Cek apakah relasi yang diminta ada di model ini
        if (method_exists($this, $relation)) {
            return $this->{$relation}();
        }

        throw new \Exception("Relasi {$relation} tidak ditemukan pada model.");
    }
}
