<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\str;
use Override;

class slug extends Model {
    protected $fillable = [
        'title',
        'description',
        'location',

    ];

    
    protected static function booted()
    {
        return parent::booted();
    }

   

}
