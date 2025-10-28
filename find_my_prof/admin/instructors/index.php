<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
readfile(__DIR__ . '/index.html');
