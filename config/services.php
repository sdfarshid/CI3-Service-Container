<?php
/**
 * ServiceContainer Configuration File
 *
 * This file defines how services are registered and managed in the ServiceContainer.
 * The configuration is divided into three main sections:
 *
 * 1. singletonServices: Services that maintain a single instance (recommended for most services)
 * 2. nonSingletonServices: Services that create new instances on each request
 * 3. interfaces: Interface to implementation bindings for dependency injection
 * 4. parameters: Simple parameter bindings (optional)
 *
 * @author Your Name
 * @version 1.0
 */

defined('BASEPATH') OR exit('No direct script access allowed');

return [

    /**
     * SINGLETON SERVICES
     * ==================
     * Services registered here will maintain a single instance throughout the application lifecycle.
     * This is the recommended approach for most services like databases, loggers, configurations, etc.
     *
     * Registration Types:
     * - Class string: 'ServiceName' => ClassName::class
     * - Closure: 'ServiceName' => function($container) { return new Service(); }
     * - Array: 'ServiceName' => ['key' => 'value'] (for configuration)
     * - Instance: 'ServiceName' => new Service() (pre-built instance)
     */
    'singletonServices' => [

        // CodeIgniter Instance - Always available
        'CI_instance' => function () {
            return get_instance();
        },

        // Example: Configuration Arrays
        'AppConfig' => [
            'app_name' => 'My CI3 Application',
            'version' => '1.0.0',
            'debug' => ENVIRONMENT === 'development',
            'timezone' => 'UTC',
            'cache_ttl' => 3600
        ],

        // Example: Email Configuration
        'EmailConfig' => [
            'protocol' => 'smtp',
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 587,
            'smtp_user' => 'your-email@gmail.com',
            'smtp_pass' => 'your-password',
            'mailtype' => 'html',
            'charset' => 'utf-8'
        ],

        // Example: Database Configuration Service
        'DatabaseConfig' => function ($container) {
            $ci = $container->get('CI_instance');
            return [
                'hostname' => $ci->db->hostname,
                'username' => $ci->db->username,
                'database' => $ci->db->database,
                'dbdriver' => $ci->db->dbdriver
            ];
        },

        // Example: Logger Service
        'Logger' => function ($container) {
            $config = $container->get('AppConfig');
            return new Logger($config['debug']);
        },

        // Example: Cache Service
        'CacheService' => function ($container) {
            $ci = $container->get('CI_instance');
            $ci->load->driver('cache');
            return $ci->cache;
        },

        // Example: Email Service with Dependencies
        'EmailService' => function ($container) {
            $ci = $container->get('CI_instance');
            $config = $container->get('EmailConfig');
            $ci->load->library('email', $config);
            return $ci->email;
        },

        // Example: Custom Service Class
        // UserService::class => UserService::class,

        // Example: Service with Complex Dependencies
        // 'NotificationService' => function ($container) {
        //     return new NotificationService(
        //         $container->get('EmailService'),
        //         $container->get('Logger'),
        //         $container->get('AppConfig')
        //     );
        // },

    ],

    /**
     * NON-SINGLETON SERVICES
     * ======================
     * Services registered here will create a new instance every time they are requested.
     * Use this for services that should not maintain state between requests.
     *
     * Examples: HTTP requests, form validators, temporary data processors
     */
    'nonSingletonServices' => [

        // Example: HTTP Request Handler (new instance each time)
        // 'HttpRequest' => HttpRequest::class,

        // Example: Form Validator (stateless, new instance preferred)
        // 'FormValidator' => FormValidator::class,

        // Example: Data Transfer Objects
        // 'UserDTO' => UserDTO::class,

        // Example: Temporary File Handler
        // 'TempFileHandler' => function ($container) {
        //     return new TempFileHandler($container->get('AppConfig')['temp_path']);
        // },

    ],

    /**
     * INTERFACE BINDINGS
     * ==================
     * Bind interfaces to their concrete implementations.
     * This enables dependency injection based on interfaces rather than concrete classes.
     *
     * Benefits:
     * - Loose coupling
     * - Easy testing (mock implementations)
     * - Flexible implementation switching
     */
    'interfaces' => [

        // Example: Logger Interface
        // 'LoggerInterface' => FileLogger::class,

        // Example: Repository Pattern
        // 'UserRepositoryInterface' => DatabaseUserRepository::class,

        // Example: Cache Interface
        // 'CacheInterface' => function ($container) {
        //     return $container->get('CacheService');
        // },

        // Example: Payment Gateway Interface
        // 'PaymentGatewayInterface' => StripePaymentGateway::class,

    ],

    /**
     * PARAMETERS
     * ==========
     * Simple parameter bindings for primitive values.
     * These can be injected into constructors by parameter name.
     */
    'parameters' => [

        // Example: API Keys
        // 'api_key' => 'your-api-key-here',
        // 'secret_key' => 'your-secret-key-here',

        // Example: File Paths
        // 'upload_path' => APPPATH . 'uploads/',
        // 'log_path' => APPPATH . 'logs/',

        // Example: Configuration Values
        // 'max_file_size' => 2048, // KB
        // 'allowed_types' => 'gif|jpg|png|pdf',

    ]

];
