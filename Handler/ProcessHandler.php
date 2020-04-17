<?php

namespace Lexik\Bundle\WorkflowBundle\Handler;

use Lexik\Bundle\WorkflowBundle\Entity\ModelState;
use Lexik\Bundle\WorkflowBundle\Event\StepEvent;
use Lexik\Bundle\WorkflowBundle\Event\ValidateStepEvent;
use Lexik\Bundle\WorkflowBundle\Exception\AccessDeniedException;
use Lexik\Bundle\WorkflowBundle\Exception\WorkflowException;
use Lexik\Bundle\WorkflowBundle\Flow\Process;
use Lexik\Bundle\WorkflowBundle\Flow\Step;
use Lexik\Bundle\WorkflowBundle\Handler\ProcessHandlerInterface;
use Lexik\Bundle\WorkflowBundle\Model\ModelInterface;
use Lexik\Bundle\WorkflowBundle\Model\ModelStorage;
use Lexik\Bundle\WorkflowBundle\Validation\Violation;
use Lexik\Bundle\WorkflowBundle\Validation\ViolationList;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * Contains all logic to handle a process and its steps.
 */
class ProcessHandler implements ProcessHandlerInterface
{
    /**
     * @var Process
     */
    protected $process;

    /**
     * @var ModelStorage
     */
    protected $storage;

