<?php

namespace App\Services\Contracts;

use App\DTOs\ServicoDTO;

interface ServicoServiceInterface
{
    public function create(ServicoDTO $data);

    public function read($id = null);

    public function update(ServicoDTO $data);

    public function delete($id);
}
