<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class ServiceContainer {

    // Singleton instance of the ServiceContainer
    protected static $instance = null;

    // Array to hold registered services
    protected $services = [];

    // Array to hold service bindings
    protected $bindings = [];

    // Returns the singleton instance of the ServiceContainer
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new ServiceContainer();
            self::$instance->registerDefaults(); // Register default bindings and services
        }
        return self::$instance;
    }

    // Register a service directly with an object
    public function set($key, $object) {
        $this->services[$key] = $object;
    }

    // Retrieve a service or resolve it
    public function get($key)
    {
        // Check if the service is explicitly registered
        if (isset($this->services[$key])) {
            $service = $this->services[$key];
            // If the service is a closure, execute it
            if ($service instanceof Closure) {
                $this->services[$key] = $service($this);
            }
            return $this->services[$key];
        }

        // Check if the service is bound
        if (isset($this->bindings[$key])) {
            $this->services[$key] = $this->bindings[$key]($this);
            return $this->services[$key];
        }

        // Attempt to auto-resolve the service
        return $this->resolve($key);

        throw new Exception("Service {$key} not found.");
    }

    // Register a service or a resolver
    public function register($key, $resolver = null)
    {
        if (is_array($resolver)) {
            $this->set($key, $resolver);
        }
        elseif (!isset($this->services[$key])) {
            $resolver = $resolver ?: $key;
            $this->set($key, $this->resolve($resolver));
        }
        return $this->get($key);
    }

    // Resolve a service or class with its dependencies
    public function resolve($abstract)
    {
        // Check bindings first
        if (isset($this->bindings[$abstract])) {
            return call_user_func($this->bindings[$abstract], $this);
        }

        // Check registered services
        if (isset($this->services[$abstract])) {
            $service = $this->services[$abstract];
            if ($service instanceof Closure) {
                $this->services[$abstract] = $service($this);
            }
            return $this->services[$abstract];
        }

        // Auto-resolve classes with dependency injection
        if (class_exists($abstract)) {
            $reflectionClass = new ReflectionClass($abstract);
            $constructor = $reflectionClass->getConstructor();

            if (!$constructor) {
                return new $abstract; // Instantiate the class if it has no constructor
            }

            // Resolve dependencies for the constructor
            $dependencies = $this->getDependencies($constructor->getParameters());
            return $reflectionClass->newInstanceArgs($dependencies);
        }

        throw new Exception("Cannot resolve '{$abstract}'. It is not a class, binding, or service.");
    }

    // Resolve dependencies for a constructor
    public function getDependencies($parameters)
    {
        $dependencies = [];
        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass(); // Get the type-hinted class
            if ($dependency === null) {
                // Handle primitive types or parameters without type hints
                $parameterName = $parameter->name;
                if (isset($this->bindings[$parameterName])) {
                    $dependencies[] = call_user_func($this->bindings[$parameterName]);
                }
                elseif ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    // Special case for CI instance or unresolvable parameters
                    if (strpos($parameter->name, "CI") !== false) {
                        $dependencies[] = $this->get('CI_instance');
                    } else {
                        throw new Exception("Cannot resolve dependency {$parameter->name}");
                    }
                }
            } else {
                // Resolve the dependency by its class name
                $dependencies[] = $this->register($dependency->name);
            }
        }
        return $dependencies;
    }

    // Bind a resolver to a key
    public function bind($key, $resolver) {
        $this->bindings[$key] = $resolver;
    }

    // Manually create or resolve a service
    public function make($key)
    {
        if (isset($this->bindings[$key])) {
            return $this->bindings[$key]($this);
        }

        if (isset($this->services[$key])) {
            if ($this->services[$key] instanceof Closure) {
                return $this->services[$key]($this);
            }
            return $this->resolve($key);
        }

        throw new Exception("Binding or Service '{$key}' not found.");
    }

    // Register default bindings and services
    private function registerDefaults()
    {
        $this->bind('CI_instance', function () {
            return clone get_instance(); // Clone the CI instance
        });
        $this->registerDefaultsFromConfig(); // Register services from the config file
    }

    // Load default services from the config file
    private function registerDefaultsFromConfig()
    {
        $servicesFilePath = APPPATH . 'config/services.php';

        if (!file_exists($servicesFilePath)) {
            throw new Exception("The services config file does not exist: {$servicesFilePath}");
        }

        $services = include($servicesFilePath);
        if (!is_array($services)) {
            throw new Exception("The services config file must return an array.");
        }

        foreach ($services as $key => $resolver) {
            try {
                if ($resolver instanceof Closure) {
                    $this->bind($key, $resolver);
                }
                elseif (is_string($resolver)) {
                    $this->register($key, $resolver);
                } else {
                    $this->set($key, $resolver);
                }
            } catch (Exception $e) {
                log_message('error', "Failed to register service '{$key}': " . $e->getMessage());
                continue;
            }
        }
    }
}
