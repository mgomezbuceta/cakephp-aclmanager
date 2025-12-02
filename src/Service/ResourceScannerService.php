<?php
declare(strict_types=1);

namespace AclManager\Service;

use AclManager\Model\Table\ResourcesTable;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use DirectoryIterator;
use ReflectionClass;
use ReflectionMethod;

/**
 * Resource Scanner Service
 *
 * Scans the application for controllers and actions to build the resource list
 */
class ResourceScannerService
{
    private ResourcesTable $Resources;
    private array $ignoredActions;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->Resources = TableRegistry::getTableLocator()->get('AclManager.Resources');
        $this->ignoredActions = Configure::read('AclManager.ignoreActions', [
            'isAuthorized',
            'beforeFilter',
            'afterFilter',
            'initialize',
            'beforeRender',
        ]);
    }

    /**
     * Scan all controllers and update resources table
     *
     * @return array Statistics about the scan
     */
    public function scanAndSync(): array
    {
        $found = $this->scanAllControllers();
        $stats = [
            'found' => count($found),
            'created' => 0,
            'updated' => 0,
            'deactivated' => 0,
        ];

        // Create or update resources
        foreach ($found as $resource) {
            $existing = $this->Resources->find()
                ->where([
                    'controller' => $resource['controller'],
                    'action' => $resource['action'],
                    'plugin IS' => $resource['plugin'],
                ])
                ->first();

            if ($existing) {
                if (!$existing->active) {
                    $existing->active = true;
                    $this->Resources->save($existing);
                    $stats['updated']++;
                }
            } else {
                $entity = $this->Resources->newEntity($resource);
                if ($this->Resources->save($entity)) {
                    $stats['created']++;
                }
            }
        }

        // Deactivate resources that no longer exist
        $foundIdentifiers = array_map(function ($r) {
            return $r['controller'] . '::' . $r['action'] . '::' . ($r['plugin'] ?? 'null');
        }, $found);

        $allResources = $this->Resources->find()->where(['active' => true])->all();
        foreach ($allResources as $resource) {
            $identifier = $resource->controller . '::' . $resource->action . '::' . ($resource->plugin ?? 'null');
            if (!in_array($identifier, $foundIdentifiers)) {
                $resource->active = false;
                $this->Resources->save($resource);
                $stats['deactivated']++;
            }
        }

        return $stats;
    }

    /**
     * Scan all controllers in the application and plugins
     *
     * @return array List of resources
     */
    protected function scanAllControllers(): array
    {
        $resources = [];

        // Scan app controllers
        $resources = array_merge($resources, $this->scanControllersInPath(APP . 'Controller', null));

        // Scan plugin controllers
        foreach (Plugin::loaded() as $plugin) {
            if ($this->shouldIgnorePlugin($plugin)) {
                continue;
            }

            $pluginPath = Plugin::path($plugin) . 'src' . DS . 'Controller';
            if (is_dir($pluginPath)) {
                $resources = array_merge($resources, $this->scanControllersInPath($pluginPath, $plugin));
            }
        }

        return $resources;
    }

    /**
     * Scan controllers in a specific path
     *
     * @param string $path Path to scan
     * @param string|null $plugin Plugin name
     * @return array List of resources
     */
    protected function scanControllersInPath(string $path, ?string $plugin = null): array
    {
        $resources = [];

        if (!is_dir($path)) {
            return $resources;
        }

        $files = $this->findControllerFiles($path);

        foreach ($files as $file) {
            $controllerName = str_replace('Controller.php', '', $file);

            if ($this->shouldIgnoreController($controllerName, $plugin)) {
                continue;
            }

            $className = $this->getControllerClassName($controllerName, $plugin);
            if (!class_exists($className)) {
                continue;
            }

            $actions = $this->getControllerActions($className);
            foreach ($actions as $action) {
                if ($this->shouldIgnoreAction($action, $controllerName, $plugin)) {
                    continue;
                }

                $resources[] = [
                    'controller' => $controllerName,
                    'action' => $action,
                    'plugin' => $plugin,
                    'description' => $this->generateDescription($controllerName, $action, $plugin),
                    'active' => true,
                ];
            }
        }

        return $resources;
    }

    /**
     * Find all controller files in a directory
     *
     * @param string $path Directory path
     * @return array List of controller filenames
     */
    protected function findControllerFiles(string $path): array
    {
        $files = [];

        try {
            $iterator = new DirectoryIterator($path);
            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isDot() || $fileInfo->isDir()) {
                    continue;
                }

                $filename = $fileInfo->getFilename();
                if (preg_match('/.*Controller\.php$/', $filename)) {
                    $files[] = $filename;
                }
            }
        } catch (\Exception $e) {
            // Directory not readable or doesn't exist
            return [];
        }

        return $files;
    }

    /**
     * Get controller class name
     *
     * @param string $controllerName Controller name
     * @param string|null $plugin Plugin name
     * @return string Full class name
     */
    protected function getControllerClassName(string $controllerName, ?string $plugin = null): string
    {
        if ($plugin) {
            return $plugin . '\\Controller\\' . $controllerName . 'Controller';
        }

        return 'App\\Controller\\' . $controllerName . 'Controller';
    }

    /**
     * Get all public actions from a controller
     *
     * @param string $className Controller class name
     * @return array List of action names
     */
    protected function getControllerActions(string $className): array
    {
        $reflection = new ReflectionClass($className);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        $actions = [];
        foreach ($methods as $method) {
            // Skip methods from parent classes
            if ($method->class !== $className) {
                continue;
            }

            // Skip magic methods and protected/private methods
            if (str_starts_with($method->name, '_') || str_starts_with($method->name, '__')) {
                continue;
            }

            $actions[] = $method->name;
        }

        return $actions;
    }

    /**
     * Check if a plugin should be ignored
     *
     * @param string $plugin Plugin name
     * @return bool
     */
    protected function shouldIgnorePlugin(string $plugin): bool
    {
        $ignorePatterns = array_filter($this->ignoredActions, fn($pattern) => str_ends_with($pattern, '.*'));
        foreach ($ignorePatterns as $pattern) {
            $pluginPattern = str_replace('.*', '', $pattern);
            if ($plugin === $pluginPattern) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a controller should be ignored
     *
     * @param string $controller Controller name
     * @param string|null $plugin Plugin name
     * @return bool
     */
    protected function shouldIgnoreController(string $controller, ?string $plugin = null): bool
    {
        $fullName = $plugin ? $plugin . '.' . $controller : $controller;

        foreach ($this->ignoredActions as $pattern) {
            if (str_ends_with($pattern, '/*')) {
                $controllerPattern = str_replace('/*', '', $pattern);
                if ($fullName === $controllerPattern || $controller === $controllerPattern) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if an action should be ignored
     *
     * @param string $action Action name
     * @param string $controller Controller name
     * @param string|null $plugin Plugin name
     * @return bool
     */
    protected function shouldIgnoreAction(string $action, string $controller, ?string $plugin = null): bool
    {
        // Check if action is in the ignore list
        if (in_array($action, $this->ignoredActions)) {
            return true;
        }

        // Check full pattern (Plugin.Controller/action)
        $fullPattern = $plugin ? $plugin . '.' . $controller . '/' . $action : $controller . '/' . $action;
        if (in_array($fullPattern, $this->ignoredActions)) {
            return true;
        }

        return false;
    }

    /**
     * Generate a description for a resource
     *
     * @param string $controller Controller name
     * @param string $action Action name
     * @param string|null $plugin Plugin name
     * @return string
     */
    protected function generateDescription(string $controller, string $action, ?string $plugin = null): string
    {
        $parts = [];

        if ($plugin) {
            $parts[] = $plugin;
        }

        $parts[] = Inflector::humanize(Inflector::underscore($controller));
        $parts[] = Inflector::humanize(Inflector::underscore($action));

        return implode(' - ', $parts);
    }

    /**
     * Get all resources grouped by plugin and controller
     *
     * @return array
     */
    public function getGroupedResources(): array
    {        
        $resources = $this->Resources->getGroupedResources();

        foreach($resources as $pluginName => $controllers){
            if(!$this->shouldIgnorePlugin($pluginName)){
                foreach($controllers as $controllerName => $actions){
                    if (!$this->shouldIgnoreController($controllerName, $pluginName)) {
                        $deleteAction = false;
                        foreach($actions as $keyAction => $action){
                            if ($this->shouldIgnoreAction($action->action, $controllerName, $pluginName)) {                                                            
                                unset($resources[$pluginName][$controllerName][$keyAction]);
                                $deleteAction = true;
                            }
                        }
                        if($deleteAction) {
                            $resources[$pluginName][$controllerName] = array_values($resources[$pluginName][$controllerName]);
                        }
                    }
                    else{
                        unset($resources[$pluginName][$controllerName]);
                    }
                }
            }
            else{
                unset($resources[$pluginName]);
            }
        }

        return $resources;

    }
}
