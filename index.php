<?php

/////////////
// Globals //
/////////////
define('BASEURL', 'http://localhost:9999');
define('BASEURI', '/');
define('DS', DIRECTORY_SEPARATOR);
define('DATADIR', __DIR__ . DS . 'data' . DS);
define('DATASEARCHPATH', DS . 'storage' . DS . 'emulated' . DS . '0' . DS . 'bluetooth' . DS);
define('CONFIG', DATADIR . 'config.json');
// Dear users of github, yes this is 'bad' but really, doesn't matter. It's just made to prevent "Oops I deleted the data" situations.
define('CONFIGPWDHASH', '$2y$10$rYrLJ3BnHIO2lFk.ilVFAeHigptddTLwdsisBxT/gCgwdlSxuDnBy');
define('VERSION', '1.0.1');

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
function index($param)
{
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
function matchList($param)
{
    unset($param);

    $ec = new ExtractorConfig();

    $matches = [];
    foreach ($ec->getConfig('matches') as $match) {
        $matches[] = [
            'match'   => $match['match'],
            'teamNum' => $match[$ec->getConfig('team')],
            'current' => ($ec->getConfig('currentMatch') === $match['match'])
        ];
    }

    // Handle any extra matches.
    $extra = ExtractorStorage::fetch('sys', 'extraMatches');
    if ($extra !== false) {
        foreach ($extra as $match) {
            $matches[] = [
                'match'   => $match['match'],
                'teamNum' => $match['team'],
                'current' => ($ec->getConfig('currentMatch') === $match['match'])
            ];
        }
    }

    $context = [
        'team'      => ExtractorUtil::teamNiceName($ec->getConfig('team')),
        'teamColor' => ExtractorUtil::teamColor($ec->getConfig('team')),
        'matches'   => $matches,
    ];

    echo render('matchList', $context, 'Match List');

    return;
}

/**
 * Match Form Controller
 * Handles pre-filling and rendering the match forms.
 *
 * @param array $param Router input
 */
function matchForm($param)
{
    // Config instance.
    $ec = new ExtractorConfig();

    // Set defaults.
    $defaults = [
        'matchNumber'    => '',
        'teamNumber'     => '',
        'autoRun'        => false,
        'autoSwitch'     => false,
        'autoScale'      => false,
        'teleAllySwitch' => 0,
        'teleScale'      => 0,
        'teleOppSwitch'  => 0,
        'teleVault'      => 0,
        'endC'           => false,
        'endP'           => false,
        'endN'           => false,
        'prefC'          => false,
        'prefS'          => false,
        'prefE'          => false,
        'prefP'          => false,
        'tagNoShow'      => false,
        'tagNoMove'      => false,
        'tagFlipped'     => false,
        'tagStuck'       => false,
        'tagFell'        => false,
        'tagPenalized'   => false
    ];

    if ($param[1] !== 'blank') {
        // Search if data is in the matches config key.
        $matchKey = array_search(intval($param[1]), array_column($ec->getConfig('matches'), 'match'));

        if ($matchKey !== false) {
            $defaults['matchNumber'] = $ec->getConfig('matches')[$matchKey]['match'];
            $defaults['teamNumber'] = $ec->getConfig('matches')[$matchKey][$ec->getConfig('team')];
        }

        $es = new ExtractorScouting('match', $param[1]);
        $data = $es->get();

        // Merge data with defaults.
        $data = array_merge($defaults, $data);


        if (array_key_exists('performance', $data)) {
            $data['pref' . ucfirst($data['performance'])] = true;
        }

        if (array_key_exists('endGame', $data)) {
            $data['end' . ucfirst($data['endGame'])] = true;
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
function matchSubmit($param)
{
    unset($param);

    // Validation array.
    $validate = [
        'matchNumber'    => FILTER_VALIDATE_INT,
        'teamNumber'     => FILTER_VALIDATE_INT,
        'autoRun'        => FILTER_VALIDATE_BOOLEAN,
        'autoSwitch'     => FILTER_VALIDATE_BOOLEAN,
        'autoScale'      => FILTER_VALIDATE_BOOLEAN,
        'teleAllySwitch' => FILTER_VALIDATE_INT,
        'teleScale'      => FILTER_VALIDATE_INT,
        'teleOppSwitch'  => FILTER_VALIDATE_INT,
        'teleVault'      => FILTER_VALIDATE_INT,
        'endGame'        => [
            'filter'  => FILTER_CALLBACK,
            'options' => function ($input) {
                return in_array($input, ['c', 'p', 'n']) ? $input : 'n';
            }
        ],
        'performance'    => [
            'filter'  => FILTER_CALLBACK,
            'options' => function ($input) {
                return in_array($input, ['c', 's', 'e', 'p']) ? $input : 'c';
            }
        ],
        'tagNoShow'      => FILTER_VALIDATE_BOOLEAN,
        'tagNoMove'      => FILTER_VALIDATE_BOOLEAN,
        'tagFlipped'     => FILTER_VALIDATE_BOOLEAN,
        'tagStuck'       => FILTER_VALIDATE_BOOLEAN,
        'tagFell'        => FILTER_VALIDATE_BOOLEAN,
        'tagPenalized'   => FILTER_VALIDATE_BOOLEAN
    ];

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
        }

        if ($v === false && $validate[$k] === FILTER_VALIDATE_INT) {
            $data[$k] = 0;
        }
    }

    $es = new ExtractorScouting('match', $data['matchNumber']);
    $es->set($data);
    $es->save();

    $ec = new ExtractorConfig();

    $matchKey = array_search($data['matchNumber'], array_column($ec->getConfig('matches'), 'match'));

    if ($matchKey !== false) {
        // Set current match one up from the last.
        $ec->setConfig('currentMatch', $ec->getConfig('matches')[$matchKey]['match'] + 2); // FIXME: Redo current
        // match system
    } else {
        $ec->setConfig('currentMatch', $data['matchNumber'] + 1);

        // Check if extra already exists for the match.
        $extra = ExtractorStorage::fetch('sys', 'extraMatches');
        // Initialize if extraMatches doesn't exist yet.
        // FIXME: Could use a rework.
        if ($extra === false) {
            $extra = [];
        }

        $extraKey = array_search($data['matchNumber'], array_column($extra, 'match'));

        if ($extraKey === false) {
            $append = [
                'match' => $data['matchNumber'],
                'team'  => $data['teamNumber']
            ];

            ExtractorStorage::append('sys', 'extraMatches', $append);
        } else {
            $extra[$extraKey]['team'] = $data['teamNumber'];

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
function currentMatch($param)
{
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
function pitList($param)
{
    unset($param);

    $ec = new ExtractorConfig();

    $pits = [];
    foreach ($ec->getConfig('pits') as $pit) {
        $pits[] = [
            'team'    => $pit['team'],
            'current' => ($ec->getConfig('currentPit') === array_search($pit['team'], array_column($ec->getConfig('pits'), 'team')))

        ];
    }

    // Handle any extra matches.
    $extra = ExtractorStorage::fetch('sys', 'extraPits');
    if ($extra !== false) {
        foreach ($extra as $pit) {
            $pits[] = [
                'team'    => $pit['team'],
                'current' => ($ec->getConfig('currentPit') === array_search($pit['team'], array_column($ec->getConfig('pits'), 'team')))
            ];
        }
    }

    $context = [
        'pits' => $pits
    ];

    echo render('pitList', $context, 'Pit List');

    return;
}

/**
 * Pit Form Controller
 * Handles, renders, and pre-fills pit forms with any pre-existing data.
 *
 * @param array $param Router input
 */
function pitForm($param)
{
    // Config instance.
    $ec = new ExtractorConfig();

    // Set defaults.
    $defaults = [
        'teamNumber'     => '',
        'autoRun'        => false,
        'autoSwitch'     => false,
        'autoScale'      => false,
        'teleAllySwitch' => false,
        'teleOppSwitch'  => false,
        'teleScale'      => false,
        'teleVault'      => false,
        'endPark'        => false,
        'endClimb'       => false,
        'mainRoleV'      => false,
        'mainRoleW'      => false,
        'mainRoleS'      => false,
        'mainRoleF'      => false,
        'cubePortal'     => false,
        'cubeGround'     => false,
        'cubeRotate'     => false,
        'driveTrain4'    => false,
        'driveTrain6'    => false,
        'driveTrainT'    => false,
        'driveTrainM'    => false,
        'driveTrainS'    => false,
        'robotCamera'    => false,
        'robotVision'    => false
    ];

    if ($param[1] !== 'blank') {
        // Search if data is in the pits config key.
        $pitKey = array_search(intval($param[1]), array_column($ec->getConfig('pits'), 'team'));

        if ($pitKey !== false) {
            $defaults['teamNumber'] = $ec->getConfig('pits')[$pitKey]['team'];
        }

        $es = new ExtractorScouting('pit', $param[1]);
        $data = $es->get();

        // Merge data with defaults.
        $data = array_merge($defaults, $data);

        // Handle radio for role.
        if (array_key_exists('mainRole', $data)) {
            $data['mainRole' . ucfirst($data['mainRole'])] = true;
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
function pitSubmit($param)
{
    unset($param);

    // Validation array.
    $validate = [
        'teamNumber'     => FILTER_VALIDATE_INT,
        'autoRun'        => FILTER_VALIDATE_BOOLEAN,
        'autoSwitch'     => FILTER_VALIDATE_BOOLEAN,
        'autoScale'      => FILTER_VALIDATE_BOOLEAN,
        'teleAllySwitch' => FILTER_VALIDATE_BOOLEAN,
        'teleOppSwitch'  => FILTER_VALIDATE_BOOLEAN,
        'teleScale'      => FILTER_VALIDATE_BOOLEAN,
        'teleVault'      => FILTER_VALIDATE_BOOLEAN,
        'endPark'        => FILTER_VALIDATE_BOOLEAN,
        'endClimb'       => FILTER_VALIDATE_BOOLEAN,
        'mainRole'       => [
            'filter'  => FILTER_CALLBACK,
            'options' => function ($input) {
                return in_array($input, ['v', 'w', 's', 'f']) ? $input : 'f';
            }
        ],
        'cubePortal'     => FILTER_VALIDATE_BOOLEAN,
        'cubeGround'     => FILTER_VALIDATE_BOOLEAN,
        'cubeRotate'     => FILTER_VALIDATE_BOOLEAN,
        'driveTrain'     => [
            'filter'  => FILTER_CALLBACK,
            'options' => function ($input) {
                return in_array($input, ['4', '6', 't', 'm', 's']) ? $input : '4';
            }
        ],
        'robotCamera'    => FILTER_VALIDATE_BOOLEAN,
        'robotVision'    => FILTER_VALIDATE_BOOLEAN
    ];

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

            if ($k === 'mainRole' && $v === null) {
                $data[$k] = 'v';
            }

            if ($k === 'driveTrain' && $v === null) {
                $data[$k] = '4';
            }

        }

        if ($v === false && $validate[$k] === FILTER_VALIDATE_INT) {
            $data[$k] = 0;
        }
    }

    $es = new ExtractorScouting('pit', $data['teamNumber']);
    $es->set($data);
    $es->save();

    $ec = new ExtractorConfig();

    $pitKey = array_search($data['teamNumber'], array_column($ec->getConfig('pits'), 'team'));

    if ($pitKey !== false) {
        // Set current match one up from the last.
        $ec->setConfig('currentPit', $pitKey + 1);
    } else {
        $append = [
            'team' => $data['teamNumber']
        ];

        // Check if extra already exists for the match.
        $extra = ExtractorStorage::fetch('sys', 'extraPits');
        // Initialize if extraMatches doesn't exist yet.
        // FIXME: Could use a rework.
        if ($extra === false) {
            $extra = [];
        }

        $extraKey = array_search($data['teamNumber'], array_column($extra, 'team'));

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
function currentPit($param)
{
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
function driverList($param)
{
    unset($param);

    $ec = new ExtractorConfig();

    $drivers = [];
    $extra = ExtractorStorage::fetch('sys', 'extraDriver');
    if ($extra !== false) {
        foreach ($extra as $match) {
            $drivers[] = [
                'match'   => $match['matchNumber'],
                'teamNum' => $match['teamNumber'],
                'current' => ($ec->getConfig('currentMatch') === $match['matchNumber'])
            ];
        }
    }

    $context = [
        'drivers' => $drivers,
    ];

    echo render('driverList', $context, 'Driver List');

    return;
}

/**
 * Driver Form Controller
 * Handles, renders, and pre-fills driver forms with any pre-existing data.
 *
 * @param array $param Router input
 */
function driverForm($param)
{
    // Set defaults.
    $defaults = [
        'matchNumber' => '',
        'teamNumber'  => '',
        'prefC'       => false,
        'prefS'       => false,
        'prefE'       => false,
        'prefP'       => false
    ];

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
function driverSubmit($param)
{
    unset($param);

    // Validation array.
    $validate = [
        'matchNumber' => FILTER_VALIDATE_INT,
        'teamNumber'  => FILTER_VALIDATE_INT,
        'performance' => [
            'filter'  => FILTER_CALLBACK,
            'options' => function ($input) {
                return in_array($input, ['c', 's', 'e', 'p']) ? $input : 'c';
            }
        ],
    ];

    $data = filter_input_array(INPUT_POST, $validate, true);

    // Filter data to correct for PHP's filtering.
    foreach ($data as $k => $v) {
        if ($v === null) {
            switch ($validate[$k]) {
                case FILTER_VALIDATE_INT:
                    $data[$k] = 0;
                    break;
                case null:
                    $data[$k] = 'c';
                    break;
                default:
                    break;
            }
        }

        if ($v === false && $validate[$k] === FILTER_VALIDATE_INT) {
            $data[$k] = 0;
        }
    }

    $es = new ExtractorScouting('driver', $data['matchNumber']);
    $es->set($data);
    $es->save();

    $ec = new ExtractorConfig();

    $ec->setConfig('currentMatch', $data['matchNumber'] + 1);

    // Check if extra already exists for the match.
    $extra = ExtractorStorage::fetch('sys', 'extraDriver');
    // Initialize if extraMatches doesn't exist yet.
    // FIXME: Could use a rework.
    if ($extra === false) {
        $extra = [];
    }

    $extraKey = array_search($data['matchNumber'], array_column($extra, 'matchNumber'));

    if ($extraKey === false) {
        $append = [
            'matchNumber' => $data['matchNumber'],
            'teamNumber'  => $data['teamNumber']
        ];

        ExtractorStorage::append('sys', 'extraDriver', $append);
    } else {
        $extra[$extraKey]['teamNumber'] = $data['teamNumber'];

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
function currentDriver($param)
{
    unset($param);

    $ec = new ExtractorConfig();

    $store = ExtractorStorage::fetch('sys', 'extraDriver');

    if ($store === false) {
        $store = [];
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
function transfer($param)
{
    unset($param);

    echo render('transfer', [], 'Transfer');

    return;
}

/**
 * Transfer Display Controller
 * Processes the QRs to display.
 *
 * @param array $param Router input
 */
function transferDisplay($param)
{
    unset($param);

    $ec = new ExtractorConfig();

    // If nothing is left to transfer, fail silently.
    $transfer = ExtractorTransferUtil::listNotTransferred();
    if ($transfer === false || count($transfer) === 0) {
        redirect('transfer');

        return;
    }

    $context = [
        'qrMS' => $ec->getConfig('qrRateMS'),
        'qrs'  => []
    ];

    // Set key num. Start at 1 because 0 is start key.
    $k = 1;
    // Iterate through cat.
    foreach (ExtractorTransferUtil::listNotTransferred() as $cat => $items) {
        // Iterate through data.
        foreach ($items as $item) {
            $es = new ExtractorScouting($cat, $item);

            /** @noinspection PhpVoidFunctionResultUsedInspection */
            $context['qrs'][] = [
                'key' => $k,
                'src' => ExtractorQR::uri($es->csv())
            ];

            $k++;
        }
    }

    $context['qrs'][] = [
        'key' => 0,
        'src' => ExtractorQR::start($k - 1)
    ];

    echo render('transferDisplay', $context, 'Transfer');

    return;
}

/**
 * Transfer Finished Controller
 * Internally clears the not transferred list to avoid sending more data than we have to.
 *
 * @param array $param Router input
 */
function transferFinished($param)
{
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
function schedule($param)
{
    unset($param);

    $ec = new ExtractorConfig();

    $matches = $ec->getConfig('matches');

    foreach ($matches as $k => $match) {
        $matches[$k]['current'] = ($ec->getConfig('currentMatch') === $match['match']);
    }

    $context = [
        'matches' => $matches
    ];

    echo render('schedule', $context, 'Schedule');

    return;
}

/**
 * Pwd Controller
 * Display pwd view.
 *
 * @param array $param Router input
 */
function pwd($param)
{
    unset ($param);

    echo render('pwd', [], 'Password Required');

    return;
}

/**
 * Auth Check Controller
 * Authenticate to the configuration view.
 *
 * @param array $param Router input
 */
function pwdCheck($param)
{
    unset($param);

    $Auth = new ExtractorAuth();

    if ($Auth->auth(filter_input(INPUT_POST, 'pwd'))) {
        redirect('about');
    } else {
        redirect('pwd');
    }

    return;
}

/**
 * Deauth
 * Deauth controller.
 *
 * @param array $param Router input
 */
function authExit($param)
{
    unset ($param);

    $Auth = new ExtractorAuth();

    $Auth->deauth();

    redirect('');

    return;
}

/**
 * About Controller
 * Outputs the about page render.
 *
 * @param array $param Router input
 */
function about($param)
{
    unset($param);

    $Auth = new ExtractorAuth();

    if (!$Auth->isAuthed()) {
        redirect('pwd');

        return;
    }

    $ec = new ExtractorConfig();

    $context = [
        'deviceID'     => $ec->getConfig('deviceID'),
        'team'         => ExtractorUtil::teamNiceName($ec->getConfig('team')),
        'teamColor'    => ExtractorUtil::teamColor($ec->getConfig('team')),
        'currentMatch' => $ec->getConfig('currentMatch'),
        'currentPit'   => $ec->getConfig('currentPit'),
        'qrRateMS'     => $ec->getConfig('qrRateMS')
    ];

    echo render('about', $context, 'About');

    return;
}

/**
 * Config Controller
 * When loaded, the hot swap config location is pulled and returns a message.
 *
 * @param array $param Router input
 */
function config($param)
{
    unset($param);

    $Auth = new ExtractorAuth();

    if (!$Auth->isAuthed()) {
        redirect('pwd');

        return;
    }

    $ec = new ExtractorConfig();
    $check = $ec->fullLoad();

    if ($check) {
        $context = [
            'msg' => 'It worked! The new configuration has been saved.'
        ];
    } else {
        $context = [
            'msg' => 'Could not find the configuration file. Are you sure the config is in the right place?'
        ];
    }

    echo render('config', $context, 'Config');

    return;
}

/**
 * Set Team Controller
 * Handles setting the team without a config hotswap.
 *
 * @param array $param Router input
 */
function setTeam($param)
{
    unset($param);

    $Auth = new ExtractorAuth();

    if (!$Auth->isAuthed()) {
        redirect('pwd');

        return;
    }

    $team = filter_input(INPUT_GET, 'team');

    $allowedTeams = [
        'red1',
        'red2',
        'red3',
        'blue1',
        'blue2',
        'blue3'
    ];

    if (in_array($team, $allowedTeams)) {
        $oc = new ExtractorConfig();
        $oc->setConfig('team', $team);
    }

    redirect('about');
}

/**
 * Clear Device Data
 * Clears the device data except for config.
 *
 * @param array $param Router input
 */
function clear($param)
{
    unset($param);

    $Auth = new ExtractorAuth();

    if (!$Auth->isAuthed()) {
        redirect('pwd');

        return;
    }


    ExtractorStorage::clear();

    redirect('about');
}


/**
 * Return 404
 * Returns a 404 to the browser.
 */
function return404()
{
    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');

    echo render('404', [], '404');

    return;
}

/**
 * Redirect
 * Redirects browser to desired location.
 *
 * @param string $uri Redirect URI
 */
function redirect($uri)
{
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
function render($tpl, $context, $title = 'Extractor')
{
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
    $context['navlinks'] = [
        [
            'active' => ($tpl == 'matchList' || $tpl === 'matchForm'),
            'link'   => 'match',
            'icon'   => 'view_list',
            'name'   => 'Match'
        ],
        [
            'active' => ($tpl == 'pitList' || $tpl === 'pitForm'),
            'link'   => 'pit',
            'icon'   => 'view_list',
            'name'   => 'Pit'
        ],
        /*[
            'active' => ($tpl == 'driverList' || $tpl === 'driverForm'),
            'link'   => 'driver',
            'icon'   => 'view_list',
            'name'   => 'Driver'
        ],*/
        [
            'active' => ($tpl == 'transfer' || $tpl === 'transferDisplay'),
            'link'   => 'transfer',
            'icon'   => 'present_to_all',
            'name'   => 'Transfer'
        ],
        [
            'active' => ($tpl == 'schedule'),
            'link'   => 'schedule',
            'icon'   => 'list',
            'name'   => 'Schedule'
        ],
        [
            'active' => ($tpl == 'about' || $tpl === 'config'),
            'link'   => 'about',
            'icon'   => 'phonelink_setup',
            'name'   => 'About'
        ]
    ];

    $mustache = new Mustache_Engine([
        'loader'          => new Mustache_Loader_FilesystemLoader(__DIR__ . DS . 'templates'),
        'partials_loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . DS . 'templates' . DS . 'partial')
    ]);
    $render = $mustache->loadTemplate($tpl);

    return $render->render($context);
}

$routingArray = [
    // Index
    [
        'method' => 'get',
        'func'   => 'index',
        'uri'    => ''
    ],
    // Match list
    [
        'method' => 'get',
        'func'   => 'matchList',
        'uri'    => 'match'
    ],
    // Match form
    [
        'method' => 'get',
        'func'   => 'matchForm',
        'uri'    => 'match\/([0-9]{1,}|blank)'
    ],
    // Match data handler
    [
        'method' => 'post',
        'func'   => 'matchSubmit',
        'uri'    => 'post\/match'
    ],
    // Current match
    [
        'method' => 'get',
        'func'   => 'currentMatch',
        'uri'    => 'match\/current'
    ],
    // Pit list
    [
        'method' => 'get',
        'func'   => 'pitList',
        'uri'    => 'pit'
    ],
    // Pit form
    [
        'method' => 'get',
        'func'   => 'pitForm',
        'uri'    => 'pit\/([0-9]{1,}|blank)'
    ],
    // Pit data handler
    [
        'method' => 'post',
        'func'   => 'pitSubmit',
        'uri'    => 'post\/pit'
    ],
    // Current pit form
    [
        'method' => 'get',
        'func'   => 'currentPit',
        'uri'    => 'pit\/current'
    ],
    // Driver list
    [
        'method' => 'get',
        'func'   => 'driverList',
        'uri'    => 'driver'
    ],
    // Driver form
    [
        'method' => 'get',
        'func'   => 'driverForm',
        'uri'    => 'driver\/([0-9]{1,}|blank)'
    ],
    // Driver data handler
    [
        'method' => 'post',
        'func'   => 'driverSubmit',
        'uri'    => 'post\/driver'
    ],
    // Current pit form
    [
        'method' => 'get',
        'func'   => 'currentDriver',
        'uri'    => 'driver\/current'
    ],
    // Transfer
    [
        'method' => 'get',
        'func'   => 'transfer',
        'uri'    => 'transfer'
    ],
    // Transfer Begin
    [
        'method' => 'get',
        'func'   => 'transferDisplay',
        'uri'    => 'transfer\/display'
    ],
    // Transfer finish
    [
        'method' => 'get',
        'func'   => 'transferFinished',
        'uri'    => 'transfer\/finished'
    ],
    // Schedule
    [
        'method' => 'get',
        'func'   => 'schedule',
        'uri'    => 'schedule'
    ],
    // About
    [
        'method' => 'get',
        'func'   => 'about',
        'uri'    => 'about'
    ],
    // Pwd
    [
        'method' => 'get',
        'func'   => 'pwd',
        'uri'    => 'pwd'
    ],
    // Pwd check
    [
        'method' => 'post',
        'func'   => 'pwdCheck',
        'uri'    => 'pwd\/check'
    ],
    // Exit
    [
        'method' => 'get',
        'func'   => 'authExit',
        'uri'    => 'exit'
    ],
    // Configuration
    [
        'method' => 'get',
        'func'   => 'config',
        'uri'    => 'config'
    ],
    // Set Team
    [
        'method' => 'get',
        'func'   => 'setTeam',
        'uri'    => 'setteam'
    ],
    // Clear data
    [
        'method' => 'get',
        'func'   => 'clear',
        'uri'    => 'clear'
    ]
];

if (!Router::process($routingArray)) {
    $pre = Router::preProcess();
    if (preg_match('/^.{1,}\..{1,}$/', $pre) && file_exists(__DIR__ . DS . $pre)) {
        return false;
    }
    return404();
}
