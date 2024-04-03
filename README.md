# CI3 Service Container

A simple yet powerful service container for CodeIgniter 3 that enables dependency injection and service management.

## Features

- **Singleton Pattern**: Ensures that only one instance of the service container is created.
- **Auto-Dependency Resolution**: Automatically resolves and injects dependencies for services.
- **Configurable Services**: Define your services in a config file for easy management.
- **CI3 Integration**: Seamlessly integrates with CodeIgniter 3, allowing access to the CI instance and other CI features.

## Getting Started

### Installation

To use this service container, simply place the `ServiceContainer.php` file into your `application/libraries` directory.

You will also need to create a `MY_Autoloader.php` file in the `application/config` directory with the provided autoloader code, and include it in your `application/config/autoload.php` file to enable PSR-4-like autoloading.

Lastly, define your services in `application/config/services.php`.

### Usage

#### Registering Services

Define your services in `application/config/services.php`:

```php
return [
    'DiscountService' => DiscountService::class,
    // Other services...
];
```
### Accessing Services

Get an instance of the service container and retrieve your services anywhere in your application:

```php  
    $discountService = ServiceContainer::getInstance()->get(DiscountService::class);
```


### Adding Services Dynamically
You can also add services dynamically if needed:
```php  
    ServiceContainer::getInstance()->register('MyService', MyService::class);
```

### Autoloading Classes
The provided autoloader (MY_Autoloader.php) simplifies class loading. Ensure it's included in your application/config/autoload.php to automatically load your classes from the application/libraries directory.

### Contributions
Feel free to fork, modify, and submit pull requests to this repository. Any contributions to improve this project are welcome!

License
This project is open-sourced software licensed under the MIT license.


