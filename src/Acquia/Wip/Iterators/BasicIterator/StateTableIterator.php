<?php

namespace Acquia\Wip\Iterators\BasicIterator;

use Acquia\Wip\AdjustableTimer;
use Acquia\Wip\DependencyManagedInterface;
use Acquia\Wip\DependencyManager;
use Acquia\Wip\Exception\DependencyMissingException;
use Acquia\Wip\Exception\MissingTransitionBlockException;
use Acquia\Wip\Implementation\IteratorResult;
use Acquia\Wip\Implementation\WipVersionElement;
use Acquia\Wip\IteratorStatus;
use Acquia\Wip\Notification\NotificationInterface;
use Acquia\Wip\Notification\NotificationSeverity;
use Acquia\Wip\RecordingInterface;
use Acquia\Wip\Runtime\WipPoolController;
use Acquia\Wip\Signal\ContainerSignalInterface;
use Acquia\Wip\Signal\SignalFactoryInterface;
use Acquia\Wip\Signal\SignalInterface;
use Acquia\Wip\Signal\SshSignalInterface;
use Acquia\Wip\Signal\TaskTerminateSignal;
use Acquia\Wip\Signal\WipSignalInterface;
use Acquia\Wip\StateTableIteratorInterface;
use Acquia\Wip\Storage\SignalStoreInterface;
use Acquia\Wip\Storage\WipPoolStoreInterface;
use Acquia\Wip\Timer;
use Acquia\Wip\Utility\MetricsUtility;
use Acquia\Wip\WipContextInterface;
use Acquia\Wip\WipFactory;
use Acquia\Wip\WipInterface;
use Acquia\Wip\WipLogInterface;
use Acquia\Wip\WipLogLevel;

/**
 * A simple implementation of the StateTableIteratorInterface.
 */
class StateTableIterator implements StateTableIteratorInterface, DependencyManagedInterface {

  /**
   * Simulation mode value that indicates simulation is disabled.
   */
  const SIMULATION_DISABLED = 0;

  /**
   * Simulation mode value that indicates a simulation script is being used.
   */
  const SIMULATION_SCRIPT = 1;

  /**
   * Simulation mode value that indicates a random simulation is being used.
   */
  const SIMULATION_RANDOM = 2;

  /**
   * The current state in the state machine.
   *
   * @var string
   */
  private $currentState = NULL;

  /**
   * The Wip object this iterator traverses.
   *
   * @var WipInterface
   */
  private $wipObj = NULL;

  /**
   * The StateMachine this iterator uses to call methods in a Wip object.
   *
   * @var StateMachine
   */
  private $stateMachine = NULL;

  /**
   * Keeps track of the number of times each transition is used.
   *
   * @var array
   */
  private $transitionCounts = NULL;

  /**
   * The WipContext instances for each state.
   *
   * @var WipContext[]
   */
  private $stateContexts;

  /**
   * The exit code that will be set when this Wip object completes.
   *
   * @var int
   */
  private $exitCode = IteratorStatus::OK;

  /**
   * The final message or text status that will be displayed for the Wip object.
   *
   * @var string
   */
  private $exitMessage = NULL;

  /**
   * The WipLog instance.
   *
   * @var WipLogInterface
   *
   * @notSerializable
   */
  private $wipLog = NULL;

  /**
   * The dependency manager.
   *
   * @var DependencyManager
   */
  private $dependencyManager;

  /**
   * Indicates whether the object is running in simulation mode.
   *
   * Simulation mode causes the states and transitions to not be actually
   * invoked so the state table can be fully exercised without
   * being constrained by the implementation of the state and transition methods.
   *
   * @var int
   */
  private $simulationMode = self::SIMULATION_DISABLED;

  /**
   * Contains a script that will be followed if in simulation mode.
   *
   * The script indicates which transition values should be used when traversing
   * through the Wip object.  The script is only used if the simulation mode is
   * set to SIMULATION_SCRIPT.
   *
   * @var SimulationScriptInterpreter
   */
  private $simulation = NULL;

  /**
   * Contains the Recording object associated with this instance.
   *
   * This keeps a record of the transitions from state to state.
   *
   * @var StateTableRecording
   */
  private $recording = NULL;

  /**
   * Contains all recordings associated with this Wip object.
   *
   * @var RecordingInterface[]
   */
  private $recordings = NULL;

  /**
   * The Timer instance used to track time during Wip execution.
   *
   * @var Timer
   */
  private $timer = NULL;


  /**
   * The transition state timings.
   *
   * @var array
   */
  private $transitionStateTimings = array();

