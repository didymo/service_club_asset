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
      if (!(is_numeric($entity->getPrice()) || $entity->getPrice() == '')) {
        $form_state->setErrorByName('price', $this->t('Given price was not a valid value.'));
      }
    }

    $current_asset = AssetEntity::load($entity->id());



    //print_r($current_asset);
    //print_r($entity->getOriginalId());
    //$this->logger('relations')->error($entity->id());
    //$form_state->setErrorByName('pause', $this->t('Trying to halt execution'));

    return $entity;
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
      $child_asset->set('parent_related_assets', $current_asset->id());

      // Save the changes to the children assets.
      try {
        $child_asset->save();
      }
      catch (EntityStorageException $e) {
        $this->logger('AssetEntityForm')
          ->error('Failed to save the child asset when setting it\'s parent. The child id is ' . $child_asset->id());
      }
    }
  }

}
