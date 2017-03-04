<?php

/////////////
// Globals //
/////////////
define('BASEURL', 'http://localhost:9999');
define('BASEURI', '/');
define('DATADIR', __DIR__ . 'data' . DIRECTORY_SEPARATOR);
define('CONFIG', DATADIR . 'config.json');
define('VERSION', '0.0.0');

//////////
// Main //
//////////
include_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

// Include everything for Extractor.
$files = scandir(__DIR__ . DIRECTORY_SEPARATOR . 'inc');
foreach ($files as $file) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . $file;
    if (is_file($path) || pathinfo($path)['extension'] === 'php') {
        include_once $file;
    }
}

////////////
// Models //
////////////

/**
 * Index Redirect
 * Simply redirects index ('/') to match list.
 *
 * @param array $param Router input
 */
function index($param) {
    unset($param);

    redirect('match');

    return;
}

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

function transfer($param) {

}

function transferDisplay($param) {

}

function matchSubmit($param) {

}

function pitSubmit($param) {

}

function config($param) {

}

/**
 * Return 404
 * Returns a 404 to the browser.
 */
function return404() {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');

    // TODO: Add 404 template.
    echo render('404', array());

    return;
}

/**
 * Redirect
 * Redirects browser to desired location.
 *
 * @param string $uri Redirect URI
 */
function redirect($uri) {
    header('Location: ' . BASEURI . $uri);

    return;
}

/**
 * Render Template
 * Renders a Template in Mustache
 *
 * @param string $tmp     Template
 * @param array  $context Placeholder context
 * @param string $title   Page title
 *
 * @return false|string
 */
function render($tmp, $context, $title = 'Extractor') {
    if (!isset($tmp) || isset($context)) {
        return false;
    }

    if (!file_exists('templates/partial/' . $tmp . '.mustache')) {
        return false;
    }

    // Define globals.
    $context['title'] = $title;
    $context['BASEURI'] = BASEURI;
    $context['VERSION'] = VERSION;
    $context['navlinks'] = array(
        array(
            'active' => ($tmp == 'matchList' || $tmp === 'matchForm'),
            'link'   => 'match',
            'icon'   => 'view_list',
            'name'   => 'Match'
        ),
        array(
            'active' => ($tmp == 'pitList' || $tmp === 'pitForm'),
            'link'   => 'pit',
            'icon'   => 'view_list',
            'name'   => 'Pit'
        ),
        array(
            'active' => ($tmp == 'transfer' || $tmp === 'transferDisplay'),
            'link'   => 'transfer',
            'icon'   => 'present_to_all',
            'name'   => 'Transfer'
        ),
        array(
            'active' => ($tmp == 'schedule'),
            'link'   => 'schedule',
            'icon'   => 'list',
            'name'   => 'Schedule'
        ),
        array(
            'active' => ($tmp == 'about' || $tmp === 'config'),
            'link'   => 'about',
            'icon'   => 'phonelink_setup',
            'name'   => 'About'
        )
    );

    $mustache = new Mustache_Engine(array(
        'loader'          => new Mustache_Loader_FilesystemLoader('templates/'),
        'partials_loader' => new Mustache_Loader_FilesystemLoader('templates/partial')
    ));
    $render = $mustache->loadTemplate($tmp);

    return $render->render($context);
}

$routingArray = array(
    // Index
    array(
        'method' => 'get',
        'func'   => 'index',
        'uri'    => ''
    ),
    // Scouting form
    array(
        'method' => 'get',
        'func'   => 'matchScouting',
        'uri'    => 'match\/([0-9]{1,})'
    ),

    // Match list
    array(
        'method' => 'get',
        'func'   => 'matchList',
        'uri'    => 'match'
    ),

    // Schedule
    array(
        'method' => 'get',
        'func'   => 'schedule',
        'uri'    => 'schedule'
    ),

    // About
    array(
        'method' => 'get',
        'func'   => 'about',
        'uri'    => 'about'
    ),

    // Pit list
    array(
        'method' => 'get',
        'func'   => 'pitList',
        'uri'    => 'pit'
    ),

    // Pit form
    array(
        'method' => 'get',
        'func'   => 'pitScouting',
        'uri'    => 'pit\/([0-9]{1,})'
    ),

    // Transfer
    array(
        'method' => 'get',
        'func'   => 'transfer',
        'uri'    => 'transfer'
    ),

    // Transfer Begin
    array(
        'method' => 'get',
        'func'   => 'transferDisplay',
        'uri'    => 'transfer\/display'
    ),

    // Match data handler
    array(
        'method' => 'post',
        'func'   => 'matchSubmit',
        'uri'    => 'post\/match'
    ),

    // Pit data handler
    array(
        'method' => 'post',
        'func'   => 'pitSubmit',
        'uri'    => 'post\/pit'
    ),

    // Configuration
    array(
        'method' => 'get',
        'func'   => 'config',
        'uri'    => 'config'
    )
);

if (!Router::process($routingArray)) {
    return404();
}
