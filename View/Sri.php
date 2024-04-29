<?php declare(strict_types = 1);
/**
 * Proporciona métodos para implementar Subresource Integrity
 *
 * @package Kansas
 * @author Marcos Porto
 * @copyright Marcos Porto
 * @since v0.5
 */

namespace Kansas\View;

use Kansas\Environment;
use Kansas\Plugin\BackendCache;
use function System\String\startWith as StringStartWith;
use function realpath;

class Sri {

  public static function script(string $filePath, array $attributes = []) : string  {
    require_once 'System/String/startWith.php';
    global $environment;

    $result = '<script src="' . $filePath . '"';
    if (StringStartWith($filePath, '/')) {
      $path = realpath($environment->getSpecialFolder(Environment::SF_PUBLIC) . '.' . $filePath);
      if ($path) {
        $hash = hash_file('sha256', $path, true);
        $attributes['integrity'] = 'sha256-' . base64_encode($hash);
      }
    }
    foreach ($attributes as $key => $value) {
      $result .= ' ' . $key . '="' . $value .'"';
    }
    $result .= '></script>';

    return $result;
  }

  public static function inlineScript(string $script, array $attributes = []) {
    global $application;

    $hash = hash('sha256', $script, true);
    $attributes['integrity'] = 'sha256-' . base64_encode($hash);

    $cachePlugin = $application->hasPlugin('BackendCache');
    if ($cachePlugin) {
      global $environment;
      $filename = bin2hex($hash) . '.js';
      $cache = $cachePlugin->getCache('router',
        BackendCache::TYPE_FILE, [
          'cache_dir' => $environment->getSpecialFolder(Environment::SF_V_CACHE)]
      );
      $data = serialize([
        'content'   => $script,
        'mimetype'  => 'text/javascript',
        'etag'      => md5($script)
      ]);
      $cache->save($data, $filename);
      $attributes['src'] = '/' . $filename;
      $script = '';
    }

    $result = '<script';
    foreach ($attributes as $key => $value) {
      $result .= ' ' . $key . '="' . $value .'"';
    }
    $result .= '>' . $script . '</script>';

    return $result;
  }

  public static function stylesheet(string $filePath, array $attributes = ['rel' => 'stylesheet']) : string {
    return self::link($filePath, $attributes);
  }

  public static function inlineStylesheet(string $style, array $attributes = []) {
    global $application;

    if (empty($attributes)) {
      $attributes = ['rel' => 'stylesheet'];
    }

    $hash = hash('sha256', $style, true);
    $attributes['integrity'] = 'sha256-' . base64_encode($hash);

    $cachePlugin = $application->hasPlugin('BackendCache');
    if ($cachePlugin) {
      global $environment;
      $filename = bin2hex($hash) . '.css';
      $cache = $cachePlugin->getCache('router',
        BackendCache::TYPE_FILE, [
          'cache_dir' => $environment->getSpecialFolder(Environment::SF_V_CACHE)]
      );
      $data = serialize([
        'content'   => $style,
        'mimetype'  => 'text/css',
        'etag'      => md5($style)
      ]);
      $cache->save($data, $filename);
      $attributes['href'] = '/' . $filename;
      $style = '';
    }

    if (empty($style)) {
      $result = '<link';
      foreach ($attributes as $key => $value) {
        $result .= ' ' . $key . '="' . $value .'"';
      }
      $result .= '>';
    } else {
      unset($attributes['rel']);
      $result = '<style';
      foreach ($attributes as $key => $value) {
        $result .= ' ' . $key . '="' . $value .'"';
      }
      $result .= '>' . $style . '</style>';
    }

    return $result;
  }


  public static function link(string $filePath, array $attributes = []) : string {
    require_once 'System/String/startWith.php';
    global $environment;

    $result = '<link href="' . $filePath . '"';
    if (StringStartWith($filePath, '/')) {
      $path = realpath($environment->getSpecialFolder(Environment::SF_PUBLIC) . '.' . $filePath);
      if ($path) {
        $hash = hash_file('sha256', $path, true);
        $attributes['integrity'] = 'sha256-' . base64_encode($hash);
      }
    }
    foreach ($attributes as $key => $value) {
      $result .= ' ' . $key . '="' . $value .'"';
    }
    $result .= '>';

    return $result;
  }

}
