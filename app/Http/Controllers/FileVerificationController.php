<?php

namespace App\Http\Controllers;

use App\Services\FileVerificationService;
use App\Http\Requests\VerifyUploadRequest;

class FileVerificationController extends Controller
{
    protected $fileVerificationService;

    public function __construct(FileVerificationService $fileVerificationService)
    {
        $this->fileVerificationService = $fileVerificationService;
    }

    public function __invoke(VerifyUploadRequest $request)
    {
        $file = $request->file('file');
        $jsonData = json_decode(file_get_contents($file->getRealPath()), true);
        return $this->fileVerificationService->verifyFileUpload($jsonData);
    }
}
