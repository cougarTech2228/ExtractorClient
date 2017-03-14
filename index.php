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
define('VERSION', '1.0.0');

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
        $ec->setConfig('currentMatch', $data['match'] + 1);

        // Check if extra already exists for the match.
        $extra = ExtractorStorage::fetch('sys', 'extraMatches');
        // Initialize if extraMatches doesn't exist yet.
        // FIXME: Could use a rework.
        if ($extra === false) {
            $extra = array();
        }

        $extraKey = array_search($data['match'], array_column($extra, 'match'));

        if ($extraKey === false) {
            $append = array(
                'match' => $data['match'],
                'team'  => $data['team']
            );

            ExtractorStorage::append('sys', 'extraMatches', $append);
        } else {
            $extra[$extraKey]['team'] = $data['team'];

            ExtractorStorage::store('sys', 'extraMatches', $extra);
        }
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

    $pits = array();
    foreach ($ec->getConfig('pits') as $pit) {
        $pits[] = array(
            'team'    => $pit['team'],
            'current' => ($ec->getConfig('currentPit') === array_search($pit['team'], array_column($ec->getConfig('pits'), 'team')))

        );
    }

    // Handle any extra matches.
    $extra = ExtractorStorage::fetch('sys', 'extraPits');
    if ($extra !== false) {
        foreach ($extra as $pit) {
            $pits[] = array(
                'team'    => $pit['team'],
                'current' => ($ec->getConfig('currentPit') === array_search($pit['team'], array_column($ec->getConfig('pits'), 'team')))
            );
        }
    }

    $context = array(
        'pits' => $pits
    );

    echo render('pitList', $context, 'Pit List');

    return;
}

/**
 * Pit Form Controller
 * Handles, renders, and pre-fills pit forms with any pre-existing data.
 *
 * @param array $param Router input
 */
function pitForm($param) {
    // Config instance.
    $ec = new ExtractorConfig();

    // Set defaults.
    $defaults = array(
        'team'           => '',
        'autoFuelHigh'   => false,
        'autoFuelLow'    => false,
        'autoBaseline'   => false,
        'autoGear'       => false,
        'autoMultiple'   => false,
        'teleFuelHigh'   => false,
        'teleFuelLow'    => false,
        'teleGear'       => false,
        'teleTakeOff'    => false,
        'teleRoleFuel'   => false,
        'teleRoleGear'   => false,
        'driveTrain4'    => false,
        'driveTrain6'    => false,
        'driveTrainTank' => false,
        'robotCamera'    => false,
        'robotVision'    => false,
        'gearGround'     => false
    );

    if ($param[1] !== 'blank') {
        // Search if data is in the pits config key.
        $pitKey = array_search(intval($param[1]), array_column($ec->getConfig('pits'), 'team'));

        if ($pitKey !== false) {
            $defaults['team'] = $ec->getConfig('pits')[$pitKey]['team'];
        }

        $es = new ExtractorScouting('pit', $param[1]);
        $data = $es->get();

        // Merge data with defaults.
        $data = array_merge($defaults, $data);

        // Handle radio for role.
        if (array_key_exists('teleRole', $data)) {
            $data['teleRole' . ucfirst($data['teleRole'])] = true;
        }

        // Handle radio for drive train.
        if (array_key_exists('driveTrain', $data)) {
            $data['driveTrain' . ucfirst($data['driveTrain'])] = true;
        }

        $context = $data;
    } else {
        $context = $defaults;
    }


    echo render('pitForm', $context, 'Pit Form');

    return;
}

/**
 * Pit Submission Controller
 * Validates, filters, and handles incoming pit data.
 *
 * @param array $param Router input
 */
function pitSubmit($param) {
    unset($param);

    // Validation array.
    $validate = array(
        'team'         => FILTER_VALIDATE_INT,
        'autoFuelHigh' => FILTER_VALIDATE_BOOLEAN,
        'autoFuelLow'  => FILTER_VALIDATE_BOOLEAN,
        'autoBaseline' => FILTER_VALIDATE_BOOLEAN,
        'autoGear'     => FILTER_VALIDATE_BOOLEAN,
        'autoMultiple' => FILTER_VALIDATE_BOOLEAN,
        'teleFuelHigh' => FILTER_VALIDATE_BOOLEAN,
        'teleFuelLow'  => FILTER_VALIDATE_BOOLEAN,
        'teleGear'     => FILTER_VALIDATE_BOOLEAN,
        'teleTakeOff'  => FILTER_VALIDATE_BOOLEAN,
        'teleRole'     => null,
        'driveTrain'   => null,
        'robotCamera'  => FILTER_VALIDATE_BOOLEAN,
        'robotVision'  => FILTER_VALIDATE_BOOLEAN,
        'gearGround'   => FILTER_VALIDATE_BOOLEAN
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
                default:
                    break;
            }

            if ($k === 'teleRole' && $v === null) {
                $data[$k] = 'fuel';
            }

            if ($k === 'driveTrain' && $v === null) {
                $data[$k] = '4';
            }

        }

        if ($v === false && $validate[$k] === FILTER_VALIDATE_INT) {
            $data[$k] = 0;
        }
    }

    $es = new ExtractorScouting('pit', $data['team']);
    $es->set($data);
    $es->save();

    $ec = new ExtractorConfig();

    $pitKey = array_search($data['team'], array_column($ec->getConfig('pits'), 'team'));

    if ($pitKey !== false) {
        // Set current match one up from the last.
        $ec->setConfig('currentPit', $pitKey + 1);
    } else {
        $append = array(
            'team' => $data['team']
        );

        // Check if extra already exists for the match.
        $extra = ExtractorStorage::fetch('sys', 'extraPits');
        // Initialize if extraMatches doesn't exist yet.
        // FIXME: Could use a rework.
        if ($extra === false) {
            $extra = array();
        }

        $extraKey = array_search($data['team'], array_column($extra, 'team'));

        if ($extraKey === false) {
            ExtractorStorage::append('sys', 'extraPits', $append);
        }
    }

    redirect('pit/current');

    return;
}

