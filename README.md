# ServiceContainer for CodeIgniter 3

`ServiceContainer` is a powerful tool for managing services and handling dependency injection in the CodeIgniter 3 framework. It allows you to define, manage, and utilize services easily and efficiently.

---

## Features

- **Singleton Pattern**: Ensures that only one instance of the ServiceContainer exists throughout the application.
- **Auto-Dependency Resolution**: Automatically resolves and injects dependencies for classes and services.
- **Configurable Services**: Define services in a configuration file for simplified management.
- **Lazy Loading**: Services are only instantiated when they are needed, improving performance.
- **CodeIgniter Integration**: Seamlessly integrates with CodeIgniter 3, with access to its instance and features.

---

## Installation

1. Place the `ServiceContainer.php` file into your `application/libraries` directory.

2. Create a `services.php` file in the `application/config` directory to define your services.

3. (Optional) Ensure your `application/config/autoload.php` is set up for custom autoloading if required.

---

## Configuring Services

The `services.php` file allows you to define all your services. It should return an array of service definitions:

### Example:

```php
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
```

---

## Usage

### Accessing the ServiceContainer

You can access the ServiceContainer anywhere in your application using the `getInstance` method:

```php
$container = ServiceContainer::getInstance();

//OR 
function AppContainer(){

    return  ServiceContainer::getInstance();
}



```

### Retrieving a Service

Retrieve a service that has been registered or bound to the container:

```php
$logger = $container->get('Logger');
```

If the service has dependencies, they will be automatically resolved.

### Binding a Service

Use the `bind` method to register a service with a custom resolver:

```php
$container->bind('Logger', function ($container) {
    return new Logger();
});
```

### Registering a Service

Use the `register` method to register and immediately make a service available:

```php
$container->register('EmailService', EmailService::class);
```

### Dynamically Creating a Service

The `make` method allows you to dynamically resolve a service:

```php
$emailService = $container->make('EmailService');
```

### Auto-Dependency Injection

When retrieving or resolving a class, the ServiceContainer automatically resolves and injects its dependencies:

```php
class NotificationService {
    public function __construct(Logger $logger, Config $config) {
        // Dependencies are resolved automatically
    }
}

$notificationService = $container->get(NotificationService::class);
```

---

## Predefined CodeIgniter Integration

The container comes with built-in integration for CodeIgniter 3:

### Access the CodeIgniter instance

The `CI_instance` service provides access to the current CI instance:

```php
$ci = $container->get('CI_instance');
```

---

## Debugging and Utilities

### List All Registered Services

```php
$services = $container->listServices();
```

### List All Bindings

```php
$bindings = $container->listBindings();
```

### Get Service Status

```php
$statuses = $container->listServicesWithStatus();
```

---

## Singleton vs Non-Singleton

### Singleton in `ServiceContainer`

#### How Singleton Works:
- When you register or bind a service in the `ServiceContainer`, the service instance is stored in the `services` or `bindings` array.
- If a service already exists in the `services` array, it is reused whenever requested.
- This ensures that the same instance is returned every time you call `get` or `register`.

#### Example of Singleton:

```php
// Registering a service as a Singleton
ServiceContainer::getInstance()->bind('Logger', function () {
    return new Logger();
});

// Accessing the service multiple times
$logger1 = ServiceContainer::getInstance()->get('Logger');
$logger2 = ServiceContainer::getInstance()->get('Logger');

// Checking if both instances are the same
var_dump($logger1 === $logger2); // Outputs: true (Singleton)
```

### Non-Singleton in `ServiceContainer`

#### How Non-Singleton Works:
- By default, if you use `make` directly or manually resolve a service without storing it in `services` or `bindings`, a new instance of the class will be created each time.
- This is because `make` or `resolve` does not cache the instance unless explicitly registered.

#### Example of Non-Singleton:

```php
// Resolving a service dynamically (Non-Singleton)
$logger1 = ServiceContainer::getInstance()->make(Logger::class);
$logger2 = ServiceContainer::getInstance()->make(Logger::class);

// Checking if both instances are the same
var_dump($logger1 === $logger2); // Outputs: false (Non-Singleton)
```

### Comparison Table

| **Aspect**        | **Singleton**                                    | **Non-Singleton**                                |
|--------------------|--------------------------------------------------|-------------------------------------------------|
| **Definition**     | Single instance shared throughout the app.       | New instance created every time.               |
| **Method Used**    | `bind`, `register`, or `get`                     | `make` or direct `resolve`.                    |
| **Instance Sharing** | Same instance for all calls.                    | Separate instance for each call.               |
| **Performance**    | More efficient for frequently used services.     | May have higher memory and CPU cost.           |


---

## Method Comparison

| **Method**     | **Purpose**                           | **Behavior**                                                                                   | **Use Case**                              |
|----------------|---------------------------------------|-----------------------------------------------------------------------------------------------|-------------------------------------------|
| **make**       | Create or retrieve a service          | Returns the service only if it exists in `bindings` or `services`, otherwise throws an error. | For dynamically creating or accessing services. |
| **bind**       | Define a resolver for a service       | Registers a method for creating the service without instantiating it immediately.             | For defining services using lazy loading. |
| **register**   | Register and create a service         | Combines registration and creation; ensures the service is available immediately.             | For registering and creating services simultaneously. |
| **get**        | Retrieve or resolve a service         | Looks up `services` or `bindings`, and resolves if not explicitly registered.                 | For accessing already registered services. |


---


## Best Practices

1. **Use Config for Core Services**: Define core services like database connections or loggers in the `services.php` file.
2. **Leverage Dependency Injection**: Define your classes to rely on constructor injection for better testability and modularity.
3. **Minimize Direct CI Instance Access**: Use the container for managing and retrieving services rather than directly relying on the CodeIgniter instance.

---

## Troubleshooting

1. **Service Not Found**: Ensure the service is properly registered or bound in the container.
2. **Circular Dependencies**: Avoid circular dependencies between services, as this can lead to infinite loops during resolution.
3. **Invalid Config File**: Verify that the `services.php` file returns a valid array.

---

## Example Workflow

### 1. Define a service in `services.php`:

```php
return [
    'Logger' => Logger::class,
    'NotificationService' => function ($container) {
        return new NotificationService($container->get('Logger'));
    },
];
```

### 2. Access the service in your code:

```php
$notificationService = ServiceContainer::getInstance()->get('NotificationService');
$notificationService->sendNotification($message);
```

---

## Conclusion

`ServiceContainer` simplifies dependency injection and service management in CodeIgniter 3. It is a robust solution for organizing your application's services while maintaining flexibility and improving testability.

Feel free to contribute or suggest improv to contribute or suggest improvements!ements!