    /**
     * @var SecurityContextInterface
     */
    protected $security;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * Construct.
     *
     * @param Process                  $process
     * @param ModelStorage             $storage
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(Process $process, ModelStorage $storage, EventDispatcherInterface $dispatcher)
    {
        $this->process = $process;
        $this->storage = $storage;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Set security context.
     *
     * @param SecurityContextInterface $security
     */
    public function setSecurityContext(SecurityContextInterface $security)
    {
        $this->security = $security;
    }

    /**
     * {@inheritdoc}
     */
    public function start(ModelInterface $model)
    {
        $modelState = $this->storage->findCurrentModelState($model, $this->process->getName());

        if ($modelState instanceof ModelState) {
            throw new WorkflowException(sprintf('The given model has already started the "%s" process.', $this->process->getName()));
        }

        $parentStep = $this->process->getParent();
        if($parentStep){
            $parentStepName = explode(".",$parentStep);
            $parentModelState = $this->storage->findCurrentModelStateByWorkflowIdentifier($model->getWorkflowIdentifier(),$parentStepName[0],$parentStepName[1]);
            $pid = $parentModelState;
        }else{
            $pid = null;
        }

        $step = $this->getProcessStep($this->process->getStartStep());

        return $this->reachStep($model, $step,null,false,$pid);
    }

    /**
     * {@inheritdoc}
     */
    public function canStartWorkflow(ModelInterface $model)
    {
        $modelState = $this->storage->findCurrentModelState($model, $this->process->getName());

        if ($modelState instanceof ModelState) {
            return false;
        }

        return true;

    }


    /**
     * {@inheritdoc}
     */
    public function reachNextState(ModelInterface $model, $stateName, $silentError = true)
    {
        $currentModelState = $this->storage->findCurrentModelState($model, $this->process->getName());

        if ( ! ($currentModelState instanceof ModelState) ) {
            throw new WorkflowException(sprintf('The given model has not started the "%s" process.', $this->process->getName()));
        }

        $currentStep = $this->getProcessStep($currentModelState->getStepName());

        if ( !$currentStep->hasNextState($stateName) ) {
            throw new WorkflowException(sprintf('The step "%s" does not contain any next state named "%s".', $currentStep->getName(), $stateName));
        }

        $state = $currentStep->getNextState($stateName);
        $step = $state->getTarget($model);

        // pre validations
        $event = new ValidateStepEvent($step, $model, new ViolationList());
        $eventName = sprintf('%s.%s.%s.pre_validation', $this->process->getName(), $currentStep->getName(), $stateName);
//        var_dump(get_class($event));
        $this->dispatcher->dispatch($eventName, $event);

        $modelState = null;

        if (count($event->getViolationList()) > 0) {
            if($silentError == true){
                $modelState = $this->createModelState($model, $this->process->getName(), $step->getName(), $currentModelState);
                $modelState->setSuccessful(false);
                $modelState->setErrors($event->getViolationList()->toArray());
            }
            else{
                $modelState = $this->storage->newModelStateError($model, $this->process->getName(), $step->getName(), $event->getViolationList(), $step->isStationary(), $currentModelState);
            }

            $eventName = sprintf('%s.%s.%s.pre_validation_fail', $this->process->getName(), $currentStep->getName(), $stateName);
            $this->dispatcher->dispatch($eventName, new StepEvent($step, $model, $modelState));
        } else {
            $modelState = $this->reachStep($model, $step, $currentModelState);
        }

        return $modelState;
    }

    /**
     * {@inheritdoc}
     */
    public function canReachState(ModelInterface $model, $stateName)
    {
        $currentModelState = $this->storage->findCurrentModelState($model, $this->process->getName());

        if ( ! ($currentModelState instanceof ModelState) ) {
//            var_dump('The given model has not started the '.$this->process->getName().' process.');
//            throw new WorkflowException(sprintf('The given model has not started the "%s" process.', $this->process->getName()));
            return false;
        }

        $currentStep = $this->getProcessStep($currentModelState->getStepName());
//        var_dump($currentStep);
        if ( !$currentStep->hasNextState($stateName) ) {

//            var_dump($currentModelState);die();
//            var_dump('Does not have a next state called ' . $stateName);die();
//            throw new WorkflowException(sprintf('The step "%s" does not contain any next state named "%s".', $currentStep->getName(), $stateName));
            return false;
        }

        $state = $currentStep->getNextState($stateName);
        $step = $state->getTarget($model);

        // pre validations
        $event = new ValidateStepEvent($step, $model, new ViolationList());
        $eventName = sprintf('%s.%s.%s.pre_validation', $this->process->getName(), $currentStep->getName(), $stateName);
        $this->dispatcher->dispatch($eventName, $event);

        $modelState = null;

//        var_dump('Violation List greater than 0');die();
        return count($event->getViolationList()) == 0;

    }

    /**
     * Reach the given step.
     *
     * @param  ModelInterface $model
     * @param  Step           $step
     * @param  ModelState     $currentModelState
     * @return ModelState
     */
    protected function reachStep(ModelInterface $model, Step $step, ModelState $currentModelState = null, $silentError = false, $parentId = null)
    {
        try {
            $this->checkCredentials($model, $step);
        } catch (AccessDeniedException $e) {
            $violations = new ViolationList();
            $violations->add(new Violation($e->getMessage()));

            if($silentError){
                $modelState = $this->createModelState($model, $this->process->getName(), $step->getName(), $currentModelState);
                $modelState->setSuccessful(false);
                $modelState->setErrors($violations);
            }
            else{
                $modelState = $this->storage->newModelStateError($model, $this->process->getName(), $step->getName(), $violations, $step->isStationary(), $currentModelState, $parentId);
            }

            $eventName = sprintf('%s.%s.bad_credentials', $this->process->getName(), $step->getName());
            $this->dispatcher->dispatch($eventName, new StepEvent($step, $model, $modelState));

            if ($step->getOnInvalid()) {
                $step = $this->getProcessStep($step->getOnInvalid());
                $modelState = $this->reachStep($model, $step);
            }

            return $modelState;
        }

        $event = new ValidateStepEvent($step, $model, new ViolationList());
        $eventName = sprintf('%s.%s.validate', $this->process->getName(), $step->getName());
        $this->dispatcher->dispatch($eventName, $event);

        if (0 === count($event->getViolationList())) {
            $modelState = $this->storage->newModelStateSuccess($model, $this->process->getName(), $step->getName(), $step->isStationary(), $currentModelState, $parentId);

            // update model status
            $this->updateModelStatus($step, $model);
//            if ($step->hasModelStatus()) {
//                var_dump($step->getModelStatus());
//                list($method, $constant) = $step->getModelStatus();
//                $model->$method(constant($constant));
//            }

            $eventName = sprintf('%s.%s.reached', $this->process->getName(), $step->getName());
            $this->dispatcher->dispatch($eventName, new StepEvent($step, $model, $modelState));
        } else {
            //Deactivate error writing
            if($silentError == true){
                $modelState = $this->createModelState($model, $this->process->getName(), $step->getName(), $currentModelState);
                $modelState->setSuccessful(false);
                $modelState->setErrors($event->getViolationList()->toArray());
            }
            else{
                $modelState = $this->storage->newModelStateError($model, $this->process->getName(), $step->getName(), $event->getViolationList(), $step->isStationary(), $currentModelState, $parentId);
            }

            $eventName = sprintf('%s.%s.validation_fail', $this->process->getName(), $step->getName());
            $this->dispatcher->dispatch($eventName, new StepEvent($step, $model, $modelState));

            if ($step->getOnInvalid()) {
                $step = $this->getProcessStep($step->getOnInvalid());
                $modelState = $this->reachStep($model, $step);
            }
        }

        return $modelState;
    }

    private function updateModelStatus($step,$model){

        // update model status
        if ($step->hasModelStatus()) {

            list( $method, $value ) = $step->getModelStatus();
            $model->$method($value);

        }


    }


    /**
     * {@inheritdoc}
     */
    public function getCurrentState(ModelInterface $model)
    {
        return $this->storage->findCurrentModelState($model, $this->process->getName());
    }

    /**
     * Get previous stationary state
     */
    public function getPreviousStationaryState(ModelInterface $model)
    {
        $state = $this->storage->findCurrentModelState($model, $this->process->getName());

        if ($state){
            return $this->getPreviousStationaryStateRecursion($state);
        }
        else{
            return null;
        }
    }

    private function getPreviousStationaryStateRecursion($state){
        $prev = $state->getPrevious();

        if(!$prev){
            return null;
        }

        if($prev->isStationary()){
            return $prev;
        }
        else{
            return $this->getPreviousStationaryStateRecursion($prev);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isProcessComplete(ModelInterface $model)
    {
        $state = $this->getCurrentState($model);

        return ( $state->getSuccessful() && in_array($state->getStepName(), $this->process->getEndSteps()) );
    }

    /**
     * {@inheritdoc}
     */
    public function getAllStates(ModelInterface $model, $successOnly = true)
    {
        return $this->storage->findAllModelStates($model, $this->process->getName(), $successOnly);
    }


    public function getAllStatuses(ModelInterface $model, $successOnly = true){

        $statuses = array();
        $states = $this->getAllStates($model, $successOnly);
        foreach($states as $state){
            $step = $this->process->getStep($state->getStepName());
            $statuses[] = $step->getModelStatus()[1];
        }

        return $statuses;
    }


    public function getStepModelStatus($state){
        $step = $this->process->getStep($state->getStepName());
        return $step->getModelStatus()[1];
    }

    /**
     * Returns a step by its name.
     *
     * @param  string $stepName
     * @return Step
     */
    public function getProcessStep($stepName)
    {
        $step = $this->process->getStep($stepName);

        if (! ($step instanceof Step)) {
            throw new WorkflowException(sprintf('Can\'t find step named "%s" in process "%s".', $stepName, $this->process->getName()));
        }

        return $step;
    }

    /**
     * Check if the user is allowed to reach the step.
     *
     * @param  ModelInterface        $model
     * @param  Step                  $step
     * @throws AccessDeniedException
     */
    protected function checkCredentials(ModelInterface $model, Step $step)
    {
        $roles = $step->getRoles();

        if (!empty($roles) && !$this->security->isGranted($roles, $model->getWorkflowObject())) {
            throw new AccessDeniedException($step->getName());
        }
    }

    /**
     * Undo stationary step to the previous one
     *
     * @param ModelInterface $model
     * @param string $entityIdentifier
     * @param $container
     */
    public function undoStationaryStep(ModelInterface $model, $entityIdentifier, $container, $em){

        $prevState = $this->getPreviousStationaryState($model);
        $states =$this->storage->findAllStatesFromLastStationary($model->getWorkflowIdentifier(),$prevState->getId(),false);

        $ids = array();
        foreach($states as $state) {
            $ids[] = $state->getId();
        }

        foreach($states as $state) {

            $subProcessHandler = $container->get('lexik_workflow.handler.'.$state->getProcessName());
            $subModelName = $container->getParameter('course_config')[$entityIdentifier][$state->getProcessName()]["model"];
            $subProcessModel = new $subModelName($model->getWorkflowObject(), $container, $em);

            $subPrevState = $state->getPrevious();
            if($subPrevState){
                $step = $subProcessHandler->getProcessStep($subPrevState->getStepName());
                $status = $step->getModelStatus()[1];

                $subProcessModel->setStatus($status);
                $em->remove($state);

            }
            else{
                $step = $subProcessHandler->getProcessStep($state->getStepName());
                $event = new StepEvent($step, $subProcessModel, $state);
                $eventName = sprintf('%s.%s.reset', $state->getProcessName(), $state->getStepName());
                $this->dispatcher->dispatch($eventName, $event);
//
//                //Reset subprocess data
//                $subPData = $container->getParameter('course_config')[$entityIdentifier][$state->getProcessName()]["subp_data"];
//                if($subPData){
//                    if(is_array($subPData)){
//                        foreach($subPData as $setter){
//
//                            $model->getWorkflowObject()->$setter(null);
//                            $em->persist($model->getWorkflowObject());
//
//                        }
//                    }
//                    elseif(is_string($subPData) and $subPData == "Provider"){
//                        $provider = $container->getParameter('course_config')[$entityIdentifier][$state->getProcessName()]["subp_data_provider"];
//                        $container->get($provider["service"])->{$provider["method"]}($model->getWorkflowObject());
//                    }
//                }

                if($state->getId() != $prevState->getId() and ($state->getParent() == null or ($state->getParent() != null and $prevState->getId() != $state->getParent()->getId()))){
                    $subProcessModel->setStatus(null);
                    $em->remove($state);
                }
            }
        }

        $em->flush();

    }

}
