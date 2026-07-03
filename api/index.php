<?php

// Log untuk debugging (opsional)
error_log('=== api/index.php loaded ===');

// Forward all requests to the Laravel application
require __DIR__.'/../public/index.php';