<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag_number extends Model
{
    use HasFactory;
    protected $fillable = ['type_id', 'tag_number', 'description', 'status'];

    public function type()
    {
        return $this->belongsTo(Type::class);
    }
}
