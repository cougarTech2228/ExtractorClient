<?php

/**
 * Class ExtractorConfig
 */
class ExtractorConfig {
    private $config;

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
        $this->config = file_get_contents(CONFIG);
        $this->config = json_decode($this->config, true);

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
            return $this->config[$key];
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
            $this->config[$key] = $value;
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
        foreach ($files as $file) {
            if (is_file(DATASEARCHPATH . $file) && $file === 'config.json') {
                $new = file_get_contents(DATASEARCHPATH . $file);
                $new = json_decode($new, true);

                if (!is_array($new)) {
                    return false;
                }

                $this->config = $new;
                $this->saveConfig();

                return true;
            }
        }

        return false;
    }
}
