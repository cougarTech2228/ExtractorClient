<?php

/**
 * Class ExtractorTransferUtil
 */
class ExtractorTransferUtil {
    /**
     * List Not Transferred
     * Lists the currently not transferred data.
     *
     * @return array|false
     */
    public static function listNotTransferred() {
        $list = ExtractorStorage::fetch('sys', 'notTransferred');

        return $list;
    }

    /**
     * Set Transferred Status
     * Sets the transferred status for the given $cat $key combo.
     *
     * @param string $cat           Category
     * @param string $key           Key
     * @param bool   $isTransferred Set transferred
     *
     * @return true
     */
    public static function setTransferred($cat, $key, $isTransferred) {
        $list = ExtractorStorage::fetch('sys', 'notTransferred');

        // Prep if it doesn't exist.
        if ($list === false) {
            $list = array();
        }

        if (!array_key_exists($cat, $list)) {
            $list[$cat] = array();
        }

        // Add if not transferred, else remove.
        if (!$isTransferred && !in_array($key, $list[$cat])) {
            $list[$cat][] = $key;
        } else {
            $arrayKey = array_search($key, $list[$cat]);
            unset($list[$cat][$arrayKey]);
        }

        ExtractorStorage::store('sys', 'notTransferred', $list);

        return true;
    }

    /**
     * Set All Transferred
     * Removes all not transferred items.
     *
     * @return true
     */
    public static function setAllTransferred() {
        $list = ExtractorStorage::fetch('sys', 'notTransferred');

        if ($list === false) {
            return true;
        }

        ExtractorStorage::store('sys', 'notTransferred', array());

        return true;
    }
}