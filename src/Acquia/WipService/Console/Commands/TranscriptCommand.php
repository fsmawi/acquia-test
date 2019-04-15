<?php

namespace Acquia\WipService\Console\Commands;

use Acquia\WipIntegrations\DoctrineORM\WipLogStore;
use Acquia\WipService\Console\WipConsoleCommand;
use Acquia\Wip\RecordingInterface;
use Acquia\Wip\Runtime\WipPool;
use Acquia\Wip\Storage\BasicWipPoolStore;
use Acquia\Wip\WipLogEntryInterface;
use Acquia\Wip\WipLogLevel;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Defines a console command for displaying log messages.
 */
class TranscriptCommand extends WipConsoleCommand {

  const ENTRY_TYPE_OPEN_DISPATCH = 'openDispatch';
  const ENTRY_TYPE_CLOSE_DISPATCH = 'closeDispatch';
  const ENTRY_TYPE_TRANSCRIPT = 'transcript';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $usage = 'The transcript command prints the transcript for the specified object.';
    $this->setName('transcript')
      ->setHelp($usage)
      ->setDescription('View the transcript.')
      ->addArgument(
        'object-id',
        InputArgument::REQUIRED,
        'The object ID for which the transcript will be displayed.'
      )
      ->addOption(
        'dispatch',
        NULL,
        InputOption::VALUE_NONE,
        'Includes dispatch information and checks for dispatch errors.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $result = 0;
    $object_id = $input->getArgument('object-id');

    // Numeric values passed on the command line are strings.
    if (is_numeric($object_id)) {
      $object_id = (int) $object_id;
    }

    try {
      $wip_pool = new WipPool();
      $task = $wip_pool->getTask($object_id);
      $task->loadWipIterator();
      $iterator = $task->getWipIterator();
      $recordings = $iterator->getRecordings();
      if (!empty($recordings)) {
        $errors = [];
        /** @var RecordingInterface $recording */
        $recording = reset($recordings);
        $recording_elements = explode("\n", $recording->getTranscript());

        $header_elements = array_slice($recording_elements, 0, 3);
        $transcript_entries = array_slice($recording_elements, 3);

        $transcript = $this->getTranscript(
          $object_id,
          $transcript_entries,
          $errors,
          $input->getOption('dispatch')
        );
        $output->writeln(
          sprintf('Transcript for task %d:', $object_id)
        );
        $output->writeln(sprintf("<info>%s</info>\n", implode("\n", $header_elements)));

        foreach ($transcript as $element) {
          $output->write(sprintf('%s ', $element->timeString));
          if ($element->type === self::ENTRY_TYPE_TRANSCRIPT) {
            $output->writeln(sprintf('<comment>%s</comment>', $element->log));
          } elseif ($element->type === self::ENTRY_TYPE_OPEN_DISPATCH) {
            $output->writeln(sprintf('<info>%s</info>', $element->log));
          } else {
            $output->writeln($element->log);
          }
        }

        if (count($errors) > 0) {
          $output->writeln(
            sprintf(
              "\n<error>---- DISPATCH ERRORS FOUND ----\n%s</error>",
              implode("\n", $errors)
            )
          );
          $result = 1;
        }
      } else {
        $output->writeln(
          sprintf('<error>No transcript is available for task %d.</error>', $object_id)
        );
      }
    } catch (\Exception $e) {
      $messages = array();
      $messages[] = sprintf(
        '<error>Unable to load the transcript for task %d.</error>',
        $object_id
      );
      $messages[] = sprintf('<error>%s</error>', $e->getMessage());
      $output->writeln($messages);
    }
    return $result;
  }

  /**
   * Returns a printable transcript for the specified task.
   *
   * @param int $object_id
   *   The task ID.
   * @param string[] $transcript
   *   The transcript from the object's recording.
   * @param string[] $errors
   *   Any errors found will be appended to this array.
   * @param bool $show_dispatch
   *   Indicates whether dispatch events should be shown.
   *
   * @return object[]
   *   The transcript.
   */
  private function getTranscript($object_id, $transcript, &$errors, $show_dispatch = FALSE) {
    $result = $this->transcriptStringsToObjects($transcript, $errors);
    if ($show_dispatch === TRUE) {
      $dispatch_entries = $this->getInternalDispatchLogs($object_id);
      $result = $this->interweave(
        $this->transcriptStringsToObjects($dispatch_entries, $errors),
        $result,
        $errors
      );
    }
    return $result;
  }

  /**
   * Gets the internal dispatch logs for the specified task.
   *
   * @param int $task_id
   *   The task ID.
   *
   * @return string[]
   *   The internal dispatch logs.
   */
  private function getInternalDispatchLogs($task_id) {
    $result = array();
    $wip_pool = BasicWipPoolStore::getWipPoolStore($this->dependencyManager);
    $task = $wip_pool->get($task_id);
    $started = $task->getStartTimestamp();
    $log_store = WipLogStore::getWipLogStore($this->dependencyManager);
    $regexp = sprintf('^INTERNAL:.*task %d on thread [[:digit:]]+.', $task_id);
    $entries = $log_store->loadRegex(
      $task_id,
      $regexp,
      'ACS',
      WipLogLevel::INFO,
      WipLogLevel::FATAL,
      FALSE
    );

    /** @var WipLogEntryInterface $entry */
    foreach ($entries as $entry) {
      $result[] = sprintf(
        "%s  %s",
        $this->formatElapsedTime($entry->getTimestamp(), $started),
        $entry->getMessage()
      );
    }
    return $result;
  }

