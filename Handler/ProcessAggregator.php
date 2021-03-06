<?php

namespace Lexik\Bundle\WorkflowBundle\Handler;

use Lexik\Bundle\WorkflowBundle\Exception\WorkflowException;

/**
 * Aggregate all processes.
 *
 * @author Cédric Girard <c.girard@lexik.fr>
 */
class ProcessAggregator
{
    /**
     * @var array
     */
    private $processes;

    /**
     * Construct.
     *
     * @param array $processes
     */
    public function __construct(array $processes)
    {
        $this->processes = $processes;
    }

    /**
     * Returns a process by its name.
     *
     * @param  string                                   $name
     * @return Lexik\Bundle\WorkflowBundle\Flow\Process
     *
     * @throws WorkflowException
     */
    public function getProcess($name)
    {
        if (!isset($this->processes[$name])) {
            throw new WorkflowException(sprintf('Unknown process "%s".', $name));
        }

        return $this->processes[$name];
    }
}
