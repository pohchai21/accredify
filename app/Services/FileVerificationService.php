<?php

namespace App\Services;

use App\Models\FileVerification;
use App\Enums\VerificationResult;
use App\DTO\VerificationResultDTO;
use Illuminate\Support\Facades\Http;

class FileVerificationService
{
    public function verifyFileUpload($jsonData)
    {
        $recipientValidation = $this->validateRecipient($jsonData);
        if ($recipientValidation !== VerificationResult::Verified) {
            return $this->storeResult($jsonData, $recipientValidation);
        }

        $issuerValidation = $this->validateIssuer($jsonData);
        if ($issuerValidation !== VerificationResult::Verified) {
            return $this->storeResult($jsonData, $issuerValidation);
        }

        $signatureValidation = $this->validateSignature($jsonData);
        if ($signatureValidation !== VerificationResult::Verified) {
            return $this->storeResult($jsonData, $signatureValidation);
        }

        return $this->storeResult($jsonData, VerificationResult::Verified);
    }

    private function validateRecipient($jsonData): VerificationResult
    {
        if (!isset($jsonData['data']['recipient']['name']) || !isset($jsonData['data']['recipient']['email'])) {
            return VerificationResult::InvalidRecipient;
        }

        return VerificationResult::Verified;
    }

    private function validateIssuer($jsonData): VerificationResult
    {
        if (!isset($jsonData['data']['issuer']['name']) || !isset($jsonData['data']['issuer']['identityProof'])) {
            return VerificationResult::InvalidIssuer;
        }

        $issuer = $jsonData['data']['issuer'];
        $identityProof = $issuer['identityProof'];
        $key = $identityProof['key'];
        $location = $identityProof['location'];

        $dnsResponse = Http::get("https://dns.google/resolve?name={$location}&type=TXT");

        if (!$dnsResponse->successful()) {
            return VerificationResult::InvalidIssuer;
        }

        $txtRecords = $dnsResponse->json('Answer');

        if (empty($txtRecords)) {
            return VerificationResult::InvalidIssuer;
        }

        $validKey = false;
        foreach ($txtRecords as $record) {
            if (isset($record['data']) && strpos($record['data'], $key) !== false) {
                $validKey = true;
                break;
            }
        }

        if (!$validKey) {
            return VerificationResult::InvalidIssuer;
        }

        return VerificationResult::Verified;
    }

    private function validateSignature($jsonData): VerificationResult
    {
        $signature = $jsonData['signature'] ?? null;
        if (!$signature) {
            return VerificationResult::InvalidSignature;
        }

        $data = $jsonData['data'];
        $hashes = [];

        foreach ($this->flattenArray($data) as $key => $value) {
            $hash = hash('sha256', json_encode([$key => $value], JSON_UNESCAPED_SLASHES));
            $hashes[] = $hash;
        }

        sort($hashes);
        $finalHash = hash('sha256', json_encode($hashes, JSON_UNESCAPED_SLASHES));

        if (!hash_equals($finalHash, $signature['targetHash'])) {
            return VerificationResult::InvalidSignature;
        }

        return VerificationResult::Verified;
    }

    private function flattenArray($array, $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $prefix . $key . '.'));
            } else {
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
    }

    private function storeResult($jsonData, VerificationResult $result)
    {
        FileVerification::create([
            'user_id' => auth()->id(),
            'file_type' => 'JSON',
            'verification_result' => $result->value,
        ]);

        return new VerificationResultDTO(
            $jsonData['data']['issuer']['name'] ?? '',
            $result->value
        );
    }
}
