<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Upload</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .upload-form {
            border: 2px dashed #dee2e6;
            padding: 2rem;
            text-align: center;
            margin: 2rem 0;
            border-radius: 10px;
        }
        #imagePreview {
            max-width: 300px;
            max-height: 300px;
            margin: 20px auto;
            display: none;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .custom-file-input {
            cursor: pointer;
        }
        #loadingSpinner {
            display: none;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h1 class="text-center mb-4">Image Upload</h1>
                        
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        <div class="upload-form">
                            <form id="uploadForm" action="{{ route('upload') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <label for="image" class="form-label">Choose an image</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*" required onchange="previewImage(this)">
                                </div>
                                
                                <img id="imagePreview" src="#" alt="Preview" class="img-fluid">
                                
                                <button type="submit" class="btn btn-primary mt-3" id="uploadButton">
                                    <span id="buttonText"><i class="bi bi-upload"></i> Upload Image</span>
                                    <span id="loadingSpinner" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }

        document.getElementById('uploadForm').addEventListener('submit', function() {
            const button = document.getElementById('uploadButton');
            const spinner = document.getElementById('loadingSpinner');
            const buttonText = document.getElementById('buttonText');
            
            button.disabled = true;
            spinner.style.display = 'inline-block';
            buttonText.style.display = 'none';
        });
    </script>
</body>
</html>
