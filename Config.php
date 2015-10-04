<?php
/**
 * Zend Framework
 *
 * @package    Zend_Config
 * @version    $Id: Ini.php 23484 2010-12-10 03:57:59Z mjh_ca $
 */
class Kansas_Config {
  
  /**
   * Loads the section $section from the config file $filename for
   * access facilitated by nested object properties.
   *
   * If the section name contains a ":" then the section name to the right
   * is loaded and included into the properties. Note that the keys in
   * this $section will override any keys of the same
   * name in the sections that have been included via ":".
   *
   * If the $section is null, then all sections in the ini file are loaded.
   *
   * If any key includes a ".", then this will act as a separator to
   * create a sub-property.
   *
   * example ini file:
   *      [all]
   *      db.connection = database
   *      hostname = live
   *
   *      [staging : all]
   *      hostname = staging
   *
   * after calling $data = new Zend_Config_Ini($file, 'staging'); then
   *      $data->hostname === "staging"
   *      $data->db->connection === "database"
   *
   * The $options parameter may be provided as either a boolean or an array.
   * If provided as a boolean, this sets the $allowModifications option of
   * Zend_Config. If provided as an array, there are two configuration
   * directives that may be set. For example:
   *
   * $options = array(
   *     'allowModifications' => false,
   *     'nestSeparator'      => '->'
   *      );
   *
   * @param  string        $filename
   * @param  string|null   $section
   * @param  boolean|array $options
   * @throws Zend_Config_Exception
   * @return void
   */  
  public static function ParseIni($filename, array $options, $section = null) {
    if (empty($filename))
      throw new System_ArgumentOutOfRangeException('filename');

    $nestSeparator    = isset($options['nestSeparator'])      ? (string) $options['nestSeparator']    : '.'; // String that separates nesting levels of configuration data identifiers
    $sectionSeparator = isset($options['sectionSeparator'])   ? (string) $options['sectionSeparator'] : ':'; // String that separates the parent section name
    $skipExtends      = isset($options['skipExtends'])        ? (bool)   $options['skipExtends']      : false; // Whether to skip extends or not

    $iniArray = self::loadIniFile($filename, $sectionSeparator);

    $dataArray = [];
    if (null === $section) { // Load entire file
      foreach ($iniArray as $sectionName => $sectionData) {
          if(!is_array($sectionData))
              $dataArray = array_replace_recursive ($dataArray, self::ProcessIniKey([], $sectionName, $sectionData, $nestSeparator));
          else
              $dataArray[$sectionName] = self::processIniSection($iniArray, $sectionName, $skipExtends, $nestSeparator);
      }
    } else { // Load one or more sections
      if (!is_array($section))
        $section = [$section];
      foreach ($section as $sectionName) {
        if (!isset($iniArray[$sectionName])) {
            require_once 'Zend/Config/Exception.php';
            throw new Zend_Config_Exception("Section '$sectionName' cannot be found in $filename");
        }
        $dataArray = array_replace_recursive (self::processIniSection($iniArray, $sectionName, $skipExtends, $nestSeparator), $dataArray);
      }
    }
    return $dataArray;
  } 
  
    /**
     * Load the ini file and preprocess the section separator (':' in the
     * section name (that is used for section extension) so that the resultant
     * array has the correct section names and the extension information is
     * stored in a sub-key called ';extends'. We use ';extends' as this can
     * never be a valid key name in an INI file that has been loaded using
     * parse_ini_file().
     *
     * @param string $filename
     * @throws Zend_Config_Exception
     * @return array
     */
    protected static function loadIniFile($filename, $sectionSeparator) {
        $loaded = parse_ini_file($filename, true);
        $iniArray = [];
        foreach ($loaded as $key => $data) {
          $pieces = explode($sectionSeparator, $key);
          $thisSection = trim($pieces[0]);
          switch (count($pieces)) {
            case 1:
              $iniArray[$thisSection] = $data;
              break;

            case 2:
              $iniArray[$thisSection] = array_merge([';extends' => trim($pieces[1])], $data);
              break;

            default:
              require_once 'Zend/Config/Exception.php';
              throw new Zend_Config_Exception("Section '$thisSection' may not extend multiple sections in $filename");
          }
        }

        return $iniArray;
    }

    /**
     * Process each element in the section and handle the ";extends" inheritance
     * key. Passes control to _processKey() to handle the nest separator
     * sub-property syntax that may be used within the key name.
     *
     * @param  array  $iniArray
     * @param  string $section
     * @param  array  $config
     * @throws Zend_Config_Exception
     * @return array
     */
    protected static function processIniSection($iniArray, $section, $skipExtends, $nestSeparator, $config = []) {
        $thisSection = $iniArray[$section];

        foreach ($thisSection as $key => $value) {
            if (strtolower($key) == ';extends' && !$skipExtends) {
                if (isset($iniArray[$value])) {
                  $config = self::processIniSection($iniArray, $value, $skipExtends, $nestSeparator, $config);
                } else {
                  require_once 'Zend/Config/Exception.php';
                  throw new Zend_Config_Exception("Parent section '$section' cannot be found");
                }
            } else {
              $config = self::processIniKey($config, $key, $value, $nestSeparator);
            }
        }
        return $config;
    }

    /**
     * Assign the key's value to the property list. Handles the
     * nest separator for sub-properties.
     *
     * @param  array  $config
     * @param  string $key
     * @param  string $value
     * @throws Zend_Config_Exception
     * @return array
     */
    protected static function processIniKey($config, $key, $value, $nestSeparator)
    {
        if (strpos($key, $nestSeparator) !== false) {
            $pieces = explode($nestSeparator, $key, 2);
            if (strlen($pieces[0]) && strlen($pieces[1])) {
              if (!isset($config[$pieces[0]])) {
                if ($pieces[0] === '0' && !empty($config)) { // convert the current values in $config into an array
                  $config = [$pieces[0] => $config];
                } else {
                  $config[$pieces[0]] = [];
                }
              } elseif (!is_array($config[$pieces[0]])) {
                require_once 'Zend/Config/Exception.php';
                throw new Zend_Config_Exception("Cannot create sub-key for '{$pieces[0]}' as key already exists");
              }
            $config[$pieces[0]] = self::processIniKey($config[$pieces[0]], $pieces[1], $value, $nestSeparator);
          } else {
            require_once 'Zend/Config/Exception.php';
            throw new Zend_Config_Exception("Invalid key '$key'");
          }
        } else {
          $config[$key] = $value;
        }
        return $config;
    }
}
