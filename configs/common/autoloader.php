<?php

return [
    // PSR-0 autoload paths
    'paths' => [
        './application/controllers',
        './application/classes',
        './src',
    ],
    // paths priority (cannot be overridden by external modules)
    'priority'=>[
        './application/controllers',
        './application/models',
        './application/library',
    ],
    // Use class maps
    'useMap' => true,
    // Use class map (Reduce IO load during autoload)
    // Class map file path (string / false)
    'map' => 'classmap.php',
    // PSR-4 autoload paths
    'psr-4' =>[
        'Zend\\Stdlib' => './vendor/zendframework/zend-stdlib/src',
        'Zend\\Db' => './vendor/zendframework/zend-db/src',
        'Zend\\Mail' => './vendor/zendframework/zend-mail/src',
        'Zend\\Mime' => './vendor/zendframework/zend-mime/src',
        'Zend\\Validator' => './vendor/zendframework/zend-validator/src',
        'Zend\\Loader' => './vendor/zendframework/zend-loader/src',
    ],
    // Paths to be excluded from class map
    'noMap' =>[

    ]
];