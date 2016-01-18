<?php
/**
 * Router - routing urls to closures and controllers.
 *
 * @author Virgil-Adrian Teaca - virgil@giulianaeassociati.com
 * @version 3.0
 * @date December 17th, 2015
 */


namespace Nova\Core;

use Nova\Core\View;
use Nova\Net\Response;
use Nova\Forensics\Console;
use Nova\Config;

/**
 * Core controller, all other controllers extend this base controller.
 */
abstract class Controller
{
    // The Controller's instance.
    private static $instance;

    // The Controller's variables.
    protected $data = array();

    // Module where the Controller is located.
    protected $module = null;
    //
    protected $params = array();

    // Current called Method
    protected $method;

    protected $className;
    protected $viewsPath;

    // Theming support.
    protected $template = 'Default';
    protected $layout   = 'default';

    protected $autoRender = true;
    protected $useLayout  = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        self::$instance =& $this;
    }

    /**
     * Get instance
     * @return Controller
     */
    public static function &getInstance()
    {
        return self::$instance;
    }

    /**
     * Set current instance into instance holder
     */
    public function setInstance()
    {
        self::$instance =& $this;
    }

    /**
     * Initialize controller
     *
     * @param string $className
     * @param string $method
     * @param array $params
     * @throws \Exception
     */
    public function initialize($className, $method, $params = array())
    {
        $this->className = $className;

        $this->method = $method;
        $this->params = $params;

        // Prepare the Views Path using the Controller's full Name including its namespace.
        $classPath = str_replace('\\', '/', $className);

        // First, check on the App path.
        if (preg_match('#^App/Controllers/(.*)$#i', $classPath, $matches)) {
            $viewsPath = str_replace('/', DS, 'Views/'.$matches[1]);
        // Secondly, check on the Modules path.
        } else if (preg_match('#^App/Modules/(.+)/Controllers/(.*)$#i', $classPath, $matches)) {
            $this->module = $matches[1];

            // The View paths are in Module sub-directories.
            $viewsPath = str_replace('/', DS, 'Modules/'.$matches[1].'/Views/'.$matches[2]);
        } else {
            throw new \Exception(__d('system', 'Unknown Views Path for the Class: {0}', $className));
        }

        $this->viewsPath = APPPATH .$viewsPath .DS;

        Console::log(__d('system', '{0} initialized', $this->className));
    }

    /**
     * @return bool
     */
    protected function beforeFlight()
    {
        Console::log(__d('system', '{0} finished the beforeFlight stage', $this->className));

        return true;
    }

    /**
     * @param $result
     * @return bool
     */
    protected function afterFlight($result)
    {
        return true;
    }

    /**
     * Execute Controller Method
     * @return bool
     */
    public function execute()
    {
        $options = Config::get('profiler');

        Console::log(__d('system', '{0} enter into beforeFlight stage', $this->className));

        if ($this->beforeFlight() === false) {
            // Is wanted to stop the Flight.
            return false;
        }

        Console::log(__d('system', '{0} execute the Method: {1}', $this->className, $this->method()));

        // Execute the Controller's Method with the given arguments.
        try {
            $result = call_user_func_array(array($this, $this->method()), $this->params());
        }
        // Catch the exceptions.
        catch(\Exception $e) {
            if((ENVIRONMENT == 'development') && ($options['use_forensics'] == true)) {
                Console::logError($e, $e->getMessage());

                $result = null;
            } else {
                throw $e;
            }
        }

        Console::log(__d('system', '{0} enter into afterFlight stage', $this->className));

        if (($this->afterFlight($result) === false) || ! $this->autoRender) {
            // Is wanted to stop the Flight or there is no auto-rendering.
            return true;
        }

        $this->renderResult($result);

        return true;
    }

    /**
     * Render Result
     * @param View|array|string $result
     */
    protected function renderResult($result)
    {
        if ($result instanceof View) {
            $result->display();

            return;
        }

        if (is_array($result)) {
            Response::addHeader('Content-Type: application/json');

            $result = json_encode($result);
        } else if (is_string($result)) {
            Response::addHeader('Content-Type: text/html; charset=UTF-8');
        } else {
            return;
        }

        // Output the result.
        Response::sendHeaders();

        echo $result;
    }

    /**
     * Auto render
     * @param null|bool $value
     * @return bool
     */
    protected function autoRender($value = null)
    {
        if (is_null($value)) {
            return $this->autoRender;
        }

        $this->autoRender = $value;
    }

    /**
     * Use Layouts
     * @param null|bool $value
     * @return bool
     */
    protected function useLayout($value = null)
    {
        if (is_null($value)) {
            return $this->useLayout;
        }

        $this->useLayout = $value;
    }

    /**
     * Data
     * @param string $name
     * @return array|null
     */
    public function data($name = null)
    {
        if (is_null($name)) {
            return $this->data;
        } else if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return null;
    }

    /**
     * Set data, will be available in the View.
     *
     * @param string $name Key
     * @param mixed $value Value
     */
    protected function set($name, $value = null)
    {
        if (is_array($name)) {
            if (is_array($value)) {
                $data = array_combine($name, $value);
            } else {
                $data = $name;
            }
        } else {
            $data = array($name => $value);
        }

        $this->data = $data + $this->data;
    }

    /**
     * Set title, shorthand for set('title', $title);
     * @param $title
     */
    protected function title($title)
    {
        $data = array('title' => $title);

        $this->data = $data + $this->data;

        // Activate the Rendering on Layout.
        $this->useLayout = true;
    }

    // Some getters.

    /**
     * @return null
     */
    public function module()
    {
        return $this->module;
    }

    /**
     * @return mixed
     */
    public function method()
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function params()
    {
        return $this->params;
    }

    /**
     * @return mixed
     */
    public function viewsPath()
    {
        return $this->viewsPath;
    }

    /**
     * @return string
     */
    public function template()
    {
        return $this->template;
    }

    /**
     * @return string
     */
    public function layout()
    {
        return $this->layout;
    }
}
