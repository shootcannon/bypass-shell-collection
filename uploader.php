<?php
$uploadDir = 'uploads/';
$response = ['status' => '', 'message' => ''];

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $response['status'] = 'error';
        $response['message'] = 'Upload failed with error code: ' . $file['error'];
        echo json_encode($response);
        exit;
    }
    
    $fileName = basename($file['name']);
    $targetPath = $uploadDir . time() . '_' . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $response['status'] = 'success';
        $response['message'] = 'File uploaded successfully';
        $response['file'] = $targetPath;
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Failed to move uploaded file';
    }
    
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Uploader</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .upload-container { border: 2px dashed #ccc; padding: 30px; text-align: center; border-radius: 10px; }
        #fileInput { margin: 20px 0; }
        button { background: #4CAF50; color: white; border: none; padding: 10px 20px; cursor: pointer; border-radius: 5px; }
        button:hover { background: #45a049; }
        #result { margin-top: 20px; padding: 10px; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .file-list { margin-top: 30px; }
        .file-list ul { list-style: none; padding: 0; }
        .file-list li { background: #f4f4f4; margin: 5px 0; padding: 10px; border-radius: 5px; word-break: break-all; }
    </style>
</head>
<body>
    <div class="upload-container">
        <h2>File Uploader</h2>
        <form id="uploadForm" enctype="multipart/form-data">
            <input type="file" name="file" id="fileInput" required>
            <br>
            <button type="submit">Upload File</button>
        </form>
        <div id="result"></div>
    </div>
    <div class="file-list">
        <h3>Uploaded Files</h3>
        <ul id="fileList"></ul>
    </div>

    <script>
        document.getElementById('uploadForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            const fileInput = document.getElementById('fileInput');
            formData.append('file', fileInput.files[0]);
            
            const resultDiv = document.getElementById('result');
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    resultDiv.className = 'success';
                    resultDiv.innerHTML = '✅ ' + data.message + ': ' + data.file;
                    loadFileList();
                } else {
                    resultDiv.className = 'error';
                    resultDiv.innerHTML = '❌ ' + data.message;
                }
            } catch (error) {
                resultDiv.className = 'error';
                resultDiv.innerHTML = '❌ Upload failed: ' + error.message;
            }
            
            fileInput.value = '';
        });
        
        async function loadFileList() {
            try {
                const response = await fetch('?action=list');
                const files = await response.json();
                const fileList = document.getElementById('fileList');
                fileList.innerHTML = '';
                
                if (files.length === 0) {
                    fileList.innerHTML = '<li>No files uploaded yet</li>';
                    return;
                }
                
                files.forEach(file => {
                    const li = document.createElement('li');
                    const fileUrl = '<?php echo $uploadDir; ?>' + file;
                    li.innerHTML = `<a href="${fileUrl}" target="_blank">${file}</a>`;
                    fileList.appendChild(li);
                });
            } catch (error) {
                console.error('Failed to load file list:', error);
            }
        }
        
        if (window.location.href.indexOf('?action=list') === -1) {
            loadFileList();
        }
    </script>
</body>
</html>

<?php
if (isset($_GET['action']) && $_GET['action'] === 'list') {
    header('Content-Type: application/json');
    $files = scandir($uploadDir);
    $files = array_diff($files, ['.', '..']);
    echo json_encode(array_values($files));
    exit;
}
?>
