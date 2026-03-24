<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class Cliente extends Authenticatable implements JWTSubject
{
    protected $table = 'cliente';
    public $timestamps = false;

    protected $fillable = [
        'nome',
        'cpf_cnpj',
        'telefone',
        'email',
        'endereco',
    ];

    protected $hidden = [];

    /**
     * Identificador JWT (primary key).
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Claims customizados para diferenciar auth por CPF.
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'auth_type' => 'cpf',
            'cliente_nome' => $this->nome,
        ];
    }
}
