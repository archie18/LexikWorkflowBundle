<?php

namespace Lexik\Bundle\WorkflowBundle\Twig\Extension;

use Lexik\Bundle\WorkflowBundle\Entity\ModelState;
use Lexik\Bundle\WorkflowBundle\Handler\ProcessAggregator;
use Lexik\Bundle\WorkflowBundle\Flow\Step;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class WorkflowExtension extends AbstractExtension
{
    /**
     * @var ProcessAggregator
     */
    private $aggregator;

    /**
     * Construct.
     *
     * @param ProcessAggregator $aggregator
     */
    public function __construct(ProcessAggregator $aggregator)
    {
        $this->aggregator = $aggregator;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            'get_step_label'     => new TwigFunction($this, 'getStepLabel'),
            'get_state_messsage' => new TwigFunction($this, 'getStateMessage'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'workflow_extension';
    }

    /**
     * Return the state's step label.
     *
     * @param  ModelState $state
     * @return string
     */
    public function getStepLabel(ModelState $state)
    {
        $step = $this->aggregator
            ->getProcess($state->getProcessName())
            ->getStep($state->getStepName());

        return $step instanceof Step ? $step->getLabel() : '';
    }

    /**
     * Returns the state message.
     *
     * @param  ModelState $state
     * @return string
     */
    public function getStateMessage(ModelState $state)
    {
        $message = '';

        if ($state->getSuccessful()) {
            $data = $state->getData();

            $message = isset($data['success_message']) ? $data['success_message'] : '';
        } else {
            $message = implode("\n", $state->getErrors());
        }

        return $message;
    }
}
