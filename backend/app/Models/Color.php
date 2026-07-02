<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    protected $table      = 'mausac';
    protected $primaryKey = 'id';
    public $timestamps    = false;

    protected $fillable = [
        'ten',
        'mamau',
    ];
}