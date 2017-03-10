<?php

/**
 * Class ExtractorUtil
 */
class ExtractorUtil {
    /**
     * Team Nice Name
     * Creates a nice name for teams based on an identifier.
     *
     * @param string $identifier Team Identifier
     *
     * @return string
     */
    public static function teamNiceName($identifier) {
        preg_match('/^(red|blue)([1-3])$/', $identifier, $match);

        return ucfirst($match[1]) . ' ' . $match[2];
    }

    /**
     * Team Color
     * Returns color of the team.
     *
     * @param string $identifier
     *
     * @return string
     */
    public static function teamColor($identifier) {
        preg_match('/^(red|blue)[1-3]$/', $identifier, $match);

        switch ($match[1]) {
            case 'red':
                return 'rgb(255,0,0)';
            case 'blue':
                return 'rgb(0,0,255)';
            default:
                return 'rgb(53,53,53)';
        }
    }
}