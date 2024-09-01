<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FileVerification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Http\Requests\VerifyUploadRequest;

class FileVerificationController extends Controller
{
    public function verifyUpload(VerifyUploadRequest $request)
    {
        $file = $request->file('file');
        $jsonData = json_decode(file_get_contents($file->getRealPath()), true);

        if (!$jsonData) {
            return response()->json(['data' => ['issuer' => null, 'result' => 'invalid_signature']], 400);
        }

        $recipientValidation = $this->validateRecipient($jsonData);
        if ($recipientValidation !== 'verified') {
            return $this->storeResult($request, $jsonData, $recipientValidation);
        }

        $issuerValidation = $this->validateIssuer($jsonData);
        if ($issuerValidation !== 'verified') {
            return $this->storeResult($request, $jsonData, $issuerValidation);
        }

        $signatureValidation = $this->validateSignature($jsonData);
        if ($signatureValidation !== 'verified') {
            return $this->storeResult($request, $jsonData, $signatureValidation);
        }

        return $this->storeResult($request, $jsonData, 'verified');
    }

    private function validateRecipient($jsonData)
    {
        if (!isset($jsonData['data']['recipient']['name']) || !isset($jsonData['data']['recipient']['email'])) {
            return 'invalid_recipient';
        }

        return 'verified';
    }

    private function validateIssuer($jsonData)
    {
        if (!isset($jsonData['data']['issuer']['name']) || !isset($jsonData['data']['issuer']['identityProof'])) {
            return 'invalid_issuer';
        }

        $issuer = $jsonData['data']['issuer'];
        $identityProof = $issuer['identityProof'];
        $key = $identityProof['key'];
        $location = $identityProof['location'];

        $dnsResponse = Http::get("https://dns.google/resolve?name={$location}&type=TXT");

        if (!$dnsResponse->successful()) {
            return 'invalid_issuer';
        }

        $txtRecords = $dnsResponse->json('Answer');

        if (empty($txtRecords)) {
            return 'invalid_issuer';
        }

        $validKey = false;
        foreach ($txtRecords as $record) {
            if (isset($record['data']) && strpos($record['data'], $key) !== false) {
                $validKey = true;
                break;
            }
        }

        if (!$validKey) {
            return 'invalid_issuer';
        }

        return 'verified';
    }

    private function validateSignature($jsonData)
    {
        $signature = $jsonData['signature'] ?? null;
        if (!$signature) {
            return 'invalid_signature';
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
            return 'invalid_signature';
        }

        return 'verified';
    }

    private function flattenArray($array, $prefix = '')
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

    private function storeResult(Request $request, $jsonData, $result)
    {
        FileVerification::create([
            'user_id' => auth()->id(),
            'file_type' => 'JSON',
            'verification_result' => $result,
        ]);

        return response()->json([
            'data' => [
                'issuer' => $jsonData['data']['issuer']['name'] ?? null,
                'result' => $result,
            ],
        ]);
    }
}
