<?php

namespace Drupal\service_club_asset\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\service_club_asset\Entity\AssetEntity;

/**
 * Form controller for Asset entity edit forms.
 *
 * @ingroup service_club_asset
 */
class AssetEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\service_club_asset\Entity\AssetEntity */
    $form = parent::buildForm($form, $form_state);

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 10,
      ];
    }

    $entity = $this->entity;

    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|\Drupal\Core\Entity\ContentEntityTypeInterface|void
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Call the original validateForm function.
    $entity = parent::validateForm($form, $form_state);

    // Guardian IF ensuring that we are looking at an Asset Entity.
    if ($entity instanceof AssetEntity) {
      // Check the price to ensure it's a valid number or is blank.
      if (!(is_numeric($entity->getPrice()) || empty($entity->getPrice()))) {
        $form_state->setErrorByName('price', $this->t('Given price was not a valid value.'));
      }

      // Check to ensure each child isn't associated with another parent.
      for ($counter = 0; $counter < count($entity->getChildRelationships()); $counter++) {
        // Load the child asset so that values can be set.
        $child_asset = AssetEntity::load($entity->getChildRelationships()[$counter]['target_id']);

        // Get the child's parent id value.
        $possible_parent = $child_asset->getParentId();

        // If the child has a parent that isn't the current asset then it
        // already has an association.
        if ($possible_parent !== $entity->id() && !empty($possible_parent)) {
          $form_state->setErrorByName('existing parent relationship',
            ("Child ID: " . $child_asset->id() .
              " already has a parental association with another asset. ID: " . $possible_parent));
        }

        /*
         * In the special case that the current asset's child is itself, the
         * circular dependency will not catch this issue and save the asset.
         * To prevent this the following if checks if the current asset has
         * itself as a child.
         *
         * Variable $entity->id() will be empty when an asset is first created
         * so this will not trigger.
         */
        if ($entity->id() === $child_asset->id()) {
          $form_state->setErrorByName('circular_dependency',
            $this->t('An asset is not permitted to be a child of itself.'));
        }
      }

      if (!empty($entity->getParentId())) {
        // Load the actual parent to be passed into dependency checker.
        $parent_asset = AssetEntity::load($entity->getParentId());

        // If there is a circular dependency then halt the save.
        if ($this->circularDependency($entity, $parent_asset)) {
          $form_state->setErrorByName('circular_dependency',
            $this->t('There is a cirular relationship which is not permitted.'));
        }
      }
    }

    return $entity;
  }

  /**
   * Finds circular dependencies between assets.
   *
   * @param \Drupal\service_club_asset\Entity\AssetEntity $current_asset
   *   The current_asset is the asset currently being validated.
   * @param \Drupal\service_club_asset\Entity\AssetEntity $parent_asset
   *   This is the parent of current_asset.
   *
   * @return bool
   *   True when a circular dependency is found, False when there isn't a
   *   circular dependency
   */
  public function circularDependency(AssetEntity $current_asset, AssetEntity $parent_asset) {
    // Static variable to exist through recursion. Initially false.
    static $dependency = FALSE;

    // Finite state machine to make decision.
    if ($parent_asset->id() === $current_asset->id()) {
      // Circular dependency found.
      $dependency = TRUE;
    }
    elseif (!empty($parent_asset->getParentId())) {
      // There is another parent to check.
      $next_parent = AssetEntity::load($parent_asset->getParentId());
      $this->circularDependency($current_asset, $next_parent);
    }

    // No more parents in the chain.
    return $dependency;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Set initial value of parent changed to false to cover entity creation.
    $parent_changed = FALSE;
    $children_changed = FALSE;

    // Ignore if the asset is being created.
    if (!empty($entity->id())) {
      // Load the current asset.
      $current_asset = AssetEntity::load($entity->id());

      // Test if the parent has changed from edits.
      $parent_changed = $form_state->getValue('parent_related_assets')[0]['target_id'] !== $current_asset->getParentId() ? TRUE : FALSE;
      $previous_parent_id = $current_asset->getParentId();

      // Check if the given children list has changed.
      $changed_children_list = $this->checkChildrenChange($current_asset, $form_state->getValue('child_related_assets'));
      $children_changed = count($changed_children_list) === 0 ? FALSE : TRUE;

    }

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {

      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime(REQUEST_TIME);
      $entity->setRevisionUserId(\Drupal::currentUser()->id());
    }

    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Asset entity.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Asset entity.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.asset_entity.canonical', ['asset_entity' => $entity->id()]);

    // Reload the current asset.
    $current_asset = AssetEntity::load($entity->id());

    // Removes duplicate children from the current asset.
    $this->removeDuplicateChildren($current_asset);

    // For all children assets. Set their parent to point at the current asset.
    for ($counter = 0; $counter < count($current_asset->getChildRelationships()); $counter++) {
      // Load the child asset so that values can be set.
      $child_asset = AssetEntity::load($current_asset->getChildRelationships()[$counter]['target_id']);

      // Set the child asset's parent to the current asset.
      $child_asset->setParentId($current_asset->id());

      // Save the changes to the children assets.
      try {
        $child_asset->save();
      } catch (EntityStorageException $e) {
        $this->logger('AssetEntityForm')
          ->error('Failed to save the child asset when setting it\'s parent. The child id is ' . $child_asset->id());
      }
    }

    // Automatically complete relationship with parent asset.
    if (!empty($current_asset->getParentId())) {
      $this->addAssetToParent($current_asset->getParentId(), $current_asset->id());
    }

    // Automatically deal with assets if parent changes.
    if ($parent_changed) {
      $this->removeAssetFromParent($previous_parent_id, $current_asset->id());
    }

    // Automatically deal with assets if children change.
    if ($children_changed) {
      $this->removeParentFromChildren($changed_children_list);
    }
  }

  /**
   * Checks if any assets have been removed and returns their Id's.
   *
   * @param \Drupal\service_club_asset\Entity\AssetEntity $current_asset
   *   The current asset before it's been saved and updated.
   * @param array $new_children_list
   *   The updated list of asset children.
   *
   * @return array
   *   Returns an array of Id's corresponding to assets that were removed.
   */
  public function checkChildrenChange(AssetEntity $current_asset, array $new_children_list) {
    // Load the previous child asset list.
    $previous_children_list = $current_asset->getChildRelationships();

    // Create an empty map to store the id of the previous children.
    $previous_child_id_list = [];

    // Create the map of children id's.
    foreach ($previous_children_list as $previous_child) {
      $previous_child_id_list += [$previous_child['target_id'] => 1];
    }

    // Get the new children list and loop.
    foreach ($new_children_list as $new_child) {
      // Ignore the case when an empty block is left in the form.
      if (!empty([$new_child['target_id']])) {

        // If it's empty it's a new child we can ignore here.
        if (!empty($previous_child_id_list[$new_child['target_id']])) {
          // Check if a child has been removed.
          if ($previous_child_id_list[$new_child['target_id']] === 1) {
            $previous_child_id_list[$new_child['target_id']] = 2;
          }
        }
      }
    }

    // The list will store children who have been removed.
    $changed_children_list = [];

    // Check if a child has been removed.
    foreach ($previous_child_id_list as $child_id => $changed) {
      if ($changed === 1) {
        $changed_children_list += [$child_id];
      }
    }

    return $changed_children_list;
  }

  /**
   * Add the current asset to it's parent's children list.
   *
   * @param \Drupal\service_club_asset\Form\int $parent_id
   *   Id of the parent asset.
   * @param \Drupal\service_club_asset\Form\int $current_asset_id
   *   Id of the current asset.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addAssetToParent(int $parent_id, int $current_asset_id) {
    // Load the parent Asset.
    $parent_asset = AssetEntity::load($parent_id);

    $total_children = count($parent_asset->getChildRelationships());

    // Get the child list of the parent asset and append the current asset
    // to that list.
    $children_list = $parent_asset->getChildRelationships();
    $children_list += [$total_children => ['target_id' => $current_asset_id]];

    $unique_children_list = [];
    foreach ($children_list as $child) {
      // If the same child appears twice it will overwrite the previous child.
      $unique_children_list += [$child['target_id'] => $child];
    }

    // Set the current asset to be a child of it's parent.
    $parent_asset->setChildRelationships($unique_children_list);

    // Save the changes to the parent asset.
    try {
      $parent_asset->save();
    } catch (EntityStorageException $e) {
      $this->logger('AssetEntityForm')
        ->error('Failed to save the parent asset when setting it\'s child. The parent id is ' . $parent_asset->id());
    }
  }

  /**
   * Disassociates current asset from parent.
   *
   * @param \Drupal\service_club_asset\Form\int $previous_parent_id
   *   Id corresponding to previous parent.
   * @param \Drupal\service_club_asset\Form\int $current_asset_id
   *   Id corresponding to the current asset.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeAssetFromParent(int $previous_parent_id, int $current_asset_id) {
    // Ensure that there was previously a parent.
    if (!empty($previous_parent_id)) {
      $previous_parent = AssetEntity::load($previous_parent_id);

      $children_list = $previous_parent->getChildRelationships();

      // Find and remove the current asset from the children list.
      $new_children_list = [];
      foreach ($children_list as $child) {
        if ($child['target_id'] !== $current_asset_id) {
          $new_children_list += [count($new_children_list) => $child];
        }
      }

      // Save the children list without the current asset.
      $previous_parent->setChildRelationships($new_children_list);

      // Save the changes to the previous parent asset.
      try {
        $previous_parent->save();
      } catch (EntityStorageException $e) {
        $this->logger('AssetEntityForm')
          ->error('Failed to save the previous parent asset when setting it\'s children. The parent id is ' . $previous_parent->id());
      }
    }
  }

  /**
   * Function removes current asset as parent from the current asset's children.
   *
   * @param array $changed_children_list
   *   Is an array containing the id's of children which have been removed
   *   from the relationship.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeParentFromChildren(array $changed_children_list) {
    // Loop over each child that is no longer part of the list.
    foreach ($changed_children_list as $child_id) {
      $child_asset = AssetEntity::load($child_id);

      // Remove the parent.
      $child_asset->setParentId('');

      // Save the changes to the child asset.
      try {
        $child_asset->save();
      } catch (EntityStorageException $e) {
        $this->logger('AssetEntityForm')
          ->error('Failed to save the child asset while removing it\'s previous parent. The child id is ' . $child_asset->id());
      }
    }
  }

  /**
   * Removes duplicate children from the current asset.
   *
   * @param \Drupal\service_club_asset\Entity\AssetEntity $current_asset
   *   Is the asset with a children list to remove duplicates from.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeDuplicateChildren(AssetEntity $current_asset) {
    $unique_children_list = [];
    foreach ($current_asset->getChildRelationships() as $child) {
      // If the same child appears twice it will overwrite the previous child.
      $unique_children_list += [$child['target_id'] => $child];
    }
    // Save the changes.
    $current_asset->setChildRelationships($unique_children_list);
    $current_asset->save();
  }

}
