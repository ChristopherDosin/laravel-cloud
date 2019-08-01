<?php

namespace App;

class SecureShellCommand
{
    /**
     * Build an SSH command for the given script.
     *
     * @param  string  $ipAddress
     * @param  string  $keyPath
     * @param  int  $port
     * @param  string  $user
     * @param  string  $script
     * @return string
     */
    public static function forScript($ipAddress, $port, $keyPath, $user, $script)
    {
        $token = str_random(40);

        return implode(' ', [
            'ssh -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no',
            '-i '.$keyPath,
            '-p '.$port,
            $user.'@'.$ipAddress,
            $script,
        ]);
    }

    /**
     * Build an SSH command for a file upload.
     *
     * @param  string  $ipAddress
     * @param  string  $keyPath
     * @param  int  $port
     * @param  string  $user
     * @param  string  $from
     * @param  string  $to
     * @return string
     */
    public static function forUpload($ipAddress, $port, $keyPath, $user, $from, $to)
    {
        return sprintf(
            'scp -i %s -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -o PasswordAuthentication=no -P %s %s %s:%s',
            $keyPath, $port, $from, $user.'@'.$ipAddress, $to
        );
    }
}
