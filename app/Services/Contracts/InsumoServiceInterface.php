<?php

namespace App\Services\Contracts;

use App\DTOs\PecaDTO;
use App\DTOs\PecaQuantidadeDTO;

interface InsumoServiceInterface
{
    public function create(PecaDTO $data);

    public function read($id = null);

    public function update(PecaDTO $data);

    public function delete($id);

    public function estoque(PecaQuantidadeDTO $dto);

}
