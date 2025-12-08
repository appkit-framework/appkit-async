<?php

namespace AppKit\Async;

use AppKit\Async\Exception\CanceledException;

use React\Promise\Promise;
use React\EventLoop\Loop;
use function React\Async\await;

function delay($seconds) {
    $timer = null;

    await(new Promise(
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
    await(new Promise(
        function($resolve) {
            $resolve(null);
        },
        function() {
            throw new CanceledException();
        }
    ));
}
