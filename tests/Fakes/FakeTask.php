<?php

namespace Tests\Fakes;

use App\Task;

class FakeTask extends Task
{
    public $ranInBackground = false;

    /**
     * Run the given script in the background on a remote server.
     *
     * @return $this
     */
    public function runInBackground()
    {
        $this->ranInBackground = true;

        return $this;
    }
}
