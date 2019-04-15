<?php

namespace Acquia\Wip\Iterators\BasicIterator;

use Acquia\Wip\VisualizationInterface;

/**
 * Provides a visual representation of a basic Wip State Machine graph.
 *
 * Currently, the output image is in PNG format.
 */
class DotVisualization implements VisualizationInterface {

  /**
   * The default font size for rendering the title.
   */
  const TITLE_FONT_SIZE = 18;

  /**
   * The default font size for rendering a node label.
   */
  const NODE_FONT_SIZE = 14;

  /**
   * The default font size for rendering an edge label.
   */
  const EDGE_FONT_SIZE = 16;

  /**
   * The default font size for rendering edge details.
   */
  const DETAIL_FONT_SIZE = 12;

  /**
   * The default font size for rendering line numbers.
   */
  const LINE_NUMBER_FONT_SIZE = 10;

  /**
   * The default font size for rendering timer names.
   */
  const TIMER_FONT_SIZE = 12;

  /**
   * The state table.
   *
   * @var string
   */
  private $stateTable;

  /**
   * The title that will be displayed on the graph.
   *
   * @var string
   */
  private $title = '';

  /**
   * The path to the 'dot' utility.
   *
   * @var string
   */
  private $dotPath = 'dot';

  /**
   * The name of the dot file that will be written.
   *
   * @var string
   */
  private $dotFile = '';

  /**
   * The state machine.
   *
   * @var StateMachine
   */
  private $stateMachine = NULL;

  /**
   * The font size to be used for the title.
   *
   * @var int
   */
  private $titleFontSize = self::TITLE_FONT_SIZE;

  /**
   * The font size to be used for node labels.
   *
   * @var int
   */
  private $nodeFontSize = self::NODE_FONT_SIZE;

  /**
   * The font size to be used for edge labels.
   *
   * @var int
   */
  private $edgeFontSize = self::EDGE_FONT_SIZE;

  /**
   * The font size to be used for node and edge details.
   *
   * @var int
   */
  private $detailFontSize = self::DETAIL_FONT_SIZE;

  /**
   * The font size for line numbers.
   *
   * @var int
   */
  private $lineNumberFontSize = self::LINE_NUMBER_FONT_SIZE;

  /**
   * The font size for timer names.
   *
   * @var int
   */
  private $timerFontSize = self::TIMER_FONT_SIZE;

  /**
   * The ordered set of timer colors.
   *
   * @var string[]
   */
  private $timerColors = array(
    'yellow',
    'purple',
    'blue',
    'orange',
    'coral',
    'cornflowerblue',
    'gold',
    'cyan',
    'azure',
    'antiquewhite',
    'orangered',
    'pink',
    'sandybrown',
    'yellowgreen',
    'lavender',
    'teal',
  );

  /**
   * Creates a new instance of DotVisualization with the specified title.
   *
   * @param string $title
   *   Optional. The title.
   *
   * @throws \InvalidArgumentException
   *   If the title is not a string.
   */
  public function __construct($title = '') {
    if (!is_string($title)) {
      throw new \InvalidArgumentException('The title must be a string.');
    }
    $this->title = $title;
  }

  /**
   * Sets the state table that will be visualized.
   *
   * @param string $state_table
   *   The state table.
   */
  public function setStateTable($state_table) {
    $this->stateTable = $state_table;
  }

  /**
   * Causes the dot utility to render the state table in graph form.
   *
   * @param string $filename
   *   Optional. The filename of the resulting image file.
   *
   * @return string
   *   The output.
   *
   * @throws \Exception
   *   If no state table has been set.
   */
  public function visualize($filename = NULL) {
    if (!isset($this->stateTable)) {
      throw new \RuntimeException(
        'No state table has been set.  Ensure setStateTable() is called before calling visualize().'
      );
    }
    $parser = new StateTableParser($this->stateTable);
    $this->stateMachine = $parser->parse();
    // @todo - can we add stdout to the out pipes?
    $outfile = !empty($filename) ? '-o ' . escapeshellarg($filename) : '';
    $command = sprintf('%s -Tpng %s', $this->dotPath, $outfile);
    $proc = $this->getDotProc($command);
    $output_pipes = array('graphviz' => $proc['stdin']);
    if (!empty($this->dotFile)) {
      $output_pipes['dotfile'] = fopen($this->dotFile, 'w');
    }
    // Setup the output to pipe to graphviz, optionally dumping the graph
    // definition also.
    $this->writeDotFile(NULL, $output_pipes);
    // General wrapper: directed graph.
    $this->writeDotFile("digraph WIP {\n");
    $this->writeImageProperties();
    $this->writeNodeProperties();
    $this->writeNodeRanking();
    $this->writeLabels();
    $this->writeTransitions();
    $this->writeDotFile("}\n");
    fclose($proc['stdin']);
    $output = stream_get_contents($proc['stdout']);
    $stderr = $this->getStdErr($proc);
    if (!empty($stderr)) {
      throw new \RuntimeException($stderr);
    }
    fclose($proc['stdout']);
    fclose($proc['stderr']);
    proc_close($proc['proc']);
    if (!empty($output_pipes['dotfile'])) {
      fclose($output_pipes['dotfile']);
    }
    // @TODO - handle stderr?
    return $output;
  }

