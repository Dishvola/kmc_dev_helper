<?php

namespace Drupal\kmc_dev_helper;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityInterface;

/**
 * Dev Helper Interface to collect all custom utilities.
 */
interface DevHelperInterface {

  /**
   * Get Request Time Unix timestamp.
   *
   * @return int
   *   A Unix timestamp.
   */
  public function getRequestTime(): int;

  /**
   * Get config via ConfigFactory.
   *
   * @param string $name
   *   The name of the configuration object to retrieve, which typically
   *   corresponds to a configuration file. For @code
   *   \Drupal::config('book.admin') @endcode, the configuration
   *   object returned will contain the content of the book.admin
   *   configuration file.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   An immutable configuration object.
   */
  public function getConfig(string $name): ImmutableConfig;

  /**
   * Load Drupal User by id.
   *
   * @param mixed $id
   *   Drupal User ID.
   *
   * @return mixed
   *   User entity or NULL.
   */
  public function userLoad($id);

  /**
   * Load Redhen Organization by id.
   *
   * @param mixed $id
   *   Redhen Org ID.
   *
   * @return mixed
   *   Org entity or NULL.
   */
  public function orgLoad($id);

  /**
   * Load Redhen Contact by id.
   *
   * @param mixed $id
   *   Redhen Contact ID.
   *
   * @return mixed
   *   Contact entity or NULL.
   */
  public function contactLoad($id);

  /**
   * Load Redhen Connection by id.
   *
   * @param mixed $id
   *   Redhen Connection ID.
   *
   * @return mixed
   *   Connection entity (redhen_connection) or NULL.
   */
  public function connectionRedhenLoad($id);

  /**
   * Load Group Connection by id.
   *
   * @param mixed $id
   *   Group Connection ID.
   *
   * @return mixed
   *   Connection entity (group_content) or NULL.
   */
  public function connectionGroupLoad($id);

  /**
   * Get SF ID string by Entity object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Any Entity to check SF ID.
   *
   * @return string
   *   Salesforce ID or empty string.
   */
  public function getSfIdByEntity(EntityInterface $entity): string;

}
