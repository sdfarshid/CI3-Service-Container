<?php

defined('BASEPATH') OR exit('No direct script access allowed');



return [
    // Simple class registration
    'Logger' => Logger::class,

    //OR
    EmailService::class => EmailService::class,

    // Service with dependency injection
    'EmailService' => function ($container) {
        return new EmailService($container->get('Logger'));
    },

    // Simple Array Binding
    "WebMailConfig" =>  [
        'host' => '',
        'username' => '',
        'password' => '',
    ],

    //Get A service for adding as dependency
    IMAPConnection::class => function ($container) {
        $config = $container->get('WebMailConfig');
        return new IMAPConnection(
            $config['host'],
            $config['username'],
            $config['password']
        );
    },

    // Make a service for adding as dependency
    EmailManager::class => function ($container) {
        return new EmailManager($container->make(IMAPConnection::class));
    },

    // Pre-built instance
    'ConfigService' => new ConfigService(),
];