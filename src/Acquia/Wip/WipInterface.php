<?php

namespace Acquia\Wip;

use Acquia\Wip\Implementation\WipVersionElement;
use Acquia\Wip\Iterators\BasicIterator\SimulationScriptInterpreter;
use Acquia\Wip\Iterators\BasicIterator\StateTableIterator;
use Acquia\Wip\Notification\NotificationInterface;
use Acquia\Wip\Objects\ParameterDocument;
use Acquia\Wip\Signal\CallbackInterface;
use Acquia\Wip\Signal\SignalInterface;

/**
 * The WipInterface represents the public interface of all Wip objects.
 *
 * The Wip object is the basic unit of functionality that can be processed
 * through the Wip service.  You can think of a Wip object as a task that is
 * executed.
 *
 * This functionality is broken into a series of steps and conditionals that
 * can react to failures or other runtime state in a way that makes the Wip
 * object resilient to changing external conditions or failures in dependent
 * services.
 */
interface WipInterface {

  /**
   * Gets the title of this Wip instance.
   *
   * @return string
   *   The title of this Wip object.
   */
  public function getTitle();

  /**
   * Sets the group name of this Wip instance.
   *
   * If not provided, the class name will be used as the group name.
   *
   * @param string $group_name
   *   The group name.
   */
  public function setGroup($group_name);

  /**
   * Gets the group name of this Wip instance.
   *
   * The group name is used to control how many objects of a particular type
   * can be executed simultaneously.
   *
   * @return string
   *   The group of this Wip object.
   */
  public function getGroup();

  /**
   * Returns the human-readable version of the state machine.
   *
   * This method should be overridden to follow a sequence of states that
   * constitutes a useful functionality.
   *
   * Format:
   * [CurrentState] [TransitionValue] [NewState] [Wait] [MaxAttempts]
   *
   * If Wait is 0, there will be no delay between the current state
   * and the new state.  If MaxAttempts is 0, the number of attempts
   * will be unlimited.
   *
   * @return string
   *   A human-readable version of the finite state machine.
   */
  public function getStateTable();

  /**
   * The start state in the FSM graph.
   *
   * Most subclasses should override this function and initialize
   * all object properties that are not defined at object creation time to their
   * default values in case the task is restarted after failure.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function start(WipContextInterface $wip_context);

  /**
   * The end state in the FSM graph.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function finish(WipContextInterface $wip_context);

  /**
   * The empty transition check.
   *
   * If a state method returns nothing, this empty transition check
   * will be used instead.
   *
   * NOTE: This transition check should only be used if there is only
   * one transition from the current state.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   *
   * @return string
   *   A string that will cause the 'default' transition.
   */
  public function emptyTransition(WipContextInterface $wip_context);

  /**
   * The default failure state in the FSM.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   * @param \Exception $exception
   *   The exception that caused the failure (assuming the failure was caused
   *   by an exception.
   */
  public function failure(WipContextInterface $wip_context, \Exception $exception = NULL);

  /**
   * The default terminate state in the FSM.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface is the interface through which a Wip object
   *   interacts with its runtime environment and provides a means of sharing
   *   data between a state method and a transition method.
   */
  public function terminate(WipContextInterface $wip_context);

  /**
   * Lifecycle method that is called when this Wip object is added to the pool.
   */
  public function onAdd();

  /**
   * Lifecycle method that is called when this Wip object is started.
   */
  public function onStart();

  /**
   * Lifecycle method that is called when this wip object moves to in process.
   */
  public function onProcess();

  /**
   * Lifecycle method that is called when moving from in process to wait.
   */
  public function onWait();

  /**
   * Lifecycle method that is called upon successful completion.
   */
  public function onFinish();

  /**
   * Lifecycle method that is called upon termination of the Wip object.
   */
  public function onTerminate();

  /**
   * Lifecycle method that is called when this Wip object is restarted.
   */
  public function onRestart();

  /**
   * Lifecycle method that is called when this Wip object fails to complete with a user error.
   */
  public function onUserError();

  /**
   * Lifecycle method that is called when this Wip object fails to complete with a system error.
   */
  public function onSystemError();

  /**
   * Lifecycle method that is called when this Wip object fails to complete.
   */
  public function onFail();

  /**
   * Called when a signal has been received.
   *
   * @param SignalInterface $signal
   *   The signal.
   */
  public function onSignal(SignalInterface $signal);

