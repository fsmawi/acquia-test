<?php
use Acquia\SfSignup\GardensSignup;

class SiteFactorySiteRelationshipGenerator {

  public function __construct() {
    $this->renderDocument();
  }

  /**
   * Returns the contents of the Site Factory cloud credentials file.
   *
   * @param string $sitegroup
   *   The hosting site group of the Site Factory.
   *
   * @return array
   *   The cloud credentials, broken down by sitegroup.
   */
  function getCloudCreds($sitegroup) {
    $result = NULL;
    $creds_filename = sprintf('/mnt/gfs/%s/nobackup/cloudapi.ini', $sitegroup);
    if (file_exists($creds_filename) && is_readable($creds_filename)) {
      $result = parse_ini_file($creds_filename, TRUE);
    }
    return $result;
  }

  public function renderDocument() {
    gardens_signup_includes();

    $site_factory_sitegroup = gardens_cloud_get_site_group();
    $cloud_creds = $this->getCloudCreds($site_factory_sitegroup);
    $gardens_signup = new GardensSignup();
    $tangles = gardens_cloud_get_all_tangles();
    $result = new \stdClass();
    $result->sitegroups = array();
    foreach ($tangles as $tangle) {
      $prod_site_info = gardens_cloud_get_site_and_environment($tangle, 'live_env');
      $update_site_info = gardens_cloud_get_site_and_environment($tangle, 'update_env');
      $sitegroup->name = $prod_site_info->site;
      $sitegroup->liveEnvironment = $prod_site_info->env;
      $sitegroup->updateEnvironment = $update_site_info->env;
      $sitegroup->multisite = TRUE;
      $sitegroup->sites = array();

      // Get all of the sites, construct a Site object.
      $nids = gardens_cloud_get_site_and_environment($tangle, 'live_env');
      $nids = $gardens_signup->getInstalledSites($tangle);
      foreach ($nids as $nid) {
	$site = new \stdClass();
	$site->id = $nid;
	$site->dbRole = gardens_signup_get_database_name_from_nid($nid);
	$site->siteGroup = $prod_site_info->site;
	$node = node_load($nid);
	$site->domains = array();
	foreach ($node->field_domain as $domain) {
	  if (!empty($domain['value'])) {
	    $site->domains[] = $domain['value'];
	  }
	}
	$sitegroup->sites[] = $site;
      }
      $result->sitegroups[] = $sitegroup;
      $result->cloudCreds = new \stdClass();
      $result->cloudCreds->endpoint = $cloud_creds['endpoint'];
      $result->cloudCreds->stage = $cloud_creds['stage'];
      $result->cloudCreds->username = $cloud_creds[$sitegroup->name]['username'];
      $result->cloudCreds->password = $cloud_creds[$sitegroup->name]['password'];
    }

    $result->vcsTag = 'master';
    print(json_encode($result));
  }
}

new SiteFactorySiteRelationshipGenerator();

