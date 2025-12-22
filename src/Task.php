<?php

namespace AppKit\Async;

use AppKit\Async\Exception\CanceledException;
use AppKit\Async\Exception\TaskException;

use Throwable;
use React\Promise\Deferred;
use React\EventLoop\Loop;
use function React\Async\async;
use function React\Async\await;

class Task {
    public const PENDING   = 0;
    public const RUNNING   = 1;
    public const CANCELING = 2;
    public const COMPLETED = 3;
    public const CANCELED  = 4;
    public const FAILED    = 5;

    private $task;

    private $status;
    private $deferred;
    private $callPromise;

    function __construct($task) {
        $this -> task = $task;

        $this -> status = self::PENDING;
        $this -> deferred = new Deferred(function() {
            if($this -> callPromise)
                $this -> callPromise -> cancel();
            else
                throw new CanceledException();
        });

        $this -> deferred -> promise() -> then(function($result) {
            $this -> status = self::COMPLETED;
        }) -> catch(function(CanceledException $e) {
            $this -> status = self::CANCELED;
        }) -> catch(function(Throwable $e) {
            $this -> status = self::FAILED;
        });
    }

    public function run(...$args) {
        if($this -> status != self::PENDING)
            throw new TaskException('Task is not in PENDING state');

        $this -> status = self::RUNNING;
        Loop::futureTick(function() use($args) {
            $this -> callPromise = async($this -> task)(...$args);
            $this -> deferred -> resolve($this -> callPromise);
        });

        return $this;
    }

    public function cancel() {
        if($this -> status == self::PENDING || $this -> status == self::RUNNING) {
            $this -> status = self::CANCELING;
            $this -> deferred -> promise() -> cancel();
        }

        return $this;
    }

    public function await() {
        return await($this -> deferred -> promise());
    }

    public function join() {
        try {
            $this -> await();
        } catch(Throwable $e) {}

        return $this;
    }

    public function getStatus() {
        return $this -> status;
    }

    public function isActive() {
        return $this -> status == self::RUNNING ||
               $this -> status == self::CANCELING;
    }

    public function isDone() {
        return $this -> status >= self::COMPLETED;
    }
}
