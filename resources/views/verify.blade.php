<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('File Upload Verification') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form id="upload-form" class="relative">
                        @csrf
                        <div class="upload-container" id="upload-container">
                            <input class="upload-input" id="file" type="file" name="file" required>
                            <div class="upload-overlay">
                                <div class="upload-text">
                                    <i class="fas fa-upload upload-icon"></i>
                                    <p>{{ __('Drag & drop your verifiable file') }}</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        (.trustdoc, .opencert, .oa, .pdf, .png, .svg)
                                    </p>
                                    <p>{{ __('or browse to choose a file') }}</p>
                                </div>
                            </div>
                        </div>
                        <div id="message" class="mt-4"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <meta name="api-token" content="{{ session('api_token') }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadContainer = document.getElementById('upload-container');
            const fileInput = document.getElementById('file');
            const form = document.getElementById('upload-form');
            const messageDiv = document.getElementById('message');

            // Handle file selection
            fileInput.addEventListener('change', handleFileSelect);

            // Handle drag and drop
            uploadContainer.addEventListener('dragover', function(event) {
                event.preventDefault();
                event.stopPropagation();
                uploadContainer.classList.add('dragging');
            });

            uploadContainer.addEventListener('dragleave', function(event) {
                event.preventDefault();
                event.stopPropagation();
                uploadContainer.classList.remove('dragging');
            });

            uploadContainer.addEventListener('drop', function(event) {
                event.preventDefault();
                event.stopPropagation();
                uploadContainer.classList.remove('dragging');
                const files = event.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    handleFileSelect();
                }
            });

            function handleFileSelect() {
                const file = fileInput.files[0];

                if (file) {
                    const formData = new FormData();
                    formData.append('file', file);
                    const token = document.querySelector('meta[name="api-token"]').getAttribute('content');

                    fetch('/api/verifyUpload', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${token}`,
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            return response.json().then(errorData => {
                                throw new Error(errorData.message || 'Validation error');
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        const issuer = data.issuer || 'Unknown';
                        const result = data.result || 'Unknown result';
                        messageDiv.innerHTML = `<p>Issuer: ${issuer}</p><p>Result: ${result}</p>`;
                    })
                    .catch(error => {
                        if (error.message === 'Validation error') {
                            messageDiv.innerHTML = `<p>Validation error: ${error.message}</p>`;
                        } else {
                            messageDiv.innerHTML = `<p>An unexpected error occurred: ${error.message}</p>`;
                        }
                    });
                }
            }
        });
    </script>

    <style>
        .upload-container {
            position: relative;
            width: 100%;
            height: 300px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            background-color: #f9f9f95e;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            overflow: hidden;
        }

        .upload-container.dragging {
            border-color: #007bff;
            background-color: #e6f0ff;
        }

        .upload-input {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .upload-overlay {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .upload-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ffffff;
        }

        .upload-text p {
            margin: 0.5rem 0;
        }
    </style>
</x-app-layout>
