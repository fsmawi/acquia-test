<?php

namespace Acquia\Wip\Signal;

/**
 * This is a tag interface used to identify the source of a signal.
 *
 * This particular interface should be added to any signal implementation that
 * is used for containers.
 */
interface ContainerSignalInterface extends ProcessSignalInterface {

}
