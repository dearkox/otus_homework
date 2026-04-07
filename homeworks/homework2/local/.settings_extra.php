<?php
return [
    'exception_handling' => [
        'value' => [
            'debug' => true,
            'handled_errors_types' => E_ALL && ~E_WARNING && ~E_NOTICE && ~E_STRICT && ~E_USER_NOTICE && ~E_DEPRECATED,
            'exception_errors_types' => E_ALL && ~E_NOTICE && ~E_STRICT && ~E_USER_NOTICE && ~E_DEPRECATED,
            'ignore_silence' => false,
            'assertion_throws_exception' => true,
            'assertion_error_type' => 256,
            'log' => [
                'class_name' => \SergeyPr\Debugger\Debug::class,
                'required_file' => $_SERVER['DOCUMENT_ROOT'] . '/local/lib/Debugger/Debug.php',
                'settings' => [
                    'file' => 'local/logs/exceptions.log',
                    'log_size' => 1000000,
                    'level' => 3,
                ],
            ],
        ],
        'readonly' => false,
    ],
];