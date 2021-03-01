<?php

namespace Drupal\civicrm_member_roles\Commands;

use Drush\Commands\DrushCommands;

/**
 * A drush command file.
 *
 * @package Drupal\ctrl_drush_clear_ftp\Commands
 */
class Drush extends DrushCommands {

  /**
   * Drush command for CiviMember Roles Sync..
   *
   * @param array $options
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @command civicrm-member-role-sync:civicrm-member-role-sync
   * @aliases cmrs
   * @usage cmrs
   * @options uid Optional User ID.
   * @options contact_id Optional Contact ID.
   */
  public function drush_cmrs($options = [
    'uid' => NULL,
    'contact_id' => NULL,
  ]) {
    /** @var \Drupal\civicrm_member_roles\CivicrmMemberRoles $civicrm_member_roles */
    $civicrm_member_roles = \Drupal::service('civicrm_member_roles');
    if (isset($options['uid']) && $uid = $options['uid']) {
      $storage = \Drupal::entityTypeManager()->getStorage('user');
      if (!$account = $storage->load($uid)) {
        $this->output()
          ->writeln(print_r(dt('Unable to load user ID @uid.', ['@uid' => $uid]), TRUE));
      }
      else {
        $civicrm_member_roles->syncUser($account);
        $this->output()
          ->writeln(print_r(dt('Successfully synced user ID @uid.', ['@uid' => $uid]), TRUE));
      }
    }
    else {
      if (isset($options['contact_id']) && $contact_id = $options['contact_id']) {
        if (!$account = $civicrm_member_roles->getContactAccount($contact_id)) {
          $this->output()
            ->writeln(print_r(dt('Unable to load user for contact ID @cid.', ['@cid' => $contact_id]), TRUE));
        }
        else {
          $civicrm_member_roles->syncContact($contact_id, $account);
          $this->output()
            ->writeln(print_r(dt('Successfully synced contact ID @cid.', ['@cid' => $contact_id]), TRUE));
        }
      }
      else {
        $batch = \Drupal::service('civicrm_member_roles.batch.sync')->getBatch();
        $batch['progressive'] = FALSE;
        batch_set($batch);
        drush_backend_batch_process();
        $this->output()
          ->writeln(print_r("Successfully synced", TRUE));
      }
    }

  }

}
