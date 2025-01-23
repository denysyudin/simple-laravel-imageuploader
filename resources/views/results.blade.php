<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generated Image Result</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .image-container {
            position: relative;
            display: inline-block;
            margin: 20px auto;
        }
        #generatedImage {
            max-width: 100%;
            height: auto;
        }
        #overlayPreview {
            position: absolute;
            cursor: move;
            max-width: 200px;
            max-height: 200px;
            display: none;
            opacity: 0.7;
            transition: opacity 0.3s;
            transform-origin: center;
        }
        #overlayPreview:hover {
            opacity: 1;
        }
        #imagePreview {
            max-width: 300px;
            max-height: 300px;
            margin: 20px auto;
            display: none;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        #loadingSpinner {
            display: none;
        }
        .position-controls {
            margin-top: 10px;
            display: none;
        }
        .position-controls input {
            width: 100px;
            margin: 0 5px;
        }
        .resize-handle {
            width: 10px;
            height: 10px;
            background-color: white;
            border: 1px solid #666;
            position: absolute;
            border-radius: 50%;
        }
        .resize-handle.nw { top: -5px; left: -5px; cursor: nw-resize; }
        .resize-handle.ne { top: -5px; right: -5px; cursor: ne-resize; }
        .resize-handle.sw { bottom: -5px; left: -5px; cursor: sw-resize; }
        .resize-handle.se { bottom: -5px; right: -5px; cursor: se-resize; }
        
        .size-controls {
            margin-top: 10px;
            display: none;
        }
        .size-controls input {
            width: 80px;
            margin: 0 5px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h1 class="text-center mb-4">Generated Image</h1>
                        <div class="text-center">
                            <div class="image-container">
                                <img id="generatedImage" src="{{ $generatedImage }}" alt="Generated Image" class="rounded">
                                <div id="overlayContainer">
                                    <img id="overlayPreview" src="#" alt="Overlay Preview">
                                    <div class="resize-handle nw"></div>
                                    <div class="resize-handle ne"></div>
                                    <div class="resize-handle sw"></div>
                                    <div class="resize-handle se"></div>
                                </div>
                            </div>
                            
                            <form id="uploadForm" action="{{ route('merge') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="base_image" value="{{ $generatedImage }}">
                                <input type="hidden" name="overlay_x" id="overlay_x" value="0">
                                <input type="hidden" name="overlay_y" id="overlay_y" value="0">
                                <input type="hidden" name="overlay_width" id="overlay_width" value="0">
                                <input type="hidden" name="overlay_height" id="overlay_height" value="0">
                                
                                <div class="mb-3">
                                    <label for="image" class="form-label">Upload image to overlay</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*" required onchange="previewImage(this)">
                                </div>

                                <div class="position-controls" id="positionControls">
                                    <label>Position: X: <input type="number" id="posX" step="1"> Y: <input type="number" id="posY" step="1"></label>
                                </div>
                                
                                <div class="size-controls" id="sizeControls">
                                    <label>Size: W: <input type="number" id="width" step="1" min="10"> H: <input type="number" id="height" step="1" min="10"></label>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary" id="uploadButton">
                                        <span id="buttonText">Generate New Image</span>
                                        <span id="loadingSpinner" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                    </button>
                                    <a href="{{ route('home') }}" class="btn btn-secondary ms-2">home</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let isDragging = false;
        let currentX;
        let currentY;
        let initialX;
        let initialY;
        let xOffset = 0;
        let yOffset = 0;
        let currentWidth = 200;
        let currentHeight = 200;
        let isResizing = false;
        let currentResizeHandle = null;

        function previewImage(input) {
            const overlay = document.getElementById('overlayPreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    overlay.src = e.target.result;
                    overlay.style.display = 'block';
                    document.getElementById('positionControls').style.display = 'block';
                    document.getElementById('sizeControls').style.display = 'block';
                    
                    // Center the overlay initially
                    const container = document.querySelector('.image-container');
                    xOffset = (container.offsetWidth - overlay.offsetWidth) / 2;
                    yOffset = (container.offsetHeight - overlay.offsetHeight) / 2;
                    setTranslate(xOffset, yOffset, overlay);
                    updatePositionInputs();
                    
                    // Set initial size
                    currentWidth = overlay.offsetWidth;
                    currentHeight = overlay.offsetHeight;
                    updateSizeInputs();
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }

        function dragStart(e) {
            if (e.type === "touchstart") {
                initialX = e.touches[0].clientX - xOffset;
                initialY = e.touches[0].clientY - yOffset;
            } else {
                initialX = e.clientX - xOffset;
                initialY = e.clientY - yOffset;
            }

            if (e.target === document.getElementById('overlayPreview')) {
                isDragging = true;
            }
        }

        function dragEnd(e) {
            initialX = currentX;
            initialY = currentY;
            isDragging = false;
        }

        function drag(e) {
            if (isDragging) {
                e.preventDefault();
                
                if (e.type === "touchmove") {
                    currentX = e.touches[0].clientX - initialX;
                    currentY = e.touches[0].clientY - initialY;
                } else {
                    currentX = e.clientX - initialX;
                    currentY = e.clientY - initialY;
                }

                xOffset = currentX;
                yOffset = currentY;

                setTranslate(currentX, currentY, document.getElementById('overlayPreview'));
                updatePositionInputs();
            }
        }

        function setTranslate(xPos, yPos, el) {
            el.style.transform = `translate3d(${xPos}px, ${yPos}px, 0)`;
            document.getElementById('overlay_x').value = Math.round(xPos);
            document.getElementById('overlay_y').value = Math.round(yPos);
        }

        function updatePositionInputs() {
            document.getElementById('posX').value = Math.round(xOffset);
            document.getElementById('posY').value = Math.round(yOffset);
        }

        // Position input handlers
        document.getElementById('posX').addEventListener('change', function(e) {
            xOffset = parseInt(e.target.value) || 0;
            setTranslate(xOffset, yOffset, document.getElementById('overlayPreview'));
        });

        document.getElementById('posY').addEventListener('change', function(e) {
            yOffset = parseInt(e.target.value) || 0;
            setTranslate(xOffset, yOffset, document.getElementById('overlayPreview'));
        });

        // Add event listeners
        const container = document.querySelector('.image-container');
        container.addEventListener('touchstart', dragStart, false);
        container.addEventListener('touchend', dragEnd, false);
        container.addEventListener('touchmove', drag, false);
        container.addEventListener('mousedown', dragStart, false);
        container.addEventListener('mouseup', dragEnd, false);
        container.addEventListener('mousemove', drag, false);

        function startResize(e) {
            if (e.target.classList.contains('resize-handle')) {
                isResizing = true;
                currentResizeHandle = e.target.classList[1];
                e.preventDefault();
            }
        }

        function stopResize() {
            isResizing = false;
            currentResizeHandle = null;
        }

        function resize(e) {
            if (!isResizing) return;
            e.preventDefault();

            const overlay = document.getElementById('overlayPreview');
            const rect = overlay.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            switch(currentResizeHandle) {
                case 'se':
                    currentWidth = Math.max(50, x);
                    currentHeight = Math.max(50, y);
                    break;
                case 'sw':
                    currentWidth = Math.max(50, rect.width - x);
                    currentHeight = Math.max(50, y);
                    xOffset += rect.width - currentWidth;
                    break;
                case 'ne':
                    currentWidth = Math.max(50, x);
                    currentHeight = Math.max(50, rect.height - y);
                    yOffset += rect.height - currentHeight;
                    break;
                case 'nw':
                    currentWidth = Math.max(50, rect.width - x);
                    currentHeight = Math.max(50, rect.height - y);
                    xOffset += rect.width - currentWidth;
                    yOffset += rect.height - currentHeight;
                    break;
            }

            setSize(currentWidth, currentHeight);
            setTranslate(xOffset, yOffset, overlay);
            updateSizeInputs();
        }

        function setSize(width, height) {
            const overlay = document.getElementById('overlayPreview');
            overlay.style.width = `${width}px`;
            overlay.style.height = `${height}px`;
            document.getElementById('overlay_width').value = Math.round(width);
            document.getElementById('overlay_height').value = Math.round(height);
        }

        function updateSizeInputs() {
            document.getElementById('width').value = Math.round(currentWidth);
            document.getElementById('height').value = Math.round(currentHeight);
        }

        // Size input handlers
        document.getElementById('width').addEventListener('change', function(e) {
            currentWidth = parseInt(e.target.value) || currentWidth;
            setSize(currentWidth, currentHeight);
        });

        document.getElementById('height').addEventListener('change', function(e) {
            currentHeight = parseInt(e.target.value) || currentHeight;
            setSize(currentWidth, currentHeight);
        });

        // Add resize event listeners
        document.addEventListener('mousedown', startResize);
        document.addEventListener('mousemove', resize);
        document.addEventListener('mouseup', stopResize);
        document.addEventListener('mouseleave', stopResize);

        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const button = document.getElementById('uploadButton');
            const spinner = document.getElementById('loadingSpinner');
            const buttonText = document.getElementById('buttonText');
            
            button.disabled = true;
            spinner.style.display = 'inline-block';
            buttonText.style.display = 'none';

            fetch(this.action, {
                method: 'POST',
                body: new FormData(this)
            })
            .then(response => {
                if (response.ok) {
                    return response.blob();
                }
                throw new Error('Network response was not ok');
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'merged_image.png';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
            })
            .catch(error => {
                alert('An error occurred while processing your request.');
            })
            .finally(() => {
                button.disabled = false;
                spinner.style.display = 'none';
                buttonText.style.display = 'inline';
            });
        });
    </script>
</body>
</html> 