/**
 * Current Pit Controller
 * Redirects the user to the current pit form.
 *
 * @param array $param Router input
 */
function currentPit($param) {
    unset($param);

    $ec = new ExtractorConfig();

    if (array_key_exists($ec->getConfig('currentPit'), $ec->getConfig('pits'))) {
        $team = $ec->getConfig('pits')[$ec->getConfig('currentPit')]['team'];
        redirect('pit/' . $team);

        return;
    }

    redirect('pit/blank');

    return;
}

/**
 * Driver List Controller
 * Lists driver data the user is responsible for.
 *
 * @param array $param Router input
 */
function driverList($param) {
    unset($param);

    $ec = new ExtractorConfig();

    $drivers = array();
    $extra = ExtractorStorage::fetch('sys', 'extraDriver');
    if ($extra !== false) {
        foreach ($extra as $match) {
            $drivers[] = array(
                'match'   => $match['match'],
                'teamNum' => $match['team'],
                'current' => ($ec->getConfig('currentMatch') === $match['match'])
            );
        }
    }

    $context = array(
        'drivers' => $drivers,
    );

    echo render('driverList', $context, 'Driver List');

    return;
}

/**
 * Driver Form Controller
 * Handles, renders, and pre-fills driver forms with any pre-existing data.
 *
 * @param array $param Router input
 */
function driverForm($param) {
    // Config instance.
    $ec = new ExtractorConfig();

    // Set defaults.
    $defaults = array(
        'match'          => '',
        'team'           => '',
        'prefConfused'   => false,
        'prefSlow'       => false,
        'prefEfficient'  => false,
        'prefPowerhouse' => false
    );

    if ($param[1] !== 'blank') {
        $es = new ExtractorScouting('driver', $param[1]);
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


    echo render('driverForm', $context, 'Driver Form');

    return;
}

/**
 * Pit Submission Controller
 * Validates, filters, and handles incoming pit data.
 *
 * @param array $param Router input
 */
function driverSubmit($param) {
    unset($param);

    // Validation array.
    $validate = array(
        'match'       => FILTER_VALIDATE_INT,
        'team'        => FILTER_VALIDATE_INT,
        'performance' => null
    );

    $data = filter_input_array(INPUT_POST, $validate, true);

    // Filter data to correct for PHP's filtering.
    foreach ($data as $k => $v) {
        if ($v === null) {
            switch ($validate[$k]) {
                case FILTER_VALIDATE_INT:
                    $data[$k] = 0;
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

    $es = new ExtractorScouting('driver', $data['match']);
    $es->set($data);
    $es->save();

    $ec = new ExtractorConfig();

    $ec->setConfig('currentMatch', $data['match'] + 1);

    // Check if extra already exists for the match.
    $extra = ExtractorStorage::fetch('sys', 'extraDriver');
    // Initialize if extraMatches doesn't exist yet.
    // FIXME: Could use a rework.
    if ($extra === false) {
        $extra = array();
    }

    $extraKey = array_search($data['match'], array_column($extra, 'match'));

    if ($extraKey === false) {
        $append = array(
            'match' => $data['match'],
            'team'  => $data['team']
        );

        ExtractorStorage::append('sys', 'extraDriver', $append);
    } else {
        $extra[$extraKey]['team'] = $data['team'];

        ExtractorStorage::store('sys', 'extraDriver', $extra);
    }

    redirect('driver/current');

    return;
}

/**
 * Current Pit Controller
 * Redirects the user to the current pit form.
 *
 * @param array $param Router input
 */
function currentDriver($param) {
    unset($param);

    $ec = new ExtractorConfig();

    $store = ExtractorStorage::fetch('sys', 'extraDriver');

    if ($store === false) {
        $store = array();
    }

    if (in_array($ec->getConfig('currentMatch'), array_column($store, 'match'))) {
        redirect('driver/' . $ec->getConfig('currentMatch'));

        return;
    }

    redirect('driver/blank');

    return;
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
        'currentPit'   => $ec->getConfig('currentPit'),
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
            'active' => ($tpl == 'driverList' || $tpl === 'driverForm'),
            'link'   => 'driver',
            'icon'   => 'view_list',
            'name'   => 'Driver'
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
        'func'   => 'pitForm',
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
        'func'   => 'currentPit',
        'uri'    => 'pit\/current'
    ),
    // Driver list
    array(
        'method' => 'get',
        'func'   => 'driverList',
        'uri'    => 'driver'
    ),
    // Driver form
    array(
        'method' => 'get',
        'func'   => 'driverForm',
        'uri'    => 'driver\/([0-9]{1,}|blank)'
    ),
    // Driver data handler
    array(
        'method' => 'post',
        'func'   => 'driverSubmit',
        'uri'    => 'post\/driver'
    ),
    // Current pit form
    array(
        'method' => 'get',
        'func'   => 'currentDriver',
        'uri'    => 'driver\/current'
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
