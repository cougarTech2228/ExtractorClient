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
        switch ($identifier) {
            case 'red1':
            case 'red2':
            case 'red3':
            case 'blue1':
            case 'blue2':
            case 'blue3':
                preg_match('/^(red|blue)([1-3])$/', $identifier, $match);

                return ucfirst($match[1]) . $match[2];
                break;
            default;
                return 'Unknown';
        }
    }
}