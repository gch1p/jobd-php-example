<?php

class JobResult {

    /**
     * @var string $result
     */
    protected $result;

    /**
     * @var int $returnCode
     */
    protected $returnCode;

    /**
     * @var string|null $signal
     */
    protected $signal;

    /**
     * @var string $stdout
     */
    protected $stdout;

    /**
     * @var string $stderr
     */
    protected $stderr;

    /**
     * @param string $result
     * @param int $return_code
     * @param string $stdout
     * @param string $stderr
     * @param null $signal
     * @return $this
     */
    public function setResult(string $result,
                              int $return_code,
                              string $stdout,
                              string $stderr,
                              $signal = null): JobResult
    {
        $this->result = $result;
        $this->returnCode = $return_code;
        $this->stdout = $stdout;
        $this->stderr = $stderr;
        $this->signal = $signal;

        return $this;
    }

    /**
     * @param string $error
     * @return $this
     */
    public function setError(string $error): JobResult
    {
        $this->result = Job::RESULT_FAIL;
        $this->stderr = $error;

        return $this;
    }

    /**
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->result == Job::RESULT_FAIL;
    }

    /**
     * @return string
     */
    public function getStdout(): string
    {
        return $this->stdout;
    }

    /**
     * @return mixed|null
     */
    public function getStdoutAsJSON() {
        $json = jsonDecode($this->stdout);
        return $json ? $json : null;
    }

    /**
     * @return string
     */
    public function getError(): string {
        return $this->stderr ?? '';
    }

}