<?php

function expect($actual): Expectation {
    return new Expectation($actual);
}

class Expectation {
    private $actual;

    public function __construct($actual) {
        $this->actual = $actual;
    }

    public function toBe($expected): void {
        if ($this->actual !== $expected) {
            throw new AssertionError(
                'Expected ' . var_export($expected, true) . ', got ' . var_export($this->actual, true)
            );
        }
    }

    public function toNotBe($expected): void {
        if ($this->actual === $expected) {
            throw new AssertionError(
                'Expected not ' . var_export($expected, true) . ', but got it'
            );
        }
    }

    public function toBeGreaterThan($expected): void {
        if ($this->actual <= $expected) {
            throw new AssertionError(
                var_export($this->actual, true) . ' is not greater than ' . var_export($expected, true)
            );
        }
    }

    public function toBeLessThan($expected): void {
        if ($this->actual >= $expected) {
            throw new AssertionError(
                var_export($this->actual, true) . ' is not less than ' . var_export($expected, true)
            );
        }
    }

    public function toBeNull(): void {
        if ($this->actual !== null) {
            throw new AssertionError('Expected null, got ' . var_export($this->actual, true));
        }
    }

    public function toBeTrue(): void {
        if ($this->actual !== true) {
            throw new AssertionError('Expected true, got ' . var_export($this->actual, true));
        }
    }

    public function toBeFalse(): void {
        if ($this->actual !== false) {
            throw new AssertionError('Expected false, got ' . var_export($this->actual, true));
        }
    }
}
