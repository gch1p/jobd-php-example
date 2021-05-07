<?php

namespace jobs;

class CreateFile extends \Job
{

    public function run()
    {
        $file = $this->input['file'];
        if (!touch($file))
            throw new \Exception("failed to touch file '".$file."'");
    }

}
