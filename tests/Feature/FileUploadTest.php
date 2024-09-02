<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_upload_with_invalid_type()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('document.txt', 2048, 'text/plain');

        $response = $this->actingAs($this->user())
                         ->postJson('/api/verifyUpload', [
                             'file' => $file,
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['file']);
    }

    public function test_file_upload_with_missing_file()
    {
        $response = $this->actingAs($this->user())
                         ->postJson('/api/verifyUpload', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['file']);
    }

    public function test_file_upload_with_invalid_recipient()
    {
        $invalidJson = json_encode([
            "data" => [
                "id" => "63c79bd9303530645d1cca00",
                "name" => "Certificate of Completion",
                "recipient" => [
                    "first_name" => "Marty McFly",
                    "phone_number" => "60123456789"
                ],
                "issuer" => [
                    "name" => "Accredify",
                    "identityProof" => [
                        "type" => "DNS-DID",
                        "key" => "did:ethr:0x05b642ff12a4ae545357d82ba4f786f3aed84214#controller",
                        "location" => "ropstore.accredify.io"
                    ]
                ],
                "issued" => "2022-12-23T00:00:00+08:00"
            ],
            "signature" => [
                "type" => "SHA3MerkleProof",
                "targetHash" => "288f94aadadf486cfdad84b9f4305f7d51eac62db18376d48180cc1dd2047a0e"
            ]
        ]);

        $file = UploadedFile::fake()->createWithContent('document.json', $invalidJson);

        $response = $this->actingAs($this->user())
                        ->postJson('/api/verifyUpload', [
                            'file' => $file,
                        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'issuer' => 'Accredify',
                    'result' => 'invalid_recipient',
                ]);
    }


    public function test_file_upload_with_invalid_issuer()
    {
        $invalidJson = json_encode([
            "data" => [
                "id" => "63c79bd9303530645d1cca00",
                "name" => "Certificate of Completion",
                "recipient" => [
                    "name" => "Marty McFly",
                    "email" => "marty.mcfly@gmail.com"
                ],
                "issuer" => [
                    "name" => "Invalid Issuer",
                    "identityProof" => [
                        "type" => "DNS-DID",
                        "key" => "did:ethr:0x05b642ff12a4ae54535755555f786f3aed84214#controller",
                        "location" => "invalid.issuer.io"
                    ]
                ],
                "issued" => "2022-12-23T00:00:00+08:00"
            ],
            "signature" => [
                "type" => "SHA3MerkleProof",
                "targetHash" => "288f94aadadf486cfdad84b9f4305f7d51eac62db18376d48180cc1dd2047a0e"
            ]
        ]);

        $file = UploadedFile::fake()->createWithContent('document.json', $invalidJson);

        $response = $this->actingAs($this->user())
                        ->postJson('/api/verifyUpload', [
                            'file' => $file,
                        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'issuer' => 'Invalid Issuer',
                    'result' => 'invalid_issuer',
                ]);
    }

    public function test_file_upload_with_invalid_signature()
    {
        $invalidJson = json_encode([
            "data" => [
                "id" => "63c79bd9303530645d1cca00",
                "name" => "Certificate of Completion",
                "recipient" => [
                    "name" => "Marty McFly",
                    "email" => "marty.mcfly@gmail.com"
                ],
                "issuer" => [
                    "name" => "Accredify",
                    "identityProof" => [
                        "type" => "DNS-DID",
                        "key" => "did:ethr:0x05b642ff12a4ae545357d82ba4f786f3aed84214#controller",
                        "location" => "ropstore.accredify.io"
                    ]
                ],
                "issued" => "2022-12-23T00:00:00+08:00"
            ],
            "signature" => [
                "type" => "SHA3MerkleProof",
                "targetHash" => "288f94aadadf416cfdad84b9f4305f7d51eac62db18376d48180cc1dd2047a0e"
            ]
        ]);

        $file = UploadedFile::fake()->createWithContent('document.json', $invalidJson);
        $response = $this->actingAs($this->user())
                        ->postJson('/api/verifyUpload', [
                            'file' => $file,
                        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'issuer' => 'Accredify',
                    'result' => 'invalid_signature',
                ]);
    }

    public function test_successful_file_upload()
    {
        $validJson = json_encode([
            "data" => [
                "id" => "63c79bd9303530645d1cca00",
                "name" => "Certificate of Completion",
                "recipient" => [
                    "name" => "Marty McFly",
                    "email" => "marty.mcfly@gmail.com"
                ],
                "issuer" => [
                    "name" => "Accredify",
                    "identityProof" => [
                        "type" => "DNS-DID",
                        "key" => "did:ethr:0x05b642ff12a4ae545357d82ba4f786f3aed84214#controller",
                        "location" => "ropstore.accredify.io"
                    ]
                ],
                "issued" => "2022-12-23T00:00:00+08:00"
            ],
            "signature" => [
                "type" => "SHA3MerkleProof",
                "targetHash" => "288f94aadadf486cfdad84b9f4305f7d51eac62db18376d48180cc1dd2047a0e"
            ]
        ]);

        $file = UploadedFile::fake()->createWithContent('document.json', $validJson);
        $response = $this->actingAs($this->user())
                        ->postJson('/api/verifyUpload', [
                            'file' => $file,
                        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'issuer' => 'Accredify',
                    'result' => 'verified',
                ]);
    }

    /**
     * Helper method to create an authenticated user.
     *
     * @return \App\Models\User
     */
    protected function user()
    {
        return \App\Models\User::factory()->create();
    }
}
