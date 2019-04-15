<?php

namespace Acquia\Wip\Test\PrivateStable\Iterator\BasicIterator;

use Acquia\Wip\Iterators\BasicIterator\DotVisualization;

/**
 * Missing summary.
 */
class DotVisualizationTest extends \PHPUnit_Framework_TestCase {

  /**
   * The visualization object being tested.
   *
   * @var DotVisualization
   */
  private $visualization = NULL;

  private $stateTable = <<<EOT
start:transition1 {
  a step1
  b step2
}

step1:checkAsyncProcess {
  success step2
  waiting step1 wait=30 exec=false
  ! failure
}

step2 {
  * finish
}

failure {
  * finish
}
EOT;

  /**
   * Missing summary.
   */
  public function setUp() {
    parent::setUp();
    $this->visualization = new DotVisualization('Example');
    $this->visualization->setStateTable($this->stateTable);
    $this->visualization->setDotFile('/tmp/dotFile');
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testBadTitle() {
    new DotVisualization(15);
  }

  /**
   * Missing summary.
   *
   * @expectedException \RuntimeException
   */
  public function testBadDotUtility() {
    $this->visualization->setDotPath('/usr/lib/dotDoesNotExist');
    $this->visualization->visualize();
  }

  /**
   * Missing summary.
   *
   * @expectedException \Exception
   */
  public function testVisualizeWithNoStateTable() {
    $this->visualization = new DotVisualization();
    $this->visualization->visualize();
  }

  /**
   * Missing summary.
   */
  public function testVisualizeWithNoDotFile() {
    $this->visualization = new DotVisualization();
    $this->visualization->setStateTable($this->stateTable);
    $output = $this->visualization->visualize();

    // Verify that the $output contains a PNG file.
    $this->runCommandWithInput('file -', $output, $result);
    $expected_string = 'PNG image data';
    $this->assertTrue(FALSE !== strpos($result, $expected_string));
  }

  /**
   * Missing summary.
   */
  public function testVisualizationWithDotFile() {
    $this->visualization->visualize('/tmp/dot.png');
  }

  /**
   * Missing summary.
   */
  public function testSetTitleFontSize() {
    $size = 3;
    $this->visualization->setTitleFontSize($size);
    $this->assertEquals($size, $this->visualization->getTitleFontSize());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetTitleFontSizeWithBadSize() {
    $size = 'hello';
    $this->visualization->setTitleFontSize($size);
  }

  /**
   * Missing summary.
   */
  public function testSetNodeFontSize() {
    $size = 7;
    $this->visualization->setNodeFontSize($size);
    $this->assertEquals($size, $this->visualization->getNodeFontSize());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetNodeFontSizeWithBadSize() {
    $size = 'hello';
    $this->visualization->setNodeFontSize($size);
  }

  /**
   * Missing summary.
   */
  public function testSetEdgeFontSize() {
    $size = 11;
    $this->visualization->setEdgeFontSize($size);
    $this->assertEquals($size, $this->visualization->getEdgeFontSize());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetEdgeFontSizeWithBadSize() {
    $size = 'hello';
    $this->visualization->setEdgeFontSize($size);
  }

  /**
   * Missing summary.
   */
  public function testSetDetailFontSize() {
    $size = 2;
    $this->visualization->setDetailFontSize($size);
    $this->assertEquals($size, $this->visualization->getDetailFontSize());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetDetailFontSizeWithBadSize() {
    $size = 'hello';
    $this->visualization->setDetailFontSize($size);
  }

  /**
   * Missing summary.
   */
  public function testSetLineNumberFontSize() {
    $size = 1;
    $this->visualization->setLineNumberFontSize($size);
    $this->assertEquals($size, $this->visualization->getLineNumberFontSize());
  }

  /**
   * Missing summary.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetLineNumberFontSizeWithBadSize() {
    $size = 'hello';
    $this->visualization->setLineNumberFontSize($size);
  }

  /**
   * Missing summary.
   */
  public function testVisualizationWithComplexDotFile() {
    $state_table = <<<EOT
start {
  * envSetup wait=3
  ! start wait=10 max=5
}

envSetup:verifyVcsTags {
  done selectStep wait=10 max=5
  * envSetup wait=10 max=5
  ! envSetup wait=10 max=5
}

# If this object has been restarted, it doesn't make sense to repeat
# some operations that were successful.  Setting the vcs path and
# moving domains should only be done once successfully.  Skip those
# steps if needed.
selectStep:getLastSuccessfulInitializeStep {
  * deployVcsOnUpdateSite
  vcs_up moveAllDomainsToUp
  domain deployVcsOnLiveSite
  vcs_live clearLiveDrushCache
}

deployVcsOnUpdateSite:checkHostingTaskStatus {
  running deployVcsOnUpdateSite wait=20 exec=false
  success waitForUpdateVcsPath
  * deployVcsOnUpdateSite wait=5 max=2 exec=true
}

waitForUpdateVcsPath:verifyUpdateVcsPath {
  done clearUpdateDrushCache wait=5
  pending_hosting waitForUpdateVcsPath wait=30
  pending_deploy waitForUpdateVcsPath wait=30
  workspace_fail waitForUpdateVcsPath wait=30 max=2
  bad_path failure
  ! failure
}

clearUpdateDrushCache {
  * moveAllDomainsToUp
}

moveAllDomainsToUp:checkHostingTaskStatus {
  running moveAllDomainsToUp wait=30 exec=false
  success deployVcsOnLiveSite wait=5
  * moveAllDomainsToUp wait=5 max=2
}

deployVcsOnLiveSite:checkHostingTaskStatus {
  running deployVcsOnLiveSite wait=20 exec=false
  success waitForLiveVcsPath
  * deployVcsOnLiveSite wait=5 max=2
}

waitForLiveVcsPath:verifyLiveVcsPath {
  done clearLiveDrushCache wait=5
  pending_hosting waitForLiveVcsPath wait=30
  pending_deploy waitForLiveVcsPath wait=30
  workspace_fail waitForLiveVcsPath wait=30 max=2
  bad_path failure
  ! failure
}

clearLiveDrushCache {
  * ensureRegistryRebuild wait=5
}

ensureRegistryRebuild:checkProcessExitCode {
  success restartFailedChildren wait=5
  muted_fail restartFailedChildren wait=5
  fail failure
}

# If the docroot update failed because of failed children, try to
# restart them.
restartFailedChildren:childRestartComplete {
  done identifySites wait=5
  processing restartFailedChildren wait=5
}

identifySites {
  * addUpdateTasks wait=5
}

addUpdateTasks:updateTasksComplete {
  done waitForUpdateTasks wait=5
  working addUpdateTasks wait=5
}

waitForUpdateTasks:getDocrootStatus {
  processing waitForUpdateTasks wait=600
  done moveAllDomainsBack wait=5
  ! clearTemporaryDrushCache
}

moveAllDomainsBack:checkHostingTaskStatus {
  running moveAllDomainsBack wait=30 exec=false
  success syncUpdateVcs wait=5
  * moveAllDomainsBack wait=30 max=3
}

syncUpdateVcs:checkHostingTaskStatus {
  running syncUpdateVcs wait=20 exec=false
  success waitForUpdateVcsSync
  * syncUpdateVcs wait=5 max=2
}

waitForUpdateVcsSync:checkVcsSync {
  done clearUpdateDrushCacheAgain
  pending_hosting waitForUpdateVcsSync wait=30
  pending_deploy waitForUpdateVcsSync wait=30
  workspace_fail waitForUpdateVcsSync wait=30 max=2
  bad_path failure
  ! failure
}

clearUpdateDrushCacheAgain {
  * clearTemporaryDrushCache
}

clearTemporaryDrushCache {
  * finish
}

failure {
  * clearTemporaryDrushCache
}

EOT;

    $this->visualization->setStateTable($state_table);
    $this->visualization->visualize('/tmp/dot.png');
  }

  /**
   * Runs the specified command, send the specified input and collects output.
   *
   * @param string $command
   *   The command.
   * @param string $input
   *   The content to send to the command over STDIN.
   * @param string $output
   *   The stdout from the command.
   *
   * @return int
   *   The exit code of the command.
   */
  private function runCommandWithInput($command, $input, &$output) {
    $output = '';
    $descriptor_spec = array(
      0 => array("pipe", "r"), // STDIN.
      1 => array("pipe", "w"), // STDOUT.
      2 => array("pipe", "w"), // STDERR.
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
      $result = 255;
    }
    return $result;
  }

}