  /**
   * Lifecycle method that is called before serialization of this instance.
   */
  public function onSerialize();

  /**
   * Lifecycle method that is called after deserialization of this instance.
   */
  public function onDeserialize();

  /**
   * Adds an include file that will be loaded before the FSM is deserialized.
   *
   * The use of this method should be rare; it is needed in case PHP code
   * that this instance requires in order to deserialize without error resides
   * in files that are not auto-loaded and are not automatically included before
   * this instance is deserialized.
   *
   * @param string $docroot
   *   The path to the container docroot.
   * @param string $path
   *   The path to the include, relative to the container docroot.
   *
   * @throws \InvalidArgumentException
   *   If the docroot or path are empty or a type other than string.
   */
  public function addInclude($docroot, $path);

  /**
   * Returns the set of required include files for deserialization.
   *
   * @return IncludeFileInterface[]
   *   An array of include files that must be loaded before this object is
   *   deserialized.
   */
  public function getIncludes();

  /**
   * Returns the ID associated with this Wip instance.
   *
   * @return int
   *   The ID associated with this Wip instance.
   */
  public function getId();

  /**
   * Sets the ID associated with this Wip instance.
   *
   * @param int $id
   *   The ID.
   */
  public function setId($id);

  /**
   * Returns the UUID of the user who created this Wip instance.
   *
   * @return string
   *   The UUID of the user associated with this Wip instance.
   */
  public function getUuid();

  /**
   * Sets the UUID of the user who created this Wip instance.
   *
   * @param string $uuid
   *   The UUID of the user who created this Wip instance.
   */
  public function setUuid($uuid);

  /**
   * Returns the ID that uniquely identifies the work this Wip object will do.
   *
   * Note that this work ID can be generated any time after this Wip object has
   * been configured.
   *
   * The work ID must remain constant throughout the life of the Wip object.
   *
   * @return string
   *   The work ID.
   */
  public function getWorkId();

  /**
   * Generates a work ID.
   *
   * Two or more work items that are considered identical in terms of what they
   * are meant to accomplish must return identical work IDs if they are to be
   * blocked from running simultaneously.
   *
   * Typically this ID will be generated by concatenating the class name and
   * any number of elements of the configuration that uniquely identify the
   * work being done.  Because this can be a string of significant length,
   * use sha1 to create a fingerprint of the work.
   *
   * @return string
   *   The value that uniquely identifies a particular workload.
   */
  public function generateWorkId();

  /**
   * Sets the logger this Wip instance will use.
   *
   * @param WipLogInterface $wip_log
   *   The logger.
   */
  public function setWipLog(WipLogInterface $wip_log);

  /**
   * Gets the logger this Wip instance will use.
   *
   * @return WipLogInterface
   *   The logger
   */
  public function getWipLog();

  /**
   * Sets the log level that identifies log messages that will not be pruned.
   *
   * Any log message logged at a less important level than the specified level
   * will be pruned upon successful completion of this Wip instance.
   *
   * @param int $level
   *   The log level.
   *
   * @throws \InvalidArgumentException
   *   If the specified log level is not valid.
   */
  public function setLogLevel($level);

  /**
   * Gets the log level that identifies log messages that will not be pruned.
   *
   * Any log message logged at a less important level than the specified level
   * will be pruned upon successful completion of this Wip instance.
   *
   * @return int
   *   The log level.
   */
  public function getLogLevel();

  /**
   * Logs the specified message.
   *
   * @param int $level
   *   The WipLogLevel value describing the level of this log message.
   * @param string $message
   *   The message to log.
   * @param bool $user_readable
   *   Whether the log message is user-readable.
   *
   * @return bool
   *   TRUE if the log message was logged successfully; FALSE otherwise.
   */
  public function log($level, $message, $user_readable = FALSE);

  /**
   * Log one of several log messages depending on the level.
   *
   * You do not have to specify a log message for every level.  If you
   * specify more than one, all log messages configured for logging will
   * be concatenated together in order of log level.
   *
   * Example:
   * ```` php
   * multiLog(
   *   WipLogLevel::ERROR, 'An error occurred',
   *   WipLogLevel::TRACE, ' - on line 43',
   *   ...
   * );
   * ````
   * This example will log the message 'An error occurred - on line 43' at the
   * 'ERROR' log level, assuming that $max_log_level is >= WipLogLevel::ERROR.
   *
   * @param int $level
   *   The WipLogLevel value describing the level of the associated log message.
   * @param string $message
   *   This message is paired to the preceding log level.
   */
  public function multiLog($level, $message);

