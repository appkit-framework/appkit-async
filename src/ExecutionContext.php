<?php

namespace AppKit\Async;

use Fiber;

class ExecutionContext {
    private static $data = [];

    public static function has($key) {
        return isset(self::$data[ self::getFiberKey() ][$key]);
    }

    public static function get($key) {
        return self::$data[ self::getFiberKey() ][$key] ?? null;
    }

    public static function set($key, $value) {
        self::$data[ self::getFiberKey() ][$key] = $value;
    }

    public static function delete($key) {
        unset(self::$data[ self::getFiberKey() ][$key]);
    }

    public static function clear() {
        unset(self::$data[ self::getFiberKey() ]);
    }

    public static function inherit($prevFiber) {
        $prevContext = self::$data[ self::getFiberKey($prevFiber) ] ?? null;
        if($prevContext)
            self::$data[ self::getFiberKey() ] = $prevContext;
    }

    private static function getFiberKey($fiber = null) {
        if(!$fiber)
            $fiber = Fiber::getCurrent();

        if(!$fiber)
            return 0;

        return spl_object_id($fiber);
    }
}
