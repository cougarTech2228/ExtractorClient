<?php

/**
  *returns config
  *
  *handles various tasks, loads config
  *
  *
  *
  *
  */
class ExtractorClient{
  private $config;
  public function __construct(){
    $this->loadConfig();
  }

/**
  *loadConfig
  *
  * reloads data in config
  *
  * @return true
  */
  private function loadConfig(){
    $this->config = file_get_contents(CONFIG);
    $this->config = json_decode($this->config, true);
    return true;
  }

  /**
    *getConfig
    * just returns config
    *
    * @return config
    */
  public function getConfig(){
    return $this->config;
  }

  /**
    *setConfig
    *verifys that $config
    *
    * @param array $config
    *
    * @return false|true
    */
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
