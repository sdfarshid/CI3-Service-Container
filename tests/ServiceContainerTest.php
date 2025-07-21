<?php
/**
 * ServiceContainer Test Suite
 * 
 * Comprehensive test suite for the ServiceContainer class
 * Tests all major functionality including dependency injection,
 * service binding, singleton behavior, and error handling.
 * 
 * @author Your Name
 * @version 1.0
 */

// Mock classes for testing
class MockLogger {
    public $messages = [];
    
    public function log($message) {
        $this->messages[] = $message;
    }
}

class MockEmailService {
    private $logger;
    
    public function __construct(MockLogger $logger) {
        $this->logger = $logger;
    }
    
    public function send($message) {
        $this->logger->log("Email sent: " . $message);
        return true;
    }
    
    public function getLogger() {
        return $this->logger;
    }
}

class MockUserService {
    private $emailService;
    private $config;
    
    public function __construct(MockEmailService $emailService, array $config) {
        $this->emailService = $emailService;
        $this->config = $config;
    }
    
    public function createUser($userData) {
        $this->emailService->send("Welcome " . $userData['name']);
        return ['id' => 1, 'name' => $userData['name']];
    }
    
    public function getConfig() {
        return $this->config;
    }
}

interface MockRepositoryInterface {
    public function find($id);
}

class MockRepository implements MockRepositoryInterface {
    public function find($id) {
        return ['id' => $id, 'data' => 'mock data'];
    }
}

/**
 * ServiceContainer Test Class
 */
class ServiceContainerTest {
    
    private $container;
    private $testResults = [];
    
    public function __construct() {
        $this->container = ServiceContainer::getInstance();
        $this->runAllTests();
    }
    
    /**
     * Run all test methods
     */
    public function runAllTests() {
        echo "🧪 Running ServiceContainer Test Suite\n";
        echo "=====================================\n\n";
        
        $this->testSingletonPattern();
        $this->testServiceBinding();
        $this->testDependencyInjection();
        $this->testInterfaceBinding();
        $this->testConfigurationArrays();
        $this->testMakeVsGet();
        $this->testErrorHandling();
        $this->testUtilityMethods();
        
        $this->printResults();
    }
    
    /**
     * Test singleton pattern behavior
     */
    public function testSingletonPattern() {
        echo "🔍 Testing Singleton Pattern...\n";
        
        // Test container singleton
        $container1 = ServiceContainer::getInstance();
        $container2 = ServiceContainer::getInstance();
        $this->assert($container1 === $container2, "Container should be singleton");
        
        // Test service singleton behavior
        $this->container->bind('TestSingleton', function() {
            return new MockLogger();
        });
        
        $service1 = $this->container->get('TestSingleton');
        $service2 = $this->container->get('TestSingleton');
        $this->assert($service1 === $service2, "Bound services should be singleton");
        
        echo "✅ Singleton Pattern tests passed\n\n";
    }
    
    /**
     * Test service binding functionality
     */
    public function testServiceBinding() {
        echo "🔍 Testing Service Binding...\n";
        
        // Test closure binding
        $this->container->bind('TestLogger', function() {
            return new MockLogger();
        });
        
        $logger = $this->container->get('TestLogger');
        $this->assert($logger instanceof MockLogger, "Should bind closure correctly");
        
        // Test class binding
        $this->container->bind('TestLoggerClass', MockLogger::class);
        $loggerClass = $this->container->get('TestLoggerClass');
        $this->assert($loggerClass instanceof MockLogger, "Should bind class correctly");
        
        echo "✅ Service Binding tests passed\n\n";
    }
    
    /**
     * Test automatic dependency injection
     */
    public function testDependencyInjection() {
        echo "🔍 Testing Dependency Injection...\n";
        
        // Setup dependencies
        $this->container->bind('DILogger', MockLogger::class);
        $this->container->bind('DIEmailService', MockEmailService::class);
        $this->container->set('DIConfig', ['app_name' => 'Test App']);
        
        // Test automatic dependency resolution
        $this->container->bind('DIUserService', function($container) {
            return new MockUserService(
                $container->get('DIEmailService'),
                $container->get('DIConfig')
            );
        });
        
        $userService = $this->container->get('DIUserService');
        $this->assert($userService instanceof MockUserService, "Should resolve dependencies");
        
        // Test that dependencies are properly injected
        $config = $userService->getConfig();
        $this->assert($config['app_name'] === 'Test App', "Should inject configuration");
        
        echo "✅ Dependency Injection tests passed\n\n";
    }
    
    /**
     * Test interface binding
     */
    public function testInterfaceBinding() {
        echo "🔍 Testing Interface Binding...\n";
        
        // This would normally be done in config, but testing manually
        $this->container->bind('MockRepositoryInterface', MockRepository::class);
        
        $repo = $this->container->get('MockRepositoryInterface');
        $this->assert($repo instanceof MockRepository, "Should bind interface to implementation");
        
        $result = $repo->find(123);
        $this->assert($result['id'] === 123, "Interface implementation should work");
        
        echo "✅ Interface Binding tests passed\n\n";
    }
    
