<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('faculty');
readfile(__DIR__ . '/status.html');
