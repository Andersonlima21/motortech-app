<?php

namespace App\Services\Contracts;

use Illuminate\Http\JsonResponse;

interface ClienteServiceInterface
{
    public function create(array $data): string;

   public function read($id = null, $cpf = null, $cnpj = null): object;

    public function update(array $data, $id): string;

    public function delete($id);
}
