<?php

namespace jobs;

use jobd\exceptions\JobInterruptedException;

class LongRunningTask extends \Job
{

    public function run()
    {
        set_time_limit(0);
        sleep(120);
        echo 'ok';
    }

    public function signalHandler(int $signal)
    {
        if ($signal == 15) {
            throw new JobInterruptedException(0, 'i\'m exiting gracefully');
        }
    }

}
