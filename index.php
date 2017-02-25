<?php
include_once __DIR__ . DIRECTORY_SEPARATOR . 'Router.php';
function matchScouting($param){
echo 'hello world';

}

$routingArray = array(
  //returns scouting form for
  array(
    'method' => 'get',
    'func' => 'matchScouting',
    'uri' => 'match\/([0-9]{1,})'
  )

);

Router::route ($routingArray);
