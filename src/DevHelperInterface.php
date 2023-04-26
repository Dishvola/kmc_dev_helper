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
  public function userLoad(mixed $id);

  /**
   * Load Redhen Organization by id.
   *
   * @param mixed $id
   *   Redhen Org ID.
   *
   * @return mixed
   *   Org entity or NULL.
   */
  public function orgLoad(mixed $id);

  /**
   * Load Redhen Organization of the current drupal user.
   *
   * It's mean we load Organization from the field_account of the current
   * Redhen Contact of the current Drupal User.
   *
   * @return mixed
   *   Org entity or NULL.
   */
  public function orgLoadCurrent();

  /**
   * Load Contacts from org connections (organizational_affiliation).
   *
   * @param mixed $org
   *   You can pass Redhen Org entity or Redhen Org ID.
   *
   * @return mixed
   *   Return Contacts entities or empty array.
   */
  public function orgGetConnectedEntities(mixed $org);

  /**
   * Load Contacts from current org connections (organizational_affiliation).
   *
   * @return mixed
   *   Return Contacts entities or empty array.
   */
  public function orgCurrentGetConnectedEntities();

  /**
   * Load Redhen Contact by id.
   *
   * @param mixed $id
   *   Redhen Contact ID.
   *
   * @return mixed
   *   Contact entity or NULL.
   */
  public function contactLoad(mixed $id);

  /**
   * Load Redhen Contact for current Drupal User.
   *
   * @return mixed
   *   Contact or FALSE if not found.
   */
  public function contactLoadCurrent();

  /**
   * Load Orgs from contact connections (organizational_affiliation).
   *
   * @param mixed $contact
   *   You can pass Redhen Contact entity or Redhen Contact ID.
   *
   * @return mixed
   *   Return Org entities or empty array.
   */
  public function contactGetConnectedEntities(mixed $contact);

  /**
   * Load Orgs from current contact connections (organizational_affiliation).
   *
   * @return mixed
   *   Return Org entities or empty array.
   */
  public function contactCurrentGetConnectedEntities();

  /**
   * Load Redhen Connection by id.
   *
   * @param mixed $id
   *   Redhen Connection ID.
   *
   * @return mixed
   *   Connection entity (redhen_connection) or NULL.
   */
  public function connectionRedhenLoad(mixed $id);

  /**
   * Load Redhen Connection by pair Contact + Org.
   *
   * @param mixed $contact
   *   Redhen Contact entity or Redhen Contact ID.
   * @param mixed $org
   *   Redhen Org entity or Redhen Org ID.
   * @param bool $active
   *   (optional) Return only active connections. TRUE by default.
   *
   * @return mixed
   *   Connection entity (redhen_connection) or NULL.
   */
  public function connectionRedhenLoadByEndpoints(mixed $contact, mixed $org, bool $active = TRUE);

  /**
   * Load Connections (organizational_affiliation type) by Redhen Contact.
   *
   * @param mixed $contact
   *   You can pass Redhen Contact entity or Redhen Contact ID.
   * @param bool $active
   *   (optional) Return only active connections. TRUE by default.
   *
   * @return mixed
   *   Return Connection entities (redhen_connection) or empty array.
   */
  public function connectionsRedhenLoadByContact(mixed $contact, bool $active = TRUE);

  /**
   * Load Connections (organizational_affiliation) by current Redhen Contact.
   *
   * @param bool $active
   *   (optional) Return only active connections. TRUE by default.
   *
   * @return mixed
   *   Return Connection entities (redhen_connection) or empty array.
   */
  public function connectionsRedhenLoadByCurrentContact(bool $active = TRUE);

  /**
   * Load Connections (organizational_affiliation type) by Redhen Contact.
   *
   * @param mixed $org
   *   You can pass Redhen Org entity or Redhen Org ID.
   * @param bool $active
   *   (optional) Return only active connections. TRUE by default.
   *
   * @return mixed
   *   Return Connection entities (redhen_connection) or empty array.
   */
  public function connectionsRedhenLoadByOrg(mixed $org, bool $active = TRUE);

  /**
   * Load Connections (organizational_affiliation type) by Redhen Org.
   *
   * @param bool $active
   *   (optional) Return only active connections. TRUE by default.
   *
   * @return mixed
   *   Return Connection entities (redhen_connection) or empty array.
   */
  public function connectionsRedhenLoadByCurrentOrg(bool $active = TRUE);

  /**
   * Load Group by id.
   *
   * @param mixed $id
   *   Group ID.
   *
   * @return mixed
   *   Group entity or NULL.
   */
  public function groupLoad(mixed $id);

  /**
   * Load Group Content by id.
   *
   * @param mixed $id
   *   Group Content ID.
   *
   * @return mixed
   *   Group Content entity or NULL.
   */
  public function groupContentLoad(mixed $id);

  /**
   * Load Group Content entity by pair Group + User.
   *
   * @param mixed $group
   *   You can pass Group entity or Group ID.
   * @param mixed $user
   *   You can pass User entity or User ID.
   *
   * @return mixed
   *   Group Content entity or NULL.
   */
  public function groupContentLoadByEndpointsUser(mixed $group, mixed $user);

  /**
   * Load Group Content entity by pair Group + current user.
   *
   * @param mixed $group
   *   You can pass Group entity or Group ID.
   *
   * @return mixed
   *   Group Content entity or NULL.
   */
  public function groupContentLoadByEndpointsCurrentUser(mixed $group);

  /**
   * Load Group Content entity by pair Group + Contact.
   *
   * @param mixed $group
   *   You can pass Group entity or Group ID.
   * @param mixed $contact
   *   You can pass Redhen Contact entity or Contact ID.
   *
   * @return mixed
   *   Group Content entity or NULL.
   */
  public function groupContentLoadByEndpointsContact(mixed $group, mixed $contact);

  /**
   * Load all existed Group Content entities for passed user.
   *
   * @param mixed $user
   *   You can pass User entity or User ID.
   *
   * @return mixed
   *   Group Content entities array or NULL.
   */
  public function groupContentsLoadByUser(mixed $user);

  /**
   * Load all existed Group Content entities for current user.
   *
   * @return mixed
   *   Group Content entities array or NULL.
   */
  public function groupContentsLoadByCurrentUser();

  /**
   * Load all existed Group Content entities for passed Redhen Contact.
   *
   * @param mixed $contact
   *   You can pass Redhen Contact entity or Contact ID.
   *
   * @return mixed
   *   Group Content entities array or NULL.
   */
  public function groupContentsLoadByContact(mixed $contact);

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
