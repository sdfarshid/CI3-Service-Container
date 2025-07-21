# 🚀 ServiceContainer for CodeIgniter 3

A powerful **Dependency Injection Container** and **Service Locator** for CodeIgniter 3 that brings modern dependency management to your legacy applications. This container provides automatic dependency resolution, lazy loading, and flexible service configuration.

[![PHP Version](https://img.shields.io/badge/PHP-7.0%2B-blue.svg)](https://php.net)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-3.x-orange.svg)](https://codeigniter.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## ✨ Key Features

- **🔄 Dependency Injection**: Automatic constructor dependency resolution
- **🏗️ Service Binding**: Multiple ways to register and bind services
- **⚡ Lazy Loading**: Services instantiated only when needed
- **🎯 Singleton Management**: Control service lifecycle (singleton vs transient)
- **🔗 Interface Binding**: Bind interfaces to concrete implementations
- **📝 Configuration-Based**: Define services in configuration files
- **🔍 Debugging Tools**: Built-in utilities for service inspection
- **🧩 CI3 Integration**: Seamless integration with CodeIgniter 3

---

## 📦 Installation

### Step 1: Copy Files
```bash
# Copy the ServiceContainer to your libraries directory
cp library/ServiceContainer.php application/libraries/

# Copy the configuration files
cp config/services.php application/config/
cp config/MY_Autoloader.php application/config/
```

### Step 2: Enable Autoloading (Optional)
Add to your `application/config/autoload.php`:
```php
$autoload['libraries'] = array('ServiceContainer');
```

### Step 3: Load in your application
```php
// In your controller or anywhere in CI3
$this->load->library('ServiceContainer');
$container = ServiceContainer::getInstance();
```

---

## ⚙️ Configuration Structure

The ServiceContainer uses a structured configuration approach in `config/services.php`. The configuration file must return an array with three main sections:

### Configuration Schema

```php
<?php
return [
    'singletonServices' => [
        // Services that maintain single instance throughout application lifecycle
    ],
    'nonSingletonServices' => [
        // Services that create new instance on each request
    ],
    'interfaces' => [
        // Interface to implementation bindings
    ],
    'parameters' => [
        // Simple parameter bindings (optional)
    ]
];
```

### Service Registration Options

#### 1. **Singleton Services** (Recommended for most services)
```php
'singletonServices' => [
    // Class-based registration
    'Logger' => Logger::class,

    // Closure-based registration with dependencies
    'DatabaseService' => function ($container) {
        return new DatabaseService($container->get('ConfigService'));
    },

    // Configuration arrays
    'AppConfig' => [
        'debug' => true,
        'timezone' => 'UTC',
        'cache_ttl' => 3600
    ],

    // Pre-built instances
    'CacheService' => new CacheService(),
]
```

#### 2. **Non-Singleton Services** (New instance each time)
```php
'nonSingletonServices' => [
    // Always creates new instance
    'EmailMessage' => EmailMessage::class,
    'HttpRequest' => HttpRequest::class,
]
```

#### 3. **Interface Bindings**
```php
'interfaces' => [
    // Bind interface to concrete implementation
    'LoggerInterface' => Logger::class,
    'CacheInterface' => RedisCache::class,

    // Complex interface binding
    'PaymentGatewayInterface' => function ($container) {
        return new StripePaymentGateway($container->get('PaymentConfig'));
    },
]
```

---

## 🎯 Usage Guide

### Basic Container Access

```php
// Get the singleton instance
$container = ServiceContainer::getInstance();

// Helper function (recommended)
function container() {
    return ServiceContainer::getInstance();
}

// Usage in CI3 controllers
class Welcome extends CI_Controller {
    public function index() {
        $logger = container()->get('Logger');
        $logger->info('Welcome page accessed');
    }
}
```

### Core Methods Overview

| Method | Purpose | Returns | Use Case |
|--------|---------|---------|----------|
| `get($key)` | Retrieve service (singleton behavior) | Service instance | Getting configured services |
| `make($key)` | Create new instance | New instance | When you need fresh instances |
| `bind($key, $resolver)` | Register service definition | void | Defining how services are created |
| `register($key, $resolver)` | Register and instantiate | Service instance | Immediate registration |
| `set($key, $instance)` | Store pre-built instance | void | Storing existing objects |

### 1. **Retrieving Services**

```php
// Get singleton service (recommended)
$logger = $container->get('Logger');
$database = $container->get('DatabaseService');

// Get configuration arrays
$config = $container->get('AppConfig');
echo $config['debug']; // true

// Get services with automatic dependency injection
$emailService = $container->get('EmailService');
```

### 2. **Creating New Instances**

```php
// Always creates new instance (non-singleton)
$request1 = $container->make('HttpRequest');
$request2 = $container->make('HttpRequest');
// $request1 !== $request2

// Create with custom parameters
$customService = $container->makeNewInstance('CustomService', [
    'param1' => 'value1',
    'param2' => 'value2'
]);
```

### 3. **Runtime Service Registration**

```php
// Bind a service with closure
$container->bind('PaymentProcessor', function ($container) {
    $config = $container->get('PaymentConfig');
    return new PaymentProcessor($config['api_key']);
});

// Register and instantiate immediately
$mailer = $container->register('Mailer', function ($container) {
    return new Mailer($container->get('SmtpConfig'));
});

// Set pre-built instance
$container->set('CustomLogger', new FileLogger('/path/to/log'));
```

### 4. **Automatic Dependency Injection**

The container automatically resolves constructor dependencies:

```php
class EmailService {
    public function __construct(Logger $logger, DatabaseService $db) {
        $this->logger = $logger;
        $this->db = $db;
    }
}

// Dependencies are automatically injected
$emailService = $container->get(EmailService::class);
```

### 5. **Interface-Based Development**

```php
// Define in services.php
'interfaces' => [
    'LoggerInterface' => FileLogger::class,
]

// Use in your classes
class UserService {
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger; // FileLogger instance injected
    }
}
```

---

## 🔧 Built-in CodeIgniter Integration

### CI Instance Access
```php
// Access CodeIgniter instance anywhere
$ci = $container->get('CI_instance');
$ci->load->model('User_model');
$ci->load->helper('url');

// Use in service definitions
'UserRepository' => function ($container) {
    $ci = $container->get('CI_instance');
    return new UserRepository($ci->db);
}
```

### Parameter Resolution
The container automatically detects CI-related parameters:
```php
class MyService {
    // Any parameter containing 'CI' gets CI instance
    public function __construct($CI, Logger $logger) {
        $this->CI = $CI; // CodeIgniter instance
        $this->logger = $logger;
    }
}
```

---

## 🔍 Debugging and Inspection Tools

```php
// List all registered services
$services = $container->listServices();
print_r($services); // ['Logger', 'DatabaseService', 'EmailService']

// List all service bindings
$bindings = $container->listBindings();
print_r($bindings); // ['PaymentProcessor', 'Mailer']

// Get service registration status
$statuses = $container->listServicesWithStatus();
print_r($statuses);
// ['Logger' => 'Registered', 'DatabaseService' => 'Registered']

// List singleton services
$singletons = $container->listSingleton();
print_r($singletons);

// Get all services (instances)
$allServices = $container->getServices();

// Get all bindings (closures)
$allBindings = $container->getBindings();
```

---

## 📊 Service Lifecycle Management

### Singleton vs Non-Singleton Behavior

| **Aspect** | **Singleton Services** | **Non-Singleton Services** |
|------------|----------------------|---------------------------|
| **Instance Management** | Single shared instance | New instance each request |
| **Memory Usage** | Lower (reused instances) | Higher (multiple instances) |
| **State Persistence** | Maintains state between calls | Fresh state each time |
| **Performance** | Faster (no re-instantiation) | Slower (object creation overhead) |
| **Use Cases** | Databases, Loggers, Configs | DTOs, Validators, Requests |
| **Registration** | `singletonServices` array | `nonSingletonServices` array |

### Lifecycle Examples

```php
// Singleton behavior (recommended for most services)
$db1 = $container->get('DatabaseService');
$db2 = $container->get('DatabaseService');
var_dump($db1 === $db2); // true - same instance

// Non-singleton behavior
$request1 = $container->make('HttpRequest');
$request2 = $container->make('HttpRequest');
var_dump($request1 === $request2); // false - different instances

// Force new instance even for singletons
$freshLogger = $container->makeNewInstance('Logger');
```

---

## 🛠️ Advanced Usage Patterns

### 1. **Factory Pattern Integration**
```php
'singletonServices' => [
    'UserFactory' => function ($container) {
        return new UserFactory(
            $container->get('DatabaseService'),
            $container->get('ValidationService')
        );
    }
]

// Usage
$userFactory = $container->get('UserFactory');
$user = $userFactory->createUser($userData);
```

### 2. **Repository Pattern**
```php
'interfaces' => [
    'UserRepositoryInterface' => function ($container) {
        return new DatabaseUserRepository($container->get('CI_instance')->db);
    }
]

// In your service
class UserService {
    public function __construct(UserRepositoryInterface $repository) {
        $this->repository = $repository;
    }
}
```

### 3. **Configuration-Based Service Selection**
```php
'singletonServices' => [
    'PaymentGateway' => function ($container) {
        $config = $container->get('AppConfig');

        switch ($config['payment_provider']) {
            case 'stripe':
                return new StripeGateway($config['stripe_key']);
            case 'paypal':
                return new PayPalGateway($config['paypal_config']);
            default:
                throw new Exception('Unknown payment provider');
        }
    }
]
```

### 4. **Conditional Service Registration**
```php
'singletonServices' => [
    'CacheService' => function ($container) {
        $config = $container->get('AppConfig');

        if ($config['cache_enabled']) {
            return new RedisCache($config['redis_config']);
        }

        return new NullCache(); // No-op cache for development
    }
]
```

---

## 📋 Method Reference Guide

| Method | Signature | Returns | Description | Use Case |
|--------|-----------|---------|-------------|----------|
| `getInstance()` | `static getInstance()` | `ServiceContainer` | Get singleton container instance | Entry point |
| `get($key)` | `get(string $key)` | `mixed` | Retrieve service (singleton behavior) | Primary service access |
| `make($key)` | `make(string $key)` | `mixed` | Create new instance | Fresh instances needed |
| `bind($key, $resolver)` | `bind(string $key, $resolver)` | `void` | Register service definition | Lazy service definition |
| `register($key, $resolver)` | `register(string $key, $resolver)` | `mixed` | Register and instantiate | Immediate registration |
| `set($key, $instance)` | `set(string $key, $instance)` | `void` | Store pre-built instance | External instances |
| `resolve($abstract)` | `resolve(string $abstract)` | `mixed` | Resolve with DI | Internal resolution |
| `singleton($key, $resolver)` | `singleton(string $key, $resolver)` | `void` | Register singleton manually | Runtime singleton registration |

---

## 🎯 Best Practices & Recommendations

### ✅ **DO's**

1. **Use Configuration-Based Registration**
   ```php
   // ✅ Good: Define in services.php
   'EmailService' => function ($container) {
       return new EmailService($container->get('EmailConfig'));
   }
   ```

2. **Leverage Interface-Based Dependencies**
   ```php
   // ✅ Good: Depend on interfaces
   class UserService {
       public function __construct(LoggerInterface $logger) {}
   }
   ```

3. **Use Descriptive Service Names**
   ```php
   // ✅ Good: Clear, descriptive names
   'UserNotificationService' => UserNotificationService::class,
   'PaymentGatewayFactory' => PaymentGatewayFactory::class
   ```

4. **Group Related Configurations**
   ```php
   // ✅ Good: Logical grouping
   'DatabaseConfig' => [...],
   'EmailConfig' => [...],
   'CacheConfig' => [...]
   ```

### ❌ **DON'Ts**

1. **Avoid Circular Dependencies**
   ```php
   // ❌ Bad: Service A depends on B, B depends on A
   'ServiceA' => function ($c) { return new ServiceA($c->get('ServiceB')); },
   'ServiceB' => function ($c) { return new ServiceB($c->get('ServiceA')); }
   ```

2. **Don't Hardcode Dependencies**
   ```php
   // ❌ Bad: Hardcoded dependencies
   'EmailService' => function () {
       return new EmailService('smtp.gmail.com', 'user', 'pass');
   }

   // ✅ Good: Use configuration
   'EmailService' => function ($container) {
       $config = $container->get('EmailConfig');
       return new EmailService($config['host'], $config['user'], $config['pass']);
   }
   ```

3. **Avoid Heavy Computations in Service Definitions**
   ```php
   // ❌ Bad: Heavy computation in closure
   'DataProcessor' => function ($container) {
       $data = file_get_contents('large-file.json'); // Heavy operation
       return new DataProcessor($data);
   }
   ```

---

## 🚨 Troubleshooting Guide

### Common Issues and Solutions

#### 1. **Service Not Found Exception**
```
Exception: resolveDependenciesAndInstantiate - Class ServiceName not found.
```
**Solutions:**
- Verify service is registered in `config/services.php`
- Check class name spelling and namespace
- Ensure class file is autoloaded properly

```php
// ✅ Fix: Register the service
'singletonServices' => [
    'MissingService' => MissingService::class
]
```

#### 2. **Circular Dependency Detection**
```
Exception: Cannot resolve parameter: parameterName
```
**Solutions:**
- Review service dependencies for circular references
- Use factory pattern to break circular dependencies
- Consider redesigning service architecture

```php
// ❌ Problem: Circular dependency
'ServiceA' => function ($c) { return new ServiceA($c->get('ServiceB')); },
'ServiceB' => function ($c) { return new ServiceB($c->get('ServiceA')); }

// ✅ Solution: Use factory or mediator pattern
'ServiceFactory' => function ($c) {
    return new ServiceFactory($c);
}
```

#### 3. **Invalid Configuration File**
```
Exception: The services config file must return an array.
```
**Solutions:**
- Ensure `config/services.php` returns an array
- Check for PHP syntax errors in config file
- Verify file permissions

#### 4. **Class Not Found During Auto-Resolution**
```
Exception: Class ClassName not found.
```
**Solutions:**
- Check autoloader configuration
- Verify class namespace and file location
- Ensure `MY_Autoloader.php` is properly configured

#### 5. **Parameter Resolution Issues**
```
Exception: Cannot resolve parameter: parameterName
```
**Solutions:**
- Add parameter to `parameters` section in config
- Use default parameter values in constructor
- Register parameter explicitly

```php
// ✅ Solution: Add to parameters section
'parameters' => [
    'api_key' => 'your-api-key',
    'debug_mode' => true
]
```

---

## 📚 Complete Example: E-commerce Service Setup

Here's a comprehensive example showing how to structure services for an e-commerce application:

### config/services.php
```php
<?php
return [
    'singletonServices' => [
        // Core Configuration
        'AppConfig' => [
            'app_name' => 'E-commerce Store',
            'currency' => 'USD',
            'tax_rate' => 0.08,
            'shipping_cost' => 9.99
        ],

        // Database Services
        'ProductRepository' => function ($container) {
            $ci = $container->get('CI_instance');
            return new ProductRepository($ci->db);
        },

        'UserRepository' => function ($container) {
            $ci = $container->get('CI_instance');
            return new UserRepository($ci->db);
        },

        // Business Logic Services
        'CartService' => function ($container) {
            return new CartService(
                $container->get('ProductRepository'),
                $container->get('AppConfig')
            );
        },

        'OrderService' => function ($container) {
            return new OrderService(
                $container->get('CartService'),
                $container->get('PaymentGateway'),
                $container->get('EmailService')
            );
        },

        // External Services
        'PaymentGateway' => function ($container) {
            $config = $container->get('PaymentConfig');
            return new StripePaymentGateway($config['stripe_key']);
        },

        'EmailService' => function ($container) {
            $ci = $container->get('CI_instance');
            $config = $container->get('EmailConfig');
            $ci->load->library('email', $config);
            return new EmailService($ci->email);
        },

        // Utility Services
        'Logger' => function ($container) {
            return new FileLogger(APPPATH . 'logs/');
        }
    ],

    'nonSingletonServices' => [
        // Create new instances for each request
        'OrderDTO' => OrderDTO::class,
        'PaymentRequest' => PaymentRequest::class
    ],

    'interfaces' => [
        'PaymentGatewayInterface' => function ($container) {
            return $container->get('PaymentGateway');
        },
        'LoggerInterface' => function ($container) {
            return $container->get('Logger');
        }
    ]
];
```

### Usage in Controllers
```php
class Shop extends CI_Controller {

    public function add_to_cart() {
        $cartService = container()->get('CartService');
        $productId = $this->input->post('product_id');
        $quantity = $this->input->post('quantity');

        try {
            $cartService->addItem($productId, $quantity);
            $this->output->set_json(['success' => true]);
        } catch (Exception $e) {
            container()->get('Logger')->error($e->getMessage());
            $this->output->set_json(['error' => $e->getMessage()]);
        }
    }

    public function checkout() {
        $orderService = container()->get('OrderService');

        try {
            $order = $orderService->processOrder($this->session->userdata('user_id'));
            redirect('order/success/' . $order->getId());
        } catch (Exception $e) {
            container()->get('Logger')->error($e->getMessage());
            $this->session->set_flashdata('error', 'Order processing failed');
            redirect('cart');
        }
    }
}
```

---

## 🧪 Testing Your Services

### Unit Testing with PHPUnit
```php
class CartServiceTest extends PHPUnit\Framework\TestCase {

    public function setUp(): void {
        // Mock dependencies
        $this->productRepo = $this->createMock(ProductRepository::class);
        $this->config = ['tax_rate' => 0.08];

        $this->cartService = new CartService($this->productRepo, $this->config);
    }

    public function testAddItemToCart() {
        $this->productRepo->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn(new Product(1, 'Test Product', 19.99));

        $result = $this->cartService->addItem(1, 2);

        $this->assertTrue($result);
        $this->assertEquals(2, $this->cartService->getItemCount());
    }
}
```

### Integration Testing
```php
class ServiceContainerIntegrationTest extends PHPUnit\Framework\TestCase {

    public function testServiceResolution() {
        $container = ServiceContainer::getInstance();

        // Test singleton behavior
        $service1 = $container->get('CartService');
        $service2 = $container->get('CartService');
        $this->assertSame($service1, $service2);

        // Test dependency injection
        $orderService = $container->get('OrderService');
        $this->assertInstanceOf(OrderService::class, $orderService);
    }
}
```

---

## 🤝 Contributing

We welcome contributions! Here's how you can help:

1. **Fork the repository**
2. **Create a feature branch**: `git checkout -b feature/amazing-feature`
3. **Commit your changes**: `git commit -m 'Add amazing feature'`
4. **Push to the branch**: `git push origin feature/amazing-feature`
5. **Open a Pull Request**

### Development Guidelines
- Follow PSR-4 autoloading standards
- Add PHPDoc comments for all public methods
- Include unit tests for new features
- Update documentation for any API changes

---

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

- Inspired by modern PHP dependency injection containers
- Built specifically for CodeIgniter 3 compatibility
- Thanks to the CI3 community for feedback and suggestions

---

## 📞 Support

- **Email**: sdfarshidmousavi@gmail.com

---

**Made with ❤️ for the CodeIgniter 3 community**