  /**
   * Returns the API to send notifications.
   *
   * @return NotificationInterface
   *   An implementation of NotificationInterface.
   */
  public function getNotifier();

  /**
   * Returns the API used to work with Wip objects.
   *
   * @return WipTaskInterface
   *   The WipTaskInterface instance.
   */
  public function getWipApi();

  /**
   * Returns the API used to work with Ssh.
   *
   * @return WipSshInterface
   *   The API.
   */
  public function getSshApi();

  /**
   * Returns the API used to work with Acquia's Cloud API.
   *
   * @return WipAcquiaCloudInterface
   *   The API.
   */
  public function getAcquiaCloudApi();

  /**
   * Returns the API used to work with Containers.
   *
   * @return WipContainerInterface
   *   The container API.
   */
  public function getContainerApi();

  /**
   * Returns the status of Wip children associated with the specified context.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface instance where the child Wip IDs are stored.
   *
   * @return string
   *   'success' - All tasks have completed successfully.
   *   'wait' - One or more tasks are still running.
   *   'uninitialized' - No child Wip objects have been added to the context.
   *   'fail' - At least one task failed.
   */
  public function checkWipTaskStatus(WipContextInterface $wip_context);

  /**
   * Returns the status of Ssh results and processes in the specified context.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface instance where the Ssh results and/or processes
   *   are stored.
   *
   * @return string
   *   'success' - All Ssh calls were completed successfully.
   *   'wait' - One or more Ssh processes are still running.
   *   'uninitialized' - No Ssh results or processes have been added.
   *   'fail' - At least one Ssh call failed.
   */
  public function checkSshStatus(WipContextInterface $wip_context);

  /**
   * Returns the status of Acquia Cloud task results and processes.
   *
   * @param WipContextInterface $wip_context
   *   The WipContextInterface instance where the Ssh results and/or processes
   *   are stored.
   *
   * @return string
   *   'success' - All cloud tasks were completed successfully.
   *   'wait' - One or more cloud processes are still running.
   *   'uninitialized' - No cloud results or processes have been added.
   *   'fail' - At least one cloud task failed.
   */
  public function checkAcquiaCloudTaskStatus(WipContextInterface $wip_context);

  /**
   * Adds the specified callback.
   *
   * The callback is a request for a signal that generally occurs upon
   * completion of this Wip object instance. The signal is an efficient means of
   * indicating a particular Wip object completed, which is used to short
   * circuit fail-safe polling. Fail-safe polling is used as a backup mechanism
   * only and prevents failure in cases where the signal did not get sent or
   * failed to be delivered.
   *
   * @param CallbackInterface $callback
   *   The callback.
   */
  public function addCallback(CallbackInterface $callback);

  /**
   * Adds the specified data to each callback.
   *
   * @param object $data
   *   The data to add to each callback.
   */
  public function addCallbackData($data);

  /**
   * Gets the callback data.
   *
   * @return object
   *   The callback data.
   */
  public function getCallbackData();

  /**
   * Gets the iterator associated with this instance.
   *
   * @return StateTableIteratorInterface
   *   The iterator.
   */
  public function getIterator();

  /**
   * Sets the iterator associated with this instance.
   *
   * @param StateTableIteratorInterface $iterator
   *   The iterator.
   */
  public function setIterator(StateTableIteratorInterface $iterator);

  /**
   * Sets the ParameterDocument instance that controls behavior.
   *
   * @param ParameterDocument $document
   *   The document.
   */
  public function setParameterDocument(ParameterDocument $document);

  /**
   * Gets the ParameterDocument.
   *
   * @return ParameterDocument
   *   The document.
   */
  public function getParameterDocument();

  /**
   * Sets the options.
   *
   * @param object $options
   *   The options.
   */
  public function setOptions($options);

  /**
   * Gets the options.
   *
   * @return object
   *   The options.
   */
  public function getOptions();

  /**
   * Sets the task configuration.
   *
   * Note that not all fields are required to be populated in the specified
   * configuration.
   *
   * @param WipTaskConfig $configuration
   *   The configuration.
   */
  public function setWipTaskConfig(WipTaskConfig $configuration);

