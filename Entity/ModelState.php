<?php

namespace Lexik\Bundle\WorkflowBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Used to store a state of a model object.
 *
 */
class ModelState
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $workflowIdentifier;

    /**
     * @var string
     */
    protected $processName;

    /**
     * @var string
     */
    protected $stepName;

    /**
     * @var boolean
     */
    protected $successful;

    /**
     * @var boolean
     */
    protected $stationary;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var array
     */
    protected $errors;

    /**
     * @var ModelState
     */
    protected $previous;
    /**
     * @var ModelState
     */
    protected $parent;

    /**
     * @var ArrayCollection
     */
    protected $next;

    /**
     * @var ArrayCollection
     */
    protected $children;

    //New variables

    /**
     *
     * @var string
     */
    protected $entityClass;

    /**
     *
     * @var int
     */
    protected $entityId;

    /**
     *
     * @var int
     */
    protected $entityIteration;

    /**
     * @var int
     */
    protected $userId;

    /**
     * Construct.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime('now');
        $this->next = new ArrayCollection();
    }

    /**
     * Get Id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get workflowIdentifier
     *
     * @return string
     */
    public function getWorkflowIdentifier()
    {
        return $this->workflowIdentifier;
    }

    /**
     * Set workflowIdentifier
     *
     * @param string $workflowIdentifier
     */
    public function setWorkflowIdentifier($workflowIdentifier)
    {
        $this->workflowIdentifier = $workflowIdentifier;
    }

    /**
     * Get processName
     *
     * @return string
     */
    public function getProcessName()
    {
        return $this->processName;
    }

    /**
     * Set processName
     *
     * @param string $processName
     */
    public function setProcessName($processName)
    {
        $this->processName = $processName;
    }

    /**
     * Get stepName
     *
     * @return string
     */
    public function getStepName()
    {
        return $this->stepName;
    }

    /**
     * Set stepName
     *
     * @param string $stepName
     */
    public function setStepName($stepName)
    {
        $this->stepName = $stepName;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set createdAt
     *
     * @param DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        return json_decode($this->data, true);
    }

    /**
     * Set data
     *
     * @param mixed $data An array or a JSON string
     */
    public function setData($data)
    {
        if (!is_string($data)) {
            $data = json_encode($data);
        }

        $this->data = $data;
    }

    /**
     * Get successful
     *
     * @return boolean
     */
    public function getSuccessful()
    {
        return $this->successful;
    }

    /**
     * Set successful
     *
     * @param boolean
     */
    public function setSuccessful($successful)
    {
        $this->successful = (boolean) $successful;
    }


    /**
     * @return bool
     */
    public function isStationary()
    {
        return $this->stationary;
    }

    /**
     * @param bool $stationary
     */
    public function setStationary($stationary)
    {
        $this->stationary = (boolean) $stationary;
    }


    /**
     * Get errors
     *
     * @return string
     */
    public function getErrors()
    {
        return json_decode($this->errors, true);
    }

    /**
     * Set errors
     *
     * @param string $errors
     */
    public function setErrors($errors)
    {
        if (!is_string($errors)) {
            $errors = json_encode($errors);
        }

        $this->errors = $errors;
    }

    /**
     * Get previous
     *
     * @return \Lexik\Bundle\WorkflowBundle\Entity\ModelState
     */
    public function getPrevious()
    {
        return $this->previous;
    }

    /**
     * Set previous
     *
     * @param ModelState $state
     */
    public function setPrevious($state)
    {
        $this->previous = $state;
    }
    
    /**
     * Get parent
     *
     * @return \Lexik\Bundle\WorkflowBundle\Entity\ModelState
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set parent
     *
     * @param ModelState $state
     */
    public function setParent(ModelState $state = null)
    {
        $this->parent = $state;
    }

    /**
    * Get next
    *
    * @return ArrayCollection
    */
    public function getNext()
    {
        return $this->next;
    }

    /**
     * Add next
     *
     * @param ModelState $state
     */
    public function addNext(ModelState $state)
    {
        $state->setPrevious($this);

        $this->next[] = $state;
    }

    /**
    * Get children
    *
    * @return ArrayCollection
    */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add children
     *
     * @param ModelState $state
     */
    public function addChildren(ModelState $state)
    {
        $state->setPrevious($this);

        $this->children[] = $state;
    }
    
    /**
     * Get entityClass
     *
     * @return string
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * Set entityClass
     *
     * @param string
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }
    
    /**
     * Get entityId
     *
     * @return int
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * Set entityId
     *
     * @param int
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;
    }
    
    /**
     * Get entityIteration
     *
     * @return int
     */
    public function getEntityIteration()
    {
        return $this->entityIteration;
    }

    /**
     * Set entityIteration
     *
     * @param int
     */
    public function setEntityIteration($entityIteration)
    {
        $this->entityIteration = $entityIteration;
    }
    
    /**
     * Get user Id
     * @return int
     */
    function getUserId() {
        return $this->userId;
    }

    /**
     * Set userId
     * @param int $userId
     * @return \Lexik\Bundle\WorkflowBundle\Entity\ModelState
     */
    function setUserId($userId) {
        $this->userId = $userId;
        return $this;
    }


}
