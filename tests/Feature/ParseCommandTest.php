<?php

namespace Tests\Feature;

use Tests\TestCase;

class ParseCommandTest extends TestCase
{
    /**
     * @return void
     */
    public function testParseCommandParsesTheCodeWithoutErrors()
    {
        $this->artisan('parse', ['file' => 'hello-world.php'])
            ->expectsOutput('No errors found!')
            ->assertExitCode(0);
    }

    /**
     * @return void
     */
    public function testParseCommandReturnsSyntaxError()
    {
        $this->artisan('parse', ['file' => 'hello-world-with-syntax-error.php'])
            ->expectsOutput("Syntax error, unexpected EOF, expecting ';' on line 4")
            ->assertExitCode(0);
    }

    /**
     * @return void
     */
    public function testParseCommandReturnsUndefinedVariableError()
    {
        $this->artisan('parse', ['file' => 'sum-with-undefined-variable.php'])
            ->expectsOutput('Undefined variable: $b')
            ->expectsOutput('Undefined variable: $c')
            ->assertExitCode(0);
    }
}
