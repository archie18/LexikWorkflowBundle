<?php

namespace Lexik\Bundle\WorkflowBundle\Event;

use Lexik\Bundle\WorkflowBundle\Handler\ProcessHandler;
use Symfony\Contracts\EventDispatcher\Event;

use Lexik\Bundle\WorkflowBundle\Entity\ModelState;
use Lexik\Bundle\WorkflowBundle\Model\ModelInterface;
use Lexik\Bundle\WorkflowBundle\Flow\Step;

/**
 * Step event.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class StepEvent extends Event
{
    /**
     * @var Step
     */
    private $step;

    /**
     * @var ModelInterface
     */
    private $model;

    /**
     * @var ModelState
     */
    private $modelState;

    /**
     * @var ProcessHandler
     */
    private $pHandler;

    /**
     * Construct.
     *
     * @param Step           $step
     * @param ModelInterface $model
     * @param ModelState     $modelState
     */
    public function __construct(Step $step, ModelInterface $model, ModelState $modelState, ProcessHandler $handler)
    {
        $this->step = $step;
        $this->model = $model;
        $this->modelState = $modelState;
        $this->pHandler = $handler;
    }

    /**
     * Returns the process handler
     * @return ProcessHandler $handler
     */
    public function getProcessHandler(){
        return $this->pHandler;
    }

    /**
     * Returns the reached step.
     *
     * @return Step
     */
    public function getStep()
    {
        return $this->step;
    }

    /**
     * Returns the model.
     *
     * @return ModelInterface
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Returs the last model state.
     *
     * @return ModelState
     */
    public function getModelState()
    {
        return $this->modelState;
    }
}
