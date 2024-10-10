<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;
    protected $guarded = [''];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function office()
    {
        return $this->hasMany(Office::class, 'id');
    }
}
