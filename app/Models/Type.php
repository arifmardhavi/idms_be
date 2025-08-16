<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Type extends BaseModel
{
    use HasFactory;

    protected $fillable = ['type_name', 'description', 'status', 'category_id'];

    // Relation with category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
