<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');



class ServiceContainer {

    protected static $instance = null;
    protected $services = [];
    protected $bindings = [];
    protected $interfaces = [];
    protected $parameters = [];
    protected $singletonServices = [];

    /**
     * Get the singleton instance of ServiceContainer
     * Implements the Singleton design pattern to ensure only one instance exists
     *
     * @return ServiceContainer The singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new ServiceContainer();
            self::$instance->registerDefaults();
        }
        return self::$instance;
    }

    private function isSingleton($key)
    {
        return isset($this->singletonServices[$key]);
    }


    public function set($key, $object) {
        $this->services[$key] = $object;
    }

    /**
     * Bind a service to the container with lazy loading
     * Supports both class names and closure resolvers with automatic dependency injection
     *
     * @param string $key The service identifier
     * @param string|Closure $resolver Class name or closure that returns the service instance
     * @throws Exception When resolver is invalid
     */
    public function bind($key, $resolver) {
        // Bind if resolver is a class - auto resolve dependencies
        if (is_string($resolver) && class_exists($resolver)) {
            $this->bindings[$key] = function ($container) use ($resolver) {
                return $container->resolve($resolver);
            };
        }
        // Bind if resolver is a closure
        elseif ($resolver instanceof Closure) {
            $this->bindings[$key] = $resolver;
        }
        else {
            throw new Exception("Invalid resolver for binding '{$key}'.");
        }
    }

    public function register($key, $resolver = null)
    {
        try {
            if (is_array($resolver)) {
                $this->set($key, $resolver);
            }
            elseif (!isset($this->services[$key])) {
                $resolver = $resolver ?: $key;
                $this->set($key, $this->resolve($resolver));
            }
            return $this->get($key);

        }catch (Exception $exception){
            throw  $exception ;
        }

    }

    public function get($key)
    {
        if (isset($this->services[$key])) {
            $service = $this->services[$key];
            if ($service instanceof Closure) {
                $this->services[$key] = $service($this);
            }
            return $this->services[$key];
        }
        if (isset($this->bindings[$key])) {
            $service = $this->bindings[$key]($this);
            if ($this->isSingleton($key)) {
                $this->services[$key] = $service;
            }
            return $service;
        }

        $service = $this->resolve($key);

        if ($this->isSingleton($key)) {
            $this->services[$key] = $service;
        }

        return $service;

    }

    public function make($key)
    {
        if (isset($this->bindings[$key])) {
            return $this->bindings[$key]($this);
        }
        return $this->makeNewInstance($key);
    }

    public function makeNewInstance($key, $resolver = null)
    {
        if (is_array($resolver)) {
            return $this->resolveDependenciesAndInstantiate($resolver);
        }


        $resolver = $resolver ?: $key;


        if (is_string($resolver) && class_exists($resolver)) {
            return $this->resolveDependenciesAndInstantiate($resolver);
        }

        throw new Exception("Cannot register '{$key}'. Invalid resolver provided.");
    }

    public function resolve($abstract)
    {

        if (isset($this->singletonServices[$abstract])) {
            return $this->get($abstract);
        }

        return $this->resolveDependenciesAndInstantiate($abstract);
    }



    private function resolveDependenciesAndInstantiate($abstract)
    {
        if (!class_exists($abstract)) {
            return   $this->bindingInterface($abstract);
        }
        $reflection = new ReflectionClass($abstract);
        $constructor = $reflection->getConstructor();
        if (!$constructor) {
            return new $abstract();
        }
        $dependencies = array_map(function ($param) {
            return $this->resolveParameter($param);
        }, $constructor->getParameters());

        return $reflection->newInstanceArgs($dependencies);
    }

    private function bindingInterface($abstract)
    {

        if (isset($this->interfaces[$abstract])) {
            return $this->interfaces[$abstract];
        }

        throw new Exception(" resolveDependenciesAndInstantiate - Class {$abstract} not found.");
    }


    private function resolveParameter(ReflectionParameter $param)
    {
        if ($param->getClass()) {
            return $this->resolve($param->getClass()->name);
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        if (strpos($param->name, 'CI') !== false) {
            return $this->get('CI_instance');
        }

        $paramName = $param->name;

        if (isset($this->parameters[$paramName])) {
            return $this->parameters[$paramName];
        }

        throw new Exception("Cannot resolve parameter: {$param->name}");
    }

    private function registerDefaults()
    {
        //From Config File
        try {
            $this->registerDefaultsFromConfig();

        }
        catch (Exception $exception){
            log_message('error', "Failed to registerDefaults: " . $exception->getMessage());
        }
    }


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

        // register Singleton Services
        foreach ($services['singletonServices'] as $key => $resolver) {
            try {
                if ($resolver instanceof Closure) {
                    $this->bind($key, $resolver);
                }
                elseif (is_string($resolver)) {
                    $this->register($key, $resolver);
                } else {
                    $this->set($key, $resolver);
                }
                $this->singletonServices [$key]=$key;

            } catch (Exception $e) {
                log_message('error', "Failed to register singleton service '{$key}': " . $e->getMessage());
                continue;
            }
        }

        //register Non-Singleton Services  (bind)
        foreach ($services['nonSingletonServices'] as $key => $resolver) {
            try {

                if ($resolver instanceof Closure) {
                    $this->bind($key, $resolver);
                }
                else {

                    $this->bind($key,$resolver );
                }
            } catch (Exception $e) {
                log_message('error', "Failed to register non-singleton service '{$key}': " . $e->getMessage());
                continue;
            }
        }


        // register Interfaces as bindings
        foreach ($services['interfaces'] ?? [] as $interface => $implementation) {
            if ($implementation instanceof Closure) {
                $this->bind($interface, $implementation);
            }
            else {
                $this->interfaces [$interface]  = $this->make($implementation);
            }

        }



        // register Parameters
        $this->parameters = $config['parameters'] ?? [];




    }

    public function singleton($key, $resolver = null)
    {
        if (is_string($resolver) && class_exists($resolver)) {
            $this->services[$key] = $this->resolve($resolver);
        } elseif ($resolver instanceof Closure) {
            $this->services[$key] = $resolver($this);
        } else {
            throw new Exception("Invalid resolver for singleton '{$key}'.");
        }
    }


    public function getServices(): array
    {
        return $this->services;
    }
    public function listServices(): array
    {
        return array_keys($this->services);
    }
    public function listServicesWithStatus(): array
    {
        $status = [];
        foreach ($this->services as $key => $service) {
            $status[$key] = is_object($service) ? 'Registered' : 'Not Registered';
        }
        return $status;
    }
    /**
     * @return array
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }
    /**
     * @return array
     */
    public function listBindings(): array
    {
        return array_keys($this->bindings);
    }
    public function listSingleton(): array
    {
        return $this->singletonServices;
    }


}

