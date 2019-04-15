<?php

namespace Acquia\Wip\Test\PublicStable\Ssh;

/**
 * Global variable that modifies ssh_wrapper behavior for testing.
 *
 * This global changes the way the ssh_wrapper script is interpreted. If this
 * value is TRUE, the main function is not executed. This allows a mechanism
 * for testing some of the functionality in the ssh_wrapper directly.
 */
$unit_test_ssh_wrapper = TRUE;
