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
use Drupal\redhen_contact\Entity\Contact;
use Drupal\redhen_org\Entity\Org;
use Drupal\salesforce_mapping\Entity\MappedObject;
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
   *   The cache factory service.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, ConfigFactoryInterface $config_factory, MessengerInterface $messenger, LoggerChannelFactoryInterface $logger_factory, ModuleHandlerInterface $moduleHandler, CacheFactoryInterface $cacheFactory) {
    $this->entityTypeManager = $entity_manager;
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
    $this->loggerFactory = $logger_factory;
    $this->logger = $logger_factory->get('kmc_dev_helper');
    $this->moduleHandler = $moduleHandler;
    $this->cacheFactory = $cacheFactory;
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
  public function orgLoad($id) {
    $org = NULL;
    if (is_numeric($id)) {
      $org = Org::load($id);
    }
    return $org;
  }

  /**
   * {@inheritdoc}
   */
  public function contactLoad($id) {
    $contact = NULL;
    if (is_numeric($contact)) {
      $contact = Contact::load($contact);
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
    $entity_smo_array = $mapped_object_storage->loadByEntity($entity);
    $entity_smo = reset($entity_smo_array);
    if ($entity_smo instanceof MappedObject
      && $entity_smo->hasField('salesforce_id')
      && !$entity_smo->get('salesforce_id')->isEmpty()
    ) {
      $sf_id = $entity_smo->salesforce_id->value;
    }

    return $sf_id;
  }

}
