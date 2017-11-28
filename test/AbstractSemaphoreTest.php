<?php

namespace Amp\Sync\Test;

use Amp\Loop;
use Amp\PHPUnit\TestCase;
use Amp\Sync\Semaphore;

abstract class AbstractSemaphoreTest extends TestCase {
    /**
     * @var \Amp\Sync\Semaphore
     */
    protected $semaphore;

    /**
     * @param int $locks Number of locks in the semaphore.
     *
     * @return \Amp\Sync\Semaphore
     */
    abstract public function createSemaphore(int $locks): Semaphore;

    public function tearDown() {
        unset($this->semaphore); // Force Semaphore::__destruct() to be invoked.
    }

    public function testAcquire() {
        Loop::run(function () {
            $this->semaphore = $this->createSemaphore(1);

            $lock = yield $this->semaphore->acquire();

            $this->assertFalse($lock->isReleased());

            $lock->release();

            $this->assertTrue($lock->isReleased());
        });
    }

    public function testAcquireMultiple() {
        $this->assertRunTimeGreaterThan(function () {
            $this->semaphore = $this->createSemaphore(1);

            Loop::run(function () {
                $lock1 = yield $this->semaphore->acquire();
                Loop::delay(500, function () use ($lock1) {
                    $lock1->release();
                });

                $lock2 = yield $this->semaphore->acquire();
                Loop::delay(500, function () use ($lock2) {
                    $lock2->release();
                });

                $lock3 = yield $this->semaphore->acquire();
                Loop::delay(500, function () use ($lock3) {
                    $lock3->release();
                });
            });
        }, 1500);
    }

    public function testSimultaneousAcquire() {
        $this->assertRunTimeGreaterThan(function () {
            $this->semaphore = $this->createSemaphore(1);

            Loop::run(function () {
                $promise1 = $this->semaphore->acquire();
                $promise2 = $this->semaphore->acquire();

                Loop::delay(500, function () use ($promise1) {
                    (yield $promise1)->release();
                });

                (yield $promise2)->release();
            });
        }, 500);
    }
}
