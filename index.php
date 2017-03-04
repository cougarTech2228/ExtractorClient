<?php
define('BASEURL', 'http://localhost:9999');
define('BASEURI', '/');
define('CONFIG', 'config.json');
include_once __DIR__ . DIRECTORY_SEPARATOR . 'Router.php';
include_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor/autoload.php';

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

/**
 * return404
 * returns browser to a 404 page
 *
 * @return true
 */
function return404() {
    header('HTTP/1.0 404 Not Found');

    echo render('404', array());

    return true;
}

/**
 * redirect
 * redirects browser to a different location
 *
 * @param string $uri redirect to
 *
 * @return true
 */
function redirect($uri) {
    header('Location: ' . $uri);

    return true;
}

/**
 * Render Template
 * Renders a Template in Mustache
 *
 * @param string $template template file
 * @param array  $context  gives context to template
 * @param string $title    sets page title
 *
 * @return false|string updated php dock
 */
function render($template, $context, $title = 'Extractor') {
    if (!isset($template) || isset($context)) {
        return false;
    }

    if (!file_exists('templates/partial/' . $template . '.mustache')) {
        return false;
    }

    $context['title'] = $title;
    $context['BASEURI'] = BASEURI;
    $context['navlinks'] = array(
        array(
            'active' => false,
            'link'   => '',
            'name'   => ''
        )
    );

    $mustache = new Mustache_Engine(array(
        'loader'          => new Mustache_Loader_FilesystemLoader('templates/'),
        'partials_loader' => new Mustache_Loader_FilesystemLoader('templates/partial')
    ));
    $render = $mustache->loadTemplate($template);

    return $render->render($context);
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
