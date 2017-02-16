<?php

// A PHP mock API Server
require '../vendor/autoload.php';
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

define("CODE_MISSING_ARGUMENT", "MissingArgument");
define("CODE_SERVER_ERROR", "ServerError");
define("CODE_BAD_YAML", "InvalidYAML");
define("CODE_NOT_FOUND", "NotFound");

/**
 * Helper function
 * @param  $key     [description]
 * @param  $array   [description]
 * @param  $default [description]
 */
function __($key, array $array, $default = null)
{
  return array_key_exists($key, $array) ? $array[$key] : $default;
}

/**
 * Helper function for creating response
 * @param  $data
 * @param  $status
 */
function response($data, $status = 200)
{
  header("Access-Control-Allow-Orgin: *");
  header("Access-Control-Allow-Methods: *");
  header("Content-Type: application/json");
  header("HTTP/1.1 " . $status . " " . requestStatus($status));
  echo json_encode($data);
  die();
}

/**
 * Helper function
 * @param  $code
 */
function requestStatus($code)
{
  $status = [
      200 => 'OK',
      404 => 'Not Found',
      400 => 'Bad Request',
      500 => 'Internal Server Error'
  ];
  return isset($status[$code])?$status[$code]:$status[500];
}

/**
 * Helper function to get cookie value
 */
function getPipelineCookie() {
  $data = [];
  if (isset($_COOKIE['pipeline'])) {
    $data = unserialize($_COOKIE['pipeline']);
  }

  return $data;
}

/**
 * Helper function to set cookie value
 * @param  $data
 */
function setPipelineCookie($data) {
  setcookie('pipeline', serialize($data), time()+3600, '/');
}

/**
 * Helper function to clear pipeline cookie
 */
function clearCookies() {
  $past = time() - 3600;
  setcookie('pipeline', '', $past, '/');
}

// Get request attributes
$method = __('REQUEST_METHOD', $_SERVER);
$apiFile = isset($_SERVER['HTTP_X_ACQUIA_PIPELINES_N3_APIFILE'])?$_SERVER['HTTP_X_ACQUIA_PIPELINES_N3_APIFILE']:'default.yml';

// Check if mock file exists
$contentFile = file_get_contents(dirname(__FILE__).'/../../../test/api-mock-resources/'.$apiFile);
if ($contentFile === false) {
  response([
            "code" => CODE_NOT_FOUND,
            "message" => "Could not find mock file",
          ], 400);
}

// Parse yaml file
$definition = Yaml::parse($contentFile);

// Display home page
if (empty($_REQUEST['q'])) {
  // Clear cookies
  clearCookies();
  $readme = file_get_contents('README.md');
  $Parsedown = new Parsedown();
  echo '<div class="markdown-body">
          <link href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/2.4.1/github-markdown.css" rel="stylesheet">
          '.$Parsedown->text($readme).'
        </div>';
  die();
}

// Get route name
$route = "/".$_REQUEST['q'];

// Processing API call
if (isset($definition['routes'][$route])) {

  $item = $definition['routes'][$route];

  if (!isset($item[$method])) {
    response([
            "code" => CODE_NOT_FOUND,
            "message" => "Merver could not find $route with $method method in your definition",
            "definition" => $definition
          ], 404);
  }

  if (isset($item[$method]['response'])) {

    response($item[$method]['response'], isset($item[$method]['status'])?$item[$method]['status']:200);

  } elseif (isset($item[$method]['responses'])) {
    $currentResponse = [];
    for ($i = 0; $i < count($item[$method]['responses']); $i++) {

      $currentResponse = $item[$method]['responses'][$i];

      $data = getPipelineCookie();
      if (isset($data[$route][$method]) && $data[$route][$method] >= $i) {
        continue;
      }

      $data[$route][$method] = $i;
      setPipelineCookie($data);
      break;
    }
    response($currentResponse['response'], isset($currentResponse['status'])?$currentResponse['status']:200);

  } else {
    response([
            "code" => CODE_MISSING_ARGUMENT,
            "message" => "Your definition is missing the response|responses attribute",
            "definition" => $definition
          ], 400);
  }

} elseif (isset($definition['redirections'][$route])) { // Handle redirections

  if (!isset($definition['redirections'][$route]['redirect_to'])) {
    response([
            "code" => CODE_NOT_FOUND,
            "message" => "Your definition is missing the redirect_to attribute",
          ], 400);
  }

  // Process redirection
  unset($_GET['q']);
  $params = http_build_query($_GET);
  header("Location:".$definition['redirections'][$route]['redirect_to']."?".$params);

} else {

  // Route not found in definition
  response([
            "code" => CODE_NOT_FOUND,
            "message" => "Merver could not find $route in your definition",
            "definition" => $definition
          ], 404);
}
