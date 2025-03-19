<?php

/**
 *  Mini Engine: https://github.com/openfunltd/mini-engine
 *  License: BSD 3-Clause License
 */
define('MINI_ENGINE_VERSION', '0.1.0');

class MiniEngine
{
    protected static $session_data = null;

    public static function getSessionDomain()
    {
        return getenv('SESSION_DOMAIN') ?: $_SERVER['HTTP_HOST'];
    }

    public static function getSessionTimeout()
    {
        return 60 * 60 * 24 * 30; // 30 days
    }

    public static function initSessionData()
    {
        if (is_null(self::$session_data)) {
            $session_secret = getenv('SESSION_SECRET');
            if (!$session_secret) {
                throw new Exception("SESSION_SECRET is not set.");
            }

            $session = $_COOKIE[session_name()] ?? '';
            $session = explode('|', $session, 2);
            if (count($session) != 2) {
                self::$session_data = new StdClass;
                return;
            }

            $sig = $session[0];
            $data = $session[1];
            if ($sig != self::sessionSignature($data . self::getSessionDomain() . $session_secret)) {
                self::$session_data = new StdClass;
                return;
            }

            self::$session_data = json_decode($data);
        }
    }

    public static function writeSession()
    {
        $session_secret = getenv('SESSION_SECRET');
        if (!$session_secret) {
            throw new Exception("SESSION_SECRET is not set.");
        }

        $data = json_encode(self::$session_data);
        $sig = self::sessionSignature(json_encode(self::$session_data) . self::getSessionDomain() . $session_secret);

        setcookie(
            session_name(), // name
            $sig . '|' . $data, // value
            self::getSessionTimeout() ? time() + self::getSessionTimeout() : null, // expire
            '/', // path
            self::getSessionDomain(), // domain
            true // secure
        );
    }

    public static function deleteSession($key)
    {
        self::initSessionData();
        if (!property_exists(self::$session_data, $key)) {
            return;
        }
        unset(self::$session_data->{$key});

        self::writeSession();
    }

    public static function setSession($key, $value)
    {
        self::initSessionData();
        if (property_exists(self::$session_data, $key) and self::$session_data->{$key} === $value) {
            return;
        }
        self::$session_data->{$key} = $value;
        self::writeSession();
    }

    public static function sessionSignature($data)
    {
        return hash_hmac('sha256', $data, getenv('SESSION_SECRET'));
    }

    public static function getSession($key)
    {
        self::initSessionData();
        return self::$session_data->{$key} ?? null;
    }

