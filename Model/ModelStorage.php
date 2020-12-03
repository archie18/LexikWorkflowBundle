<?php

namespace Lexik\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use MeloLab\Postgrado\UserBundle\Entity\User;
use Lexik\Bundle\WorkflowBundle\Entity\ModelState;
use Lexik\Bundle\WorkflowBundle\Validation\ViolationList;
use Symfony\Component\Security\Core\Security;

class ModelStorage
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $om;

    /**
     * @var Doctrine\ORM\EntityRepository
     */
    protected $repository;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ContainerInterface
     */
    protected $security;

    /**
     * Construct.
     *
     * @param EntityManager $om
     * @param string        $entityClass
     */
    public function __construct(EntityManager $om, $entityClass, $container, $security)
    {
        $this->om = $om;
        $this->repository = $this->om->getRepository($entityClass);
        $this->container = $container;
        $this->security = $security;
    }

    /**
     * Returns the current model state.
     *
     * @param ModelInterface $model
     * @param string         $processName
     * @param string         $stepName
     *
     * @return ModelState
     */
    public function findCurrentModelState(ModelInterface $model, $processName, $stepName = null)
    {
        return $this->repository->findLatestModelState(
            $model->getWorkflowIdentifier(),
            $processName,
            $stepName
        );
    }

    /**
     * Returns all model states from the last stationary state
     *
     * @param  string  $workflowIdentifier
     * @param  int  $prevStateId
     * @param  boolean $successOnly
     * @return array
     */
    public function findAllStatesFromLastStationary($workflowIdentifier, $prevStateId, $successOnly)
    {
        return $this->repository->findAllStatesFromLastStationary(
            $workflowIdentifier,
            $prevStateId,
            $successOnly
        );
    }

    /**
     * Returns the current model state.
     *
     * @param ModelInterface $model
     * @param string         $processName
     * @param string         $stepName
     *
     * @return ModelState
     */
    public function findCurrentModelStateByWorkflowIdentifier($identifier, $processName, $stepName = null)
    {
        return $this->repository->findLatestModelState(
            $identifier,
            $processName,
            $stepName
        );
    }

    /**
     * Returns the current model state.
     *
     * @param ModelInterface $model
     * @param string         $processName
     * @param string         $stepName
     *
     * @return ModelState
     */
    public function findCurrentModelStateByWorkflowIdentifierWithoutProcess($identifier, $stepName = null)
    {
        return $this->repository->findLatestModelStateWithoutProcess(
            $identifier,
            $stepName
        );
    }

    /**
     * Returns all model states.
     *
     * @param ModelInterface $model
     * @param string         $processName
     * @param bool           $successOnly
     *
     * @return mixed
     */
    public function findAllModelStates(ModelInterface $model, $processName, $successOnly = true)
    {
        return $this->repository->findModelStates(
            $model->getWorkflowIdentifier(),
            $processName,
            $successOnly
        );
    }

    /**
     * Create a new invalid model state.
     *
     * @param ModelInterface  $model
     * @param string          $processName
     * @param string          $stepName
     * @param ViolationList   $violationList
     * @param null|ModelState $previous
     *
     * @return ModelState
     */
    public function newModelStateError(ModelInterface $model, $processName, $stepName, ViolationList $violationList, $stationary, $previous = null, $parent = null)
    {
        $modelState = $this->createModelState($model, $processName, $stepName, $previous);
        $modelState->setParent($parent);
        $modelState->setSuccessful(false);
        $modelState->setStationary($stationary);
        $modelState->setErrors($violationList->toArray());
        $modelState->setEntityClass(ClassUtils::getClass($model->getEntity()));
        $modelState->setEntityId($model->getEntity()->getId());
        $modelState->setEntityIteration($model->getEntityIteration());

        $this->om->persist($modelState);
        $this->om->flush($modelState);

        return $modelState;
    }

    /**
     * Delete all model states.
     *
     * @param ModelInterface $model
     * @param string         $processName
     */
    public function deleteAllModelStates(ModelInterface $model, $processName = null)
    {
        return $this->repository->deleteModelStates(
            $model->getWorkflowIdentifier(),
            $processName
        );
    }

    /**
     * Create a new successful model state.
     *
     * @param ModelInterface $model
     * @param string         $processName
     * @param string         $stepName
     * @param ModelState     $previous
     *
     * @return \Lexik\Bundle\WorkflowBundle\Entity\ModelState
     */
    public function newModelStateSuccess(ModelInterface $model, $processName, $stepName, $stationary, $previous = null, $parent = null)
    {
        $modelState = $this->createModelState($model, $processName, $stepName, $previous);
        $modelState->setSuccessful(true);
        $modelState->setParent($parent);
        $modelState->setStationary($stationary);
        $modelState->setEntityClass(ClassUtils::getClass($model->getEntity()));
        $modelState->setEntityId($model->getEntity()->getId());
        $modelState->setEntityIteration($model->getEntityIteration());
        
        if($this->security->getUser() instanceof User){
            $modelState->setUserId($this->security->getUser()->getId());
        }
        else{
            $modelState->setUserId(null);
        }
        
        $this->om->persist($modelState);
        $this->om->flush();

        return $modelState;
    }

    /**
     * Persist workflow object
     *
     * @param mixed $object
     */
    public function persistWorkflowObject($object)
    {
        $this->om->persist($object);
    }

    /**
     * Remove state
     *
     * @param mixed $state
     */
    public function removeState($state)
    {
        $this->om->persist($state);

    }

    /**
     * Persist workflow object*
     */
    public function flushData()
    {
        $this->om->flush();

    }

    /**
     * Return manager
     */
    public function getEntityManager()
    {
        return $this->om;

    }

    /**
     * Normalize by fetching workflow states of each $objects.
     *
     * @param ModelState|array $objects
     * @param array            $processes
     * @param bool             $onlySuccess
     */
    public function setStates($objects, $processes = array(), $onlySuccess = false)
    {
        $this->repository->setStates($objects, $processes, $onlySuccess);
    }

    /**
     * Create a new model state.
     *
     * @param  ModelInterface                                 $model
     * @param  string                                         $processName
     * @param  string                                         $stepName
     * @param  ModelState                                     $previous
     * @return \Lexik\Bundle\WorkflowBundle\Entity\ModelState
     */
    protected function createModelState(ModelInterface $model, $processName, $stepName, $previous = null)
    {
        $modelState = new ModelState();
        $modelState->setWorkflowIdentifier($model->getWorkflowIdentifier());
        $modelState->setProcessName($processName);
        $modelState->setStepName($stepName);
        $modelState->setData($model->getWorkflowData());

        if ($previous instanceof ModelState) {
            $modelState->setPrevious($previous);
        }

        return $modelState;
    }
}
