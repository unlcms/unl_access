<?php

namespace Drupal\unl_access\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Database\DatabaseException;

/**
 * Push term in front.
 *
 * @Action(
 *   id = "add_staff_affiliation_to_node",
 *   label = @Translation("Add 'Staff Affiliation'"),
 *   type = "node"
 * )
 */
class AddStaffAffiliationToNode extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\node\NodeInterface $entity */
    if ($entity instanceof NodeInterface) {
      $node_id = $entity->id();
      $node_title = $entity->getTitle();
      $affiliation_type = 'Staff Affiliation';

      try {
        $any_affiliation_access = \Drupal::database()->select('unl_access_node_affiliation', 'a')
          ->fields('a', array('nid', 'affiliation'))
          ->condition('nid', $node_id, 'IN')
          ->condition('affiliation', UNL_AFFILIATION_STAFF) // Additional condition for status.
          ->execute()
          ->fetchAll();

        if (!$any_affiliation_access) {
          $inserted_id = \Drupal::database()->insert('unl_access_node_affiliation')
            ->fields(array('nid', 'affiliation'))
            ->values(array($node_id, UNL_AFFILIATION_STAFF))
            ->execute();
          if ($inserted_id || $inserted_id == 0) {
            $entity->save();

            \Drupal::messenger()->addStatus(t('@affiliation_type access has been added to page "@title".', [
              '@title' => $node_title,
              '@affiliation_type' => $affiliation_type,
            ]));
          } else {
            \Drupal::messenger()->addError(t('Failed to add @affiliation_type access to page "@title".', [
              '@title' => $node_title,
              '@affiliation_type' => $affiliation_type,
            ]));
          }
        } else {
          \Drupal::messenger()->addStatus(t('Page "@name" already has @affiliation_type access assigned to it.', [
            '@name' => $node_title,
            '@affiliation_type' => $affiliation_type,
          ]));
        }
      } catch (DatabaseException $e) {
        // Log the error and show a user-friendly message.
        \Drupal::logger('unl_access')->error('Error inserting @affiliation_type records for node @nid: @message', [
          '@nid' => $node_id,
          '@message' => $e->getMessage(),
          '@affiliation_type' => $affiliation_type,
        ]);
        \Drupal::messenger()->addError(t(
          'An error occurred while attempting to insert @affiliation_type records for node @nid. Please try again later.',
          [
            '@nid' => $node_id,
            '@affiliation_type' => $affiliation_type,
          ]
        ));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $entity */
    $result = $object->access('update', $account, TRUE);

    return $return_as_object ? $result : $result->isAllowed();
  }
}