  /**
   * Opens and returns pipes to a graphviz dot process.
   *
   * @param string $path
   *   The path to the graphviz dot command.
   *
   * @return array
   *   The array of pipes.
   *
   * @throws \RuntimeException
   *   If the dot process could not be started.
   */
  private function getDotProc($path = 'dot') {
    $result = NULL;
    $descriptor_spec = array(
      0 => array("pipe", "r"),
      1 => array("pipe", "w"),
      2 => array("pipe", "w"),
    );
    $process = proc_open($path, $descriptor_spec, $pipes);
    // If the command is bad, it can take a bit of time to detect the error.
    // Move forward, and check that the result is ok at a later time.
    if (is_resource($process)) {
      $result = array(
        'stdin' => $pipes[0],
        'stdout' => $pipes[1],
        'stderr' => $pipes[2],
        'proc' => $process,
      );
    }
    return $result;
  }

  /**
   * Returns the contents of stderr.
   *
   * @param array $pipes
   *   The pipes array.
   *
   * @return string
   *   The stderr contents.
   */
  private function getStdErr($pipes) {
    $result = '';
    stream_set_blocking($pipes['stderr'], 0);
    if ($err = stream_get_contents($pipes['stderr'])) {
      $result = $err;
    }
    return $result;
  }

  /**
   * Writes a string output to multiple pipes.
   *
   * This function is used for writing to a variable number of output pipes.  An
   * initial call to this function specifies the pipes to use for all subsequent
   * calls.  Subsequent calls will then write output to the pipes previously
   * specified.
   *
   * @param string $output
   *   A string of output, or NULL if resetting the output pipes.
   * @param array $output_pipes
   *   (Optional).  If set, this will override the output pipes that subsequent
   *   calls to this function will write to.
   */
  private function writeDotFile($output = NULL, $output_pipes = array()) {
    static $pipes = array();
    if (!isset($output) && !empty($output_pipes)) {
      $pipes = $output_pipes;
    }
    if (isset($output)) {
      foreach ($pipes as $pipe) {
        fwrite($pipe, $output);
      }
    }
  }

  /**
   * Sets the path to the dot utility.
   *
   * @param string $dot_path
   *   The path.
   */
  public function setDotPath($dot_path) {
    $this->dotPath = $dot_path;
  }

  /**
   * The filename for the dot file used to generate a graph.
   *
   * @param string $dot_file
   *   The filename.
   */
  public function setDotFile($dot_file) {
    $this->dotFile = $dot_file;
  }

  /**
   * Sets the size of the font used to render the graph title.
   *
   * @param int $font_size
   *   The size of the font.
   *
   * @throws \InvalidArgumentException
   *   If the specified size is not an integer larger than zero.
   */
  public function setTitleFontSize($font_size) {
    if (!is_int($font_size) || $font_size <= 0) {
      throw new \InvalidArgumentException('The font size must be an integer that is greater than zero.');
    }
    $this->titleFontSize = $font_size;
  }

  /**
   * Gets the font size used to render the graph title.
   *
   * @return int
   *   The font size.
   */
  public function getTitleFontSize() {
    return $this->titleFontSize;
  }

  /**
   * Sets the size of the font used to render the node label.
   *
   * @param int $font_size
   *   The size of the font.
   *
   * @throws \InvalidArgumentException
   *   If the specified size is not an integer larger than zero.
   */
  public function setNodeFontSize($font_size) {
    if (!is_int($font_size) || $font_size <= 0) {
      throw new \InvalidArgumentException('The font size must be an integer that is greater than zero.');
    }
    $this->nodeFontSize = $font_size;
  }

  /**
   * Gets the font size used to render the node label.
   *
   * @return int
   *   The font size.
   */
  public function getNodeFontSize() {
    return $this->nodeFontSize;
  }

