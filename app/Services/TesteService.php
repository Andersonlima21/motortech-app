<?php

namespace App\Services;

use App\Services\Contracts\TesteServiceInterface;
use Illuminate\Support\Facades\DB;

class TesteService implements TesteServiceInterface
{
    public function teste()
    {
       $teste = DB::table('usuario')->get()->toArray();

        return count($teste);
    }

}
