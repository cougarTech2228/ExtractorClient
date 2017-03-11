<?php

/////////////
// Globals //
/////////////
define('BASEURL', 'http://localhost:9999');
define('BASEURI', '/');
define('DS', DIRECTORY_SEPARATOR);
define('DATADIR', __DIR__ . DS . 'data' . DS);
define('DATASEARCHPATH', DS . 'storage' . DS . 'sdcard1' . DS . 'Extractor' . DS);
define('CONFIG', DATADIR . 'config.json');
define('VERSION', '0.0.0');

//////////
// Main //
//////////
/** @noinspection PhpIncludeInspection */
include_once __DIR__ . DS . 'vendor' . DS . 'autoload.php';

// Include everything for Extractor.
$files = scandir(__DIR__ . DS . 'inc');
foreach ($files as $file) {
    $path = __DIR__ . DS . 'inc' . DS . $file;
    if (is_file($path) || pathinfo($path)['extension'] === 'php') {
        /** @noinspection PhpIncludeInspection */
        include_once $path;
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

    redirect('schedule');

    return;
}

/**
 * Match List Controller
 * Outputs the schedule page render.
 *
 * @param array $param Router input
 */
function matchList($param) {
    unset($param);

    $ec = new ExtractorConfig();

    $matches = array();
    foreach ($ec->getConfig('matches') as $match) {
        $matches[] = array(
            'match'   => $match['match'],
            'teamNum' => $match[$ec->getConfig('team')],
            'current' => ($ec->getConfig('currentMatch') === $match['match'])
        );
    }

    // Handle any extra matches.
    $extra = ExtractorStorage::fetch('sys', 'extraMatches');
    if ($extra !== false) {
        foreach ($extra as $match) {
            $matches[] = array(
                'match'   => $match['match'],
                'teamNum' => $match['team'],
                'current' => ($ec->getConfig('currentMatch') === $match['match'])
            );
        }
    }

    $context = array(
        'team'      => ExtractorUtil::teamNiceName($ec->getConfig('team')),
        'teamColor' => ExtractorUtil::teamColor($ec->getConfig('team')),
        'matches'   => $matches,
    );

    echo render('matchList', $context, 'Match List');

    return;
}

/**
 * Match Form Controller
 * Handles pre-filling and rendering the match forms.
 *
 * @param array $param Router input
 */
function matchForm($param) {
    // Config instance.
    $ec = new ExtractorConfig();

    // Set defaults.
    $defaults = array(
        'match'          => '',
        'team'           => '',
        'autoBaseline'   => false,
        'autoGear'       => false,
        'autoFuelHigh'   => 0,
        'autoFuelLow'    => 0,
        'teleFuelHigh'   => 0,
        'teleFuelLow'    => 0,
        'teleGears'      => 0,
        'teleTookOff'    => false,
        'tagNoShow'      => false,
        'tagNoMove'      => false,
        'tagFlipped'     => false,
        'tagStuck'       => false,
        'tagFell'        => false,
        'tagPenalized'   => false,
        'prefConfused'   => false,
        'prefSlow'       => false,
        'prefEfficient'  => false,
        'prefPowerhouse' => false
    );

    if ($param[1] !== 'blank') {
        // Search if data is in the matches config key.
        $matchKey = array_search(intval($param[1]), array_column($ec->getConfig('matches'), 'match'));

        if ($matchKey !== false) {
            $defaults['match'] = $ec->getConfig('matches')[$matchKey]['match'];
            $defaults['team'] = $ec->getConfig('matches')[$matchKey][$ec->getConfig('team')];
        }

        $es = new ExtractorScouting('match', $param[1]);
        $data = $es->get();

        // Merge data with defaults.
        $data = array_merge($defaults, $data);


        if (array_key_exists('performance', $data)) {
            $data['pref' . ucfirst($data['performance'])] = true;
        }

        $context = $data;
    } else {
        $context = $defaults;
    }


    echo render('matchForm', $context, 'Match Form');

    return;
}

/**
 * Match Submission Controller
 * Handles incoming data from the match form.
 *
 * @param array $param Router input
 */
function matchSubmit($param) {
    unset($param);

    // Validation array.
    $validate = array(
        'match'        => FILTER_VALIDATE_INT,
        'team'         => FILTER_VALIDATE_INT,
        'autoBaseline' => FILTER_VALIDATE_BOOLEAN,
        'autoGear'     => FILTER_VALIDATE_BOOLEAN,
        'autoFuelHigh' => FILTER_VALIDATE_INT,
        'autoFuelLow'  => FILTER_VALIDATE_INT,
        'teleFuelHigh' => FILTER_VALIDATE_INT,
        'teleFuelLow'  => FILTER_VALIDATE_INT,
        'teleGears'    => FILTER_VALIDATE_INT,
        'teleTookOff'  => FILTER_VALIDATE_BOOLEAN,
        'tagNoShow'    => FILTER_VALIDATE_BOOLEAN,
        'tagNoMove'    => FILTER_VALIDATE_BOOLEAN,
        'tagFlipped'   => FILTER_VALIDATE_BOOLEAN,
        'tagStuck'     => FILTER_VALIDATE_BOOLEAN,
        'tagFell'      => FILTER_VALIDATE_BOOLEAN,
        'tagPenalized' => FILTER_VALIDATE_BOOLEAN,
        'performance'  => null
    );

    $data = filter_input_array(INPUT_POST, $validate, true);

    // Filter data to correct for PHP's filtering.
    foreach ($data as $k => $v) {
        if ($v === null) {
            switch ($validate[$k]) {
                case FILTER_VALIDATE_INT:
                    $data[$k] = 0;
                    break;
                case FILTER_VALIDATE_BOOLEAN:
                    $data[$k] = false;
                    break;
                case null:
                    $data[$k] = 'efficient';
                    break;
                default:
                    break;
            }
        }

        if ($v === false && $validate[$k] === FILTER_VALIDATE_INT) {
            $data[$k] = 0;
        }
    }

    $es = new ExtractorScouting('match', $data['match']);
    $es->set($data);
    $es->save();

    $ec = new ExtractorConfig();

    $matchKey = array_search($data['match'], array_column($ec->getConfig('matches'), 'match'));

    if ($matchKey !== false) {
        // Set current match one up from the last.
        $ec->setConfig('currentMatch', $ec->getConfig('matches')[$matchKey]['match'] + 1);
    } else {
        $append = array(
            'match' => $data['match'],
            'team'  => $data['team']
        );

        ExtractorStorage::append('sys', 'extraMatches', $append);
    }

    redirect('match/current');

    return;
}

/**
 * Current Match Controller
 * Redirects to the current match.
 *
 * @param array $param Router input
 */
function currentMatch($param) {
    unset($param);

    $ec = new ExtractorConfig();

    if (in_array($ec->getConfig('currentMatch'), array_column($ec->getConfig('matches'), 'match'))) {
        redirect('match/' . $ec->getConfig('currentMatch'));

        return;
    }

    redirect('match/blank');

    return;
}

/**
 * Pit List Controller
 * Lists pit data the user is responsible for.
 *
 * @param array $param Router input
 */
function pitList($param) {
    unset($param);

    $ec = new ExtractorConfig();

    $pits = $ec->getConfig('pit');

    // Handle any extra matches.
    $extra = ExtractorStorage::fetch('sys', 'extraPits');
    if ($extra !== false) {
        foreach ($extra as $pit) {
            $pits[] = array(
                'team' => $pit['team']
            );
        }
    }

    $context = array(
        'pits' => $pits
    );

    echo render('pitList', $context, 'Pit List');

    return;
}

function pitScouting($param) {
    //TODO
}

function pitSubmit($param) {
    //TODO
}

function currentPit($param) {
    //TODO
}

/**
 * Transfer Controller
 * Returns the confirm page for beginning data transfer.
 *
 * @param array $param Router input
 */
function transfer($param) {
    unset($param);

    echo render('transfer', array(), 'Transfer');

    return;
}

/**
 * Transfer Display Controller
 * Processes the QRs to display.
 *
 * @param array $param Router input
 */
function transferDisplay($param) {
    unset($param);

    $ec = new ExtractorConfig();

    // If nothing is left to transfer, fail silently.
    $transfer = ExtractorTransferUtil::listNotTransferred();
    if ($transfer === false || count($transfer) === 0) {
        redirect('transfer');

        return;
    }

    $context = array(
        'qrMS' => $ec->getConfig('qrRateMS'),
        'qrs'  => array()
    );

    // Set key num. Start at 1 because 0 is start key.
    $k = 1;
    // Iterate through cat.
    foreach (ExtractorTransferUtil::listNotTransferred() as $cat => $items) {
        // Iterate through data.
        foreach ($items as $item) {
            $es = new ExtractorScouting($cat, $item);

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            $context['qrs'][] = array(
                'key' => $k,
                'src' => ExtractorQR::uri($es->csv())
            );

            $k++;
        }
    }

    $context['qrs'][] = array(
        'key' => 0,
        'src' => ExtractorQR::start($k - 1)
    );

    echo render('transferDisplay', $context, 'Transfer');

    return;
}

/**
 * Transfer Finished Controller
 * Internally clears the not transferred list to avoid sending more data than we have to.
 *
 * @param array $param Router input
 */
function transferFinished($param) {
    unset($param);

    // Fail silently if there is no data.
    $transfer = ExtractorTransferUtil::listNotTransferred();
    if ($transfer === false || count($transfer) === 0) {
        redirect('transfer');

        return;
    }

    ExtractorTransferUtil::setAllTransferred();

    redirect('transfer');

    return;
}

/**
 * Schedule Controller
 * Outputs the schedule page render.
 *
 * @param array $param Router input
 */
function schedule($param) {
    unset($param);

    $ec = new ExtractorConfig();

    $matches = $ec->getConfig('matches');

    foreach ($matches as $k => $match) {
        $matches[$k]['current'] = ($ec->getConfig('currentMatch') === $match['match']);
    }

    $context = array(
        'matches' => $matches
    );

    echo render('schedule', $context, 'Schedule');

    return;
}

/**
 * About Controller
 * Outputs the about page render.
 *
 * @param array $param Router input
 */
function about($param) {
    unset($param);

    $ec = new ExtractorConfig();

    $context = array(
        'deviceID'     => $ec->getConfig('deviceID'),
        'team'         => ExtractorUtil::teamNiceName($ec->getConfig('team')),
        'teamColor'    => ExtractorUtil::teamColor($ec->getConfig('team')),
        'currentMatch' => $ec->getConfig('currentMatch'),
        'qrRateMS'     => $ec->getConfig('qrRateMS')
    );

    echo render('about', $context, 'About');

    return;
}

/**
 * Config Controller
 * When loaded, the hot swap config location is pulled and returns a message.
 *
 * @param array $param Router input
 */
function config($param) {
    unset($param);

    $ec = new ExtractorConfig();
    $check = $ec->fullLoad();

    if ($check) {
        $context = array(
            'msg' => 'It worked! The new configuration has been saved.'
        );
    } else {
        $context = array(
            'msg' => 'Could not find the configuration file. Are you sure the config is in the right place?'
        );
    }

    echo render('config', $context, 'Config');

    return;
}

/**
 * Return 404
 * Returns a 404 to the browser.
 */
function return404() {
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');

    echo render('404', array(), '404');

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
 * @param string $tpl     Template
 * @param array  $context Placeholder context
 * @param string $title   Page title
 *
 * @return false|string
 */
function render($tpl, $context, $title = 'Extractor') {
    if (!isset($tpl) || !isset($context)) {
        return false;
    }

    if (!file_exists(__DIR__ . DS . 'templates' . DS . $tpl . '.mustache')) {
        return false;
    }

    // Define globals.
    $context['title'] = 'Extractor' . ($title !== null ? ' | ' . $title : '');
    $context['BASEURI'] = BASEURI;
    $context['VERSION'] = VERSION;
    $context['navlinks'] = array(
        array(
            'active' => ($tpl == 'matchList' || $tpl === 'matchForm'),
            'link'   => 'match',
            'icon'   => 'view_list',
            'name'   => 'Match'
        ),
        array(
            'active' => ($tpl == 'pitList' || $tpl === 'pitForm'),
            'link'   => 'pit',
            'icon'   => 'view_list',
            'name'   => 'Pit'
        ),
        array(
            'active' => ($tpl == 'transfer' || $tpl === 'transferDisplay'),
            'link'   => 'transfer',
            'icon'   => 'present_to_all',
            'name'   => 'Transfer'
        ),
        array(
            'active' => ($tpl == 'schedule'),
            'link'   => 'schedule',
            'icon'   => 'list',
            'name'   => 'Schedule'
        ),
        array(
            'active' => ($tpl == 'about' || $tpl === 'config'),
            'link'   => 'about',
            'icon'   => 'phonelink_setup',
            'name'   => 'About'
        )
    );

    $mustache = new Mustache_Engine(array(
        'loader'          => new Mustache_Loader_FilesystemLoader(__DIR__ . DS . 'templates'),
        'partials_loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . DS . 'templates' . DS . 'partial')
    ));
    $render = $mustache->loadTemplate($tpl);

    return $render->render($context);
}

$routingArray = array(
    // Index
    array(
        'method' => 'get',
        'func'   => 'index',
        'uri'    => ''
    ),
    // Match list
    array(
        'method' => 'get',
        'func'   => 'matchList',
        'uri'    => 'match'
    ),
    // Match form
    array(
        'method' => 'get',
        'func'   => 'matchForm',
        'uri'    => 'match\/([0-9]{1,}|blank)'
    ),
    // Match data handler
    array(
        'method' => 'post',
        'func'   => 'matchSubmit',
        'uri'    => 'post\/match'
    ),
    // Current match
    array(
        'method' => 'get',
        'func'   => 'currentMatch',
        'uri'    => 'match\/current'
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
        'uri'    => 'pit\/([0-9]{1,}|blank)'
    ),
    // Pit data handler
    array(
        'method' => 'post',
        'func'   => 'pitSubmit',
        'uri'    => 'post\/pit'
    ),
    // Current pit form
    array(
        'method' => 'get',
        'func'   => '',
        'uri'    => 'pit\/current'
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
    // Transfer finish
    array(
        'method' => 'get',
        'func'   => 'transferFinished',
        'uri'    => 'transfer\/finished'
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
    // Configuration
    array(
        'method' => 'get',
        'func'   => 'config',
        'uri'    => 'config'
    )
);

if (!Router::process($routingArray)) {
    $pre = Router::preProcess();
    if (preg_match('/^.{1,}\..{1,}$/', $pre) && file_exists(__DIR__ . DS . $pre)) {
        return false;
    }
    return404();
}
