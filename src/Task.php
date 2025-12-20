<?php

namespace AppKit\Async;

use AppKit\Async\Exception\CanceledException;

use Throwable;
use function React\Async\async;
use function React\Async\await;

class Task {
    public const RUNNING   = 0;
    public const COMPLETED = 1;
    public const CANCELED  = 2;
    public const FAILED    = 3;

    private $status;
    private $promise;

    function __construct($task) {
        $this -> status = self::RUNNING;
        $this -> promise = async($task)();

        $this -> promise -> then(function($result) {
            $this -> status = self::COMPLETED;
        }) -> catch(function(CanceledException $e) {
            $this -> status = self::CANCELED;
        }) -> catch(function(Throwable $e) {
            $this -> status = self::FAILED;
        });
    }

    public function getStatus() {
        return $this -> status;
    }

    public function getPromise() {
        return $this -> promise;
    }

    public function isRunning() {
        return $this -> status == self::RUNNING;
    }

    public function await() {
        return await($this -> promise);
    }

    public function join() {
        try {
            await($this -> promise);
        } catch(Throwable $e) {}
    }

    public function cancel() {
        $this -> promise -> cancel();
    }
}