  /**
   * Formats the difference between the specified timestamp and the start time.
   *
   * @param int $timestamp
   *   The time.
   * @param int $start
   *   Optional. The start time. If not provided, the timestamp is used as the
   *   elapsed time.
   *
   * @return string
   *   The formatted elapsed time.
   *
   * @throws \InvalidArgumentException
   *   If the timestamp is not an integer.
   */
  private function formatElapsedTime($timestamp, $start = 0) {
    if (is_int($timestamp)) {
      $result = '';
      if (NULL !== $start) {
        $elapsed_seconds = $timestamp - $start;
        $time = $this->breakdownTime($elapsed_seconds);
        $result = sprintf('%02d:%02d:%02d', $time->hours, $time->minutes, $time->seconds);
      }
      return $result;
    } else {
      throw new \InvalidArgumentException('The timestamp must be an integer.');
    }
  }

  /**
   * Extracts the time data from the specified entry.
   *
   * @param string $entry
   *   The transcript entry.
   *
   * @return int
   *   The time measured from the point at which the task started, measured in
   *   seconds.
   *
   * @throws \Exception
   *   If the time cannot be interpreted from the specified entry.
   */
  private function getTime($entry) {
    if (!is_string($entry)) {
      throw new \InvalidArgumentException(
        sprintf(
          'The "entry" parameter must be a string. Given %s.',
          get_class($entry)
        )
      );
    }
    if (1 === preg_match('/^([0-9]+):([0-9]+):([0-9]+) /', $entry, $matches)) {
      $hours = intval($matches[1]);
      $minutes = intval($matches[2]);
      $seconds = intval($matches[3]);
      $result = $hours * 3600 + $minutes * 60 + $seconds;
    } else {
      throw new \DomainException(sprintf('Failed to parse the time from entry "%s".', $entry));
    }
    return $result;
  }

  /**
   * Extracts the thread ID from the specified log message.
   *
   * @param string $log
   *   A log entry from the transcript.
   *
   * @return int
   *   The thread ID.
   *
   * @throws \DomainException
   *   If the log entry is not a thread dispatch.
   */
  private function getThreadId($log) {
    if (1 === preg_match('/INTERNAL: .*task [0-9]+ on thread ([0-9]+)./', $log, $matches)) {
      return intval($matches[1]);
    } else {
      throw new \DomainException('Log entry is not a thread dispatch: "%s".', $log);
    }
  }

  /**
   * Determines whether the specified log message is an open or close dispatch.
   *
   * @param string $log
   *   The log message.
   *
   * @return bool
   *   TRUE if the log message is an open dispatch; FALSE if it is a close
   *   dispatch.
   *
   * @throws \DomainException
   *   If the specified log message is not a dispatch at all.
   */
  private function isOpenDispatch($log) {
    if (1 === preg_match('/INTERNAL: Dispatching task [0-9]+ on thread [0-9]+.$/', $log)) {
      $result = TRUE;
    } elseif (1 === preg_match('/INTERNAL: WIP dispatch completed for task [0-9]+ on thread [0-9]+./', $log)) {
      $result = FALSE;
    } else {
      throw new \DomainException(
        sprintf('Log message is not an open or close dispatch message: "%s".', $log)
      );
    }
    return $result;
  }

  /**
   * Interweaves dispatch logs and transitions logs by time.
   *
   * @param object[] $dispatch_entries
   *   An array of dispatch entries.
   * @param object[] $transition_entries
   *   An array of transition entries.
   * @param string[] $errors
   *   Any errors encountered during the interweave will be appended.
   *
   * @return array
   *   A sorted array of transitions and dispatches.
   */
  private function interweave($dispatch_entries, $transition_entries, &$errors) {
    $result = [];
    $transition_index = 0;
    $transition_count = count($transition_entries);
    $dispatch_count = count($dispatch_entries);
    $dispatch_index = 0;
    $dispatch_stack = array();

    while ($transition_index < $transition_count || $dispatch_index < $dispatch_count) {
      if (count($transition_entries) > $transition_index) {
        $transition_entry = $transition_entries[$transition_index];
      } else {
        $transition_entry = NULL;
      }
      if (count($dispatch_entries) > $dispatch_index) {
        $dispatch_entry = $dispatch_entries[$dispatch_index];
      } else {
        $dispatch_entry = NULL;
      }

      $compare_result = $this->compareTranscriptEntries(
        $dispatch_entry,
        $transition_entry,
        count($dispatch_stack) > 0
      );
      if ($compare_result > 0) {
        $result[] = $transition_entry;
        $transition_index++;
        if (count($dispatch_stack) === 0) {
          $errors[] = sprintf(
            'Transition occurred outside of an open dispatch thread at %s.',
            $transition_entry->timeString
          );
        }
      } elseif ($compare_result < 0) {
        $result[] = $dispatch_entry;
        $dispatch_index++;
        if ($dispatch_entry->type === self::ENTRY_TYPE_OPEN_DISPATCH) {
          $open_dispatch_count = array_push($dispatch_stack, $dispatch_entry);
          if ($open_dispatch_count !== 1) {
            $errors[] = $this->dispatchStackErrorDetail(
              'Simultaneous live dispatches detected:',
              $dispatch_stack
            );
          }
        } else {
          if (count($dispatch_stack) > 0) {
            $open_dispatch = array_pop($dispatch_stack);
            if ($open_dispatch->thread !== $dispatch_entry->thread) {
              $errors[] = sprintf(
                'Attempting to close dispatch thread %d which does not match open thread %d.',
                $dispatch_entry->thread,
                $open_dispatch->thread
              );
            }
          } else {
            $errors[] = sprintf(
              'Attempting to close dispatch thread %d with no currently open dispatch.',
              $dispatch_entry->thread
            );
          }
        }
      }
    }

    if (!empty($dispatch_stack)) {
      $errors[] = $this->dispatchStackErrorDetail(
        'Completed task has open dispatch threads:',
        $dispatch_stack
      );
    }
    return $result;
  }

