<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');


class ServiceContainer {

    protected static $instance = null;
    protected $services = [];

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new ServiceContainer();
            self::$instance->registerDefaults();
        }
        return self::$instance;
    }


    public function set($key, $object) {
        $this->services[$key] = $object;
    }

    public function get($key) {
        if (isset($this->services[$key])) {
            return $this->services[$key];
        }
        throw new Exception("Service {$key} not found.");
    }

    public function register($key,  $abstract = null)
    {
        if (!isset($this->services[$key])) {
            $abstract = $abstract ?: $key; //if empty $abstract so $key is abstract
            $this->set($key, $this->resolve($abstract));
        }
        return $this->get($key);

    }

    public function resolve($abstract)
    {
        // resolve dependencies
        $constructor = (new ReflectionClass($abstract))->getConstructor();
        $dependencies = $constructor ? $this->getDependencies($constructor->getParameters()) : [];
        // instantiate class with dependencies
        return new $abstract(...$dependencies);

    }

    public function getDependencies($parameters)
    {
        $dependencies = [];
        foreach ($parameters as $parameter) {
            // register the type hinted class
            $dependency = $parameter->getClass();
            if ($dependency === NULL) {
                // check if default value for a parameter is available
                if ($parameter->isDefaultValueAvailable()) {
                    // register default value of parameter
                    $dependencies[] = $parameter->getDefaultValue();
                }
                else {
                    // Get CI Instance
                    if(strpos($parameter->name,"CI") !==false){
                        $dependencies[] =$this->get('CI_instance');
                    }
                    else{
                        // Check Have Instance  Before
                        $Have_instance  =   $this->get($parameter->name);
                        if($Have_instance){
                            $dependencies[] = $Have_instance;
                        }
                        else{
                            throw new Exception("Can not resolve class dependency {$parameter->name}");
                        }
                    }

                }
            }
            else {
                // register dependency resolved
                $dependencies[] = $this->register($dependency->name);
            }
        }
        return $dependencies;
    }


    private function registerDefaults()
    {
        $this->set('CI_instance', get_instance()); //  CI
        
        //From Config File
        $this->registerDefaultsFromConfig();
 
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

        foreach ($services as $key => $className) {
            $this->register($key, $className);
        }

    }

}

