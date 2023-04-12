<?php

require __DIR__.'/init.php';

if ($argc < 2) {
    echo <<<EOF
Usage: {$argv[0]} COMMAND

Commands:
    test
    hello
    createfile
    run_lrt
    kill_lrt ID

EOF;
    exit;
}

array_shift($argv);
$cmd = array_shift($argv);

$func = "cmd_{$cmd}";
if (!function_exists($func)) {
    echo red("command '".$cmd."' is not implemented")."\n";
    exit(1);
}

call_user_func($func, $argv);


/** Commands */

function cmd_test() {
    // MySQL
    try {
        $db = getMySQL();
        $jobs_count = $db->result($db->query("SELECT COUNT(*) FROM ".JOBD_TABLE));
    } catch (Exception $e) {
        echo red("MySQL connection failed")."\n";
        exit(1);
    }
    echo green("MySQL OK")."\n";

    // jobd
    try {
        $jobd = getJobdMaster();
        $status = $jobd->status(true);
        $workers_count = count($status->getData()['workers']);
        if ($workers_count == 2) {
            echo green("jobd-master and jobd OK");
        } else {
            $message = "jobd-master OK, but ";
            $message .= $workers_count == 1 ? "only 1 worker is connected" : "no workers are connected";
            echo yellow($message);
        }
        echo "\n";
    } catch (Exception $e) {
        echo red("jobd-master connection failed: ".$e->getMessage())."\n";
        exit(1);
    }
}

function cmd_hello() {
    $myname = input('Enter your name: ');
    try {
        $job_ids = [];
        $job_server_map = [];

        for ($server = 1; $server <= 2; $server++) {
            $id = jobs::manual(job_target::high($server), jobs\Hello::class, ['name' => $myname]);
            $job_server_map[$id] = $server;
            $job_ids[] = $id;
        }

        $results = jobs::run($job_ids);
        foreach ($results as $job_id => $job_result) {
            $server = $job_server_map[$job_id];
            echo "> server {$server}:\n";
            if ($job_result->isFailed()) {
                echo red("failed")."\n";
            } else {
                echo green($job_result->getStdoutAsJSON()['response'])."\n";
            }
            echo "\n";
        }

    } catch (Exception $e) {
        echo red("error: ".$e->getMessage())."\n";
        exit(1);
    }
}

function cmd_createfile() {
    $file = input('Enter file name: ');
    jobs::add(job_target::any, jobs\CreateFile::class, ['file' => $file]);
}

function cmd_run_lrt() {
    $ltr_id = jobs::add(job_target::low(1), jobs\LongRunningTask::class);
    echo "id: $ltr_id\n";
}

function cmd_kill_lrt($argv) {
    $id = $argv[0];
    $result = jobs::sendSignal($id, 15, job_target::low(1));
    var_dump($result);
}