  /**
   * Initializes an instance of StateTableIterator.
   *
   * @throws \Acquia\Wip\Exception\DependencyTypeException
   *   If any dependency is not satisfied.
   */
  public function __construct() {
    $this->dependencyManager = new DependencyManager();
    $dependencies = $this->getDependencies();
    if (is_array($dependencies)) {
      $this->dependencyManager->addDependencies($this->getDependencies());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(WipInterface $obj) {
    $this->currentState = NULL;
    $this->wipObj = $obj;
    $this->transitionCounts = NULL;
    $this->stateContexts = array();
    $this->exitCode = IteratorStatus::OK;
    $this->exitMessage = NULL;
    $this->timer = new Timer();
    $obj->setIterator($this);
    $this->recordings = [];
    $this->recording = new StateTableRecording();
    $this->addRecording(get_class($obj), $this->recording);
    $this->transitionStateTimings = array();
  }

  /**
   * {@inheritdoc}
   */
  public function compileStateTable() {
    $obj = $this->getWip();
    $parser = new StateTableParser($obj->getStateTable());
    $this->stateMachine = $parser->parse();
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies() {
    return array(
      'acquia.wip.wiplog' => 'Acquia\Wip\WipLogInterface',
      'acquia.wip.storage.signal' => 'Acquia\Wip\Storage\SignalStoreInterface',
      'acquia.wip.signal.signalfactory' => 'Acquia\Wip\Signal\SignalFactoryInterface',
      'acquia.wip.notification' => 'Acquia\Wip\Notification\NotificationInterface',
      'acquia.wip.storage.wippool' => 'Acquia\Wip\Storage\WipPoolStoreInterface',
      'acquia.wip.metrics.relay' => 'Acquia\Wip\Metrics\MetricsRelayInterface',
      WipPoolController::RESOURCE_NAME => 'Acquia\Wip\Runtime\WipPoolControllerInterface',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getStartState() {
    if (empty($this->stateMachine)) {
      $this->compileStateTable();
    }
    return $this->stateMachine->getStartState();
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentState() {
    return $this->currentState;
  }

  /**
   * {@inheritdoc}
   */
  public function getWip() {
    return $this->wipObj;
  }

  /**
   * Gets the transition state timings.
   *
   * @return array
   *   The transition state timings array.
   */
  public function getTransitionStateTimings() {
    return $this->transitionStateTimings;
  }

  /**
   * Gets the last outstanding terminate signal.
   *
   * Note that all terminate signals for this Wip object will be consumed as a
   * result of this call.
   *
   * @return TaskTerminateSignal
   *   The signal, or NULL if there is no such signal.
   */
  private function getTerminateSignal() {
    /** @var TaskTerminateSignal $result */
    $result = NULL;
    $signals = $this->getSignals();
    foreach ($signals as $signal) {
      if ($signal instanceof TaskTerminateSignal) {
        $result = $signal;
        $this->consumeSignal($result);
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function moveToNextState() {
    $state = $this->currentState;

    $terminate_signal = $this->getTerminateSignal();
    if ($terminate_signal instanceof TaskTerminateSignal) {
      // This task is being terminated.
      if (empty($this->getCurrentState())) {
        // There is no point in doing a formal termination since the task
        // has not actually started yet. Though call the terminate method
        // because that will set up the appropriate exit message.
        try {
          $this->getWip()->terminate($this->getWipContext('terminate'));
        } catch (\Exception $e) {
          // Ignore.
        }
        $result = new IteratorResult(
          0,
          TRUE,
          new IteratorStatus($this->getExitCode()),
          $this->getExitMessage()
        );
        return $result;
      }
      $state = 'terminate';
      if (in_array($state, $this->stateMachine->getAllStates())) {
        $transition_value = 'terminateRequested';
        $transition = new Transition($transition_value, $state);
        $this->startTimer($state);
        $this->recording->addTransition($this->currentState, $transition_value);
        $this->invokeState($state);
        $this->log(
          WipLogLevel::INFO,
          sprintf('[%s] -> {%s} -> [%s]', $this->getCurrentState(), $transition_value, $state)
        );
      }
    } elseif (empty($this->currentState)) {
      // This task has not yet started.
      $state = $this->getStartState();
      $this->startTimer($state);
      $transition = new Transition('*', 'start');
      $this->log(WipLogLevel::INFO, sprintf('Starting %s', $this->wipObj->getTitle()));

      try {
        $this->invokeState($state);
      } catch (\Exception $e) {
        $this->getWip()->log(WipLogLevel::ERROR, $e->getMessage(), TRUE);

        $this->getWipLog()->multiLog(
          $this->getId(),
          WipLogLevel::ERROR,
          sprintf('Exception caught in state %s: %s', $state, $e->getMessage()),
          WipLogLevel::DEBUG,
          sprintf('  %s', $e->getTraceAsString())
        );

        /** @var NotificationInterface $notifier */
        $notifier = $this->dependencyManager->getDependency('acquia.wip.notification');
        $notifier->notifyException($e, NotificationSeverity::ERROR, array(
          'wip_id' => $this->getId(),
          'group' => $this->wipObj->getGroup(),
          'state' => $state,
        ));

        // Force a transition to the failure state.
        $transition = new Transition('!', 'failure');
        $state = $transition->getState();
        $this->startTimer($state);
        $this->invokeState($state, TRUE, $e);
      }
    } else {
      // Here we have to use the FSM to find the next state.
      $transition_method = $this->getTransitionMethod();
      $transition_value = '';
      if (!empty($transition_method)) {
        $transition_value = $this->invokeTransition($state, $transition_method);
      }

      $wip_pool_controller = WipPoolController::getWipPoolController($this->dependencyManager);
      do {
        try {
          $transition = $this->getTransition($transition_value);
          if (NULL === $transition) {
            if ('!' === $transition_value) {
              // The failure transition is not specified in the state table.
              $transition = new Transition('!', 'failure');
            } else {
              throw new \RuntimeException(
                sprintf(
                  'Transition from state "%s" with value "%s" not found.',
                  $this->getCurrentState(),
                  $transition_value
                )
              );
            }
          }
          $unique_id = $transition->getUniqueId();
          if (!isset($this->transitionCounts[$unique_id])) {
            $this->transitionCounts[$unique_id] = 0;
          }
          $state = $transition->getState();
          $max_transition_executions = $transition->getMaxCount();
          if ($max_transition_executions !== 0 && $max_transition_executions <= $this->transitionCounts[$unique_id]) {
            throw new \RuntimeException(sprintf('Maximum transitions exceeded for transition "%s"', $unique_id));
          }
          $this->startTimer($state);
          if ($transition->getExec()) {
            if ($this->getCurrentState() !== $state) {
              if (!empty($transition_value) && $transition_value !== $transition->getValue()) {
                $transition_string = sprintf('{%s[%s]}', $transition->getValue(), $transition_value);
              } else {
                $transition_string = sprintf('{%s}', $transition->getValue());
              }
              $this->log(
                WipLogLevel::INFO,
                sprintf('[%s] ->%s-> [%s]', $this->getCurrentState(), $transition_string, $state)
              );
            }
            $this->invokeState($state);
          } else {
            $this->invokeState($state, FALSE);
            $this->addStateEntry($state, FALSE);
          }
          $this->transitionCounts[$unique_id]++;
        } catch (\Exception $e) {
          $this->getWipLog()->multiLog(
            $this->getId(),
            WipLogLevel::ERROR,
            sprintf('Exception caught in state %s: %s', $state, $e->getMessage()),
            WipLogLevel::DEBUG,
            sprintf('  %s', $e->getTraceAsString())
          );

          /** @var NotificationInterface $notifier */
          $notifier = $this->dependencyManager->getDependency('acquia.wip.notification');
          $notifier->notifyException($e, NotificationSeverity::ERROR, array(
            'wip_id' => $this->getId(),
            'group' => $this->wipObj->getGroup(),
            'state' => $state,
          ));

          $transition = NULL;
          if ($transition_value === '!') {
            // Exceeded maximum transitions on the failure transition.
            // Force a transition to the failure state.
            $transition = new Transition('!', 'failure');
            $state = $transition->getState();
            $this->startTimer($state);
            $this->invokeState($state, TRUE, $e);
          } else {
            $this->log(
              WipLogLevel::ERROR,
              sprintf('Unable to find transition from "%s" with transition value "%s"', $state, $transition_value)
            );

            $transition_value = '!';
          }
        }
      } while (!$wip_pool_controller->isHardPausedGlobal() and $transition === NULL);
    }

    if (!!array_intersect([$this->currentState, $state], MetricsUtility::TRANSITION_STATES_TO_BE_TIME_TRACKED)) {
      $this->trackTimesForTransitionStates($this->currentState, $state);
    }

    $this->currentState = $state;
    $finished = ($this->currentState === 'finish');
    if ($finished) {
      $this->timer->stop();
      $this->log(WipLogLevel::ALERT, $this->timer->report());

      // Send each timer to graphite.
      $relay = $this->dependencyManager->getDependency('acquia.wip.metrics.relay');
      $timers = $this->timer->getTimerNames();
      foreach ($timers as $timer_name) {
        if ($timer_name === 'none') {
          continue;
        }
        $namespace = 'wip.task.timer.' . $timer_name;
        $relay->timing($namespace, $this->timer->getTimeMs($timer_name));
      }
    }

    $exit_message = (string) $this->getExitMessage();
    if (!empty($transition)) {
      $wait = $transition->getWait();
    } else {
      $wait = 0;
    }
    $result = new IteratorResult(
      $wait,
      $finished,
      new IteratorStatus($this->getExitCode()),
      $exit_message
    );
    return $result;
  }

  /**
   * Tracks the transition times.
   *
   * @param string $currentState
   *   The current transition state.
   * @param string $nextState
   *   The next transition state.
   */
  public function trackTimesForTransitionStates($currentState, $nextState) {
    try {
      if (isset($this->transitionStateTimings[$currentState])) {
        $end = gettimeofday(TRUE);
        $timing = ($end - $this->transitionStateTimings[$currentState]) * 1000;
        $metric_key = 'wip.task.timer.' . $currentState;
        $relay = $this->dependencyManager->getDependency('acquia.wip.metrics.relay');
        $relay->timing($metric_key, $timing);
        unset($this->transitionStateTimings[$currentState]);
      }
      if (in_array($nextState, MetricsUtility::TRANSITION_STATES_TO_BE_TIME_TRACKED)) {
        $this->transitionStateTimings[$nextState] = gettimeofday(TRUE);
      }
    } catch (\Exception $e) {
      $this->log(
        WipLogLevel::ERROR,
        sprintf('Failed to track transition from "%s" to "%s" because %s', $currentState, $nextState, $e->getMessage())
      );
    }
  }

  /**
   * Invokes the specified state method.
   *
   * @param string $state
   *   The state name.
   * @param bool $exec
   *   Optional. False indicates the state should not be executed.
   * @param \Exception $e
   *   Optional.  The exception.
   */
  protected function invokeState($state, $exec = TRUE, \Exception $e = NULL) {
    if ($this->simulationMode === self::SIMULATION_DISABLED && $exec) {
      $this->wipObj->$state($this->getWipContext($state), $e);
    } else {
      // This is an object simulation, meant to exercise the state table.
      // Do not call the state method.
    }
    $this->addStateEntry($state, $exec);
  }

  /**
   * Invokes the specified transition method.
   *
   * @param string $state
   *   The name of the state the transition method is associated with.
   * @param string $transition_method
   *   The name of the transition method.
   *
   * @return string
   *   The transition value.
   *
   * @throws \InvalidArgumentException
   *   If the state and/or transition method names are not strings.
   */
  protected function invokeTransition($state, $transition_method) {
    if (is_string($state) && is_string($transition_method)) {
      $result = '';
      if ($this->simulationMode === self::SIMULATION_DISABLED) {
        $result = $this->wipObj->$transition_method($this->getWipContext($state));
      } else {
        // Provide a mechanism that allows scripting that forces Wip objects
        // through their paces for testing purposes.
        $result = $this->alterTransitionValue($state, $transition_method, $result);
      }
      $this->addTransitionEntry($transition_method, $result);
      return $result;
    } else {
      throw new \InvalidArgumentException(
        'The state and transition method names must be strings.'
      );
    }
  }

  /**
   * Gets the transition method used to identify the next step in the FSM.
   *
   * @return string
   *   The transition method.
   */
  private function getTransitionMethod() {
    $transition_block = $this->stateMachine->getTransitionBlock($this->currentState);
    return $transition_block->getTransitionMethod();
  }

  /**
   * {@inheritdoc}
   */
  public function getStateTransitionIds($state) {
    $result = array();
    try {
      $transition_block = $this->stateMachine->getTransitionBlock($state);
      $values = $transition_block->getAllTransitionValues();
      foreach ($values as $value) {
        $transition = $transition_block->getTransition($value);
        if (NULL !== $transition) {
          $result[] = $transition->getUniqueId();
        } else {
          $this->log(WipLogLevel::ERROR, sprintf('The transition "%s":"%s" cannot be found.', $state, $value));
        }
      }
    } catch (MissingTransitionBlockException $e) {
      $this->log(WipLogLevel::ERROR, sprintf('The transition block for state "%s" cannot be found.', $state));
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransitionId($state, $transition_value) {
    $transition_block = $this->stateMachine->getTransitionBlock($state);
    $transition = $transition_block->getTransition($transition_value);
    if (NULL === $transition) {
      throw new \DomainException(sprintf('Transition "%s":"%s" does not exist.', $state, $transition_value));
    }
    return $transition->getUniqueId();
  }

  /**
   * Gets the transition object that describes the movement to the next state.
   *
   * The transition object contains the next state and any transition options
   * should apply to the transition.
   *
   * @param string $transition_value
   *   The value returned from the transition method associated with the current
   *   state.
   *
   * @return Transition
   *   The relevant Transition instance.
   *
   * @throws \InvalidArgumentException
   *   If the transition value is not a string.
   */
  private function getTransition($transition_value) {
    if (is_string($transition_value)) {
      $transition_block = $this->stateMachine->getTransitionBlock($this->currentState);
      return $transition_block->findNextTransition($transition_value);
    } else {
      throw new \InvalidArgumentException(
        'The transition value must be a string.'
      );
    }
  }

  /**
   * Clears the transition count from a particular transition.
   *
   * This facilitates the execution of inner and outer loops in which both have
   * max execution values defined. As each outer loop restarts, it can reset
   * the execution count of the inner loop transitions so each loop starts fresh
   * and gets the same error behavior each time.
   *
   * @param string $state
   *   The name of the state that contains the transition.
   * @param string $transition_value
   *   The transition value.
   *
   * @return bool
   *   TRUE if the transition count was reset; FALSE if it could not be reset.
   */
  public function clearTransitionCount($state, $transition_value) {
    $result = FALSE;
    try {
      $transition_block = $this->stateMachine->getTransitionBlock($state);
      $transition = $transition_block->findNextTransition($transition_value);
      $unique_id = $transition->getUniqueId();
      $this->transitionCounts[$unique_id] = 0;
      $result = TRUE;
    } catch (\Exception $e) {
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getWipContext($state = NULL, $follow_link = TRUE) {
    if (empty($state)) {
      $state = $this->currentState;
    }
    if (!isset($this->stateContexts[$state])) {
      $this->stateContexts[$state] = new WipContext();
    }
    $context = $this->stateContexts[$state];
    if ($follow_link) {
      $context = $context->getLinkedContext();
    }
    $context->setIterator($this);
    return $context;
  }

  /**
   * {@inheritdoc}
   */
  public function restart() {
    $this->getWip()->onRestart();
    $this->initialize($this->getWip());
  }

  /**
   * {@inheritdoc}
   */
  public function setExitCode($exit_code) {
    if (!IteratorStatus::isValid($exit_code)) {
      throw new \InvalidArgumentException(sprintf('Exit code %s is not a valid value.', $exit_code));
    }
    $this->exitCode = $exit_code;
  }

  /**
   * {@inheritdoc}
   */
  public function getExitCode() {
    return $this->exitCode;
  }

  /**
   * {@inheritdoc}
   */
  public function setExitMessage($message) {
    if (!is_string($message)) {
      throw new \InvalidArgumentException(sprintf('The "message" argument must be a string.'));
    }
    $this->exitMessage = $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getExitMessage() {
    return $this->exitMessage;
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $result = FALSE;
    try {
      $validator = new StateMachineValidator();
      $validator_result = $validator->validate($this->stateMachine, $this->getWip()->getStateTable(), $this->wipObj);
      $result = !$validator_result->hasFailures();
    } catch (\Exception $e) {
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getWipLog() {
    if ($this->wipLog === NULL) {
      $this->wipLog = $this->dependencyManager->getDependency('acquia.wip.wiplog');
    }
    return $this->wipLog;
  }

  /**
   * {@inheritdoc}
   */
  public function setWipLog(WipLogInterface $wip_log) {
    $this->wipLog = $wip_log;
    if (!empty($this->wipObj)) {
      $this->wipObj->setWipLog($wip_log);
    }
  }

  /**
   * Logs the specified message.
   *
   * @param int $level
   *   The WipLogLevel value describing the level of this log message.
   * @param string $message
   *   The message to log.
   * @param bool $obj_message
   *   Optional. If TRUE, the log message will be logged against the associated
   *   Wip object. Otherwise no object will be associated with the log message.
   *
   * @return bool
   *   TRUE if the log message was logged successfully; FALSE otherwise.
   */
  protected function log($level, $message, $obj_message = TRUE) {
    $obj_id = NULL;
    if ($obj_message) {
      $obj_id = $this->getWip()->getId();
    }
    return $this->getWipLog()->log($level, $message, $obj_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    $result = NULL;
    if (!empty($this->wipObj)) {
      $result = $this->wipObj->getId();
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    if (!empty($this->wipObj)) {
      $this->wipObj->setId($id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSignals() {
    $signal_store = $this->getSignalStore();
    return $signal_store->loadAllActive($this->getId());
  }

  /**
   * {@inheritdoc}
   */
  public function consumeSignal(SignalInterface $signal) {
    $signal_id = $signal->getId();
    if (empty($signal_id)) {
      throw new \RuntimeException('Cannot consume a signal that has no ID.');
    }
    if ($signal->getObjectId() !== $this->getId()) {
      throw new \RuntimeException(
        sprintf(
          'Cannot consume signal %d in Wip task %d because this signal is associated with Wip task %d.',
          $signal_id,
          $this->getId(),
          $signal->getObjectId()
        )
      );
    }
    $signal_store = $this->getSignalStore();
    return $signal_store->consume($signal);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteSignal(SignalInterface $signal) {
    $signal_id = $signal->getId();
    if (empty($signal_id)) {
      throw new \RuntimeException('Cannot delete a signal that has no ID.');
    }
    if ($signal->getObjectId() !== $this->getId()) {
      throw new \RuntimeException(
        sprintf(
          'Cannot delete signal %d in Wip task %d because this signal is associated with Wip task %d.',
          $signal_id,
          $this->getId(),
          $signal->getObjectId()
        )
      );
    }
    $signal_store = $this->getSignalStore();
    $signal_store->delete($signal);
  }

  /**
   * {@inheritdoc}
   */
  public function processSignals(WipContextInterface $context) {
    $result = 0;
    // Need to get the signals, figure out which state each is associated with,
    // then for each that represents a completed asynchronous event, convert it
    // to a result within the context.
    $signals = $this->getSignals();
    if (!empty($signals)) {
      foreach ($signals as $signal) {
        $this->getWip()->onSignal($signal);
        if ($signal instanceof WipSignalInterface) {
          $result += $this->getWip()->getWipApi()->processSignal($signal, $context, $this->getWipLog());
        } elseif ($signal instanceof SshSignalInterface) {
          $result += $this->getWip()->getSshApi()->processSignal($signal, $context, $this->getWipLog());
        } elseif ($signal instanceof ContainerSignalInterface) {
          $result += $this->getWip()->getContainerApi()->processSignal($signal, $context, $this->getWipLog());
        }
      }
    }
    return $result;
  }

  /**
   * Gets the signal storage instance to use.
   *
   * @return SignalStoreInterface
   *   The configured SignalStoreInterface instance.
   *
   * @throws DependencyMissingException
   *   If the signal store has not been defined in the configuration.
   */
  public function getSignalStore() {
    return WipFactory::getObject('acquia.wip.storage.signal');
  }

  /**
   * Gets the SignalFactory instance.
   *
   * @return SignalFactoryInterface
   *   The SignalFactory instance or NULL if the signal factory has not been
   *   configured.
   */
  public function getSignalFactory() {
    return WipFactory::getObject('acquia.wip.signal.signalfactory');
  }

  /**
   * Alters the transition value for testing purposes.
   *
   * @param string $state
   *   The state in the finite state machine.
   * @param string $transition_method
   *   The transition method.
   * @param string $transition_value
   *   The actual transition value returned from the transition method.
   *
   * @return string
   *   The transition value, possibly altered
   *
   * @throws \InvalidArgumentException
   *    If one or more of state name, transition method name, and
   *    transition value are not strings.
   */
  protected function alterTransitionValue($state, $transition_method, $transition_value) {
    if (is_string($state) &&
      is_string($transition_method) &&
      is_string($transition_value)
    ) {
      $result = $transition_value;
      switch ($this->simulationMode) {
        case self::SIMULATION_DISABLED:
          // No simulation; do not alter the value.
          break;

        case self::SIMULATION_SCRIPT:
          // Script-based simulation; get the altered value from the script.
          $result = $this->simulation->getNextTransitionValue($state);
          break;

        case self::SIMULATION_RANDOM:
          // Random simulation; get the altered value from the set of legal
          // transition values.
          $legal_values = StateTableParser::getAvailableTransitionValues($this->getWip(), $transition_method);
          if (empty($legal_values)) {
            $legal_values = array('');
          }
          // 3% of the time fail out.
          if (mt_rand(0, 100) > 97) {
            $result = '!';
          } else {
            $value_offset = mt_rand(0, count($legal_values) - 1);
            $result = $legal_values[$value_offset];
          }
          break;
      }
      return $result;
    } else {
      throw new \InvalidArgumentException(
        'The state name, transition method name, and transition value must all be strings.'
      );
    }
  }

  /**
   * Add an state entry to the recording.
   *
   * @param string $state
   *   The state to add to the recording.
   * @param bool $exec
   *   Optional. Indicates whether the state was executed.
   *
   * @throws \InvalidArgumentException
   *   If the state name is not a string.
   */
  public function addStateEntry($state, $exec = TRUE) {
    if (is_string($state)) {
      $this->recording->addState($state, $exec);
    } else {
      throw new \InvalidArgumentException('The state name must be a string.');
    }
  }

  /**
   * Adds a transition entry to the recording.
   *
   * @param string $method
   *   The transition method name.
   * @param string $value
   *   The transition value.
   *
   * @throws \InvalidArgumentException
   *   If the method and/or values are not strings.
   */
  public function addTransitionEntry($method, $value) {
    if (is_string($method) && is_string($value)) {
      $this->recording->addTransition($method, $value);
    } else {
      throw new \InvalidArgumentException('The method name and value must be strings.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addRecording($name, RecordingInterface $recording) {
    if (is_string($name)) {
      $this->recordings[$name] = $recording;
    } else {
      throw new \InvalidArgumentException('The name must be a string.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRecordings() {
    return $this->recordings;
  }

  /**
   * Puts the iterator into simulation mode.
   *
   * Simulation mode prevents the state and transition methods from being called
   * so that the state table itself can be exercised. In SIMULATION_SCRIPT
   * mode, the state table will be exercised according to a script and the
   * transition sequence will be compared to an expected sequence. In
   * SIMULATION_RANDOM mode, the state table will be exercised with random
   * transition values until it reaches the finish state.
   *
   * @param int $simulation_mode
   *   Optional.  One of SIMULATION_DISABLED, SIMULATION_SCRIPT, or
   *   SIMULATION_RANDOM, indicating whether a simulation is being run and what
   *   type of simulation.  If not specified, simulation will be disabled.
   * @param SimulationScriptInterpreter $simulation
   *   Optional.
   */
  public function setSimulationMode(
    $simulation_mode = self::SIMULATION_DISABLED,
    SimulationScriptInterpreter $simulation = NULL
  ) {
    switch ($simulation_mode) {
      case self::SIMULATION_DISABLED:
        break;

      case self::SIMULATION_RANDOM:
        if (!empty($simulation)) {
          $msg = 'The specified simulation_mode does not accept a SimulationScriptInterpreter instance.';
          throw new \InvalidArgumentException($msg);
        }
        break;

      case self::SIMULATION_SCRIPT:
        if (empty($simulation)) {
          $msg = 'The simulation_mode SIMULATION_SCRIPT requires an instance of SimulationScriptInterpreter.';
          throw new \InvalidArgumentException($msg);
        }
        break;

      default:
        $msg = 'The simulation_mode must be one of SIMULATION_DISABLED, SIMULATION_SCRIPT, or SIMULATION_RANDOM.';
        throw new \InvalidArgumentException($msg);
    }
    $this->simulationMode = $simulation_mode;
    $this->simulation = $simulation;
  }

  /**
   * Returns the simulation mode of the iterator.
   *
   * @return int
   *   The simulation mode.
   */
  public function getSimulationMode() {
    return $this->simulationMode;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimer() {
    return $this->timer;
  }

  /**
   * {@inheritdoc}
   */
  public function blendTimerData(Timer $timer) {
    $this->getTimer()->blend($timer);
  }

  /**
   * Starts the timer for the specified state.
   *
   * @param string $state
   *   The state name.
   */
  public function startTimer($state) {
    $iterator_timer = $this->getTimer();
    try {
      $transition_block = $this->stateMachine->getTransitionBlock($state);
      if ($transition_block) {
        $timer_name = $transition_block->getTimerName();
        $timer_names = array_diff($iterator_timer->getTimerNames(), array('none'));
        if ($timer_name === 'user' &&
          'user' !== $iterator_timer->getCurrentTimerName() &&
          !in_array('sla.startDelay', $timer_names)) {
          try {
            $iterator_timer->getTime($timer_name);
          } catch (\Exception $e) {
            // This is the first state registered against user time. Calculate
            // the delay between the time the Wip task was added and the time
            // the user's workload begins.
            try {
              $iterator_timer->stop();
              $timer = new AdjustableTimer();
              $timer->start('sla.startDelay');
              $task = $this->getWipPoolStore()->get($this->getId());
              if (!empty($task)) {
                $add_time = $task->getCreatedTimestamp();
              } else {
                // Try to calculate the creation time. This will always be off
                // for Wip objects that run in a container because the container
                // launch time will not be included.
                $this->log(WipLogLevel::ERROR, 'Unable to get the created timestamp from the wip_pool table.');
                $add_time = microtime(TRUE);
                foreach ($timer_names as $timer_name) {
                  $add_time -= $iterator_timer->getTime($timer_name);
                }
              }
              $timer->adjustStart($add_time - microtime(TRUE));
              $timer->stop();
              $iterator_timer->blend($timer);
            } catch (\Exception $e) {
              $this->log(
                WipLogLevel::ERROR,
                sprintf('Failed to add sla.startDelay to the timer: %s', $e->getMessage())
              );
            }
          }
        }
        $iterator_timer->start($transition_block->getTimerName());
      }
    } catch (\Exception $e) {
      // No transition block exists.
      $iterator_timer->start('system');
    }
  }

  /**
   * Returns the WipPool instance.
   *
   * @return WipPoolStoreInterface
   *   The WipPool instance.
   */
  public function getWipPoolStore() {
    return $this->dependencyManager->getDependency('acquia.wip.storage.wippool');
  }

  /**
   * {@inheritdoc}
   */
  public function needsUpdate() {
    return count($this->getUpdateElements()) > 0;
  }

  /**
   * Returns the set of WipVersionElements identifying classes needing update.
   *
   * @return WipVersionElement[]
   *   The instance version elements requiring update.
   */
  private function getUpdateElements() {
    $result = array();
    $wip = $this->getWip();
    $class_versions = $wip::getClassVersions();

    // Look at each class version and compare to the instance element. It is
    // unexpected though not necessarily catastrophic if there is an instance
    // version for which there is no class version. This can occur should a Wip
    // class have its superclass changed.
    foreach ($class_versions as $class_version_element) {
      try {
        $instance_version_element = $wip->getInstanceVersion($class_version_element->getClassName());
        if ($instance_version_element->getVersionNumber() < $class_version_element->getVersionNumber()) {
          $result[] = $instance_version_element;
        }
      } catch (\Exception $e) {
        // This occurs if the instance version is not available. That can
        // happen if the class is modified to have a new super-class.
        $this->log(
          WipLogLevel::ERROR,
          sprintf(
            'Failed to find the instance version for class "%s"',
            $class_version_element->getClassName()
          )
        );
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function update() {
    $wip = $this->getWip();
    $updates_to_perform = $this->getUpdateElements();
    foreach ($updates_to_perform as $update) {
      $this->updateClass($update, $wip::getClassVersionForClass($update->getClassName()));
    }
    $this->compileStateTable();
  }

  /**
   * Updates the Wip class associated with the specified version element.
   *
   * @param WipVersionElement $instance
   *   The Wip instance version.
   * @param WipVersionElement $class
   *   The Wip class version.
   */
  private function updateClass(WipVersionElement $instance, WipVersionElement $class) {
    $wip = $this->getWip();
    $current_version = $instance->getVersionNumber();
    for ($next_version = $current_version + 1;
         $next_version <= $class->getVersionNumber();
         $next_version++
    ) {
      $log_entries = array();
      $log_entries[] = sprintf(
        'Updating %s instance from version %d to version %d ...',
        $instance->getShortClassName(),
        $instance->getVersionNumber(),
        $next_version
      );
      $update_method = $this->getUpdateMethod($class, $next_version);
      if (method_exists($wip, $update_method)) {
        $update_coordinator = $this->createUpdateCoordinator();
        $wip->$update_method($update_coordinator);
        $log_entries = array_merge($log_entries, $this->applyUpdateCoordinator($update_coordinator));

        // Update complete.
        $wip->setInstanceVersion($class->getClassName(), $next_version);
      } else {
        $message = sprintf('Missing update function "%s".', $update_method);
        $log_entries[] = sprintf('Failed - %s', $message);
        $this->log(WipLogLevel::FATAL, implode("\n", $log_entries));
        throw new \RuntimeException($message);
      }
      $log_entries[] = 'Done.';
      $this->log(WipLogLevel::ALERT, implode("\n", $log_entries));

      // Reset the instance to reflect the change in the current version.
      $instance = $wip->getInstanceVersion($instance->getClassName());
    }
  }

  /**
   * Gets the name of the update method for the specified version.
   *
   * @param WipVersionElement $instance
   *   The version element that describes the Wip object being updated.
   * @param int $version
   *   The version that the update method updates to.
   *
   * @return string
   *   The update method name.
   */
  private function getUpdateMethod(WipVersionElement $instance, $version) {
    if (!is_int($version) || $version <= 0) {
      throw new \InvalidArgumentException('The "version" parameter must be an integer greater than zero.');
    }
    return sprintf('update%s%d', $instance->getShortClassName(), $version);
  }

  /**
   * Gets the update coordinator for the current state of the Wip object.
   *
   * @return WipUpdateCoordinator
   *   The properly configured coordinator.
   */
  private function createUpdateCoordinator() {
    $result = new WipUpdateCoordinator($this, $this->getCurrentState());
    return $result;
  }

  /**
   * Applies the specified update coordinator to the iterator and the Wip object.
   *
   * @param WipUpdateCoordinatorInterface $update_coordinator
   *   The update coordinator.
   *
   * @return string[]
   *   Log entries from the update.
   */
  private function applyUpdateCoordinator(WipUpdateCoordinatorInterface $update_coordinator) {
    $result = array();
    if ($update_coordinator->requiresRestart()) {
      $result[] = '* Restarted this Wip instance.';
      $this->restart();
    } else {
      $current_state = $this->getCurrentState();
      $new_state = $update_coordinator->getNewState();

      // Handle a state change. This might occur if the new state table was
      // modified such that the current state no longer exists.
      if ($new_state !== $this->getCurrentState()) {
        $result[] = sprintf(
          '* Moved from state "%s" to state "%s".',
          $current_state,
          $new_state
        );
        $this->currentState = $new_state;
      }

      // Handle clearing of counters.
      if ($update_coordinator->requiresCounterReset()) {
        $result[] = '* Cleared all transition counts.';
        $this->transitionCounts = array();
      } else {
        $reset_transitions = $update_coordinator->getTransitionCountersToReset();
        foreach ($reset_transitions as $transition_id) {
          $result[] = sprintf('* Cleared transition counter %s.', $transition_id);
          if (isset($this->transitionCounts[$transition_id])) {
            unset($this->transitionCounts[$transition_id]);
          }
        }
      }
    }
    return $result;
  }

}
