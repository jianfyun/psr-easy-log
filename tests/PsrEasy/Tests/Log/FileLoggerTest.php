<?php
namespace PsrEasy\Tests\Log;

use PsrEasy\Log\FileLogger;

class FileLoggerTest extends \PHPUnit_Framework_TestCase
{
    private $logger = null;

    public function setUp()
    {
        $this->logger = new FileLogger(__DIR__ . '/../../../../logs');
        $this->logger->withFormat("[%s]\t[%s]\t[%s]\t[%s %s]\t[%s]\t[%s]\n")->withBacktraceLimit(1);
    }

    public function testEmergency()
    {
        $message = 'System is unusable';
        $context = ['variable' => 'some variable value'];
        $this->logger->emergency("$message. Var is {variable}", $context);
    }

    public function testAlert()
    {
        $message = 'Action must be taken immediately';
        $context = ['variable' => 'some variable value'];
        $this->logger->alert("$message. Var is {variable}", $context);
    }

    public function testCritical()
    {
        $previous = new \Exception('one exception');
        $message  = 'Critical conditions. Example: Application component unavailable, unexpected exception';
        $context  = [
            'variable'  => 'some variable value',
            'exception' => new \Exception('another exception', 0, $previous),
        ];
        $this->logger->critical("$message. Var is {variable}", $context);
    }

    public function testError()
    {
        $message = 'Runtime errors that do not require immediate action but should be logged and monitored';
        $context = ['variable' => 'some variable value', 'other' => ['a', 'b']];
        $this->logger->error("$message. Var is {variable}", $context);
    }

    public function testWarning()
    {
        $message = 'Exceptional occurrences that are not errors. Example: poor use of API, undesirable things';
        $context = ['variable' => 'some variable value'];
        $this->logger->warning("$message. Var is {variable}", $context);
    }

    public function testNotice()
    {
        $message = 'Normal but significant events';
        $context = ['variable' => 'some variable value'];
        $this->logger->notice("$message. Var is {variable}", $context);
    }

    public function testInfo()
    {
        $message = 'Interesting events. Example: User logs in, SQL logs';
        $context = ['variable' => 'some variable value'];
        $this->logger->info("$message. Var is {variable}", $context);
    }

    public function testDebug()
    {
        $message = 'Detailed debug information';
        $context = ['variable' => 'some variable value'];
        $this->logger->debug("$message. Var is {variable}", $context);
    }

    public function testLog()
    {
        $message = 'Logs with an arbitrary level';
        $context = ['variable' => 'some variable value'];
        $this->logger->log('other', "$message. Var is {variable}", $context);
    }

    public function testWithLevels()
    {
        $message = 'Message that should not be logged';
        $context = ['variable' => 'some variable value'];
        $this->logger->withLevels(['error']);
        $this->logger->debug("$message. Var is {variable}", $context);
    }
}
