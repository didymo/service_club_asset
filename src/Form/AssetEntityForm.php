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

    // Load the current asset.
    $current_asset = AssetEntity::load($entity->id());

    // Set the child asset's parent to the current asset.
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
      // Load the parent Asset.
      $parent_asset = AssetEntity::load($current_asset->getParentId());

      $total_children = count($parent_asset->getChildRelationships());

      // Get the child list of the parent asset and append the current asset
      // to that list.
      $children_list = $parent_asset->getChildRelationships();
      $children_list += [$total_children => ['target_id' => $current_asset->id()]];

      // Set the current asset to be a child of it's parent.
      $parent_asset->setChildRelationships($children_list);

      $parent_asset->save();
    }
  }

}