    protected static $db = null;
    public static function getDb()
    {
        if (is_null(self::$db)) {
            $url = getenv('DATABASE_URL');
            if (!$url) {
                throw new Exception("DATABASE_URL is not set.");
            }

            $url = parse_url($url);
            $dsn = "{$url['scheme']}:host={$url['host']};port={$url['port']};dbname=" . ltrim($url['path'], '/');
            self::$db = new PDO($dsn, $url['user'], $url['pass']);
            self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$db;
    }

    public static function dbExecute($sql, $params = [])
    {
        $db = self::getDb();
        $copy_params = $params;
        // handle ::table, ::cols to escape table and column names
        $sql = preg_replace_callback('/::[a-z_0-9A-Z]+/', function($matches) use ($db, $params, &$copy_params) {
            $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
            unset($copy_params[$matches[0]]);
            if (!array_key_exists($matches[0], $params)) {
                throw new Exception("Parameter not found: {$matches[0]}");
            }
            if ('pgsql' == $driver) {
                return '"' . $params[$matches[0]] . '"';
            } elseif ('mysql' == $driver) {
                return '`' . $params[$matches[0]] . '`';
            } else {
                throw new Exception("Unsupported database driver: $driver");
            }
        }, $sql);
        $stmt = self::getDb()->prepare($sql);
        self::log($sql, $copy_params);
        $stmt->execute($copy_params);
        return $stmt;
    }

    public static function log($sql, $params)
    {
        if (getenv('ENV') == 'production') {
            return;
        }
        // firt 100 and last 100 characters
        if (strlen($sql) > 200) {
            $sql = substr($sql, 0, 100) . '...' . substr($sql, -100);
        }
        error_log("SQL: $sql, Params: " . mb_strimwidth(json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 300, '...'));
    }

    public static function initEnv()
    {
        self::registerAutoLoad();
        error_reporting(E_ALL ^ E_NOTICE);
    }

    public static function defaultErrorHandler($error)
    {
        $message = $error->getMessage();
        $trace = $error->getTrace();
        $file = $trace[0]['file'];
        $line = $trace[0]['line'];
        $trace = array_map(function($idx) use ($trace){
            $level = $idx + 1;
            return "#{$level} {$trace[$idx]['file']}:{$trace[$idx]['line']}";
        }, array_keys($trace));
        $trace = array_merge([
            "#0 $file:$line"
        ], $trace);

        if ('MiniEngine_Controller_NotFound' != get_class($error)) {
            error_log("Error: $message in $file:$line\nStack trace:\n" . implode("\n", $trace));
        } else {
            if (getenv('ENV') == 'production') {
                header('HTTP/1.1 404 Not Found');
                echo "<h1>404 Not Found</h1>";
                exit;
            }
        }

        if (getenv('ENV') == 'production') {
            header('HTTP/1.1 500 Internal Server Error');
            exit;
        }
        echo "<p>Error: " . $error->getMessage() . "</p>";
        echo "<ul>";
        echo "<li>" . $error->getFile() . ":" . $error->getLine() . "</li>";
        foreach ($error->getTrace() as $trace) {
            echo "<li>" . $trace['file'] . ":" . $trace['line'] . "</li>";
        }
        echo "</ul>";

        throw new MiniEngine_Controller_NoView();
    }

    public static function registerAutoLoad()
    {
        spl_autoload_register(array('MiniEngine', 'autoload'));
	}

	public static function autoload($class)
    {
		if (class_exists($class, false) or interface_exists($class, false)) {
			return false;
		}

		$class = str_replace('\\', DIRECTORY_SEPARATOR, str_replace('_', DIRECTORY_SEPARATOR, $class)) . '.php';

		$paths = explode(PATH_SEPARATOR, get_include_path());
		foreach ($paths as $path) {
			$path = rtrim($path, '/');
			if (file_exists($path . '/' . $class)) {
				require $class;
				return true;
			}
		}

		return false;
    }

    public static function getRoot()
    {
        if (defined('MINI_ENGINE_ROOT')) {
            return MINI_ENGINE_ROOT;
        }
        return __DIR__;
    }

    public static function dispatch($custom_function = null)
    {
        try {
            if (strpos($_SERVER['REQUEST_URI'], '/static') === 0) {
                $file = self::getRoot() . $_SERVER['REQUEST_URI'];
                if (file_exists($file) and is_file($file)) {
                    header('Content-Type: ' . mime_content_type($file));
                    readfile($file);
                    return;
                }
            }
            $controller_action_params = self::getControllerAndAction($custom_function);
            $controller = $controller_action_params[0];
            $action = $controller_action_params[1];
            $params = $controller_action_params[2] ?? [];

            self::runControllerAction($controller, $action, $params);
        } catch (Exception $e) {
            self::runControllerAction('error', 'error', [$e]);
            return;
        } catch (Error $e) {
            self::runControllerAction('error', 'error', [$e]);
            return;
        }
    }

    protected static function ucfirst($string)
    {
        // foo_bar_baz to FooBarBaz
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }

    protected static function runControllerAction($controller, $action, $params)
    {
        $controller_class = self::ucfirst($controller) . 'Controller';
        $controller_file = self::getRoot() . '/controllers/' . $controller_class . '.php';
        if (!file_exists($controller_file)) {
            return self::runControllerAction('error', 'error', [new MiniEngine_Controller_NotFound("Controller not found: {$controller}:{$action}")]);
        }

        if (!class_exists($controller_class)) {
            include($controller_file);
        }

        $controller_instance = new $controller_class();
        $action_method = $action . 'Action';
        if (!method_exists($controller_instance, $action_method)) {
            return self::runControllerAction('error', 'error', [new MiniEngine_Controller_NotFound("Action not found: {$controller}:{$action}")]);
        }

        try {
            call_user_func_array([$controller_instance, 'init'], $params);
            call_user_func_array([$controller_instance, $action_method], $params);
            $view_file = self::getRoot() . '/views/' . $controller . '/' . $action . '.php';
            echo $controller_instance->draw($view_file);
        } catch (MiniEngine_Controller_NoView $e) {
            // do nothing
        } catch (Exception $e) {
            self::runControllerAction('error', 'error', [$e]);
        }
    }

    protected static function getControllerAndAction($custom_function)
    {
        if (!is_null($custom_function)) {
            $uri = $_SERVER['REQUEST_URI'];
            if (strpos($uri, '?')) {
                $uri = explode('?', $uri)[0];
            }
            $result = $custom_function($uri);
            if (!is_null($result)) {
                return $result;
            }
        }

        $uri = $_SERVER['REQUEST_URI'];
        $uri = explode('?', $uri)[0];
        $uri = ltrim($uri, '/');
        $uri = explode('/', $uri);
        $controller = strtolower($uri[0] ?? 'index') ?: 'index';
        $action = strtolower($uri[1] ?? 'index') ?: 'index';
        $params = array_map('urldecode', array_slice($uri, 2));
        return [$controller, $action, $params];
    }

    public static function http($url, $method = 'GET', $data = null, $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (!is_null($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($status < 200 or $status >= 300) {
            throw new Exception("HTTP request failed: $status : $response");
        }
        curl_close($ch);

        return $response;
    }
}

class MiniEngine_Controller_NoView extends Exception
{
}

class MiniEngine_Controller_NotFound extends Exception
{
}

class MiniEngine_Controller_ViewObject
{
    protected $_data = [];
    protected $_yield = [];

    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }

    public function __get($name)
    {
        return $this->_data[$name] ?? null;
    }

    public function __isset($name)
    {
        return isset($this->_data[$name]);
    }

    protected $_current_yield = [];
    public function yield_start($name)
    {
        ob_start();
        $this->_yield[$name] = '';
        array_push($this->_current_yield, $name);
    }

    public function yield_end()
    {
        $name = array_pop($this->_current_yield);
        $this->_yield[$name] = ob_get_clean();
    }

    public function yield_set($name, $value)
    {
        $this->_yield[$name] = $value;
    }

    public function yield($name)
    {
        return $this->_yield[$name] ?? '';
    }

    public function if($condition, $true, $false = '')
    {
        return $condition ? $true : $false;
    }

    public function partial($file, $data = null)
    {
        if ($data instanceof MiniEngine_Controller_ViewObject) {
            $data = $data->_data;
        } else {
            $data = (array) $data;
        }

        $original_data = $this->_data;
        $this->_data = $data;

        if (!file_exists($file)) {
            if (file_exists(MiniEngine::getRoot() . "/views/{$file}.php")) {
                $file = MiniEngine::getRoot() . "/views/{$file}.php";
            } else {
                throw new Exception("Partial file not found: $file");
            }
        }

        ob_start();
        include($file);
        $content = ob_get_clean();

        $this->_data = $original_data;
        return $content;
    }

    public function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

class MiniEngine_Controller
{
    protected $view;

    public function __construct()
    {
        $this->view = new MiniEngine_Controller_ViewObject();
    }

    public function init()
    {
    }

    public function draw($view_file)
    {
        if (!file_exists($view_file)) {
            throw new Exception("View file not found: $view_file");
        }
        return $this->view->partial($view_file, $this->view);
    }

    public function json($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $this->noview();
    }

    public function cors_json($data)
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
        return $this->json($data);
    }

    public function noview()
    {
        throw new MiniEngine_Controller_NoView();
    }

    public function redirect($uri, $code = 302)
    {
        header("Location: $uri", true, $code);
        return $this->noview();
    }

    public function alert($message, $uri = null)
    {
        echo "<script>alert(" . json_encode($message) . ");";
        if ($uri) {
            echo "location.href=" . json_encode($uri);
        }
        echo "</script>";
        return $this->noview();
    }

    public function notfound($message)
    {
        throw new MiniEngine_Controller_NotFound($message);
    }

    public function init_csrf()
    {
        $csrf_token = MiniEngine::getSession('csrf_token');
        if ($csrf_token) {
            $this->view->csrf_token = $csrf_token;
            return;
        }
        $csrf_token = bin2hex(random_bytes(32));
        MiniEngine::setSession('csrf_token', $csrf_token);
        $this->view->csrf_token = $csrf_token;
    }
}

class MiniEngine_Table_DuplicateException extends Exception
{
}

class MiniEngine_Table
{
    protected static $_tables = [];

    protected $_name = null;
    protected $_primary_keys = null;
    protected $_columns = null;
    protected $_indexes = null;
    protected $_relations = null;
    protected $_table = null;

    public function init()
    {
    }

    public function __construct()
    {
        // do nothing
    }

    public function __init()
    {
        $this->init();
        if (is_null($this->_table)) {
            $this->_table = $this->getTableName();
        }
    }

    public static function quote($value, $col = null)
    {
        $table = self::getTableClass();
        $table_columns = $table->getTableColumns();
        $db = MiniEngine::getDb();
        if (is_null($col)) {
            return $db->quote($value);
        }
        if (!array_key_exists($col, $table_columns)) {
            throw new Exception("Column not found: $col");
        }
        if (in_array($table_columns[$col]['type'], ['int', 'integer', 'bigint'])) {
            return (int) $value;
        } elseif (in_array($table_columns[$col]['type'], ['bool', 'boolean'])) {
            return $value ? 'TRUE' : 'FALSE';
        } elseif ($table_columns[$col]['type'] == 'jsonb') {
            return $db->quote(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } elseif ($table_columns[$col]['type'] == 'geometry') {
            return "ST_GeomFromGeoJSON(" . $db->quote(json_encode($value)) . ")";
        }
        return $db->quote($value);
    }

    protected static $_debug = 0;
    public static function getTableClass($class = null)
    {
        if (is_null($class)) {
            $class = get_called_class();
        }
        if (!isset(self::$_tables[$class])) {
            self::$_tables[$class] = new $class();
            self::$_tables[$class]->__init();
        }
        return self::$_tables[$class];
    }

    public static function getTableName()
    {
        $table = self::getTableClass();
        if (is_null($table->_name)) {
            // User => user, MeetingMember => meeting_member
            $table_name = get_called_class();
            $table_name = preg_replace_callback('/[A-Z]+/', function($matches) {
                if ($matches[0][1] == 0) {
                    return strtolower($matches[0][0]);
                }
                return '_' . strtolower($matches[0][0]);
            }, $table_name, -1, $count, PREG_OFFSET_CAPTURE);
            return $table_name;
        }
        return $table->_name;
    }

    public static function getTableColumns()
    {
        $table = self::getTableClass();
        if (is_null($table->_columns)) {
            throw new Exception("Columns not defined.");
        }
        return $table->_columns;
    }

    public function getTableRelations()
    {
        $table = self::getTableClass();
        if (is_null($table->_relations)) {
            return [];
        }
        return $table->_relations;
    }

    public function getResultSetClass()
    {
        if (class_exists(get_called_class() . 'Rowset')) {
            return get_called_class() . 'Rowset';
        }
        return 'MiniEngine_Table_Rowset';
    }

    public function getRowClass()
    {
        if (class_exists(get_called_class() . 'Row')) {
            return get_called_class() . 'Row';
        }
        return 'MiniEngine_Table_Row';
    }

    public static function getPrimaryKeys()
    {
        $table = self::getTableClass();
        if (is_null($table->_primary_keys)) {
            $table->_primary_keys = [array_keys($table->_columns)[0] ?? 'id'];
        }
        if (is_string($table->_primary_keys)) {
            $table->_primary_keys = [$table->_primary_keys];
        }
        return $table->_primary_keys;
    }

    protected static $_bulk_insert_data = [];

    public static function bulkInsert($data)
    {
        $table = self::getTableClass();
        $table_name = get_class($table);
        if (!array_key_exists($table_name, self::$_bulk_insert_data)) {
            self::$_bulk_insert_data[$table_name] = [
                'table' => $table,
                'columns' => [],
                'records' => [],
            ];
        }

        $record = [];
        foreach ($data as $col => $value) {
            if (!array_key_exists($col, self::$_bulk_insert_data[$table_name]['columns'])) {
                self::$_bulk_insert_data[$table_name]['columns'][$col] = count(self::$_bulk_insert_data[$table_name]['columns']);
            }
            $idx = self::$_bulk_insert_data[$table_name]['columns'][$col];
            $record[$idx] = $value;
        }
        self::$_bulk_insert_data[$table_name]['records'][] = $record;
        if (count(self::$_bulk_insert_data[$table_name]['records']) >= 1000) {
            self::bulkCommit($table_name);
        }
    }

    public static function bulkCommit($table_name = null)
    {
        if (is_null($table_name)) {
            foreach (array_keys(self::$_bulk_insert_data) as $table_name) {
                self::bulkCommit($table_name);
            }
            return;
        }

        if (!array_key_exists($table_name, self::$_bulk_insert_data)) {
            return;
        }
        $table = self::$_bulk_insert_data[$table_name]['table'];
        $params = [
            '::table' => $table->getTableName(),
        ];
        $table_columns = $table->getTableColumns();
        $col_terms = [];
        foreach (self::$_bulk_insert_data[$table_name]['columns'] as $col => $idx) {
            if (!array_key_exists($col, $table_columns)) {
                throw new Exception("Column not found: $col");
            }
            $col_terms[] = "::col_{$col}";
            $params["::col_{$col}"] = $col;
        }
        $insert_terms = [];
        $val_idx = 0;
        foreach (self::$_bulk_insert_data[$table_name]['records'] as $record) {
            $value_terms = [];
            foreach (self::$_bulk_insert_data[$table_name]['columns'] as $col => $idx) {
                $val = $record[$idx] ?? null;
                if (is_null($val)) {
                    $value_terms[] = "NULL";
                } elseif ($table_columns[$col]['type'] == 'jsonb') {
                    $val = json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $value_terms[] = ":val_{$val_idx}";
                    $params[":val_{$val_idx}"] = $val;
                } elseif ($table_columns[$col]['type'] == 'geometry') {
                    $val = json_encode($val);
                    $value_terms[] = "ST_GeomFromGeoJSON(:val_{$val_idx})";
                    $params[":val_{$val_idx}"] = $val;
                } else {
                    $value_terms[] = ":val_{$val_idx}";
                    $params[":val_{$val_idx}"] = $val;
                }
                $val_idx++;
            }
            $insert_terms[] = "(" . implode(', ', $value_terms) . ")";
        }

        $sql = "INSERT INTO ::table (" . implode(', ', $col_terms) . ") VALUES " . implode(', ', $insert_terms);
        try {
            $stmt = MiniEngine::dbExecute($sql, $params);
        } catch (PDOException $e) {
            if ($e->getCode() == 23505) {
                throw new MiniEngine_Table_DuplicateException($e->getMessage());
            }
            throw $e;
        }
        unset(self::$_bulk_insert_data[$table_name]);
    }

    public static function insert($data)
    {
        $table = self::getTableClass();
        $params = [
            '::table' => $table->getTableName(),
        ];
        $table_columns = $table->getTableColumns();
        $cols = [];
        $vals = [];
        foreach ($data as $col => $val) {
            $cols[] = "::col_{$col}";
            $params["::col_{$col}"] = $col;
            $val_item  = ":val_{$col}";
            if ($table_columns[$col]['type'] == 'jsonb') {
                $val = json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif ($table_columns[$col]['type'] == 'geometry') {
                $val_item = "ST_GeomFromGeoJSON(:val_{$col})";
                $val = json_encode($val);
            }
            $vals[] = $val_item;
            $params[":val_{$col}"] = $val;
        }
        $sql = "INSERT INTO ::table (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ")";
        try {
            $stmt = MiniEngine::dbExecute($sql, $params);
        } catch (PDOException $e) {
            if ($e->getCode() == 23505) {
                throw new MiniEngine_Table_DuplicateException($e->getMessage());
            }
            throw $e;
        }
        $insert_id = MiniEngine::getDb()->lastInsertId();
        if (!$insert_id) {
            throw new Exception("Unable to get last insert id.");
        }
        return $table->find($insert_id);
    }

    public static function find($id)
    {
        $table = self::getTableClass();
        $primary_keys = $table->getPrimaryKeys();
        return $table->search(array_combine($primary_keys, is_scalar($id) ? [$id] : $id), '*')->first();
    }

    public static function search($terms, $opts = null)
    {
        $table = self::getTableClass();
        $conf = [];
        $conf['table'] = $table;
        $rowset_class = $table->getResultSetClass();
        $rowset = new $rowset_class($conf);
        return $rowset->search($terms, $opts);
    }

    public static function createTable()
    {
        $table = self::getTableClass();
        if (is_null($table->_columns)) {
            throw new Exception("Columns not defined.");
        }
        $params = [
            '::table' => $table->getTableName(),
        ];
        $cols = [];
        foreach ($table->_columns as $col => $config) {
            if (!array_key_exists('type', $config)) {
                throw new Exception("Type not defined for column: $col");
            }
            $alias = [
                'int' => 'integer',
                'bool' => 'boolean',
            ];

            if (in_array(strtolower($config['type']), [
                'serial',
                'integer', 'int',
                'bigint',
                'bool', 'boolean',
                'text',
                'jsonb',
                'geometry',
            ])) {
                $type = strtolower($config['type']);
                if (array_key_exists($type, $alias)) {
                    $type = $alias[$type];
                }
                $cols[] = "::col{$col} " . strtoupper($type);
                $params["::col{$col}"] = $col;
            } elseif ($config['type'] == 'varchar') {
                $cols[] = "::col_{$col} VARCHAR(" . ($config['length'] ?? 255) . ")";
                $params["::col_{$col}"] = $col;
            } else {
                throw new Exception("Unsupported column type: {$config['type']}");
            }
        }
        $sql = "CREATE TABLE ::table (" . implode(', ', $cols) . ")";
        MiniEngine::dbExecute($sql, $params);

        if (is_array($table->_indexes) and count($table->_indexes)) {
            foreach ($table->_indexes as $index_name => $config) {
                if (!array_key_exists('columns', $config)) {
                    throw new Exception("Columns not defined for index.");
                }
                $index_cols = [];
                $params = [
                    '::table' => $table->getTableName(),
                    '::index_name' => $index_name,
                ];
                foreach ($config['columns'] as $col) {
                    $index_cols[] = "::index_{$index_name}_{$col}";
                    $params["::index_{$index_name}_{$col}"] = $col;
                }

                if (array_key_exists('unique', $config) and $config['unique']) {
                    $sql = "CREATE UNIQUE INDEX ::index_name ON ::table (" . implode(', ', $index_cols) . ")";
                } else {
                    $sql = "CREATE INDEX ::index_name ON ::table (" . implode(', ', $index_cols) . ")";
                }
                MiniEngine::dbExecute($sql, $params);
            }
        }
    }

    public static function __callStatic($name, $args)
    {
        $table = self::getTableClass();
        return $table->__call($name, $args);
    }

    public function __call($name, $args)
    {
        $table = self::getTableClass();
        if (preg_match('#^find_by_([a-zA-Z0-9_]+)$#', $name, $matches)) {
            $cols = explode('_and_', $matches[1]);
            return $table->search(array_combine($cols, $args), '*')->first();
        }
        throw new Exception("Method not found: $name");
    }
}

class MiniEngine_Table_Row
{
    protected $_table = null;
    protected $_data = null;
    protected $_origin_data = null;

    public function __construct($conf)
    {
        $this->_table = $conf['table'];
        $this->_data = $this->_origin_data = $conf['data'];
    }

    public function toArray()
    {
        return $this->_data;
    }

    public function delete()
    {
        $params = [
            '::table' => $this->_table->getTableName(),
        ];
        $terms = [];
        foreach ($this->_table->getPrimaryKeys() as $idx => $key) {
            $terms[] = "::id_col_{$idx} = :id_val_{$idx}";
            $params["::id_col_{$idx}"] = $key;
            $params[":id_val_{$idx}"] = $this->_data[$key];
        }
        $sql = "DELETE FROM ::table WHERE " . implode(' AND ', $terms);
        MiniEngine::dbExecute($sql, $params);
    }

    public function update($data)
    {
        foreach ($data as $k => $v) {
            $this->{$k} = $v;
        }
        $this->save();
    }

    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }

    public function __get($name)
    {
        $table_columns = $this->_table->getTableColumns();
        if (array_key_exists($name, $table_columns)) {
            return $this->_data[$name] ?? null;
        }

        $table_relations = $this->_table->getTableRelations();
        if (array_key_exists($name, $table_relations)) {
            $relation = $table_relations[$name];
            if (!array_key_exists('rel', $relation)) {
                throw new Exception("Relation type not defined.");
            }
            if (!array_key_exists('type', $relation)) {
                throw new Exception("Relation type not defined.");
            }

            $target_table = MiniEngine_Table::getTableClass($relation['type']);
            if ($relation['rel'] == 'has_many') {
                $foreign_key = $relation['foreign_key'] ?? $this->_table->getPrimaryKeys()[0];
                $foreign_value = $this->_data[$this->_table->getPrimaryKeys()[0]];
                return $target_table->search([$foreign_key => $foreign_value]);
            } else if ($relation['rel'] == 'has_one') {
                $foreign_key = $relation['foreign_key'] ?? $this->_table->getPrimaryKeys()[0];
                $foreign_value = $this->_data[$foreign_key];
                return $target_table->find($foreign_value);
            } else {
                throw new Exception("Unsupported relation type: {$relation['rel']}");
            }
        }

        throw new Exception("Column not found: $name");
    }

    public function save()
    {
        $params = [
            '::table' => $this->_table->getTableName(),
        ];
        $cols = [];
        $vals = [];
        $update_terms = [];
        $table_columns = $this->_table->getTableColumns();
        foreach ($this->_data as $col => $val) {
            if ($table_columns[$col]['type'] == 'jsonb') {
                $val = json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            if (!array_key_exists($col, $this->_origin_data)) {
                $cols[] = "::col_{$col}";
                $vals[] = ":val_{$col}";
                $params["::col_{$col}"] = $col;
                $params[":val_{$col}"] = $val;
            } elseif ($val != $this->_origin_data[$col]) {
                $update_terms[] = "::col_{$col} = :val_{$col}";
                $params["::col_{$col}"] = $col;
                $params[":val_{$col}"] = $val;
            }
        }
        if (!count($update_terms)) {
            return;
        }
        $where_terms = [];
        foreach ($this->_table->getPrimaryKeys() as $idx => $key) {
            $where_terms[] = "::id_col_{$idx} = :id_val_{$idx}";
            $params["::id_col_{$idx}"] = $key;
            $params[":id_val_{$idx}"] = $this->_origin_data[$key];
        }
        $sql = "UPDATE ::table SET " . implode(', ', $update_terms) . " WHERE " . implode(' AND ', $where_terms);
        MiniEngine::dbExecute($sql, $params);
        $this->_origin_data = $this->_data;
    }
}

class MiniEngine_Table_Rowset implements Countable, SeekableIterator
{
    protected $_table = null;
    protected $_data = null;
    protected $_flags = null;
    protected $_limit = null;
    protected $_offset = null;
    protected $_order = null;
    protected $_pointer = 0;
    protected $_search = [];

    public function __construct($conf)
    {
        $this->_table = $conf['table'];
        $this->_flags = $conf['flags'] ?? null;
    }

    public function search($terms, $flags = null)
    {
        $rs = clone $this;
        $rs->_search[] = $terms;
        $rs->_flags = $flags;
        return $rs;
    }

    public function getOrderQuery($order, &$params)
    {
        if (is_scalar($order)) {
            return $order;
        }
        $terms = [];
        $idx = 0;
        foreach ($order as $k => $v) {
            $col = "::col_order_{$idx}";
            $idx ++;

            $terms[] = "{$col} " . (strtolower($v) == 'asc' ? 'ASC' : 'DESC');
            $params["{$col}"] = $k;
        }
        return implode(', ', $terms);
    }

    public function getSearchQuery(&$params)
    {
        $terms = [];
        foreach ($this->_search as $search) {
            if (is_array($search)) {
                foreach ($search as $k => $v) {
                    if (is_array($v)) {
                        $search_params = [];
                        if (!count($v)) {
                            $search_params[] = "1 = 0"; // false
                            continue;
                        }
                        foreach ($v as $idx => $val) {
                            $search_params[] = ":val_{$k}_{$idx}";
                            $params[":val_{$k}_{$idx}"] = $val;
                        }
                        $terms[] = "::col_{$k} IN (" . implode(', ', $search_params) . ")";
                        $params["::col_{$k}"] = $k;
                    } else {
                        $terms[] = "::col_{$k} = :val_{$k}";
                        $params["::col_{$k}"] = $k;
                        $params[":val_{$k}"] = $v;
                    }
                }
                continue;
            }
            if (is_scalar($search) and 1 == $search) {
                continue;
            }

            if (is_scalar($search)) {
                return $search;
            }

            throw new Exception("Unsupported search query." . json_encode($search));
        }
        if (count($terms) == 0) {
            return '1=1';
        }
        return implode(' AND ', $terms);
    }

    public function count(): int
    {
        $params = [
            '::table' => $this->_table->getTableName(),
        ];
        $sql = "SELECT COUNT(*) AS count FROM ::table WHERE " . $this->getSearchQuery($params);
        $stmt = MiniEngine::dbExecute($sql, $params);
        return $stmt->fetchColumn();
    }

    public function seek($position): void
    {
        throw new Exception("Not implemented.");
    }

    public function current(): mixed
    {
        if (is_null($this->_data)) {
            $this->rewind();
        }
        if (!isset($this->_data[$this->_pointer])) {
            return null;
        }
        $conf = [];
        $conf['data'] = $this->_data[$this->_pointer];
        $conf['table'] = $this->_table;
        $row_class = $this->_table->getRowClass();
        return new $row_class($conf);
    }

    public function next(): void
    {
        ++ $this->_pointer;
    }

    public function key(): mixed
    {
        return $this->_pointer;
    }

    public function valid(): bool
    {
        if (is_null($this->_data)) {
            $this->rewind();
        }
        return isset($this->_data[$this->_pointer]);
    
    }

    public function rewind(): void
    {
        $params = [
            '::table' => $this->_table->getTableName(),
        ];
        $select_terms = [];
        $table_columns = $this->_table->getTableColumns();
        $col_idx = 0;
        foreach ($table_columns as $col => $config) {
            if ($this->_flags === '*') {
                // all fields
            } else if (is_array($this->_flags) and !in_array($col, $this->_flags)) {
                continue;
            } else if (is_scalar($this->_flags)) {
               if ($col != $this->_flags) {
                   continue;
               }
            } else if ($config['lazy'] ?? false) {
                continue;
            }
            $params["::select_{$col_idx}"] = $col;
            if ($config['type'] == 'geometry') {
                $select_terms[] = "ST_AsGeoJSON(::select_{$col_idx}) AS ::select_{$col_idx}";
            } else {
                $select_terms[] = "::select_{$col_idx}";
            } 
            $col_idx ++;
        }

        $sql = "SELECT " . implode(',', $select_terms) . " FROM ::table WHERE " . $this->getSearchQuery($params);
        if (!is_null($this->_order)) {
            $sql .= " ORDER BY " . $this->getOrderQuery($this->_order, $params);
        }
        if (!is_null($this->_limit)) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = $this->_limit;
        }
        if (!is_null($this->_offset)) {
            $sql .= " OFFSET :offset";
            $params[':offset'] = $this->_offset;
        }
        $stmt = MiniEngine::dbExecute($sql, $params);
        $this->_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $table_columns = $this->_table->getTableColumns();
        $this->_data = array_map(function($row) use ($table_columns) {
            foreach ($row as $k => $v) {
                if ($table_columns[$k]['type'] == 'jsonb') {
                    $row[$k] = is_null($v) ? null: json_decode($v);
                } elseif ($table_columns[$k]['type'] == 'geometry') {
                    $row[$k] = is_null($v) ? null: json_decode($v);
                }
            }
            return $row;
        }, $this->_data);

        $this->_pointer = 0;
    }

    public function first()
    {
        $this->rewind();
        return $this->current();
    }

    public function toArray($col = null)
    {
        $this->rewind();
        $data = [];
        foreach ($this as $row) {
            if (is_scalar($col)) {
                $data[] = $row->$col;
            } else {
                $data[] = $row->toArray();
            }
        }
        return $data;
    }

    public function order($order)
    {
        $rs = clone $this;
        $rs->_order = $order;
        return $rs;
    }

    public function limit($limit)
    {
        $rs = clone $this;
        $rs->_limit = $limit;
        return $rs;
    }

    public function offset($offset)
    {
        $rs = clone $this;
        $rs->_offset = $offset;
        return $rs;
    }
}

class MiniEngine_Prompt
{
    public static function init()
    {
        if (file_exists(__DIR__ . '/init.inc.php')) {
            include(__DIR__ . '/init.inc.php');
        }
        if (file_exists(__DIR__ . '/.prompt_history')) {
            readline_read_history(__DIR__ . '/.prompt_history');
        }

        while($line = readline(">> ")) {
            if (function_exists('readline_add_history')) {
                readline_add_history($line);
            }
            if (function_exists('readline_write_history')) {
                readline_write_history(__DIR__ . '/.prompt_history');
            }
            try {
                eval($line . ";");
                echo "\n";
            } catch (Exception $e) {
                echo $e->getMessage() . "\n";
                echo $e->getTraceAsString() . "\n";
            }
        }
    }
}

class MiniEngineCLI
{
    public static function dispatch()
    {
        $cmd = $_SERVER['argv'][1] ?? null;
        switch ($cmd) {
            case 'init':
                self::cmd_init();
                break;
            case 'update':
                self::cmd_update();
                break;
            case 'prompt':
                MiniEngine_Prompt::init();
                break;
            default:
                self::cmd_help();
                break;
        }
    }

    public static function cmd_update()
    {
        $url = 'https://raw.githubusercontent.com/openfunltd/mini-engine/main/mini-engine.php';
        $current_version = MINI_ENGINE_VERSION;

        $new_mini_engine = file_get_contents($url);
        preg_match('/define\(\'MINI_ENGINE_VERSION\', \'([^\']+)\'\);/', $new_mini_engine, $matches);
        $new_version = $matches[1] ?? null;

        if ($new_mini_engine === false or $new_version === false) {
            error_log("Failed to update Mini Engine.");
            return;
        }

        $current_mini_engine = file_get_contents(__FILE__);
        if ($new_mini_engine === $current_mini_engine) {
            error_log("Mini Engine is already up-to-date.");
            return;
        }

        file_put_contents(__FILE__, $new_mini_engine);
        error_log("Mini Engine updated from $current_version to $new_version.");
    }

    public static function cmd_init()
    {
        // Create directories
        $directories = [
            'controllers',
            'libraries',
            'static',
            'views',
            'views/index',
            'views/common',
        ];
        foreach ($directories  as $dir) {
            if (file_exists($dir)) {
                continue;
            }
            mkdir($dir);
        }
        error_log("created directories: " . implode(', ', $directories));

        // Create init.inc.php
        file_put_contents('init.inc.php', <<<EOF
<?php

define('MINI_ENGINE_LIBRARY', true);
define('MINI_ENGINE_ROOT', __DIR__);
require_once(__DIR__ . '/mini-engine.php');
if (file_exists(__DIR__ . '/config.inc.php')) {
    include(__DIR__ . '/config.inc.php');
}
set_include_path(
    __DIR__ . '/libraries'
    . PATH_SEPARATOR . __DIR__ . '/models'
);
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once(__DIR__ . '/vendor/autoload.php');
}
MiniEngine::initEnv();

EOF
        );
        error_log("created init.inc.php");

        // Create config.sample.inc.php
        $session_secret = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', 12)), 0, 12);
        file_put_contents('config.sample.inc.php', <<<EOF
<?php

putenv('APP_NAME=Mini Engine sample application');
putenv('DATABASE_URL=pgsql://user:password@localhost:5432/dbname');
putenv('SESSION_SECRET=___SECRET___');
putenv('SESSION_DOMAIN='); // optional

EOF
        );
        error_log("created config.sample.inc.php");
        if (!file_exists('config.inc.php')) {
            $content = file_get_contents('config.sample.inc.php');
            $content = str_replace('__SECRET__', $session_secret, $content);
            file_put_contents('config.inc.php', $content);
            error_log("created config.inc.php");
        }

        // Create .gitignore
        file_put_contents('.gitignore', <<<EOF
config.inc.php
.*.swp
.prompt_history
EOF
        );
        error_log("created .gitignore");

        // Create index.php
        file_put_contents('index.php', <<<EOF
<?php
include(__DIR__ . '/init.inc.php');

MiniEngine::dispatch(function(\$uri){
    if (\$uri == '/robots.txt') {
        return ['index', 'robots'];
    }
    // default
    return null;
});

EOF
        );

        error_log("created index.php");

        // Create controllers/IndexController.php
        file_put_contents('controllers/IndexController.php', <<<EOF
<?php

class IndexController extends MiniEngine_Controller
{
    public function indexAction()
    {
        \$this->view->app_name = getenv('APP_NAME');
    }

    public function robotsAction()
    {
        header('Content-Type: text/plain');
        echo "#\\n";
        return \$this->noview();
    }
}

EOF
        );
        error_log("created controllers/IndexController.php");

        // Create controllers/ErrorController.php
        file_put_contents('controllers/ErrorController.php', <<<EOF
<?php

class ErrorController extends MiniEngine_Controller
{
    public function errorAction(\$error)
    {
        MiniEngine::defaultErrorHandler(\$error);
    }
}

EOF
        );
        error_log("created controllers/ErrorController.php");

        // Create .htaccess
        file_put_contents('.htaccess', <<<EOF
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php [QSA,L]

EOF
        );
        error_log("created .htaccess");

        // Create /views/common/header.php
        file_put_contents('views/common/header.php', <<<EOF
<!DOCTYPE html>
<html>
<head>
<title><?= \$this->escape(\$this->app_name) ?></title>
</head>
<body>

EOF
        );
        error_log("created views/common/header.php");

        // Create /views/common/footer.php
        file_put_contents('views/common/footer.php', <<<EOF
</body>
</html>

EOF
        );
        error_log("created views/common/footer.php");

        // Create /views/index/index.php
        file_put_contents('views/index/index.php', <<<EOF
<?= \$this->partial('common/header') ?>
This is Index page
<?= \$this->partial('common/footer') ?>

EOF
        );
        error_log("created views/index/index.php");

        // Create /libraries/MiniEngineHelper.php
        file_put_contents('libraries/MiniEngineHelper.php', <<<EOF
<?php

class MiniEngineHelper
{
    public static function uniqid(\$len)
    {
        return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', \$len)), 0, \$len);
    }
}

EOF
        );
        error_log("created libraries/MiniEngineHelper.php");

        error_log("Mini Engine project initialized.");
    }

    public static function cmd_help()
    {
        echo "Usage: php mini-engine.php <command>\n";
        echo "Commands:\n";
        echo "  init    Initialize a new Mini Engine project\n";
        echo "  update  Update Mini Engine\n";
        echo "  prompt  Prompt for a command\n";
    }
}

if (!defined('MINI_ENGINE_LIBRARY')) {
    MiniEngineCLI::dispatch();
}
