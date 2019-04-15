<?php

namespace Acquia\Wip\Objects;

/**
 * The IndependentSiteGroup class represents a SiteGroup used in a WIP.
 *
 * The distinction is that this SiteGroup can be used without any further data.
 */
class IndependentSiteGroup extends SiteGroup {

  /**
   * Store any additional data from context not otherwise covered elsewhere.
   *
   * @var array
   */
  private $context = array();

  /**
   * Creates a new IndependentSiteGroup instance.
   *
   * @param SiteGroup $sitegroup
   *   The site group.
   * @param array $context
   *   The context information.
   */
  public function __construct(SiteGroup $sitegroup, $context = array()) {
    // @TODO - check everything is covered from the parent.
    // @TODO - this is the most incomplete object - almost nothing is carried
    // over from the parent at this point.
    $this->setName($sitegroup->getFullyQualifiedName());
  }

}
