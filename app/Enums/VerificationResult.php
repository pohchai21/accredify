<?php

namespace App\Enums;

enum VerificationResult: string
{
    case Verified = 'verified';
    case InvalidRecipient = 'invalid_recipient';
    case InvalidIssuer = 'invalid_issuer';
    case InvalidSignature = 'invalid_signature';
}
