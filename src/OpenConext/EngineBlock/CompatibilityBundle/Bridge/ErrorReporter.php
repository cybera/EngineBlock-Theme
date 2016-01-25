<?php

namespace OpenConext\EngineBlock\CompatibilityBundle\Bridge;

use EngineBlock_ApplicationSingleton;
use EngineBlock_Exception;
use Exception;
use Psr\Log\LoggerInterface;

class ErrorReporter
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var EngineBlock_ApplicationSingleton
     */
    private $engineBlockApplicationSingleton;

    public function __construct(
        EngineBlock_ApplicationSingleton $engineBlockApplicationSingleton,
        LoggerInterface $logger
    ) {
        $this->engineBlockApplicationSingleton = $engineBlockApplicationSingleton;
        $this->logger = $logger;
    }

    /**
     * @param Exception $exception
     * @param string    $messageSuffix
     *
     * @SuppressWarnings(PHPMD.Superglobals) This is required to mimic the existing functionality
     */
    public function reportError(Exception $exception, $messageSuffix)
    {
        $logContext = array('exception' => $exception);

        if ($exception instanceof EngineBlock_Exception) {
            $severity = $exception->getSeverity();
        } else {
            $severity = EngineBlock_Exception::CODE_ERROR;
        }

        // unwrap the exception stack
        $prevException = $exception;
        while ($prevException = $prevException->getPrevious()) {
            if (!isset($logContext['previous_exceptions'])) {
                $logContext['previous_exceptions'] = array();
            }

            $logContext['previous_exceptions'][] = (string)$prevException;
        }

        // message building
        $message = $exception->getMessage();
        if (empty($message)) {
            $message = 'Exception without message "' . get_class($exception) . '"';
        }

        if ($messageSuffix) {
            $message .= ' | ' . $messageSuffix;
        }

        $this->logger->log($severity, $message, $logContext);

        // Store some valuable debug info in session so it can be displayed on feedback pages
        $feedback = $_SESSION['feedbackInfo'];
        if (empty($feedback)) {
            $feedback = array();
        }

        if ($exception instanceof \EngineBlock_Corto_Exception_ReceivedErrorStatusCode) {
            $feedback = array_merge($feedback, $exception->getFeedbackInfo());
        }

        $_SESSION['feedbackInfo'] = array_merge(
            $feedback,
            $this->engineBlockApplicationSingleton->collectFeedbackInfo()
        );

        // flush all messages in queue, something went wrong!
        $this->engineBlockApplicationSingleton->flushLog('An error was caught');
    }
}
