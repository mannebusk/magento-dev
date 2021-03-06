<?php

/**
 * This class bootstraps the dev app.
 * 
 * @package dahl_dev
 * @copyright Copyright (C) 2015 Albert Dahlin
 * @author Albert Dahlin <info@albertdahlin.com>
 * @license GNU GPL v3.0 <http://www.gnu.org/licenses/gpl-3.0.html>
 */
class dahl_dev
{
    /**
     * Singleton instance of dahl_dev
     * 
     * @var dahl_dev
     * @access protected
     */
    static protected $_instance;

    /**
     * Dev configuration data object.
     * 
     * @var Varien_Object
     * @access protected
     */
    protected $_config;

    /**
     * Initializes dev app.
     * 
     * @static
     * @access public
     * @return void
     */
    static public function init()
    {
        self::$_instance = new dahl_dev();
    }

    /**
     * Retrieve the config object.
     * 
     * @static
     * @access public
     * @return Varien_Object
     */
    static public function getConfig()
    {
        return self::$_instance->_config;
    }

    /**
     * Constructor method. Initializes config.
     * 
     * @access public
     * @return void
     */
    public function __construct()
    {
        if (!$this->_initConfig()) {
            return;
        }
        $this->_readConfigFiles();
        $this->_initSetup();
        $this->_config->loadExternalModules();
    }

    /**
     * Writes a log message to the log file.
     * 
     * @param string $string
     * @access protected
     * @return void
     */
    protected function log($string)
    {
        $logFile = $this->_config->getLogFile();
        file_put_contents($logFile, 'DAHL_DEV: ' . $string . "\n", FILE_APPEND);
    }

    /**
     * Initialize configuration. Reads the settings from .json files.
     * 
     * @access protected
     * @return boolean
     */
    protected function _initConfig()
    {
        $mageRoot    = DAHL_MAGEROOT;
        $mageFile    = buildPath($mageRoot, 'app', 'Mage.php');
        if (!file_exists($mageFile)) {
            return false;
        }
        require_once $mageFile;

        include buildPath(DAHL_DEVROOT, 'magento', 'config.php');

        $config = new dahl_dev_config;
        $config->setMageRoot($mageRoot);
        $config->setDevRoot(DAHL_DEVROOT);
        $this->_config = $config;

        $includePath = get_include_path();
        $includePath = buildPath($config->getDevRoot(), 'magento', 'Code') . PS . $includePath;
        set_include_path($includePath);
        spl_autoload_register(array($this, 'autoload'), true, true);

        return true;
    }

    /**
     * Autoload function for external modules.
     * 
     * @param string $class
     * @access public
     * @return boolean
     */
    public function autoload($class)
    {
        $class      = explode(' ', ucwords(str_replace('_', ' ', $class)));
        $classFile  = implode(DS, $class) . '.php';
        $config     = $this->_config;
        $file       = $config->getClassFile($classFile);

        if ($file) {
            return include_once $file;
        }

        return false;
    }

    /**
     * Includes config files.
     * 
     * @access protected
     * @return void
     */
    protected function _readConfigFiles()
    {
        $config   = $this->_config;
        $devRoot  = $config->getDevRoot();
        $mageRoot = $config->getMageRoot();

        $configFiles = array(
            buildPath($devRoot, 'magento', 'default.php'),
            buildPath($devRoot, 'magento', 'local.php'),
            buildPath($mageRoot, 'dev', 'default.php'),
            buildPath($mageRoot, 'dev', 'local.php')
        );

        foreach ($configFiles as $filename) {
            if (file_exists($filename)) {
                include $filename;
            }
        }
    }

    /**
     * Initializes setup from configuration options.
     * 
     * @access protected
     * @return void
     */
    protected function _initSetup()
    {
        $config = $this->_config;

        if ($config->getMageDevmode()) {
            $_SERVER['MAGE_IS_DEVELOPER_MODE'] = 1;
            ini_set('display_errors', 1);
        } else {
            unset($_SERVER['MAGE_IS_DEVELOPER_MODE']);
        }
    }
}


/**
 * Bootstrap dev app.
 */
dahl_dev::init();

if (
    strpos($_SERVER['SCRIPT_NAME'], 'cron.php') !== false
) {
    include buildPath(DAHL_DEVROOT, 'magento', 'cron.php');
    exit();
}
