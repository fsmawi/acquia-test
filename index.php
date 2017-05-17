<?php
$file = './index.html';
header('Content-Type: text/html');
header('Content-Length: ' . filesize($file));
readfile($file);
