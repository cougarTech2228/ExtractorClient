<?php

/**
 * Class ExtractorStorage
 */
class ExtractorStorage
{
    /**
     * Store Data
     * Stores data in the data store with the given key and category.
     *
     * @param string $cat  Store category
     * @param string $key  File key
     * @param array  $data Data array
     *
     * @return true
     */
    public static function store($cat, $key, $data)
    {
        if (!file_exists(DATADIR . $cat)) {
            mkdir(DATADIR . $cat, 0755);
        }

        file_put_contents(DATADIR . $cat . DS . $key . '.json', json_encode($data, JSON_PRETTY_PRINT));

        return true;
    }

    /**
     * Fetch Store
     * Fetches the given store key in the given category.
     *
     * @param string $cat Store category
     * @param string $key File key
     *
     * @return array|false
     */
    public static function fetch($cat, $key)
    {
        if (!file_exists(DATADIR . $cat . DS . $key . '.json')) {
            return false;
        }

        return json_decode(file_get_contents(DATADIR . $cat . DS . $key . '.json'), true);
    }

    /**
     * Append to Store
     * Appends data to the end of a store.
     *
     * @param string $cat  Store category
     * @param string $key  File key
     * @param array  $data Data array to be appended
     *
     * @return bool
     */
    public static function append($cat, $key, $data)
    {
        $fetch = self::fetch($cat, $key);

        if ($fetch === false) {
            $fetch = [];
        }

        $fetch[] = $data;

        self::store($cat, $key, $fetch);

        return true;
    }

    /**
     * Clear Data Storage
     * Clears the data storage except for the config.
     *
     * @param string|null $suffix Find path suffix.
     *
     * @return bool
     */
    public static function clear($suffix = null)
    {
        // Set suffix if exists
        $basePath = $suffix ? $suffix : DATADIR . $suffix;

        // Recusivly work through the directory to clear it.
        foreach (scandir($basePath) as $item) {
            switch (true) {
                // If dir and not special, recursive callback with new suffix.
                case is_dir($basePath . $item):
                    if (!in_array($item, ['.', '..'])) {
                        self::clear($basePath . $item . DS);
                        rmdir($basePath . DS . $item);
                    }
                    break;
                // If file and not config, remove it.
                case is_file($basePath . $item):
                    if ($item !== 'config.json') {
                        unlink($basePath . $item);
                    }
                    break;
                default:
                    // Cant be bothered to error handle an edge case. Wipe the device and try again.
                    return false;
                    break;
            }
        }

        return true;
    }
}
