<?php

require_once __DIR__.'/init.php';

set_time_limit(0);
$job = null;

register_shutdown_function(function() {
    global $job;
    if ($job instanceof \jobd\exceptions\JobInterruptedException)
        exit($job->getCode());
    if ($job !== true)
        exit(1);
});

$job_id = $argv[1] ?? null;

$job_raw = jobs::get($job_id);
if (!$job_raw)
    throw new InvalidArgumentException("job $job_id not found");

$class_name = "jobs\\{$job_raw['name']}";
$job = new $class_name($job_raw);
if ($job->status != Job::STATUS_RUNNING)
    throw new RuntimeException("job status is {$job->status}");

try {
    if ($job->run() !== false)
        $job = true;
} catch (\jobd\exceptions\JobInterruptedException $e) {
    fprintf(STDERR, $e->getMessage()."\n");
    $job = $e;
} catch (Exception $e) {
    fprintf(STDERR, $e.'');
    exit(1);
}