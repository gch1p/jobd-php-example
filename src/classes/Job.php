<?php

abstract class Job extends model {

    // ENUM status
    const STATUS_WAITING = 'waiting';
    const STATUS_MANUAL = 'manual';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_IGNORED = 'ignored';
    const STATUS_RUNNING = 'running';
    const STATUS_DONE = 'done';

    // ENUM result
    const RESULT_OK = 'ok';
    const RESULT_FAIL = 'fail';

    const DB_TABLE = 'jobs';

    protected static $Fields = [
        'id' => model::INTEGER,
        'target' => model::STRING,
        'name' => model::STRING,
        'time_created' => model::INTEGER,
        'time_started' => model::INTEGER,
        'time_finished' => model::INTEGER,
        'status' => model::STRING, // ENUM
        'result' => model::STRING, // ENUM
        'return_code' => model::INTEGER,
        'sig' => model::STRING,
        'stdout' => model::STRING,
        'stderr' => model::STRING,
        'input' => model::SERIALIZED,
    ];

    public $id;
    public $target;
    public $name;
    public $timeCreated;
    public $timeStarted;
    public $timeFinished;
    public $status;
    public $result;
    public $returnCode;
    public $sig;
    public $stdout;
    public $stderr;
    public $input;

    abstract public function run();

    public function __construct(array $raw) {
        parent::__construct($raw);

        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, [$this, 'signalHandler']);
        pcntl_signal(SIGINT, [$this, 'signalHandler']);
    }

    protected function signalHandler(int $signal) {}
}