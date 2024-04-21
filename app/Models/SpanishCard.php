<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpanishCard extends Model
{
    use HasFactory;

    
    protected $table = 'spanish_cards';

    protected $fillable = [
        'title',
        'img',
        'state',
        'date',
        'description'
    ];
}
