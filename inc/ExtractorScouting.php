<?php

/**
 * Class ExtractorScouting
 */
class ExtractorScouting
{
    private $type, $key, $data;

    /**
     * ExtractorScouting constructor.
     * Initializes private variable with current storage contents.
     *
     * @param string $type Type of data (match or pit)
     * @param string $key  Match number or team number.
     */
    public function __construct($type, $key)
    {
        $this->type = $type;
        $this->key = $key;

        $this->data = ExtractorStorage::fetch($this->type, $this->key);

        if ($this->data === false) {
            $this->data = [];
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
    public function set($data)
    {
        if (!is_array($data)) {
            return false;
        }

        $this->data = $data;

        return true;
    }

    /**
     * Save Data
     * Saves the data to disk and marks the data as not transferred using ExtractorTransferUtil.
     * @return true
     */
    public function save()
    {
        ExtractorStorage::store($this->type, $this->key, $this->data);
        ExtractorTransferUtil::setTransferred($this->type, $this->key, false);

        return true;
    }

    /**
     * Get Data
     * Get the data file.
     * @return array
     */
    public function get()
    {
        return $this->data;
    }

    /**
     * Format to CSV
     * Formats the data to be outputted as a CSV.
     * @return string
     */
    public function csv()
    {
        // Check if match data or pit data
        switch ($this->type) {
            case 'match':
                $order = [
                    'matchNumber',
                    'teamNumber',
                    'autoRun',
                    'autoSwitch',
                    'autoScale',
                    'teleAllySwitch',
                    'teleScale',
                    'teleOppSwitch',
                    'teleVault',
                    'endClimb',
                    'endPark',
                    'performance',
                    'tagNoShow',
                    'tagNoMove',
                    'tagFlipped',
                    'tagStuck',
                    'tagFell',
                    'tagPenalized'
                ];
                break;
            case 'pit':
                $order = [
                    'teamNumber',
                    'autoRun',
                    'autoSwitch',
                    'autoScale',
                    'teleAllySwitch',
                    'teleScale',
                    'teleOppSwitch',
                    'teleVault',
                    'endClimb',
                    'endPark',
                    'robotCamera',
                    'robotVision',
                    'driveTrain',
                    'cubePortal',
                    'cubeGround',
                    'cubeRotate',
                    'mainRole'
                ];
                break;
            case 'driver':
                $order = [
                    'matchNumber',
                    'teamNumber',
                    'performance',
                ];
                break;
            default:
                $order = [];
                break;
        }

        $return = [];
        foreach ($order as $item) {
            switch (true) {
                case ($item === 'performance'):
                    $str = $this->data[$item] === 'c' ? 1 : 0;
                    $str .= ',';
                    $str .= $this->data[$item] === 's' ? 1 : 0;
                    $str .= ',';
                    $str .= $this->data[$item] === 'e' ? 1 : 0;
                    $str .= ',';
                    $str .= $this->data[$item] === 'p' ? 1 : 0;

                    $return[] = $str;
                    break;
                case ($item === 'mainRole'):
                case ($item === 'driveTrain'):

                    $return[] = strtoupper($this->data[$item]);
                    break;
                case (is_bool($this->data[$item])):
                    $return[] = ($this->data[$item]) ? 1 : 0;
                    break;
                case (is_int($this->data[$item])):
                    $return[] = intval($this->data[$item]);
                    break;
                default:
                    break;
            }
        }

        $return = strtoupper($this->type) . ':' . implode(',', $return);

        return $return;
    }
}
