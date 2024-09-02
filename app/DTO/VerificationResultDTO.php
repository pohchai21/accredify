<?php

namespace App\DTO;

class VerificationResultDTO
{
    public string $issuer;
    public string $result;

    public function __construct(string $issuer, string $result)
    {
        $this->issuer = $issuer;
        $this->result = $result;
    }
}