  /**
   * Sets the size of the font used to render the edge label.
   *
   * @param int $font_size
   *   The size of the font.
   *
   * @throws \InvalidArgumentException
   *   If the specified size is not an integer larger than zero.
   */
  public function setEdgeFontSize($font_size) {
    if (!is_int($font_size) || $font_size <= 0) {
      throw new \InvalidArgumentException('The font size must be an integer that is greater than zero.');
    }
    $this->edgeFontSize = $font_size;
  }

  /**
   * Gets the font size used to render the edge label.
   *
   * @return int
   *   The font size.
   */
  public function getEdgeFontSize() {
    return $this->edgeFontSize;
  }

  /**
   * Sets the size of the font used to render detailed information.
   *
   * @param int $font_size
   *   The size of the font.
   *
   * @throws \InvalidArgumentException
   *   If the specified size is not an integer larger than zero.
   */
  public function setDetailFontSize($font_size) {
    if (!is_int($font_size) || $font_size <= 0) {
      throw new \InvalidArgumentException('The font size must be an integer that is greater than zero.');
    }
    $this->detailFontSize = $font_size;
  }

  /**
   * Gets the font size used to render detailed information.
   *
   * @return int
   *   The font size.
   */
  public function getDetailFontSize() {
    return $this->detailFontSize;
  }

  /**
   * Sets the size of the font used to render line numbers.
   *
   * @param int $font_size
   *   The size of the font.
   *
   * @throws \InvalidArgumentException
   *   If the specified size is not an integer larger than zero.
   */
  public function setLineNumberFontSize($font_size) {
    if (!is_int($font_size) || $font_size <= 0) {
      throw new \InvalidArgumentException('The font size must be an integer that is greater than zero.');
    }
    $this->lineNumberFontSize = $font_size;
  }

  /**
   * Gets the font size used to render line numbers.
   *
   * @return int
   *   The font size.
   */
  public function getLineNumberFontSize() {
    return $this->lineNumberFontSize;
  }

  /**
   * Gets the font size used to render timer names.
   *
   * @return int
   *   The font size.
   */
  public function getTimerFontSize() {
    return $this->timerFontSize;
  }

  /**
   * Gets the title for this graph.
   *
   * @return string
   *   The title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Writes the labels for all of the states.
   *
   * @throws \Exception
   *   If the labels could not be written.
   */
  private function writeLabels() {
    $line_number_font_size = $this->getLineNumberFontSize();
    $timer_font_size = $this->getTimerFontSize();
    $labels = "  // Labels\n";
    foreach ($this->stateMachine->getAllStates() as $state) {
      $transition_block = $this->stateMachine->getTransitionBlock($state);
      $transition_method = '';
      if ($transition_block->getTransitionMethod() !== 'emptyTransition') {
        $transition_method = sprintf('[%s]', $transition_block->getTransitionMethod());
      }
      $line_number = sprintf(
        '<br /><font point-size="%d">line: %d</font><br />',
        $line_number_font_size,
        $transition_block->getLineNumber()
      );
      $timer = sprintf(
        '<br /><font point-size="%d">timer: %s</font><br />',
        $timer_font_size,
        $transition_block->getTimerName()
      );
      $labels .= sprintf(
        "  %s[ label=<<br />%s<br />%s<br />%s<br />%s<br />> ];\n",
        $state,
        $state,
        $transition_method,
        $timer,
        $line_number
      );
    }
    $this->writeDotFile(sprintf("  // Labels:\n%s\n", $labels));
  }

  /**
   * Writes the global graph properties.
   *
   * These properties may be overridden for each node or edge.
   */
  private function writeImageProperties() {
    $font_size = $this->getTitleFontSize();
    $title = $this->getTitle();
    $properties = <<<EOT
   // Global properties
   bgcolor=orange;
   fontsize=$font_size;
   rankdir="LR";
   splines=spline;
   nodesep=2;

   // Title
   labelloc="t";
   label="$title";


EOT;
    $this->writeDotFile($properties);
  }

