<?php
declare(strict_types = 1);
define('APPPATH',dirname(dirname(__FILE__)));
require APPPATH.'/vendor/autoload.php';
require APPPATH.'/app/config/routes.php';

// 获取请求的方法和 URI
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// 去除查询字符串( ? 后面的内容) 和 解码 URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

$result=[];
switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        $result['status']=404;
        $result['data']=[];
        $result['message']='404 Not Found 没找到对应的方法';
        // ... 404 Not Found 没找到对应的方法
        header('Content-Type:application/json; charset=utf-8');
        echo json_encode($result);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        $result['status']=405;
        $result['data']=[];
        $result['message']='405 Method Not Allowed  方法不允许';
        header('Content-Type:application/json; charset=utf-8');
        echo json_encode($result);
        // ... 405 Method Not Allowed  方法不允许
        break;
    case FastRoute\Dispatcher::FOUND: // 找到对应的方法
        $handler = $routeInfo[1]; // 获得处理函数
        $vars = $routeInfo[2]; // 获取请求参数
        // ... call $handler with $vars // 调用处理函数
        $temp=explode('/',$handler);
        $className=sprintf('\App\Controller\%s',$temp[0]);
        $function=$temp[1];
        $class=new $className();
        $class->$function(implode(',',$routeInfo[2]));
        break;
}