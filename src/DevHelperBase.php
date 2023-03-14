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
use Drupal\group\Entity\GroupContent;
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
   */
  public function __construct(
    EntityTypeManagerInterface    $entity_manager,
    ConfigFactoryInterface        $config_factory,
    MessengerInterface            $messenger,
    LoggerChannelFactoryInterface $logger_factory,
    ModuleHandlerInterface        $moduleHandler,
    CacheFactoryInterface         $cacheFactory,
    ConnectionServiceInterface    $connectionService,
  ) {
    $this->entityTypeManager = $entity_manager;
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
    $this->loggerFactory = $logger_factory;
    $this->logger = $logger_factory->get('kmc_dev_helper');
    $this->moduleHandler = $moduleHandler;
    $this->cacheFactory = $cacheFactory;
    $this->connectionService = $connectionService;
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
    $contact_connections = [];
    $org = self::orgLoadCurrent();

    if ($org instanceof OrgInterface) {
      $contact_connections = $this->connectionService->getConnectedEntities($org, 'organizational_affiliation');
    }

    return $contact_connections;
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
    $contact_connections = [];
    $contact = self::contactLoadCurrent();

    if ($contact instanceof ContactInterface) {
      $contact_connections = $this->connectionService->getConnectedEntities($contact, 'organizational_affiliation');
    }

    return $contact_connections;
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
    $contact_connections = [];
    $contact = self::contactLoadCurrent();

    if ($contact instanceof ContactInterface) {
      $contact_connections = $this->connectionService->getConnections($contact, NULL, 'organizational_affiliation', $active);
    }

    return $contact_connections;
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
    $contact_connections = [];
    $org = self::orgLoadCurrent();

    if ($org instanceof OrgInterface) {
      $contact_connections = $this->connectionService->getConnections($org, NULL, 'organizational_affiliation', $active);
    }

    return $contact_connections;
  }

  /**
   * {@inheritdoc}
   */
  public function connectionGroupLoad(mixed $id) {
    $contact = NULL;
    if (is_numeric($id)) {
      $contact = GroupContent::load($id);
    }
    return $contact;
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
