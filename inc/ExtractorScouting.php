<?php

/**
 * Class ExtractorScouting
 */
class ExtractorScouting {
    private $type, $key, $data;

    /**
     * ExtractorScouting constructor.
     * Initializes private variable with current storage contents.
     *
     * @param string $type Type of data (match or pit)
     * @param string $key  Match number or team number.
     */
    public function __construct($type, $key) {
        $this->type = $type;
        $this->key = $key;

        $this->data = ExtractorStorage::fetch($this->type, $this->key);

        if ($this->data === false) {
            $this->data = array();
        }
    }

    /**
     * Set Data
     * Pre handler for data before being passed into the storage.
     *
     * @param array $data Data array
     *
     * @return bool
     */
    public function set($data) {
        if (!is_array($data)) {
            return false;
        }

        $this->data = $data;

        return true;
    }

    /**
     * Save Data
     * Saves the data to disk and marks the data as not transferred using ExtractorTransferUtil.
     *
     * @return true
     */
    public function save() {
        ExtractorStorage::store($this->type, $this->key, $this->data);
        ExtractorTransferUtil::setTransferred($this->type, $this->key, false);

        return true;
    }

    /**
     * Get Data
     * Get the data file.
     *
     * @return array
     */
    public function get() {
        return $this->data;
    }

    public function csv() {
//TODO
    }
}