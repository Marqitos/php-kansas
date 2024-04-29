<?php
/**
 * Zend Framework 2.0
 *
 */
namespace Kansas\Cache;

interface CacheInterface {

  /**
    * Consts for clean() method
    */
  const CLEANING_MODE_ALL              = 'all';
  const CLEANING_MODE_OLD              = 'old';
  const CLEANING_MODE_MATCHING_TAG     = 'matchingTag';
  const CLEANING_MODE_NOT_MATCHING_TAG = 'notMatchingTag';
  const CLEANING_MODE_MATCHING_ANY_TAG = 'matchingAnyTag';

  /**
    * Set the frontend directives
    *
    * @param array $directives assoc of directives
    */
  public function setDirectives(array $directives);

  /**
    * Test if a cache is available for the given id and (if yes) return it (false else)
    *
    * Note : return value is always "string" (unserialization is done by the core not by the backend)
    *
    * @param  string  $id                     Cache id
    * @param  boolean $doNotTestCacheValidity If set to true, the cache validity won't be tested
    * @return string|false cached datas
    */
  public function load(string $id, $doNotTestCacheValidity = false) : mixed;

  /**
    * Test if a cache is available or not (for the given id)
    *
    * @param  string $id cache id
    * @return mixed|false (a cache is not available) or "last modified" timestamp (int) of the available cache record
    */
  public function test(string $id);

  /**
    * Save some string datas into a cache record
    *
    * Note : $data is always "string" (serialization is done by the
    * core not by the backend)
    *
    * @param  string $data            Datas to cache
    * @param  string $id              Cache id
    * @param  array $tags             Array of strings, the cache record will be tagged by each string entry
    * @param  int   $specificLifetime If != false, set a specific lifetime for this cache record (null => infinite lifetime)
    * @return boolean true if no problem
    */
  public function save(string $data, string $id, array $tags = [], $specificLifetime = false) : bool;

  /**
    * Remove a cache record
    *
    * @param  string $id Cache id
    * @return boolean True if no problem
    */
  public function remove(string $id);

  /**
    * Clean some cache records
    *
    * Available modes are :
    * CacheInterface::CLEANING_MODE_ALL (default)    => remove all cache entries ($tags is not used)
    * CacheInterface::CLEANING_MODE_OLD              => remove too old cache entries ($tags is not used)
    * CacheInterface::CLEANING_MODE_MATCHING_TAG     => remove cache entries matching all given tags
    *                                               ($tags can be an array of strings or a single string)
    * CacheInterface::CLEANING_MODE_NOT_MATCHING_TAG => remove cache entries not {matching one of the given tags}
    *                                               ($tags can be an array of strings or a single string)
    * CacheInterface::CLEANING_MODE_MATCHING_ANY_TAG => remove cache entries matching any given tags
    *                                               ($tags can be an array of strings or a single string)
    *
    * @param  string $mode Clean mode
    * @param  array  $tags Array of tags
    * @return boolean true if no problem
    */
  public function clean(string $mode = self::CLEANING_MODE_ALL, array $tags = []) : bool;

}
