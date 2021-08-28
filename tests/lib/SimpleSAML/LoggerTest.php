<?php

declare(strict_types=1);

namespace SimpleSAML\Test;

use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use SimpleSAML\Configuration;
use SimpleSAML\Logger;
use SimpleSAML\Test\Utils\ArrayLogger;

class LoggerTest extends TestCase
{
    /**
     * @var Logger\LoggingHandlerInterface|null
     */
    protected $originalLogger;

    /**
     * @var \SimpleSAML\Logger
     */
    protected $logger;


    /**
     * @param string $handler
     */
    protected function setLoggingHandler(string $handler): void
    {
        $this->originalLogger = $this->logger::getLoggingHandler();
        $config = [
            'logging.handler' => $handler,
            'logging.level' => LogLevel::DEBUG
        ];

        // testing statics is slightly painful
        Configuration::loadFromArray($config, '[ARRAY]', 'simplesaml');
        $this->logger::setLoggingHandler(null);
    }


    /**
     */
    protected function setUp(): void
    {
        $this->logger = new Logger();
    }


    /**
     */
    protected function tearDown(): void
    {
        if (isset($this->originalLogger)) {
            // reset the logger and Configuration
            Configuration::clearInternalState();
            $this->logger::clearCapturedLog();
            $this->logger::setLogLevel(LogLevel::INFO);
            $this->logger::setLoggingHandler($this->originalLogger);
        }
    }


    /**
     */
    public function testCreateLoggingHandlerHonorsCustomHandler(): void
    {
        $this->setLoggingHandler(ArrayLogger::class);

        $this->logger->critical('array logger');

        $logger = $this->logger::getLoggingHandler();

        self::assertInstanceOf(ArrayLogger::class, $logger);
    }


    /**
     */
    public function testCaptureLog(): void
    {
        $this->setLoggingHandler(ArrayLogger::class);

        $payload = "catch this error";
        $this->logger::setCaptureLog();
        $this->logger->critical($payload);

        // turn logging off
        $this->logger::setCaptureLog(false);
        $this->logger->critical("do not catch this");

        $log = $this->logger::getCapturedLog();
        self::assertCount(1, $log);
        self::assertMatchesRegularExpression("/^[0-9]{2}:[0-9]{2}:[0-9]{2}.[0-9]{3}Z\ {$payload}$/", $log[0]);
    }


    /**
     */
    public function testExceptionThrownOnInvalidLoggingHandler(): void
    {
        $this->setLoggingHandler('nohandler');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "Invalid value for the 'logging.handler' configuration option. Unknown handler 'nohandler'."
        );

        $this->logger->critical('should throw exception');
    }


    /**
     * @return array
     */
    public function provideLogLevels(): array
    {
        return [
           ['emergency', LogLevel::EMERGENCY],
           ['alert', LogLevel::ALERT],
           ['critical', LogLevel::CRITICAL],
           ['error', LogLevel::ERROR],
           ['warning', LogLevel::WARNING],
           ['notice', LogLevel::NOTICE],
           ['info', LogLevel::INFO],
           ['debug', LogLevel::DEBUG],
        ];
    }


    /**
     * @param string $method
     * @param string $level
     * @dataProvider provideLogLevels
     */
    public function testLevelMethods(string $method, string $level): void
    {
        $this->setLoggingHandler(ArrayLogger::class);

        $this->logger->{$method}($payload = "test {$method}");

        $logger = $this->logger::getLoggingHandler();
        self::assertMatchesRegularExpression("/\[CL[0-9a-f]{8}\]\ {$payload}$/", $logger->logs[$level][0]);
    }
}
