<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileVerification extends Model
{
    use HasFactory;

    protected $table = 'file_verifications';

    protected $fillable = [
        'user_id',
        'file_type',
        'verification_result',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
