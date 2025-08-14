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
 *   id = "make_node_public",
 *   label = @Translation("Make node public"),
 *   type = "node"
 * )
 */
class MakeNodePublic extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\node\NodeInterface $entity */
    if ($entity instanceof NodeInterface) {
      $node_id = $entity->id();
      $node_title = $entity->getTitle();

      try {
        // Attempt to delete the affiliation record associated with the node.
        $deleted_count = \Drupal::database()->delete('unl_access_node_affiliation')
          ->condition('nid', $node_id)
          ->execute();
        // Add a status message based on the number of deleted records.
        if ($deleted_count > 0) {
          $entity->save();

          \Drupal::messenger()->addStatus(t('@count affiliation records associated with page @name have been deleted.', [
            '@count' => $deleted_count,
            '@name' => $node_title,
          ]));
        } else {
          \Drupal::messenger()->addWarning(t(
            'No affiliation records were found for page @name.',
            ['@name' => $node_title]
          ));
        }
      } catch (DatabaseException $e) {
        // Log the error and show a user-friendly message.
        \Drupal::logger('unl_access')->error('Error deleting affiliation records for node @nid: @message', [
          '@nid' => $node_id,
          '@message' => $e->getMessage(),
        ]);
        \Drupal::messenger()->addError(t(
          'An error occurred while attempting to delete affiliation records for node @nid. Please try again later.',
          ['@nid' => $node_id]
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
