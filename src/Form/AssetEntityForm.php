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
  }

}
