<?php

namespace jobs;

class Hello extends \Job
{

    public function run()
    {
        $greetings = "Hello, ".($this->input['name'] ?? 'noname').".\n";
        $greetings .= "I'm writing you from ".__METHOD__.", my PID is ".getmypid()." and I'm executing job #".$this->id.".";
        echo jsonEncode(['response' => $greetings]);
    }

}
