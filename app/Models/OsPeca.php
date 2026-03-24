<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OsPeca extends Model
{
    protected $table='ordem_servico_peca';
    protected $primaryKey='id';
    public $timestamps = false;
}
