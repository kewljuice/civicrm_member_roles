<?php

namespace Drupal\civicrm_member_roles\Batch;

use Drupal\civicrm_member_roles\CivicrmMemberRoles;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Class Sync.
 */
class Sync {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * Database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * CiviCRM member roles service.
   *
   * @var \Drupal\civicrm_member_roles\CivicrmMemberRoles
   */
  protected $civicrmMemberRoles;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Sync constructor.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The string translation service.
   * @param \Drupal\Core\Database\Connection $connection
   *   Database.
   * @param \Drupal\civicrm_member_roles\CivicrmMemberRoles $memberRoles
   *   CiviCRM member roles service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(TranslationInterface $stringTranslation, Connection $connection, CivicrmMemberRoles $memberRoles, MessengerInterface $messenger) {
    $this->stringTranslation = $stringTranslation;
    $this->connection = $connection;
    $this->civicrmMemberRoles = $memberRoles;
    $this->messenger = $messenger;
  }

  /**
   * Get the batch.
   *
   * @return array
   *   A batch API array for syncing user memberships and roles.
   */
  public function getBatch() {
    $batch = [
      'title' => $this->t('Updating Users...'),
      'operations' => [],
      'init_message' => $this->t('Starting Update'),
      'progress_message' => $this->t('Processed @current out of @total.'),
      'error_message' => $this->t('An error occurred during processing'),
      'finished' => [$this, 'finished'],
    ];

    $batch['operations'][] = [[$this, 'process'], []];

    return $batch;
  }

  /**
   * Batch API process callback.
   *
   * @param mixed $context
   *   Batch API context data.
   */
  public function process(&$context) {
    $civicrmMemberRoles = $this->getCivicrmMemberRoles();

    if (!isset($context['sandbox']['cids'])) {
      $context['sandbox']['cids'] = $civicrmMemberRoles->getSyncContactIds();
      $context['sandbox']['max'] = count($context['sandbox']['cids']);
      $context['results']['processed'] = 0;
    }

    $cid = array_shift($context['sandbox']['cids']);
    if ($account = $civicrmMemberRoles->getContactAccount($cid)) {
      $civicrmMemberRoles->syncContact($cid, $account);
    }
    $context['results']['processed']++;

    if (count($context['sandbox']['cids']) > 0) {
      $context['finished'] = 1 - (count($context['sandbox']['cids']) / $context['sandbox']['max']);
    }
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Batch API success indicator.
   * @param array $results
   *   Batch API results array.
   */
  public function finished($success, array $results) {
    if ($success) {
      $message = $this->stringTranslation->formatPlural($results['processed'], 'One user processed.', '@count users processed.');
      $this->messenger->addStatus($message);
    }
    else {
      $message = $this->t('Encountered errors while performing sync.');
      $this->messenger->addError($message);
    }
  }

  /**
   * Get CiviCRM member roles service.
   *
   * This is called directly from the Drupal object to avoid dealing with
   * serialization.
   *
   * @return \Drupal\civicrm_member_roles\CivicrmMemberRoles
   *   The CiviCRM member roles service.
   */
  protected function getCivicrmMemberRoles() {
    return $this->civicrmMemberRoles;
  }

  /**
   * Gets the database.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database.
   */
  protected function getDatabase() {
    return $this->connection;
  }

}
