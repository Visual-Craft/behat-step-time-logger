<?php

namespace Bex\Behat\StepTimeLoggerExtension\Listener;

use Behat\Behat\Definition\DefinitionFinder;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeStepTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Testwork\EventDispatcher\Event\SuiteTested;
use Bex\Behat\StepTimeLoggerExtension\ServiceContainer\Config;
use Bex\Behat\StepTimeLoggerExtension\Service\StepTimeLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class StepTimeLoggerListener implements EventSubscriberInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var StepTimeLogger
     */
    private $stepTimeLogger;

    /**
     * @var DefinitionFinder
     */
    private $definitionFinder;

    /**
     * @param Config $config
     * @param StepTimeLogger $stepTimeLogger
     * @param DefinitionFinder $definitionFinder
     */
    public function __construct(Config $config, StepTimeLogger $stepTimeLogger, DefinitionFinder $definitionFinder)
    {
        $this->config = $config;
        $this->stepTimeLogger = $stepTimeLogger;
        $this->definitionFinder = $definitionFinder;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            StepTested::BEFORE => 'stepStarted',
            StepTested::AFTER => 'stepFinished',
            SuiteTested::AFTER => 'suiteFinished'
        ];
    }

    /**
     * @param BeforeStepTested $event
     */
    public function stepStarted(BeforeStepTested $event)
    {
        if ($this->config->isEnabled()) {
            $this->stepTimeLogger->logStepStarted($this->getKey($event));
        }
    }

    /**
     * @param AfterStepTested $event
     */
    public function stepFinished(AfterStepTested $event)
    {
        if ($this->config->isEnabled()) {
            $this->stepTimeLogger->logStepFinished($this->getKey($event));
        }
    }

    /**
     * @return void
     */
    public function suiteFinished()
    {
        if ($this->config->isEnabled()) {
            foreach ($this->config->getOutputPrinters() as $printer) {
                $printer->printLogs($this->stepTimeLogger->executionInformationGenerator());
            }

            $this->stepTimeLogger->clearLogs();
        }
    }

    /**
     * @param StepTested $event
     * @return string
     */
    private function getKey(StepTested $event)
    {
        $definition = $this->definitionFinder->findDefinition(
            $event->getEnvironment(),
            $event->getFeature(),
            $event->getStep()
        );

        return $definition->getMatchedDefinition()->getPattern();
    }
}
