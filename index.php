<?php

define('ENVIRONMENT', 'Development'); // Accepted values: Development or Production.

if (ENVIRONMENT !== 'Production')
{
    error_reporting(E_ALL|E_STRICT);
} else {
    error_reporting(0);
}

require __DIR__ . '/src/Bootstrap.php';
