<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Person extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name', 'birthdate', 'timezone'
    ];

    /**
     * The attributes that can be used as Carbon or DateTime objects.
     *
     * @var string[]
     */
    protected $dates = ['birthdate'];
}
