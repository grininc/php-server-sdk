<?php
namespace LaunchDarkly;

/**
 * @internal
 */
class EventProcessor {

  private $_apiKey;
  private $_queue;
  private $_capacity;
  private $_timeout;
  private $_socket_failed;
  private $_host;
  private $_port;
  private $_ssl;

  public function __construct($apiKey, $options = []) {
    $this->_apiKey = $apiKey;
    if (!isset($options['base_uri'])) {
        $this->_host = 'app.launchdarkly.com';
        $this->_port = 443;
        $this->_ssl = true;
    } 
    else {
        $url = parse_url($options['base_uri']);
        $this->_host = $url['host'];
        $this->_ssl = $url['scheme'] === 'https';
        if (isset($url['port'])) {
          $this->_port = $url['port'];
        } 
        else {
          $this->_port = $this->_ssl ? 443 : 80;
        }
    }

    $this->_capacity = $options['capacity'];
    $this->_timeout = $options['timeout'];

    $this->_queue = array(); 
  }

  public function __destruct() {
    $this->flush();
  }

  public function sendEvent($event) {
    return $this->enqueue($event);
  }

  public function enqueue($event) {
    if (count($this->_queue) > $this->_capacity) {
      return false;
    }

    array_push($this->_queue, $event);

    return true;
  }

  protected function flush() {
    if (empty($this->_queue)) {
      return;
    }

    $payload = json_encode($this->_queue);

    $args = $this->createArgs($payload);

    return $this->makeRequest($args);
  }

  private function createArgs($payload) {
    $scheme = $this->_ssl ? "https://" : "http://";
    $args = " -v -X POST";
    $args.= " -H 'Content-Type: application/json'";
    $args.= " -H 'Authorization: api_key " . $this->_apiKey . "'";
    $args.= " -H 'User-Agent: PHPClient/" . LDClient::VERSION . "'";
    $args.= " -H 'Accept: application/json'";
    $args.= " -d '" . $payload . "'";
    $args.= " " . $scheme . $this->_host . ":" . $this->_port . "/api/events/bulk";
    return $args;
  }

  private function makeRequest($args) {
    error_log("curl args: " . $args);
    $cmd = "/usr/bin/env curl " . $args . ">> /tmp/curl.log 2>&1 &";
    $out = shell_exec($cmd);
    return true;
  }


}