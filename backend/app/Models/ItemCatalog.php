<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemCatalog extends Model
{
    protected $fillable = ['name', 'unit', 'type'];
}