    /**
     * Test configuration arrays
     */
    public function testConfigurationArrays() {
        echo "🔍 Testing Configuration Arrays...\n";
        
        $config = [
            'database' => 'test_db',
            'cache_ttl' => 3600,
            'debug' => true
        ];
        
        $this->container->set('TestConfig', $config);
        $retrievedConfig = $this->container->get('TestConfig');
        
        $this->assert($retrievedConfig === $config, "Should store and retrieve arrays");
        $this->assert($retrievedConfig['debug'] === true, "Should maintain array structure");
        
        echo "✅ Configuration Arrays tests passed\n\n";
    }
    
    /**
     * Test difference between make() and get()
     */
    public function testMakeVsGet() {
        echo "🔍 Testing make() vs get() behavior...\n";
        
        $this->container->bind('MakeVsGetTest', MockLogger::class);
        
        // get() should return same instance
        $instance1 = $this->container->get('MakeVsGetTest');
        $instance2 = $this->container->get('MakeVsGetTest');
        $this->assert($instance1 === $instance2, "get() should return same instance");
        
        // make() should return new instance
        $instance3 = $this->container->make('MakeVsGetTest');
        $instance4 = $this->container->make('MakeVsGetTest');
        $this->assert($instance3 !== $instance4, "make() should return new instances");
        
        echo "✅ make() vs get() tests passed\n\n";
    }
    
    /**
     * Test error handling
     */
    public function testErrorHandling() {
        echo "🔍 Testing Error Handling...\n";
        
        try {
            $this->container->get('NonExistentService');
            $this->assert(false, "Should throw exception for non-existent service");
        } catch (Exception $e) {
            $this->assert(true, "Should throw exception for non-existent service");
        }
        
        try {
            $this->container->bind('InvalidBinding', 'not a valid resolver');
            $this->assert(false, "Should throw exception for invalid binding");
        } catch (Exception $e) {
            $this->assert(true, "Should throw exception for invalid binding");
        }
        
        echo "✅ Error Handling tests passed\n\n";
    }
    
    /**
     * Test utility methods
     */
    public function testUtilityMethods() {
        echo "🔍 Testing Utility Methods...\n";
        
        // Setup some services for testing
        $this->container->bind('UtilityTest1', MockLogger::class);
        $this->container->set('UtilityTest2', new MockLogger());
        
        // Test listServices
        $services = $this->container->listServices();
        $this->assert(is_array($services), "listServices should return array");
        $this->assert(in_array('UtilityTest2', $services), "Should list registered services");
        
        // Test listBindings
        $bindings = $this->container->listBindings();
        $this->assert(is_array($bindings), "listBindings should return array");
        $this->assert(in_array('UtilityTest1', $bindings), "Should list bound services");
        
        // Test listServicesWithStatus
        $statuses = $this->container->listServicesWithStatus();
        $this->assert(is_array($statuses), "listServicesWithStatus should return array");
        
        echo "✅ Utility Methods tests passed\n\n";
    }
    
    /**
     * Assert helper method
     */
    private function assert($condition, $message) {
        if ($condition) {
            $this->testResults[] = ['status' => 'PASS', 'message' => $message];
        } else {
            $this->testResults[] = ['status' => 'FAIL', 'message' => $message];
            echo "❌ FAILED: $message\n";
        }
    }
    
    /**
     * Print test results summary
     */
    private function printResults() {
        $passed = count(array_filter($this->testResults, function($result) {
            return $result['status'] === 'PASS';
        }));
        $total = count($this->testResults);
        $failed = $total - $passed;
        
        echo "📊 Test Results Summary\n";
        echo "======================\n";
        echo "Total Tests: $total\n";
        echo "Passed: $passed ✅\n";
        echo "Failed: $failed " . ($failed > 0 ? "❌" : "") . "\n";
        echo "Success Rate: " . round(($passed / $total) * 100, 2) . "%\n\n";
        
        if ($failed === 0) {
            echo " All tests passed! ServiceContainer is working correctly.\n";
        } else {
            echo "  Some tests failed. Please review the implementation.\n";
        }
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    // You would need to include the ServiceContainer class here
    // require_once 'path/to/ServiceContainer.php';
    
    echo "To run these tests:\n";
    echo "1. Include the ServiceContainer class\n";
    echo "2. Run: php " . __FILE__ . "\n";
    echo "3. Or integrate with PHPUnit for more advanced testing\n\n";
    
    echo "Example usage:\n";
    echo "require_once 'application/libraries/ServiceContainer.php';\n";
    echo "new ServiceContainerTest();\n";
}
