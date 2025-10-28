<?php
// Basic app configuration
// Adjust DB credentials as needed for your XAMPP setup
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'find_my_prof',
        'username' => 'root',
        'password' => '', // set if your MySQL root has a password
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'base_url' => '/find_my_prof', // used for redirects/links
    ],
];
