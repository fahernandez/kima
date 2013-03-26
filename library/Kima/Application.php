<?php
/**
 * Kima Application
 * @author Steve Vega
 */
namespace Kima;

use \Kima\Action,
    \Kima\Config,
    \Kima\Error,
    \Kima\Http\Request,
    Exception;

/**
 * Application
 * Kima Application class
 */
class Application
{

    /**
     * Error messages
     */
    const ERROR_NO_DEFAULT_LANGUAGE = 'Default language should be set in the application ini or as a server param "DEFAULT_LANGUAGE"';

    /**
     * instance
     * @var \Kima\Application
     */
    private static $instance;

    /**
     * config
     * @var array
     */
    private static $config;

    /**
     * module
     * @var string
     */
    private static $module;

    /**
     * controller
     * @var string
     */
    private static $controller;

    /**
     * method
     * @var string
     */
    private static $method;

    /**
     * Current request language
     * @var string
     */
    private static $language;

    /**
     * The default application language
     * @var string
     */
    private static $default_language;

    /**
     * The language prefix used in urls
     * @var string
     */
    private static $language_url_prefix;

    /**
     * All the available languages in the application
     * @var array
     */
    private static $available_languages = [];

    /**
     * Default time zone
     * @var string
     */
    private static $time_zone;

    /**
     * Whether the connection is secure or not
     * @var boolean
     */
    private static $is_https;

    /**
     * Enforces the controller to be https
     * @var boolean
     */
    private static $enforce_https;

    /**
     * Individual controllers that should be always https
     * @var array
     */
    private static $https_controllers = [];

    /**
     * Global default view params
     * @var array
     */
    private static $view_params = [];

    /**
     * Construct
     */
    private function __construct()
    {
        // register the auto load function
        spl_autoload_register('Kima\Application::autoload');
    }

    /**
     * Get the application instance
     * @return \Kima\Application
     */
    public static function get_instance()
    {
        isset(self::$instance) || self::$instance = new self;
        return self::$instance;
    }

    /**
     * auto load function
     * @param string $class
     * @see http://php.net/manual/en/language.oop5.autoload.php
     */
    protected static function autoload($class)
    {
        // get the required file
        require_once str_replace('\\', '/', $class).'.php';
    }

    /**
     * Setup the basic application config
     */
    public static function setup()
    {
        // get the module and HTTP method
        switch (true)
        {
            case getenv('MODULE'):
                $module = getenv('MODULE');
                break;
            case !empty($_SERVER['MODULE']):
                $module = $_SERVER['MODULE'];
                break;
            default:
                $module = null;
        }
        $method = strtolower(Request::get_method());

        // set module, controller and action
        self::set_module($module);
        self::set_method($method);
        self::set_is_https();

        // set the config
        self::set_config();
    }

    /**
     * Run the application
     * @param array $urls
     */
    public static function run(array $urls)
    {
        // setup the application
        self::setup();

        // run the action
        $action = new Action($urls);
    }

    /**
     * Return the application config
     * @return array
     */
    public static function get_config()
    {
        return self::$config;
    }

    /**
     * Set the config
     * @param string $path
     */
    public static function set_config($path = '')
    {
        // set the application config
        $config = new Config($path);

        // add the model to the include path
        set_include_path(
            implode(PATH_SEPARATOR,
            [realpath($config->application['folder'] . '/model'), get_include_path()]));

        self::$config = $config;
        return self::$instance;
    }

    /**
     * Return the application module
     * @return string
     */
    public static function get_module()
    {
        return self::$module;
    }

    /**
     * Set the application module
     * @param string $module
     */
    public static function set_module($module)
    {
        self::$module = (string)$module;
        return self::$module;
    }

    /**
     * Return the application controller
     * @return string
     */
    public static function get_controller()
    {
        return self::$controller;
    }

    /**
     * Set the application controller
     * @param string $controller
     */
    public static function set_controller($controller)
    {
        self::$controller = (string)$controller;
        return self::$instance;
    }

    /**
     * Returns the application method
     * @return string
     */
    public static function get_method()
    {
        return self::$method;
    }

    /**
     * Set the method
     * @param string $method
     */
    public static function set_method($method)
    {
        self::$method = (string)$method;
        return self::$instance;
    }

    /**
     * Return the application language
     * @return string
     */
    public static function get_language()
    {
        return self::$language;
    }

    /**
     * Returns the language prefix to be used in urls
     * @return string
     */
    public static function get_language_url_prefix()
    {
        return self::$language_url_prefix;
    }

