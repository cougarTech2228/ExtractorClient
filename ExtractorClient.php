<?php

class ExtractorClient{
  private $config;
  public function __construct(){
    $this->loadConfig();
  }

  private function loadConfig(){
    $this->config = file_get_contents(CONFIG);
    $this->config = json_decode($this->config, true);
    return true;
  }

  public function getConfig(){
    return $this->config;
  }

  public function setConfig($config){
      if(!is_array($config)){
        return false;
      }

      $config = json_encode($config);
      file_put_contents(CONFIG, $config);
      $this->loadConfig();
      return true;
  }

}
