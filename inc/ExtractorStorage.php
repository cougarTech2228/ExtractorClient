<?php

/**
 * Class ExtractorStorage
 */
class ExtractorStorage {
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
    public static function store($cat, $key, $data) {
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
    public static function fetch($cat, $key) {
        if (!file_exists(DATADIR . $cat . DS . $key . '.json')) {
            return false;
        }

        return json_decode(file_get_contents(DATADIR . $cat . DS . $key . '.json'), true);
    }   
}