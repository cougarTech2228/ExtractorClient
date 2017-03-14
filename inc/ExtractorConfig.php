<?php

/**
 * Class ExtractorConfig
 */
class ExtractorConfig {
    private $config;

    /**
     * @var array Array of default config values.
     */
    protected $defaults = array(
        'deviceID'     => 1,
        'team'         => 'red1',
        'currentMatch' => 0,
        'currentPit'   => 0,
        'qrRateMS'     => 1000,
        'matches'      => array(),
        'pits'         => array()
    );

    /**
     * ExtractorClient constructor.
     * Pre-loads config.
     */
    public function __construct() {
        $this->loadConfig();
    }

    /**
     * Load Config
     * Loads Extractor's configuration file.
     *
     * @return true
     */
    private function loadConfig() {

        if (file_exists(CONFIG)) {
            $this->config = file_get_contents(CONFIG);
            $this->config = json_decode($this->config, true);
            $this->config = array_merge($this->defaults, $this->config);
        } else {
            $this->config = $this->defaults;
        }

        return true;
    }

    /**
     * Save Config
     * Saves the changes to the configuration files.
     *
     * @return true
     */
    private function saveConfig() {
        file_put_contents(CONFIG, json_encode($this->config, JSON_PRETTY_PRINT));

        return true;
    }

    /**
     * Get Config
     * Gets the desired configuration key.
     *
     * @param string $key Config key
     *
     * @return mixed
     */
    public function getConfig($key) {
        if (array_key_exists($key, $this->config)) {
            // Special treatment.
            switch ($key) {
                case 'currentMatch':
                    return $this->config[$key] + 1;
                default:
                    return $this->config[$key];
            }
        }

        return false;
    }

    /**
     * Sets Config Key
     * Sets the selected configuration key to the desired value.
     *
     * @param string $key   Config key
     * @param mixed  $value Desired config value
     *
     * @return true;
     */
    public function setConfig($key, $value) {
        if (!is_string($key)) {
            return false;
        }

        if ($value === null && array_key_exists($key, $this->config)) {
            unset($this->config[$key]);
        } else {
            // Special treatment.
            switch ($key) {
                case 'currentMatch':
                    // Since it already was incremented, save that value - 1.
                    $this->config[$key] = $value - 1;
                    break;
                default:
                    $this->config[$key] = $value;
                    break;
            }
        }

        $this->saveConfig();

        return true;
    }

    /**
     * Full Load
     * Load configuration data fully from hot swap config.
     *
     * @return bool
     */
    public function fullLoad() {
        // Check if mounted.
        if (!file_exists(DATASEARCHPATH)) {
            return false;
        }

        // Scan for files.
        $files = scandir(DATASEARCHPATH);

        if (in_array('config.json', $files) && is_file(DATASEARCHPATH . 'config.json')) {
            $new = file_get_contents(DATASEARCHPATH . 'config.json');
            $new = json_decode($new, true);

            if (!is_array($new)) {
                return false;
            }

            $this->config = array_merge($this->defaults, $this->config, $new);
            $this->saveConfig();

            return true;
        }

        return false;
    }
}
