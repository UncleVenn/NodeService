<?php
namespace service;

class NodeService {
    //命名空间
    const NAMESPACE  = 'app';
    /**
     * F:\phpStudy\PHPTutorial\WWW  项目根目录
     * 该路径是glob()函数的遍历的规则:遍历test下所有二级名为controller的文件夹
     * 以下以TP5为例,遍历application下所有文件夹下的controller文件夹
     */
    const ROOT_PATH = 'F:\phpStudy\PHPTutorial\WWW\thinkphp5\application\*/controller/';
    //获取控制器方法
    public static function getMethodList() {
        static $nodes = [];
        if (count($nodes) > 0) {
            return $nodes;
        }
        self::eachController(function (\ReflectionClass $reflection, $prenode, $class_name, $module) use (&$nodes) {
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                //过滤父类方法
                if ($method->class !== $class_name) {
                    continue;
                }
                $action = strtolower($method->getName());
                list($node, $comment) = ["{$prenode}{$action}", preg_replace("/\s/", '', $method->getDocComment())];
                $nodes[$module][$node] = [
                    'auth' => stripos($comment, '@authtrue') !== false,
                    'menu' => stripos($comment, '@menutrue') !== false,
                    'title' => preg_replace('/^\/\*\*\*(.*?)\*.*?$/', '$1', $comment),
                ];
                if (stripos($nodes[$module][$node]['title'], '@') !== false) {
                    $nodes[$module][$node]['title'] = '';
                }

            }
        });
        return $nodes;
    }
    private static function eachController($callable) {
        foreach (self::scanPath(self::ROOT_PATH) as $file) {
            if (!preg_match("|/(\w+)/controller/(.+)\.php$|", $file, $matches)) {
                continue;
            }
            list($module, $controller) = [$matches[1], strtr($matches[2], '/', '.')];
            if (class_exists($class = substr(strtr(self::NAMESPACE  . $matches[0], '/', '\\'), 0, -4))) {
                call_user_func($callable, new \ReflectionClass($class), self::parseString("{$module}/{$controller}/"), $class, $module);
            }
        }
    }

    private static function scanPath($dirname, $data = [], $ext = 'php') {
        foreach (glob("{$dirname}*") as $file) {
            if (is_dir($file)) {
                $data = array_merge($data, self::scanPath("{$file}/"));
            } elseif (is_file($file) && pathinfo($file, 4) === $ext) {
                $data[] = str_replace('\\', '/', $file);
            }
        }
        return $data;
    }

    public static function parseString($node) {
        if (count($nodes = explode('/', $node)) > 1) {
            $dots = [];
            foreach (explode('.', $nodes[1]) as $dot) {
                $dots[] = trim(preg_replace("/[A-Z]/", "_\\0", $dot), "_");
            }
            $nodes[1] = join('.', $dots);
        }
        $node = strtolower(join('/', $nodes));
        return $node;
    }
}