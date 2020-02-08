<?php

namespace ProcessExecutor;

class ExitCodeResult {
    protected $exitCode;

    public function __construct(int $exitCode) {
        $this->exitCode = $exitCode;
    }

    public function getExitCode(): int {
        return $this->exitCode;
    }
}