  /**
   * Writes the node properties that define the shapes and colors for each state.
   */
  private function writeNodeProperties() {
    $node_font_size = $this->getNodeFontSize();
    $edge_font_size = $this->getEdgeFontSize();
    $properties = <<<EOT
   // Node properties
   node [ fontsize=$node_font_size shape=rectangle style=filled color=black fillcolor=grey ];
   edge [ fontsize=$edge_font_size color=black ];
   start [ shape=diamond fillcolor=green ];
   failure [ shape=doubleoctagon fillcolor=red ];
   finish [ shape=doublecircle fillcolor=green ];
EOT;
    $asynchronous_states = $this->getAsynchronousStates();
    $all_states = $this->stateMachine->getAllStates();
    $timer_colors = array();
    $color_index = 0;
    foreach ($all_states as $state) {
      $transition_block = $this->stateMachine->getTransitionBlock($state);
      if (!in_array($transition_block->getTimerName(), array_keys($timer_colors))) {
        $timer_colors[$transition_block->getTimerName()] = $this->timerColors[$color_index++];
      }
      if (!in_array(
        $state,
        array(
          $this->stateMachine->getStartState(),
          'finish',
          'failure',
        )
      )) {
        $properties .= sprintf(
          "  $state [ shape=%s fillcolor=%s ];\n",
          in_array($state, $asynchronous_states) ? 'oval' : 'rectangle',
          $timer_colors[$transition_block->getTimerName()]
        );
      }
    }
    $this->writeDotFile($properties . "\n\n");
  }

  /**
   * Returns the set of states that apparently perform asynchronous calls.
   *
   * @return string[]
   *   The set of states.
   */
  private function getAsynchronousStates() {
    $result = array();
    foreach ($this->stateMachine->getAllStates() as $state) {
      $transition_block = $this->stateMachine->getTransitionBlock($state);
      foreach ($transition_block->getAllTransitionValues() as $value) {
        $transition = $transition_block->getTransition($value);
        if ($state === $transition->getState()) {
          $result[] = $state;
          break;
        }
      }
    }
    return $result;
  }

  /**
   * Writes the node ranking, which is responsible for basic graph layout.
   */
  private function writeNodeRanking() {
    $start_state = $this->stateMachine->getStartState();
    $rankings = "  // Node ranking\n";
    $count = 1;
    $all_states = $this->stateMachine->getAllStates();
    $all_states[] = 'finish';
    foreach ($all_states as $state) {
      if ($state === $start_state) {
        $rank = "min";
      } elseif ($state === 'failure') {
        $rank = '999999';
      } elseif ($state === 'finish') {
        $rank = 'max';
      } else {
        $rank = '' . $count++;
      }
      $rankings .= "  {rank=$rank; $state};\n";
    }
    $this->writeDotFile($rankings . "\n");
  }

  /**
   * Writes the transitions, which correlate to edges.
   *
   * @throws \Exception
   *   If the transitions could not be written.
   */
  private function writeTransitions() {
    $positions = array(
      array(':n', ':ne'),
      array(':s', ':sw'),
    );
    $colors = array(
      'black',
      'purple',
      'red',
      'blue',
    );
    $detail_font_size = $this->getDetailFontSize();
    $line_number_font_size = $this->getLineNumberFontSize();
    $transitions = "  // Transitions\n";
    foreach ($this->stateMachine->getAllStates() as $state) {
      $transition_block = $this->stateMachine->getTransitionBlock($state);
      $self_reference_count = 0;
      $color_count = 0;
      foreach ($transition_block->getAllTransitionValues() as $value) {
        $color = $colors[$color_count];
        $position = array('', '');
        $transition = $transition_block->getTransition($value);
        $attributes = '';
        $wait = $transition->getWait();
        if ($wait > 0) {
          $attributes .= sprintf(
            '<br /><font point-size="%d" color="%s">wait:%d</font>',
            $detail_font_size,
            $color,
            $wait
          );
        }
        $max = $transition->getMaxCount();
        if ($max > 0) {
          $attributes .= sprintf(
            '<br /><font point-size="%d" color="%s">max:%d</font>',
            $detail_font_size,
            $color,
            $max
          );
        }
        $execute = $transition->getExec();
        if (!$execute) {
          $attributes .= sprintf(
            '<br /><font point-size="%d" color="%s">exec:false</font>',
            $detail_font_size,
            $color
          );
        }
        if ($transition->getState() === $state) {
          // Self referential transition.  Move it around.
          $position = $positions[$self_reference_count];
          $self_reference_count = ($self_reference_count + 1) % count($positions);
        }
        $attributes .= sprintf(
          '<br /><br /><font point-size="%d" color="%s">line:%d</font>',
          $line_number_font_size,
          $color,
          $transition->getLineNumber()
        );
        $transitions .= sprintf(
          "  %s%s -> %s%s [ color=\"%s\" xlabel=<<font color=\"%s\">%s</font>%s>]\n",
          $state,
          $position[0],
          $transition->getState(),
          $position[1],
          $color,
          $color,
          $value,
          $attributes
        );
        $color_count = ($color_count + 1) % count($colors);
      }
    }
    $this->writeDotFile($transitions . "\n\n");
  }

}
