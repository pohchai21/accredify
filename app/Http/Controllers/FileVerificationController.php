<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\DocumentResource;
use App\Services\FileVerificationService;
use App\Http\Requests\VerifyUploadRequest;
use Symfony\Component\HttpFoundation\Response;

class FileVerificationController extends Controller
{
    protected $fileVerificationService;

    public function __construct(FileVerificationService $fileVerificationService)
    {
        $this->fileVerificationService = $fileVerificationService;
    }

    public function __invoke(VerifyUploadRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $jsonData = json_decode(file_get_contents($file->getRealPath()), true);
        $verificationResultDTO = $this->fileVerificationService->verifyFileUpload($jsonData);

        return new JsonResponse(new DocumentResource($verificationResultDTO), Response::HTTP_OK);
    }
}
