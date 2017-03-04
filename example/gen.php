<?php
// Generate config.
$a = array(
    'deviceID'     => 1,
    'team'         => 'red1',
    'currentMatch' => 1,
    'qrRateMS'     => 1000,
    'matches'      => array(
        array(
            'match' => 1,
            'red1'  => 2228,
            'red2'  => 1234,
            'red3'  => 4321,
            'blue1' => 1122,
            'blue2' => 2211,
            'blue3' => 3321
        ),
        array(
            'match' => 2,
            'red1'  => 2228,
            'red2'  => 1234,
            'red3'  => 4321,
            'blue1' => 1122,
            'blue2' => 2211,
            'blue3' => 3321
        )
    )
);

file_put_contents('config.json', json_encode($a, JSON_PRETTY_PRINT));

