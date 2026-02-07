<?php

namespace AppKit\Async;

use Fiber;
use React\Promise\Promise;
use React\EventLoop\Loop;
use function React\Async\async as _async;
use function React\Async\await as _await;

function async($function) {
    $prevFiber = Fiber::getCurrent();
    return _async(function(...$args) use($function, $prevFiber) {
        ExecutionContext::inherit($prevFiber);
        try {
            return $function(...$args);
        } finally {
            ExecutionContext::clear();
        }
    });
}

function await($promise) {
    return _await($promise);
}

function delay($seconds) {
    $timer = null;

    _await(new Promise(
        function($resolve) use($seconds, &$timer) {
            $timer = Loop::addTimer(
                $seconds,
                function() use($resolve) {
                    $resolve(null);
                }
            );
        },
        function() use(&$timer) {
            Loop::cancelTimer($timer);
            throw new CanceledException();
        }
    ));
}

function throwIfCanceled() {
    _await(new Promise(
        function($resolve) {
            $resolve(null);
        },
        function() {
            throw new CanceledException();
        }
    ));
}
