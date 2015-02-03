<?php

class dahl_dev_config
{
    /**
     * Holds config data.
     * 
     * @var array
     * @access protected
     */
    protected $_data = array();

    /**
     * Holds name cache.
     * 
     * @var array
     * @access protected
     */
    static protected $_pathCache = array();

    /**
     * Holds external modules.
     * 
     * @var array
     * @access protected
     */
    protected $_modules = array();

    /**
     * Load an external module.
     * 
     * @param string $path  The path to the module root.
     * @param stirng $url   An url from where static files can be loaded.
     * @access public
     * @return void
     */
    public function loadModule($path, $url = null)
    {
        if (is_dir($path)) {
            $realpath = $path;
            $path = explode('/', $path);
            $path = end($path);
        } else {
            $realpath = realpath($this->getModulePath() . '/' . $path);
        }
        if (is_dir($realpath)) {
            if (!$url) {
                $url = sprintf($this->getModuleUrl(), $path);
            }

            $this->_modules[$path] = array(
                'path' => $realpath,
                'url' => $url
            );
        }
    }

    public function getModules()
    {
        return $this->_modules;
    }

    /**
     * Stores value into self::$_data array.
     *
     * @param mixed $key
     * @param mixed $value
     * @static
     * @access public
     * @return void
     */
    public function setPath($key, $value = null)
    {
        if (is_array($key)) {
            $this->_data = $key;
        } else if (is_array($value)) {
            foreach ($value as $k => $v) {
                $this->setPath($key . '/' . $k, $v);
            }
        } else {
            if (strpos($key, '/')) {
                $keyArr = explode('/', $key);
                $data = &$this->_data;
                foreach ($keyArr as $i => $k) {
                    if (is_array($data)) {
                        if (!isset($data[$k])) {
                            $data[$k] = array();
                        }
                        $data = &$data[$k];
                    }
                }

                $data = $value;
            } else {
                $this->_data[$key] = $value;
            }
        }
    }

    /**
     * Returns data from self::$_data.
     *
     * @param string $key
     * @static
     * @access public
     * @return mixed
     */
    public function getPath($key = '')
    {
        if ($key === '') {
            return $this->_data;
        }

        $data = $this->_data;
        $default = null;

        if (strpos($key, '/')) {
            $keyArr = explode('/', $key);
            foreach ($keyArr as $i => $k) {
                if ($k==='') {

                    return $default;
                }
                if (is_array($data)) {
                    if (!isset($data[$k])) {
                        return $default;
                    }
                    $data = $data[$k];
                } else {

                    return $default;
                }
            }

            return $data;
        }

        if (isset($data[$key])) {
            return $data[$key];
        }

        return $default;
    }

    /**
     * Set/Get attribute wrapper
     *
     * @param   string $method
     * @param   array $args
     * @return  mixed
     */
    public function __call($method, $args)
    {
        switch (substr($method, 0, 3)) {
            case 'get' :
                $key = $this->_buildPath(substr($method, 3));
                $data = $this->getPath($key, isset($args[0]) ? $args[0] : null);

                return $data;

            case 'set' :
                $key = $this->_buildPath(substr($method, 3));
                dahbug::dump($key);
                $result = $this->setPath($key, isset($args[0]) ? $args[0] : null);

                return $result;
        }

        throw new Exception("DAHL_DEV: Invalid method {$method}");
    }

    /**
     * Converts field names for setters and geters
     *
     * @return string
     */
    protected function _buildPath($name)
    {
        if (isset(self::$_pathCache[$name])) {
            return self::$_pathCache[$name];
        }
        $result = strtolower(preg_replace('/(.)([A-Z])/', "$1/$2", $name));
        self::$_pathCache[$name] = $result;

        return $result;
    }
}
