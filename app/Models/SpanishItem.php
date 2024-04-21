<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpanishItem extends Model
{
    use HasFactory;

    protected $table = 'spanish_items';

    protected $primaryKey = 'item_id';

    protected $fillable = [
        'title',
        'img',
        'price',
        'status'
    ];
}