  /**
   * Compares two transcript entries.
   *
   * @param object $dispatch_entry
   *   The dispatch entry.
   * @param object $transition_entry
   *   The transition entry.
   * @param bool $has_open_dispatch
   *   TRUE if there is currently an open dispatch; FALSE otherwise.
   *
   * @return int
   *   A negative number if the transition entry should precede the dispatch
   *   entry; a positive number otherwise.
   */
  private function compareTranscriptEntries($dispatch_entry, $transition_entry, $has_open_dispatch) {
    if ($transition_entry === NULL) {
      $result = -1;
    } elseif ($dispatch_entry === NULL) {
      $result = 1;
    } elseif ($transition_entry->time === $dispatch_entry->time) {
      if ($dispatch_entry->type === self::ENTRY_TYPE_OPEN_DISPATCH) {
        if (!$has_open_dispatch) {
          $result = -1;
        } else {
          $result = 1;
        }
      } else {
        // Close dispatch.
        $result = 1;
      }
    } else {
      $result = $dispatch_entry->time - $transition_entry->time;
    }
    return $result;
  }

  /**
   * Breaks elapsed seconds into hours, minutes, seconds.
   *
   * @param int $seconds
   *   The number of seconds.
   *
   * @return object
   *   An object containing hours, minutes, seconds.
   *
   * @throws \InvalidArgumentException
   *   If the $seconds argument is not an integer.
   */
  private function breakdownTime($seconds) {
    if (is_int($seconds)) {
      $remaining_time = max($seconds, 0);
      $result = new \stdClass();
      $result->seconds = $remaining_time % 60;
      $remaining_time = ($remaining_time - $result->seconds) / 60;
      $result->minutes = $remaining_time % 60;
      $remaining_time = ($remaining_time - $result->minutes) / 60;
      $result->hours = $remaining_time;
      return $result;
    } else {
      throw new \InvalidArgumentException(
        'The number of seconds must be an integer.'
      );
    }
  }

  /**
   * Creates a message for the specified dispatch stack error.
   *
   * @param string $error_message
   *   The error message.
   * @param object[] $dispatch_stack
   *   The current dispatch stack.
   *
   * @return string
   *   The error message.
   */
  private function dispatchStackErrorDetail($error_message, $dispatch_stack) {
    $detail = [];
    foreach ($dispatch_stack as $entry) {
      $detail[] = sprintf("\tThread %d started at %s.", $entry->thread, $entry->timeString);
    }
    return sprintf("%s\n%s", $error_message, implode("\n", $detail));
  }

  /**
   * Returns objects corresponding to each of the specified log strings.
   *
   * @param string[] $strings
   *   The ordered set of log entries.
   * @param string[] $errors
   *   Any errors encountered during the interweave will be appended.
   *
   * @return object[]
   *   The ordered set of log entries in object form.
   */
  private function transcriptStringsToObjects($strings, &$errors) {
    $result = array();
    foreach ($strings as $log) {
      $entry = new \stdClass();
      try {
        $entry->time = $this->getTime($log);
        $entry->timeString = $this->formatElapsedTime($entry->time);
        $entry->log = substr($log, strlen($entry->timeString) + 1);
      } catch (\Exception $e) {
        $errors[] = sprintf('Failed to get the time from transition string "%s".', $log);
      }
      try {
        if ($this->isOpenDispatch($log)) {
          $entry->type = self::ENTRY_TYPE_OPEN_DISPATCH;
        } else {
          $entry->type = self::ENTRY_TYPE_CLOSE_DISPATCH;
        }
        $entry->thread = $this->getThreadId($log);
      } catch (\Exception $e) {
        // This log entry doesn't contain a dispatch at all.
        $entry->type = self::ENTRY_TYPE_TRANSCRIPT;
      }
      $result[] = $entry;
    }
    return $result;
  }

}
