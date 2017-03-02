<?php
include_once __DIR__ . DIRECTORY_SEPARATOR . 'Router.php';

function matchScouting($param) {

}

function matchList($param) {

}

function schedule($param) {

}

function about($param) {

}

function pitList($param) {

}

function pitScouting($param) {

}

function xfer($param) {

}

function matchSubmit($param) {

}

function pitSubmit($param) {

}

function config($param) {

}

function postConfig($param) {

}

function return404() {

}

function redirect() {

}

$routingArray = array(
    //returns scouting form for
    array(
        'method' => 'get',
        'func'   => 'matchScouting',
        'uri'    => 'match\/([0-9]{1,})'
    ),

    //list of matches
    array(
        'method' => 'get',
        'func'   => 'matchList',
        'uri'    => 'match'
    ),

    //schedule of matches
    array(
        'method' => 'get',
        'func'   => 'schedule',
        'uri'    => 'schedule'
    ),

    //about
    array(
        'method' => 'get',
        'func'   => 'about',
        'uri'    => 'about'
    ),

    //list of pit data
    array(
        'method' => 'get',
        'func'   => 'pitList',
        'uri'    => 'pit'
    ),

    //pit scouting
    array(
        'method' => 'get',
        'func'   => 'pitScouting',
        'uri'    => 'pit\/([0-9]{1,})'
    ),

    //xfer
    array(
        'method' => 'get',
        'func'   => 'xfer',
        'uri'    => 'xfer'
    ),


    //submits match data
    array(
        'method' => 'post',
        'func'   => 'matchSubmit',
        'uri'    => 'post\/match'
    ),

    //submits pit data
    array(
        'method' => 'post',
        'func'   => 'pitSubmit',
        'uri'    => 'post\/pit'
    ),

    //configuration
    array(
        'method' => 'get',
        'func'   => 'config',
        'uri'    => 'config'
    ),
    //post configuration
    array(
        'method' => 'post',
        'func'   => 'postConfig',
        'uri'    => 'post\/config'
    )
);

if (!Router::process($routingArray)) {
    return404();
}
