<?php

namespace App;

use Facades\App\ShellProcessRunner;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

trait InteractsWithSsh
{
    /**
     * Run the given script on a remote server.
     *
     * @return $this
     */
    public function run()
    {
        $this->markAsRunning();

        $this->ensureWorkingDirectoryExists();

        try {
            $this->upload();
        } catch (ProcessTimedOutException $e) {
            return $this->markAsTimedOut();
        }

        return $this->updateForResponse($this->runInline(sprintf(
            'bash %s 2>&1 | tee %s',
            $this->scriptFile(),
            $this->outputFile()
        ), $this->options['timeout'] ?? 60));
    }

    /**
     * Update the model for the given SSH response.
     *
     * @param  object  $response
     * @return $this
     */
    protected function updateForResponse($response)
    {
        return tap($this)->update([
            'status' => $response->timedOut ? 'timeout' : 'finished',
            'exit_code' => $response->exitCode,
            'output' => $response->output,
        ]);
    }

    /**
     * Run the given script in the background on a remote server.
     *
     * @return $this
     */
    public function runInBackground()
    {
        $this->markAsRunning();

        $this->addCallbackToScript();

        $this->ensureWorkingDirectoryExists();

        try {
            $this->upload();
        } catch (ProcessTimedOutException $e) {
            return $this->markAsTimedOut();
        }

        ShellProcessRunner::run($this->toProcess(sprintf(
            '\'nohup bash %s >> %s 2>&1 &\'',
            $this->scriptFile(),
            $this->outputFile()
        ), 10));

        return $this;
    }

    /**
     * Add a callback to the script.
     *
     * @return void
     */
    protected function addCallbackToScript()
    {
        $this->update([
            'script' => view('scripts.tools.callback', [
                'task' => $this,
                'path' => str_replace('.sh', '-script.sh', $this->scriptFile()),
                'token' => str_random(20),
            ])->render(),
        ]);
    }

    /**
     * Create the remote working directory for the task.
     *
     * @return void
     */
    protected function ensureWorkingDirectoryExists()
    {
        $this->runInline('mkdir -p '.$this->path(), 10);
    }

    /**
     * Upload the given script to the server.
     *
     * @return bool
     */
    protected function upload()
    {
        $process = (new Process(SecureShellCommand::forUpload(
            $this->provisionable->ipAddress(),
            $this->provisionable->port(),
            $this->provisionable->ownerKeyPath(),
            $this->user,
            $localScript = $this->writeScript(),
            $this->scriptFile()
        ), base_path()))->setTimeout(15);

        $response = ShellProcessRunner::run($process);

        @unlink($localScript);

        return $response->exitCode === 0;
    }

    /**
     * Write the script to storage in preparation for upload.
     *
     * @return string
     */
    protected function writeScript()
    {
        $hash = md5(str_random(20).$this->script);

        return tap(storage_path('app/scripts').'/'.$hash, function ($path) {
            file_put_contents($path, $this->script);
        });
    }

    /**
     * Download the output of the task from the remote server.
     *
     * @param  string|null  $path
     * @return string
     */
    public function retrieveOutput($path = null)
    {
        return $this->runInline('tail --bytes=2000000 '.($path ?? $this->outputFile()), 10)->output;
    }

    /**
     * Run a given script inline on the remote server.
     *
     * @param  string  $script
     * @param  int  $timeout
     * @return ShellResponse
     */
    protected function runInline($script, $timeout = 60)
    {
        $token = str_random(20);

        return ShellProcessRunner::run($this->toProcess('\'bash -s \' << \''.$token.'\'
'.$script.'
'.$token, $timeout));
    }

    /**
     * Get the remote working directory path for the task.
     *
     * @return string
     */
    protected function path()
    {
        return $this->user === 'root'
                        ? '/root/.cloud'
                        : '/home/cloud/.cloud';
    }

    /**
     * Get the remote path to the script.
     *
     * @return string
     */
    protected function scriptFile()
    {
        return $this->path().'/'.$this->id.'.sh';
    }

    /**
     * Get the remote path to the output.
     *
     * @return string
     */
    protected function outputFile()
    {
        return $this->path().'/'.$this->id.'.out';
    }

    /**
     * Create a Process instance for the given script.
     *
     * @param  string  $script
     * @param  int  $timeout
     * @return Process
     */
    protected function toProcess($script, $timeout)
    {
        return (new Process(
            SecureShellCommand::forScript(
                $this->provisionable->ipAddress(),
                $this->provisionable->port(),
                $this->provisionable->ownerKeyPath(),
                $this->user,
                $script
            )
        ))->setTimeout($timeout);
    }
}
