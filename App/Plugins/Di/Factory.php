<?php


namespace App\Plugins\Di;


class Factory extends Base
{
    /** The DI instance */
    private static $instance = null;

    /**
     * Function to get the di
     * @return Factory|null     instance of this
     * @throws \Exception
     */
    public static function getDi(): self
    {
        global $di;
        if ($di && !self::$instance) {
            throw new \Exception('Global variable $di usage is restricted, it already exists with incorrect DI instance');
        } elseif ($di) {
            return $di;
        } else {
            self::$instance = new self();
            $di = self::$instance;
            return $di;
        }
    }

    public static function create($class, ...$args): object
    {
        $params = ReflectionHelper::getConstructorParams($class);
        if(!$params){
            return new $class();
        }
        $useParams = [];
        foreach($params as $param){
            if(!$param->getClass()){
                continue;
            }
            $paramType = $param->getClass()->name;
            $val = self::getDi()->getTyped($paramType);
            $useParams[] = $val;
        }
        $useParams = array_merge($useParams, $args);
        return new $class(...$useParams);
    }
}