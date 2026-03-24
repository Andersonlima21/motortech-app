<?php

namespace App\Utils;

class Utils
{
    /**
     * Valida CPF.
     */
    public function validaCPF(string $cpf): bool
    {
        if (strlen($cpf) !== 11 || preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    /**
     * Valida CNPJ.
     */
    public function validaCNPJ(string $cnpj): bool
    {
        if (strlen($cnpj) !== 14 || preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        $peso1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $peso2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        for ($i = 0, $soma1 = 0; $i < 12; $i++) {
            $soma1 += $cnpj[$i] * $peso1[$i];
        }
        $resto1 = ($soma1 % 11 < 2) ? 0 : 11 - ($soma1 % 11);

        for ($i = 0, $soma2 = 0; $i < 13; $i++) {
            $soma2 += $cnpj[$i] * $peso2[$i];
        }
        $resto2 = ($soma2 % 11 < 2) ? 0 : 11 - ($soma2 % 11);

        return $resto1 == $cnpj[12] && $resto2 == $cnpj[13];
    }

    /**
     * Formata CPF ou CNPJ com máscara.
     */
    public function formatarDocumento(string $doc): string
    {
        $doc = preg_replace('/\D/', '', $doc);

        if (strlen($doc) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $doc);
        }

        if (strlen($doc) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $doc);
        }

        return $doc;
    }
}
