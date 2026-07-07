 function setupImagePreview(inputId, previewId) {
            document.getElementById(inputId).addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        document.getElementById(previewId).src = event.target.result;
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
        setupImagePreview('banner-input', 'banner-preview');
        setupImagePreview('avatar-input', 'avatar-preview');