<?php
declare(strict_types=1);
namespace App\service\database;
use PDO;
use Exception;
class Dao{
    private static $db;
    private static $dbConfig =null;
    private static $connection_error='';
    
    private static function db()
    {
        if(empty(self::$db)){
            if(is_null(self::$dbConfig)){
                self::$dbConfig=require(APPPATH.'/app/config/database.php');
            }
            self::connect(self::$dbConfig);

        }
        return self::$db;
    }

    private static function connect($databaseConfig)
    {
        $config=$databaseConfig['mysql'];
        $config['timeout']='30';
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $config['host'], $config['port'], $config['database'], $config['charset']);
        $options = array(
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
            PDO::ATTR_TIMEOUT => $config['timeout'],
        );
        try {
            self::$db = new PDO($dsn, $config['user'], $config['pass'], $options);
            $retry_time = 0;
            while (true) {
                if (self::is_connected()) {
                    break;
                }

                if ($retry_time < 3) {
                    self::$db= new PDO($dsn, $config['user'], $config['pass'], $options);
                    $retry_time++;
                } else {
                    self::$connection_error = "重试{$retry_time}次连接失败";
                    return false;
                }
            }
            return TRUE;
        } catch (PDOException $e) {
            self::$connection_error = $e->getMessage();
            return FALSE;
        }
    }

    private static function is_connected()
    {
        try {
            $server_info = @ self::$db->getAttribute(PDO::ATTR_SERVER_INFO);
            return strpos($server_info, 'Uptime') !== false;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'gone away') !== false) {
                return false;
            }
            throw new PDOException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    private static function execute($sql,$bind)
    {
        self::db();
        $stm = self::$db->prepare($sql);
        if (is_array($bind) && $bind) {
            $assoc_array = !(array_keys($bind) === range(0, count($bind) - 1));
            foreach ($bind as $k => $v) {
                $stm->bindValue($assoc_array ? ":{$k}" : ($k + 1), $v, self::_get_bind_type($v));
            }
        }
        $result = $stm->execute();
        if ($result === FALSE) {
            $error = $stm->errorInfo();
            throw new Exception($error[2] . ', SQL: ' . $sql . ', Bind: ' . json_encode($bind));
        }
        return $stm;
    }

    public static function select ($sql,$bind)
    {
        self::db();
        $stm = self::execute($sql,$bind);
    
        $result = $stm->fetchAll(PDO::FETCH_ASSOC);
        return $result ? $result : array();
    }
    public static function find ($sql,$bind)
    {
        self::db();
        $stm = self::execute($sql,$bind);
        $result = $stm->fetch(PDO::FETCH_ASSOC);
        return $result ? $result : array();
    }

    public static function update ($sql,$bind)
    {
        self::db();
        $stm = self::execute($sql,$bind);
        $result = $stm->rowCount();
        return $result ? $result : 0;
    }

    public static function insert ($sql,$bind)
    {
        self::db();
        $stm = self::execute($sql,$bind);
        $result = $stm->rowCount();
        return $result ? $result : 0;
    }

    private static function _get_bind_type($val)
    {
        $data_type = gettype($val);
        switch ($data_type) {
            case "boolean":
                return PDO::PARAM_BOOL;
            case "integer":
                return PDO::PARAM_INT;
            case "NULL":
                return PDO::PARAM_NULL;
            default:
                return PDO::PARAM_STR;
        }
    }
}