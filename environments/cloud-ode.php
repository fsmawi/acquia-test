<?php

/**
 * Proof of concept of GitHub, Pipelines, and Cloud ODE integration.
 * Be kind, this is a quick hack.
 */

require 'vendor/autoload.php';
require 'cloudapi.php';

use Acquia\Hmac\Guzzle\HmacAuthMiddleware;
use Acquia\Hmac\Key;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

class CloudODE {
    function __construct($cloud_api, $pipelines_api, $opts = []) {
        $this->cloud_api = $cloud_api;
        $this->pipelines_api = $pipelines_api;
        $this->app = getenv('PIPELINE_APPLICATION_ID');
        $this->job = getenv('PIPELINE_JOB_ID');
        $this->auth_token = getenv('PIPELINES_AUTH_TOKEN');
        $this->api_endpoint = getenv('PIPELINES_API_ENDPOINT');
        $this->deploy_path = getenv('PIPELINE_DEPLOY_VCS_PATH');
        $this->event = getenv('PIPELINES_EVENT');
        if (empty($this->event)) {
            $this->event = 'build';
        }
    }

    // Delete all ODEs deploying the current deploy_path.
    function delete() {
        $envs = $this->cloud_api->get("applications/{$this->app}/environments");
        foreach ($envs->_embedded->items as $env) {
            if ($env->flags->ode == 1 && $env->vcs->path == $this->deploy_path) {
                print "Environments: Deleting {$env->label} ({$env->name}).\n";
                $this->cloud_api->delete("environments/{$env->id}");
            }
        }
    }

    // Find the first element for which a callback returns true, or NULL.
    function find($array, $callback) {
        foreach ($array as $elem) {
            if ($callback($elem)) {
                return $elem;
            }
        }
        return NULL;
    }

    function set_job_metadata($key, $value) {
        $body = [
            'applications' => [ $this->app ],
            'auth_token' => $this->auth_token,
            'key' => $key,
            'value' => $value
        ];
        $this->pipelines_api->put("ci/jobs/{$this->job}/metadata", $body);
    }

    // Create or update an ODE for the current build.  Environments are tied
    // to builds by the environment label being the build branch name, since
    // that is the only way we have to identify them.  Method:
    //
    // - If an environment deploying the build does not exist, create
    //   an ODE, configure it to deploy the build branch, and wait for
    //   it to be done.
    // - Otherwise, update the existing environment(s) with the latest build.
    //
    // @todo: Use
    // http://acquia.github.io/network-n3/#applications__uuid__hosting_tasks_get
    // to determine when a git push is deployed.
    function deploy() {
        $label = $this->deploy_path;
        try {
            // Find a build environment for this path, if it exists.
            $envs = $this->cloud_api->get("applications/{$this->app}/environments");
            $env = $this->find($envs->_embedded->items, function ($env) use ($label) {
                return $env->vcs->path == $this->deploy_path;
            });

            if ($env) {
                // Deploy the new build.
                // @todo: No way to know when it is done.
                print "Environments: Updating Cloud environment {$env->label} ({$env->name}).\n";
            }
            else {
                // Create the environment. We cannot select a branch that does
                // not exist yet.
                print "Environments: Creating Cloud environment...\n";
                try {
                    $this->cloud_api->post("applications/{$this->app}/environments", [
                        'label' => $label,
                        'branch' => 'master',
                    ]);
                }
                catch (CloudAPI\UnexpectedResponseStatusException $e) {
                    $result = $e->getResult();
                    if (strpos($result['message'], 'On-demand environments are not available') !== FALSE) {
                        print "Environments: {$result['message']}\n";
                        exit(1);
                    }
                    throw $e;
                }

                // Find the environment we just created. The label is the only
                // we have to identify it.
                // @todo: Could the POST call return the env id?
                $envs = $this->cloud_api->get("applications/{$this->app}/environments");
                $env = $this->find($envs->_embedded->items, function ($env) use ($label) {
                    return $env->label == $label;
                });

                // Wait for environment to be ready.
                print "Environments: Waiting for {$env->name} to be ready...\n";
                $this->cloud_api->poll("environments/{$env->id}", function ($env, $count) {
                    return $env->status == 'normal';
                });

                // Select the build branch, even if it doesn't exist yet.
                $this->cloud_api->post("environments/{$env->id}/code/actions/switch", [
                    'branch' => $this->deploy_path
                ]);

                // @todo: Wait until the branch is actually deployed.
                // Currently not sure how to do that.
            }

            // Set the deployment name and URL.
            $this->set_job_metadata('deployment_name', $env->name);
            $this->set_job_metadata('deployment_link', "http://{$env->default_domain}");

            // Write the new ODE url to the host
            $newHost = "http://pipelinesui$env->name.network.acquia-sites.com";
            print "Writing new ODE URI to ~/ode.url : $newHost\n";
            exec("echo $newHost > ~/ode.url");
        }
        catch (CloudAPI\Exception $e) {
            print "Environments: Cloud API error: " . $e->getMessage();
            exit(1);
        }
    }

    function execute() {
        print "Environments: Event {$this->event}.\n";
        switch ($this->event) {
        case 'build':
            $this->deploy();
            break;

        case 'merge':
            $this->delete();
            break;
        }
    }
}

$key = getenv('N3_KEY');
$secret = getenv('N3_SECRET');
if (empty($key) || empty($secret)) {
    print "N3_KEY and N3_SECRET environment variables are required.\n";
    exit(1);
}
$cloud_api = new CloudAPI\QuickCloudAPI($key, $secret, [
    'debug' => getenv('ENVIRONMENTS_DEBUG'),
]);
$pipelines_api = new CloudAPI\QuickPipelinesAPI([
    'debug' => getenv('ENVIRONMENTS_DEBUG'),
]);
$ode = new CloudODE($cloud_api, $pipelines_api);
$ode->execute();
