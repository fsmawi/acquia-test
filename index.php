<?php

$username = 'Acquia';
$password = 'pipelines2017';

if (!(isset($_SERVER['PHP_AUTH_USER']) && ($_SERVER['PHP_AUTH_USER']==$username && $_SERVER['PHP_AUTH_PW']==$password))) {
    header('WWW-Authenticate: Basic realm="Acquia Pipelines"');
    header('HTTP/1.0 401 Unauthorized');
    // Fallback message when the user presses cancel / escape
    echo 'Access denied';
    exit;
}

header('Content-Type: text/html');
header('Content-Length: ' . filesize($file));
readfile('./index.html');
