<?php

namespace Lexik\Bundle\WorkflowBundle\Flow;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Process class.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class Process extends Node
{
    /**
     * @var ArrayCollection
     */
    protected $steps;

    /**
     * @var string
     */
    protected $startStep;

    /**
     * @var array
     */
    protected $endSteps;

    /**
     * @var string
     */
    protected $parent;

    /**
     * Construct.
     *
     * @param string $name
     * @param array  $steps
     * @param string $startStep
     * @param array  $endSteps
     * @param string $parent
     */
    public function __construct($name, array $steps, $startStep, array $endSteps, $parent = null)
    {
        parent::__construct($name);

        $this->steps     = new ArrayCollection($steps);
        $this->startStep = $startStep;
        $this->endSteps  = $endSteps;
        $this->parent = $parent;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     * Get process steps.
     *
     * @return ArrayCollection
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * Returns a step by its name.
     *
     * @param string $name
     *
     * @return Lexik\Bundle\WorkflowBundle\Flow\Step
     */
    public function getStep($name)
    {
        return $this->steps->get($name);
    }

    /**
     * Returns the first step.
     *
     * @return Lexik\Bundle\WorkflowBundle\Flow\Step
     */
    public function getStartStep()
    {
        return $this->startStep;
    }

    /**
     * Returns an array of step name.
     *
     * @return array
     */
    public function getEndSteps()
    {
        return $this->endSteps;
    }

    /**
     * Returns the PARENT PROCESS.
     *
     * @return string
     */
    public function getParent()
    {
        return $this->parent;
    }
}
