<?php

namespace App\Scripts;

class GetAptLockStatus extends Script
{
    /**
     * The displayable name of the script.
     *
     * @var string
     */
    public $name = 'Echoing Apt Lock Status';

    /**
     * Get the contents of the script.
     *
     * @return string
     */
    public function script()
    {
        // https://askubuntu.com/questions/15433/unable-to-lock-the-administration-directory-var-lib-dpkg-is-another-process
        return 'lsof | grep /var/lib/dpkg/lock && ps -e | grep -e apt -e adept | grep -v grep';
    }

    /**
     * Get the timeout for the script.
     *
     * @return int|null
     */
    public function timeout()
    {
        return 15;
    }
}
