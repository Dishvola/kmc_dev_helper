<?php

namespace Drupal\kmc_dev_helper;

use Drupal\Core\Cache\CacheFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembership;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\redhen_connection\ConnectionServiceInterface;
use Drupal\redhen_connection\Entity\Connection;
use Drupal\redhen_contact\ContactInterface;
use Drupal\redhen_contact\Entity\Contact;
use Drupal\redhen_org\Entity\Org;
use Drupal\redhen_org\OrgInterface;
use Drupal\salesforce_mapping\Entity\MappedObject;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base Dev Helper class to work different features.
 */
class DevHelperBase extends \Drupal implements DevHelperInterface, ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public EntityTypeManagerInterface $entityTypeManager;

  /**
   * Include the messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  public MessengerInterface $messenger;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  public ConfigFactoryInterface $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  public LoggerChannelFactoryInterface $loggerFactory;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  public LoggerChannelInterface $logger;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  public ModuleHandlerInterface $moduleHandler;

  /**
   * The cache factory service.
   *
   * @var \Drupal\Core\Cache\CacheFactoryInterface
   */
  public CacheFactoryInterface $cacheFactory;

  /**
   * The Redhen Connections service.
   *
   * @var \Drupal\redhen_connection\ConnectionServiceInterface
   */
  public ConnectionServiceInterface $connectionService;

  /**
   * The Group Membership Loader service.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  public GroupMembershipLoaderInterface $groupMembershipLoader;

  /**
   * Constructs a Dev Helper Base.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Cache\CacheFactoryInterface $cacheFactory
   *   The Redhen Connections service.
   * @param \Drupal\redhen_connection\ConnectionServiceInterface $connectionService
   *   The cache factory service.
   * @param \Drupal\group\GroupMembershipLoaderInterface $groupMembershipLoader
   *   The Group Membership Loader service.
   */
  public function __construct(
    EntityTypeManagerInterface     $entity_manager,
    ConfigFactoryInterface         $config_factory,
    MessengerInterface             $messenger,
    LoggerChannelFactoryInterface  $logger_factory,
    ModuleHandlerInterface         $moduleHandler,
    CacheFactoryInterface          $cacheFactory,
    ConnectionServiceInterface     $connectionService,
    GroupMembershipLoaderInterface $groupMembershipLoader,
  ) {
    $this->entityTypeManager = $entity_manager;
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
    $this->loggerFactory = $logger_factory;
    $this->logger = $logger_factory->get('kmc_dev_helper');
    $this->moduleHandler = $moduleHandler;
    $this->cacheFactory = $cacheFactory;
    $this->connectionService = $connectionService;
    $this->groupMembershipLoader = $groupMembershipLoader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('messenger'),
      $container->get('logger.factory'),
      $container->get('module_handler'),
      $container->get('cache_factory'),
      $container->get('redhen_connection.connections'),
      $container->get('group.membership_loader'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestTime(): int {
    return self::time()->getRequestTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig($name): ImmutableConfig {
    return self::config($name);
  }

  /**
   * {@inheritdoc}
   */
  public function userIsStaff(mixed $user = NULL) {
    $is_staff = FALSE;

    // Get user.
    if (!isset($user)) {
      $user = self::currentUser();
    }
    elseif (is_numeric($user)) {
      $user = self::userLoad($user);
    }

    // Staff+ roles list.
    $staff_roles = [
      'staff',
      'staff_admin',
      'advanced_admin',
      'administrator',
    ];

    if ($user instanceof AccountProxyInterface) {
      $is_staff = !empty(array_intersect($staff_roles, $user->getRoles()));
    }

    return $is_staff;
  }

  /**
   * {@inheritdoc}
   */
  public function userIsGroupMember(mixed $group, mixed $user = NULL) {
    if (!isset($user)) {
      $user = self::currentUser();
    }

    return (bool) $this->groupContentLoadByEndpointsUser($group, $user);
  }

  /**
   * {@inheritdoc}
   */
  public function userIsOrgMember(mixed $org, mixed $user = NULL) {
    $contact = NULL;
    if (!isset($user)) {
      $user = self::currentUser();
    }
    elseif (is_numeric($user)) {
      $user = self::userLoad($user);
    }

    if ($user instanceof AccountInterface) {
      $contact = Contact::loadByUser($user);
    }

    return $contact instanceof Contact && $this->connectionRedhenLoadByEndpoints($contact, $org);
  }

  /**
   * {@inheritdoc}
   */
  public function userLoad(mixed $id) {
    $user = NULL;
    if (is_numeric($id)) {
      $user = User::load($id);
    }

    return $user;
  }

  /**
   * {@inheritdoc}
   */
  public function orgLoad(mixed $id) {
    $org = NULL;
    if (is_numeric($id)) {
      $org = Org::load($id);
    }

    return $org;
  }

  /**
   * @inheritDoc
   */
  public function orgLoadCurrent() {
    $org = NULL;
    $contact = self::contactLoadCurrent();

    if ($contact instanceof Contact
      && $contact->hasField('field_account')
      && !$contact->get('field_account')->isEmpty()
    ) {
      $org = $contact->field_account->entity;
    }

    return $org;
  }

  /**
   * @inheritDoc
   */
  public function orgGetConnectedEntities(mixed $org) {
    $contact_connections = [];

    if (is_numeric($org)) {
      $org = self::orgLoad($org);
    }

    if ($org instanceof OrgInterface) {
      $contact_connections = $this->connectionService->getConnectedEntities($org, 'organizational_affiliation');
    }

    return $contact_connections;
  }

  /**
   * @inheritDoc
   */
  public function orgCurrentGetConnectedEntities() {
    $org = self::orgLoadCurrent();

    return $this->orgGetConnectedEntities($org);
  }

  /**
   * {@inheritdoc}
   */
  public function contactIsGroupMember(mixed $group, mixed $contact = NULL) {
    if (!isset($contact)) {
      $contact = self::contactLoadCurrent();
    }

    return (bool) $this->groupContentLoadByEndpointsContact($group, $contact);
  }

  /**
   * {@inheritdoc}
   */
  public function contactIsOrgMember(mixed $org, mixed $contact = NULL) {
    if (!isset($contact)) {
      $contact = self::contactLoadCurrent();
    }

    return (bool) $this->connectionRedhenLoadByEndpoints($contact, $org);
  }

  /**
   * {@inheritdoc}
   */
  public function contactLoad(mixed $id) {
    $contact = NULL;
    if (is_numeric($id)) {
      $contact = Contact::load($id);
    }

    return $contact;
  }

  /**
   * @inheritDoc
   */
  public function contactLoadCurrent() {
    $current_user = self::currentUser();

    return $current_user->isAuthenticated() ? Contact::loadByUser($current_user) : FALSE;
  }

  /**
   * @inheritDoc
   */
  public function contactGetConnectedEntities(mixed $contact) {
    $contact_connections = [];

    if (is_numeric($contact)) {
      $contact = self::contactLoad($contact);
    }

    if ($contact instanceof ContactInterface) {
      $contact_connections = $this->connectionService->getConnectedEntities($contact, 'organizational_affiliation');
    }

    return $contact_connections;
  }

  /**
   * @inheritDoc
   */
  public function contactCurrentGetConnectedEntities() {
    $contact = self::contactLoadCurrent();

    return $this->contactGetConnectedEntities($contact);
  }

  /**
   * {@inheritdoc}
   */
  public function connectionRedhenLoad(mixed $id) {
    $connection = NULL;
    if (is_numeric($id)) {
      $connection = Connection::load($id);
    }

    return $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function connectionRedhenLoadByEndpoints(mixed $contact, mixed $org, bool $active = TRUE) {
    $connection = NULL;
    if (is_numeric($contact)) {
      $contact = self::contactLoad($contact);
    }
    if (is_numeric($org)) {
      $org = self::orgLoad($org);
    }

    if ($contact instanceof ContactInterface && $org instanceof OrgInterface) {
      $connections = $this->connectionService->getConnections($contact, $org, 'organizational_affiliation', $active);
      if (!empty($connections) && is_array($connections)) {
        $connection = reset($connections);
      }
    }

    return $connection;
  }

  /**
   * @inheritDoc
   */
  public function connectionsRedhenLoadByContact(mixed $contact, bool $active = TRUE) {
    $contact_connections = [];

    if (is_numeric($contact)) {
      $contact = self::contactLoad($contact);
    }

    if ($contact instanceof ContactInterface) {
      $contact_connections = $this->connectionService->getConnections($contact, NULL, 'organizational_affiliation', $active);
    }

    return $contact_connections;
  }

  /**
   * @inheritDoc
   */
  public function connectionsRedhenLoadByCurrentContact(bool $active = TRUE) {
    $contact = self::contactLoadCurrent();

    return $this->connectionsRedhenLoadByContact($contact, $active);
  }

  /**
   * @inheritDoc
   */
  public function connectionsRedhenLoadByOrg(mixed $org, bool $active = TRUE) {
    $contact_connections = [];

    if (is_numeric($org)) {
      $org = self::orgLoad($org);
    }

    if ($org instanceof OrgInterface) {
      $contact_connections = $this->connectionService->getConnections($org, NULL, 'organizational_affiliation', $active);
    }

    return $contact_connections;
  }

  /**
   * @inheritDoc
   */
  public function connectionsRedhenLoadByCurrentOrg(bool $active = TRUE) {
    $org = self::orgLoadCurrent();

    return $this->connectionsRedhenLoadByOrg($org, $active);
  }

  /**
   * {@inheritdoc}
   */
  public function groupLoad(mixed $id) {
    $group = NULL;
    if (is_numeric($id)) {
      $group = Group::load($id);
    }

    return $group;
  }

  /**
   * {@inheritdoc}
   */
  public function groupsLoadByUser(mixed $user) {
    $groups = [];
    $group_contents = $this->groupContentsLoadByUser($user);

    if (!empty($group_contents)) {
      foreach ($group_contents as $group_content) {
        if ($group_content instanceof GroupContent) {
          $groups[] = $group_content->getGroup();
        }
      }
    }

    return $groups;
  }

  /**
   * {@inheritdoc}
   */
  public function groupsLoadByCurrentUser() {
    $user = self::currentUser();

    return $this->groupsLoadByUser($user);
  }

  /**
   * {@inheritdoc}
   */
  public function groupsLoadByContact(mixed $contact) {
    $user = NULL;
    if (is_numeric($contact)) {
      $contact = self::contactLoad($contact);
    }
    if ($contact instanceof ContactInterface) {
      $user = $contact->getUser();
    }

    return $user instanceof AccountInterface ? $this->groupsLoadByUser($user) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function groupContentLoad(mixed $id) {
    $group_content = NULL;
    if (is_numeric($id)) {
      $group_content = GroupContent::load($id);
    }

    return $group_content;
  }

  /**
   * {@inheritdoc}
   */
  public function groupContentLoadByEndpointsUser(mixed $group, mixed $user) {
    $group_content = NULL;
    if (is_numeric($user)) {
      $user = self::userLoad($user);
    }
    if (is_numeric($group)) {
      $group = self::groupLoad($group);
    }

    if ($group instanceof GroupInterface && $user instanceof AccountInterface) {
      $group_membership = $this->groupMembershipLoader->load($group, $user);
      // GroupMembership is a wrapper class for a GroupContent entity
      // representing a membership.
      if ($group_membership instanceof GroupMembership) {
        $group_content = $group_membership->getGroupContent();
      }
    }

    return $group_content;
  }

  /**
   * {@inheritdoc}
   */
  public function groupContentLoadByEndpointsCurrentUser(mixed $group) {
    $user = self::currentUser();

    return $this->groupContentLoadByEndpointsUser($group, $user);
  }

  /**
   * {@inheritdoc}
   */
  public function groupContentLoadByEndpointsContact(mixed $group, mixed $contact) {
    $user = NULL;
    if (is_numeric($contact)) {
      $contact = self::contactLoad($contact);
    }
    if ($contact instanceof ContactInterface) {
      $user = $contact->getUser();
    }

    return $user instanceof AccountInterface ? $this->groupContentLoadByEndpointsUser($group, $user) : NULL;
  }

  /**
   * @inheritDoc
   */
  public function groupContentsLoadByUser(mixed $user) {
    $group_contents = [];
    if (is_numeric($user)) {
      $user = self::userLoad($user);
    }

    if ($user instanceof AccountInterface) {
      $group_memberships = $this->groupMembershipLoader->loadByUser($user);

      if (!empty($group_memberships)) {
        foreach ($group_memberships as $group_membership) {
          if ($group_membership instanceof GroupMembership) {
            $group_contents[] = $group_membership->getGroupContent();
          }
        }
      }
    }

    return $group_contents;
  }

  /**
   * @inheritDoc
   */
  public function groupContentsLoadByCurrentUser() {
    $user = self::currentUser();

    return $this->groupContentsLoadByUser($user);
  }

  /**
   * @inheritDoc
   */
  public function groupContentsLoadByContact(mixed $contact) {
    $user = NULL;
    if (is_numeric($contact)) {
      $contact = self::contactLoad($contact);
    }
    if ($contact instanceof ContactInterface) {
      $user = $contact->getUser();
    }

    return $user instanceof AccountInterface ? $this->groupContentsLoadByUser($user) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSfIdByEntity(EntityInterface $entity): string {
    $sf_id = '';
    /** @var \Drupal\salesforce_mapping\MappedObjectStorage $mapped_object_storage */
    $mapped_object_storage = $this->entityTypeManager->getStorage('salesforce_mapped_object');
    $smo_array = $mapped_object_storage->loadByEntity($entity);
    $smo = reset($smo_array);
    if ($smo instanceof MappedObject
      && $smo->hasField('salesforce_id')
      && !$smo->get('salesforce_id')->isEmpty()
    ) {
      $sf_id = $smo->sfid();
    }

    return $sf_id;
  }

}
