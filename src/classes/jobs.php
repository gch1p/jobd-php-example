<?php

class jobs
{

    /**
     * @var jobs_destructor $destructor_instance
     */
    private static $destructor_instance;

    /**
     * @var array<int, array> $new_jobs
     */
    private static $new_jobs = [];

    /**
     * Automatically poke master on exit.
     */
    public static function destruct()
    {
        if (!empty(self::$new_jobs)) {
            $targets = [];
            foreach (self::$new_jobs as $new_job) {
                if ($new_job['status'] === Job::STATUS_WAITING)
                    $targets[$new_job['target']] = true;
            }

            if (!empty($targets)) {
                $targets = array_keys($targets);
                self::poke($targets);
            }
        }
    }

    /**
     * Create job.
     *
     * @param int|string $target
     * @param string $name
     * @param array $data
     * @param string $status
     * @return int|string Job ID
     */
    public static function add($target, string $name, array $data = [], string $status = Job::STATUS_WAITING): int
    {
        if (is_null(self::$destructor_instance))
            self::$destructor_instance = new jobs_destructor();

        if (strpos($name, '\\') !== false) {
            $pos = strrpos($name, '\\');
            $name = substr($name, $pos + 1);
        }

        $db = getMySQL();
        $db->insert(JOBD_TABLE, [
            'target' => $target,
            'name' => $name,
            'time_created' => time(),
            'input' => serialize($data),
            'status' => $status
        ]);
        $id = $db->insertId();

        self::$new_jobs[$id] = [
            'target' => $target,
            'status' => $status
        ];

        return $id;
    }

    /**
     * Create manual job.
     *
     * @param int|string $target
     * @param string $name
     * @param array $data
     * @return int
     */
    public static function manual($target, string $name, array $data = []): int
    {
        return self::add($target, $name, $data, Job::STATUS_MANUAL);
    }

    /**
     * Run jobs with given ids and status=Job::STATUS_MANUAL and wait for results.
     *
     * If only one job was given and it's failed, an Exception will be thrown!
     * If multiple jobs were given and some of them failed, an array of JobResults will be returned.
     *
     * @param int|int[] $job_ids
     * @return array<int, JobResult>|JobResult
     * @throws Exception
     */
    public static function run($job_ids)
    {
        if (!is_array($job_ids))
            $job_ids = [$job_ids];

        $job_ids_orig = $job_ids;
        $job_ids = array_flip($job_ids);

        $jobs = [];

        // look for the given jobs in self::$new_jobs
        foreach (self::$new_jobs as $id => $new_job) {
            if ($new_job['status'] == Job::STATUS_MANUAL && isset($job_ids[$id])) {
                $jobs[] = ['id' => $id, 'target' => $new_job['target']];
                unset($job_ids[$id]);
            }
        }

        // if some (or all) jobs were not found in self::$new_jobs, get them from the database
        if (!empty($job_ids)) {
            $job_ids = array_keys($job_ids);

            $db = getMySQL();
            $q = $db->query("SELECT id, target, status AS target FROM ".JOBD_TABLE." WHERE id IN (".implode(',', $job_ids).")");
            $job_ids = array_flip($job_ids);

            while ($row = $db->fetch($q)) {
                // only manual jobs are allowed
                if ($row['status'] != Job::STATUS_MANUAL)
                    throw new Exception("job id=${row['id']} has status = {$row['status']} != manual");

                $jobs[] = [
                    'id' => (int)$row['id'],
                    'target' => $row['target']
                ];

                unset($job_ids[$row['id']]);
            }

            $q->free();

            // we were given invalid ids, it seems. throw an exception and don't continue
            if (!empty($job_ids))
                throw new Exception("jobs with id ".implode(', ', array_keys($job_ids))." not found");
        }

        // connect to master and send run-manual request
        $client = getJobdMaster();
        $response = $client->runManual($jobs);

        // master request failed
        if (($error = $response->getError()) !== null)
            throw new Exception("jobd returned error: ".$error);

        // at this point, jobd-master request succeeded
        // doesn't mean our jobs were successfully accepted and executed by workers,
        // but at least we have some results

        /**
         * @var array<int, JobResult> $results
         */
        $results = [];
        $data = $response->getData();

        $client->close();

        // collect results, successes and failures
        if (!empty($data['jobs'])) {
            foreach ($data['jobs'] as $job_id => $job_result_raw) {
                $job_result = (new JobResult())->setResult(
                    $job_result_raw['result'],
                    $job_result_raw['code'],
                    $job_result_raw['stdout'],
                    $job_result_raw['stderr'],
                    $job_result_raw['signal']
                );
                $results[$job_id] = $job_result;
            }
        }
        if (!empty($data['errors'])) {
            foreach ($data['errors'] as $job_id => $job_result_raw) {
                $job_result = (new JobResult())->setError($job_result_raw);
                $results[$job_id] = $job_result;
            }
        }

        // remove jobs from self::$new_jobs
        foreach ($job_ids_orig as $id) {
            if (isset(self::$new_jobs[$id]))
                unset(self::$new_jobs[$id]);
        }

        // if the $job_ids arguments wasn't an array, return the JobResult instance
        if (count($job_ids_orig) === 1 && count($results) === 1) {
            $result = reset($results);
            if ($result->isFailed())
                throw new Exception($result->getError());
            return $result;
        }

        // otherwise, return array of JobResult instances
        return $results;
    }

    /**
     * @param string|string[] $targets
     */
    public static function poke($targets)
    {

        $client = getJobdMaster();

        if (!is_array($targets))
            $targets = [$targets];

        $client->poke($targets);
        $targets = array_flip(array_unique($targets));

        // remove poked targets from self::$new_jobs to avoid meaninglessly duplicating this poke from the destructor
        if (!empty(self::$new_jobs)) {
            foreach (self::$new_jobs as $new_job_id => $new_job) {
                if ($new_job['status'] == Job::STATUS_WAITING && isset($targets[$new_job['target']]))
                    unset(self::$new_jobs[$new_job_id]);
            }
        }

        $client->close();
        return true;
    }

    /**
     * @param int $id
     * @return array
     */
    public static function get(int $id)
    {
        $db = getMySQL();
        $q = $db->query("SELECT * FROM ".JOBD_TABLE." WHERE id=?", $id);
        return $db->fetch($q);
    }

    /**
     * Delete old succeeded jobs.
     */
    public static function cleanup()
    {
        $db = getMySQL();
        $db->query("DELETE FROM ".JOBD_TABLE." WHERE status='done' AND result='ok' AND time_finished < ?",
            time() - 86400);
    }

}


class job_target
{

    const any = "any";

    public static function high(int $server): string
    {
        return "$server/high";
    }

    public static function low(int $server): string
    {
        return "$server/low";
    }

}


class jobs_destructor
{

    public function __destruct()
    {
        jobs::destruct();
    }

}
