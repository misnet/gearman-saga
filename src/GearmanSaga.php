<?php

namespace GearmanSaga;

use React\Promise\Deferred;
use function React\Promise\all;
use GearmanClient;
use GearmanTask;
use Closure;
use Generator;
use React\Promise\Promise;

final class GearmanSaga
{

    public $tasks = [];
    private $client;
    private $promises = [];

    public function __construct(GearmanClient $client)
    {
        $this->client = $client;
        $tasks =& $this->tasks;
        $promises =& $this->promises;
        $client->setCompleteCallback(Closure::bind(function (GearmanTask $e) use (&$tasks, &$promises) {
            $deferred = $tasks[$e->unique()];
            unset($promises[$e->unique()]);
            unset($tasks[$e->unique()]);
            if ($deferred && $deferred instanceof Deferred) {
                $deferred->resolve($e);
            }
            return GEARMAN_SUCCESS;
        }, $this));
    }

    public function run()
    {
        if (!empty($this->promises)) {
            all($this->promises)
                ->then(
                    Closure::bind(function () {
                        $this->run();
                    }, $this)
                );
            $this->client->runTasks();
        } else {
            exit('fin.' . PHP_EOL);
        }
    }

    public function addSaga($saga, $data = NULL)
    {
        if (is_callable($saga)) {
            $saga = $saga($data);
        }
        $this->runSaga($saga);
    }

    public function runSaga(Generator $next)
    {
        if ($next->valid()) {
            $items = $next->current();
            $task = array_shift($items);
            $data = array_shift($items);
            $this->stepThrough($task, $data, $next);
        }
    }

    public function stepThrough(string $task, $data, Generator &$current)
    {
        $t = $this->addTask($task, $data);
        $t->then(
            Closure::bind(
                function (GearmanTask $data) use (&$current) {
                    if ($current->valid()) {
                        $current->send($data->data());
                        $this->runSaga($current);
                    }
                    return $data;
                },
                $this
            )
        );
    }

    public function addTask($task, $data) : Promise
    {
        $deferred = new Deferred();
        $id = uniqid('task_');
        $promise = $deferred->promise();
        $this->tasks[$id] = $deferred;
        $this->promises[$id] = $promise;

        $this->client->addTask($task, $data, $task, $id);
        return $promise;
    }

}
