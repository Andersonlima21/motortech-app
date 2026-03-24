<?php

namespace App\Services\Contracts;

interface VeiculoServiceInterface
{
    public function create(array $data): string;

    public function read($id = null): object|array;

    public function update(array $data, $id): string;

    public function delete($id): string;
}
