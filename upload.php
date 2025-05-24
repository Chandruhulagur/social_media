<?php
session_start();
include('includes/auth.php');
include('includes/db.php');

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media'])) {
    if (!isset($_SESSION['user_id'])) {
        die("Unauthorized access.");
    }

    $user_id = $_SESSION['user_id'];
    $file = $_FILES['media'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'video/webm', 'video/ogg'];
    $max_size = 50 * 1024 * 1024; // 50MB

    if ($file['error'] === UPLOAD_ERR_OK) {
        if ($file['size'] > $max_size) {
            $message = "❌ File size exceeds 50MB limit.";
        } else {
            $media_type = mime_content_type($file['tmp_name']);

            if (in_array($media_type, $allowed_types)) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_name = uniqid("media_", true) . "." . $ext;
                $upload_path = "uploads/" . $new_name;

                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $stmt = $conn->prepare("INSERT INTO posts (user_id, media_path, media_type) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $user_id, $upload_path, $media_type);
                    $stmt->execute();
                    $message = "✅ Upload successful! Your media is now live!";
                } else {
                    $message = "❌ Failed to move uploaded file. Please try again.";
                }
            } else {
                $message = "❌ Invalid file type. We accept JPG, PNG, GIF images or MP4/WebM/Ogg videos.";
            }
        }
    } else {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => "❌ File is too large (server limit).",
            UPLOAD_ERR_FORM_SIZE => "❌ File is too large (form limit).",
            UPLOAD_ERR_PARTIAL => "❌ File upload was incomplete.",
            UPLOAD_ERR_NO_FILE => "❌ No file was selected.",
            UPLOAD_ERR_NO_TMP_DIR => "❌ Server configuration error.",
            UPLOAD_ERR_CANT_WRITE => "❌ Failed to write file to disk.",
            UPLOAD_ERR_EXTENSION => "❌ File upload stopped by extension."
        ];
        $message = $error_messages[$file['error']] ?? "❌ Unknown upload error (code: {$file['error']}).";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Your Moment | My Social Media</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --dark: #1e1e24;
            --light: #f8f9fa;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --border-radius: 12px;
            --box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .upload-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2.5rem;
            width: 100%;
            max-width: 500px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .upload-container:hover {
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }
        
        .logo {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        h2 {
            color: var(--dark);
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        
        .upload-description {
            color: #666;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }
        
        .message {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .success-message {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success);
            border: 1px solid var(--success);
        }
        
        .error-message {
            background-color: rgba(247, 37, 133, 0.1);
            color: var(--danger);
            border: 1px solid var(--danger);
        }
        
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .upload-area:hover {
            border-color: var(--primary-light);
            background-color: rgba(67, 97, 238, 0.03);
        }
        
        .upload-area.active {
            border-color: var(--primary);
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .upload-icon {
            font-size: 3rem;
            color: var(--primary-light);
            margin-bottom: 1rem;
        }
        
        .upload-text {
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .file-info {
            font-size: 0.8rem;
            color: #888;
            margin-top: 0.5rem;
        }
        
        #fileInput {
            display: none;
        }
        
        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn:hover {
            background-color: var(--secondary);
            transform: translateY(-2px);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn i {
            font-size: 1.1rem;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
        }
        
        .back-link:hover {
            color: var(--secondary);
            text-decoration: underline;
        }
        
        .preview-container {
            margin-top: 1.5rem;
            display: none;
        }
        
        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
        }
        
        @media (max-width: 576px) {
            .upload-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <div class="logo">
            <i class="fas fa-camera-retro"></i>
        </div>
        
        <h2>Share Your Moment</h2>
        <p class="upload-description">Upload images (JPG, PNG, GIF) or videos (MP4, WebM, Ogg). Max size: 50MB</p>
        
        <?php if (!empty($message)): ?>
            <div class="message <?= strpos($message, '❌') !== false ? 'error-message' : 'success-message' ?>">
                <?= str_replace(['✅', '❌'], ['<i class="fas fa-check-circle"></i>', '<i class="fas fa-exclamation-circle"></i>'], htmlspecialchars($message)) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="upload-area" id="uploadArea">
                <div class="upload-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <div class="upload-text">Drag & drop your file here or click to browse</div>
                <div class="file-info" id="fileInfo">No file selected</div>
                <input type="file" name="media" id="fileInput" accept="image/*,video/*" required>
            </div>
            
            <div class="preview-container" id="previewContainer">
                <img src="" alt="Preview" class="preview-image" id="previewImage">
            </div>
            
            <button type="submit" class="btn" id="submitBtn">
                <i class="fas fa-upload"></i> Upload Now
            </button>
        </form>
        
        <a href="feed.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Feed
        </a>
    </div>

    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const fileInfo = document.getElementById('fileInfo');
        const previewContainer = document.getElementById('previewContainer');
        const previewImage = document.getElementById('previewImage');
        const submitBtn = document.getElementById('submitBtn');
        
        // Handle drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('active');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('active');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('active');
            
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelection();
            }
        });
        
        // Handle click to browse
        uploadArea.addEventListener('click', () => {
            fileInput.click();
        });
        
        // Handle file selection
        fileInput.addEventListener('change', handleFileSelection);
        
        function handleFileSelection() {
            if (fileInput.files.length) {
                const file = fileInput.files[0];
                fileInfo.textContent = `${file.name} (${formatFileSize(file.size)})`;
                
                // Show preview if image
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        previewImage.src = e.target.result;
                        previewContainer.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else if (file.type.startsWith('video/')) {
                    previewImage.src = '';
                    previewContainer.style.display = 'block';
                    previewContainer.innerHTML = `<i class="fas fa-film" style="font-size: 3rem; color: var(--primary-light);"></i>
                                                <p>Video selected: ${file.name}</p>`;
                } else {
                    previewContainer.style.display = 'none';
                }
            }
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // Form submission feedback
        const form = document.getElementById('uploadForm');
        form.addEventListener('submit', () => {
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>