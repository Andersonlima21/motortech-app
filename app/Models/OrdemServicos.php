<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdemServicos extends Model
{
    protected $table='ordem_servico';
    protected $primaryKey='id';
    public $timestamps = false;
}
