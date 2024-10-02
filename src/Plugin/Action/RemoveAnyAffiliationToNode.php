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
 *   id = "remove_any_affiliation_to_node",
 *   label = @Translation("Remove 'Any Affiliation'"),
 *   type = "node"
 * )
 */
class RemoveAnyAffiliationToNode extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\node\NodeInterface $entity */
    if ($entity instanceof NodeInterface) {
      $node_id = $entity->id();
      $node_title = $entity->getTitle();
      $affiliation_type = 'Any Affiliation';

      try {
        $deleted_count = \Drupal::database()->delete('unl_access_node_affiliation')
          ->condition('nid', $node_id)
          ->condition('affiliation', UNL_AFFILIATION_ANY) // Additional condition for status.
          ->execute();
        // Add a status message based on the number of deleted records.
        if ($deleted_count > 0) {
          $access_records = unl_access_node_access_records($entity);
          \Drupal::service('node.grant_storage')->write($entity, $access_records);

          \Drupal::messenger()->addStatus(t('@affiliation_type access has been removed from page "@title".', [
            '@title' => $node_title,
            '@affiliation_type' => $affiliation_type,
          ]));
        } else {
          \Drupal::messenger()->addWarning(t(
            '@affiliation_type record doesn\'t exist for page @name.',
            [
              '@name' => $node_title,
              '@affiliation_type' => $affiliation_type,
            ]
          ));
        }
      } catch (DatabaseException $e) {
        // Log the error and show a user-friendly message.
        \Drupal::logger('unl_access')->error('Error deleting @affiliation_type records for node @nid: @message', [
          '@nid' => $node_id,
          '@message' => $e->getMessage(),
          '@affiliation_type' => $affiliation_type,
        ]);
        \Drupal::messenger()->addError(t(
          'An error occurred while attempting to delete @affiliation_type records for node @nid. Please try again later.',
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