  /**
   * Sets the task exit message, which will be applied upon completion.
   *
   * This exit message will be applied to the task metadata and logged with the
   * appropriate log level upon exit.  This provides a means of setting an exit
   * message within code that has enough context to describe a potential failure
   * before that failure occurs and have the log message and exit message only
   * apply at that point.
   *
   * @param \Acquia\Wip\ExitMessageInterface $message
   *   The exit message.
   */
  public function setExitMessage(ExitMessageInterface $message);

  /**
   * Gets the task exit message.
   *
   * @return ExitMessageInterface
   *   The exit message.
   */
  public function getExitMessage();

  /**
   * Sets the exit code.
   *
   * @param int $exit_code
   *   A valid exit code value, defined in IteratorStatus as class constants.
   *
   * @throws \InvalidArgumentException
   *   If the exit code is not valid.
   */
  public function setExitCode($exit_code);

  /**
   * Gets the exit code.
   *
   * @return int
   *   The exit code.
   */
  public function getExitCode();

  /**
   * Gets the recordings of any Wip objects controlled by this one.
   *
   * @return RecordingInterface[]
   *   The transcript.
   */
  public function getAllRecordings();

  /**
   * Sets the simulation mode for this Wip instance.
   *
   * Simulation mode prevents the state and transition methods from being called
   * so that the state table itself can be exercised.
   *
   * @param int $simulation_mode
   *   Optional.  One of SIMULATION_DISABLED, SIMULATION_SCRIPT, or
   *   SIMULATION_RANDOM, indicating whether a simulation is being run and what
   *   type of simulation.  If not specified, simulation will be disabled.
   * @param SimulationScriptInterpreter $simulation
   *   Optional.
   */
  public function setSimulationMode(
    $simulation_mode = StateTableIterator::SIMULATION_DISABLED,
    SimulationScriptInterpreter $simulation = NULL
  );

  /**
   * Lifecycle method that is called when a status change occurs.
   *
   * This is not triggered by status changes to "processing" or "waiting".
   *
   * @param TaskInterface $task
   *   The task containing the status changes.
   */
  public function onStatusChange(TaskInterface $task);

  /**
   * Gets the class version.
   *
   * The class version indicates the version associated with the Wip object
   * class. The class version is associated with the sourcecode available at
   * the time this method is called.
   *
   * @return int
   *   The class version.
   */
  public static function getClassVersion();

  /**
   * Gets the version information for the specified class.
   *
   * @param string $class_name
   *   The name of the class to fetch the class version for. This can be the
   *   fully-qualified class name or the short class name.
   *
   * @return WipVersionElement
   *   The version element representing the version of the specified class.
   */
  public static function getClassVersionForClass($class_name);

  /**
   * Gets the versions for each class in the class structure.
   *
   * @return WipVersionElement[]
   *   The version elements, one for each class.
   */
  public static function getClassVersions();

  /**
   * Gets the instance version.
   *
   * The instance version indicates the version associated with this particular
   * Wip object. The instance version is independent of the source code
   * available at the time this method is called.
   *
   * @param string $class_name
   *   The name of the class to get the version info for.
   *
   * @return WipVersionElement
   *   The instance version.
   */
  public function getInstanceVersion($class_name);

  /**
   * Finds the version entry for the specified class.
   *
   * @param WipVersionElement[] $versions
   *   The set of versions to search through.
   * @param string $class_name
   *   The fully-qualified or short class name identifying the desired version
   *   element.
   *
   * @return WipVersionElement
   *   The version element.
   */
  public static function findVersion($versions, $class_name);

  /**
   * Gets the instance version for this class and every superclass.
   *
   * @return WipVersionElement[]
   *   An ordered array of version information, starting with the superclass
   *   and ending with the current class.
   */
  public function getInstanceVersions();

  /**
   * Initializes the instanceVersion to the specified value.
   *
   * @param string $class_name
   *   The name of the class for which the version should be adjusted.
   * @param int $version
   *   The new version.
   */
  public function setInstanceVersion($class_name, $version);

  /**
   * Retrieves the timestamp of the last write.
   *
   * @return int
   *   The timestamp of the last write.
   */
  public function getTimestamp();

  /**
   * Updates the timestamp to indicate the time of the last write.
   *
   * @param int $timestamp
   *   The timestamp of the last write.
   */
  public function setTimestamp($timestamp);

}