    /**
     * Sets the language
     * @param string $language
     */
    public static function set_language($language)
    {
        self::$language = (string)$language;

        // set the url prefix depending on the language selected
        self::$language_url_prefix = self::get_default_language() !== $language ? "/$language" : '';
        return self::$instance;
    }

    /**
     * Sets the default language
     * @param string $language
     */
    public static function set_default_language($language)
    {
        self::$default_language = $language;
    }

    /**
     * Gets the application default language
     * @return string
     */
    public static function get_default_language()
    {
        if (!empty(self::$default_language))
        {
            return self::$default_language;
        }

        switch (true)
        {
            case Request::env('LANGUAGE_DEFAULT'):
                $language = Request::env('LANGUAGE_DEFAULT');
                break;
            case Request::server('LANGUAGE_DEFAULT'):
                $language = Request::server('LANGUAGE_DEFAULT');
                break;
            case property_exists(self::get_config(), 'language')
                && !empty(self::get_config()->language['default']):
                $language = self::get_config()->language['default'];
                break;
            default:
                Error::set(self::ERROR_NO_DEFAULT_LANGUAGE);
        }

        self::$default_language = $language;
        return self::$default_language;
    }

    /**
     * Sets all the available languages in the application
     * @param array $languages
     */
    public static function set_available_languages(array $languages)
    {
        self::$available_languages = $languages;
    }

    /**
     * Gets all the available languages in the application
     * @return array
     */
    public static function get_available_languages()
    {
        if (!empty(self::$available_languages))
        {
            return self::$available_languages;
        }

        switch (true)
        {
            case Request::env('LANGUAGES_AVAILABLE'):
                $languages = Request::env('LANGUAGES_AVAILABLE');
                break;
            case Request::server('LANGUAGES_AVAILABLE'):
                $languages = Request::server('LANGUAGES_AVAILABLE');
                break;
            case property_exists(self::get_config(), 'language')
                && !empty(self::get_config()->language['available']):
                $languages = self::get_config()->language['available'];
                break;
            default:
                $languages = '';
        }

        self::$available_languages = explode(',', $languages);
        return self::$available_languages;
    }

    /**
     * Sets the default time zone
     * @param string $time_zone
     */
    public static function set_time_zone($time_zone)
    {
        self::$time_zone = $time_zone;
    }

    /**
     * Gets the application default time zone
     * @return string
     */
    public static function get_time_zone()
    {
        return empty(self::$time_zone)
            ? date_default_timezone_get()
            : self::$time_zone;
    }

    /**
     * Sets view global default params to set
     * @param array $params
     */
    public static function set_view_params(array $params)
    {
        self::$view_params = array_merge(self::$view_params, $params);
    }

    /**
     * Gets view global default params to set
     * @param array $params
     */
    public static function get_view_params()
    {
        return self::$view_params;
    }

    /**
     * Returns whether is a secure connection or not
     * @return boolean
     */
    public static function is_https()
    {
        return self::$is_https;
    }

    /**
     * Set whether the connections is https or not
     */
    private static function set_is_https()
    {
        // get values from sever
        $https = Request::server('HTTPS');
        $port = Request::server('SERVER_PORT');

        // check if https is on
        self::$is_https = !empty($https) && 'off' !== $https || 443 == $port ? true : false;
    }

    /**
     * Makes all request https by default
     */
    public static function enforce_https()
    {
        self::$enforce_https = true;
    }

    /**
     * Returns whether the request should be https or not
     * @return boolean
     */
    public static function is_https_enforced()
    {
        return empty(self::$enforce_https) ? false : true;
    }

    /**
     * Sets the controllers that should be always https
     * @param array $controllers
     */
    public static function set_https_controllers(array $controllers)
    {
        self::$https_controllers = $controllers;
    }

    /**
     * Gets the controllers that should be always https
     * @return array
     */
    public static function get_https_controllers()
    {
        return self::$https_controllers;
    }

    /**
     * Set an http error for the page
     * @param int $status_code
     */
    public static function set_http_error($status_code)
    {
        // set the status code
        http_response_code($status_code);

        $application = Application::get_instance();
        $config = $application->get_config();
        $module = $application->get_module();

        //  the controller path
        $controller_folder = $module
            ? $config->module['folder'] . '/' . $module . '/controller'
            : $config->controller['folder'];

        self::$controller = 'Error';
        $controller_path = $controller_folder . '/Error.php';
        require_once $controller_path;

        $method = 'get';
        $controller_obj = new \Error();
        $controller_obj->$method();
        exit;
    }

}