<?php

namespace AppKit\Async;

use AppKit\Async\Exception\CanceledException;

use Throwable;
use function React\Async\async;
use function React\Async\await;

class Task {
    public const RUNNING   = 0;
    public const CANCELING = 1;
    public const COMPLETED = 2;
    public const CANCELED  = 3;
    public const FAILED    = 4;

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

    public function isActive() {
        return $this -> status == self::RUNNING ||
               $this -> status == self::CANCELING;
    }

    public function isDone() {
        return $this -> status >= self::COMPLETED;
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
        $this -> status = self::CANCELING;
        $this -> promise -> cancel();
    }
}
