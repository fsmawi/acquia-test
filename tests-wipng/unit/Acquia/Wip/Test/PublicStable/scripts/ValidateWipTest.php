<?php

namespace Acquia\Wip\Test\PublicStable\scripts;

use Acquia\Wip\Iterators\BasicIterator\ReportGenerator;

/**
 * Tests the /src/validatewip script.
 */
class ValidateWipTest extends \PHPUnit_Framework_TestCase {
  private $scriptPath = NULL;
  private $filePath = NULL;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->scriptPath = 'scripts/validateWip';
  }

  /**
   * Missing summary.
   */
  public function tearDown() {
    parent::tearDown();
    if (NULL !== $this->filePath) {
      @unlink($this->filePath);
      $this->filePath = NULL;
    }
  }

  /**
   * Missing summary.
   */
  public function testScriptExists() {
    $this->assertTrue(file_exists($this->scriptPath));
    $this->assertTrue(is_executable($this->scriptPath));
  }

  /**
   * Missing summary.
   */
  public function testHelp() {
    $command = sprintf('%s --help', $this->scriptPath);
    $output = array();
    $result = NULL;
    exec($command, $output, $result);
    $this->assertEquals(0, $result);
    $this->assertNotEmpty(implode("\n", $output));
  }

  /**
   * Missing summary.
   */
  public function testStateTableWithFile() {
    $state_table = <<<EOT
start {
  * finish
}

failure {
  * finish
}

terminate {
  * failure
}
EOT;
    $filename = tempnam(sys_get_temp_dir(), 'st');
    file_put_contents($filename, $state_table);
    $command = sprintf('%s --table %s', $this->scriptPath, $filename);
    exec($command, $output, $result);
    @unlink($filename);
    $this->assertEquals(0, $result);
    $report = implode("\n", $output);
    $this->assertNotEmpty($report);
    $this->assertTrue($this->resultContainsSection($report, ReportGenerator::SUCCESS_BUT_NO_OBJECT));
  }

  /**
   * Missing summary.
   */
  public function testStateTableWithStdIn() {
    $state_table = <<<EOT
start {
  * finish
}

failure {
  * finish
}

terminate {
  * failure
}
EOT;
    $command = sprintf('%s --table -', $this->scriptPath);
    $result = $this->runCommandWithInput($command, $state_table, $report);
    $this->assertEquals(0, $result);
    $this->assertNotEmpty($report);
    $this->assertTrue($this->resultContainsSection($report, ReportGenerator::SUCCESS_BUT_NO_OBJECT));
  }

  /**
   * Missing summary.
   */
  public function testBrokenStateTableWithStdIn() {
    $state_table = <<<EOT
start {
  * step1
}
EOT;
    $command = sprintf('%s --table -', $this->scriptPath);
    $result = $this->runCommandWithInput($command, $state_table, $report);
    $this->assertNotEquals(0, $result);
    $this->assertNotEmpty($report);
    $this->assertTrue($this->resultContainsSection($report, ReportGenerator::FAIL));
  }

  /**
   * Missing summary.
   */
  public function testWipObject() {
    $command = sprintf('%s --class Acquia\\\\Wip\\\\Implementation\\\\BasicWip', $this->scriptPath);
    $result = $this->runCommandWithInput($command, '', $report);
    $this->assertEquals(0, $result);
    $this->assertNotEmpty($report);
    $this->assertTrue($this->resultContainsSection($report, ReportGenerator::SUCCESS));
  }

  /**
   * Missing summary.
   */
  public function testBrokenWipObject() {
    $state_table = <<<EOT
start {
  * step1
}
EOT;
    $command = sprintf(
      '%s --class Acquia\\\\Wip\\\\Test\\\PrivateStable\\\\Iterator\\\\BasicIterator\\\\TranscriptTestWip --table - ',
      $this->scriptPath
    );
    $result = $this->runCommandWithInput($command, $state_table, $report);
    $this->assertNotEquals(0, $result);
    $this->assertTrue($this->resultContainsSection($report, ReportGenerator::FAIL));
  }

  /**
   * Missing summary.
   */
  public function testRenderGraph() {
    $state_table = <<<EOT
start {
  * finish
}

failure {
  * finish
}

terminate {
  * failure
}
EOT;
    $this->filePath = tempnam(sys_get_temp_dir(), 'graph');
    $md5sum = 0;
    if (file_exists($this->filePath)) {
      $md5sum = md5_file($this->filePath);
    }
    $command = sprintf('%s --table - --graph %s', $this->scriptPath, $this->filePath);
    $result = $this->runCommandWithInput($command, $state_table, $report);
    $this->assertEquals(0, $result);
    $this->assertNotEmpty($report);
    $this->assertTrue($this->resultContainsSection($report, ReportGenerator::SUCCESS_BUT_NO_OBJECT));
    $this->assertTrue(file_exists($this->filePath));
    $this->assertNotEquals($md5sum, md5_file($this->filePath));

    $file_command = sprintf('file %s', $this->filePath);
    $this->runCommandWithInput($file_command, '', $file_output);
    $this->assertTrue(strpos($file_output, 'PNG image data') > 0);
  }

  /**
   * Runs the specified command, send the specified input and collects output.
   *
   * @param string $command
   *   The command.
   * @param string $input
   *   The content to send to the command over stdin.
   * @param string $output
   *   The stdout from the command.
   *
   * @return int
   *   The exit code of the command.
   */
  private function runCommandWithInput($command, $input, &$output) {
    $output = '';
    $descriptor_spec = array(
      0 => array("pipe", "r"), // Stdin.
      1 => array("pipe", "w"), // Stdout.
      2 => array("pipe", "w"), // Stderr.
    );

    $process = proc_open($command, $descriptor_spec, $pipes);
    if (is_resource($process)) {
      fwrite($pipes[0], $input);
      fflush($pipes[0]);
      fclose($pipes[0]);
      while ($line = fread($pipes[1], 8192)) {
        $output .= $line;
      }
      $result = proc_close($process);
    } else {
      $result = 37;
    }
    return $result;
  }

  /**
   * Missing summary.
   *
   * @param string $report
   *   The report.
   * @param string $section_name
   *   The section name.
   *
   * @return bool
   *   Whether or not it contains the section.
   */
  private function resultContainsSection($report, $section_name) {
    $section_header = trim(ReportGenerator::generateSectionSeparator($section_name));
    return (1 === preg_match("/^$section_header$/m", $report));
  }

}
