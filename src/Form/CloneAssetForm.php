<?php

namespace Drupal\service_club_asset\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\service_club_asset\Entity\AssetEntity;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Class CloneAssetForm.
 */
class CloneAssetForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'clone_asset_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Create and array specifiying the values for the list.
    $cloneListElement = array(
      1 => $this->t('One'),
      2 => $this->t('Two'),
      3 => $this->t('Three'),
      4 => $this->t('Four'),
      5 => $this->t('Five'),
    );

    // Add list element to $form.
    $form['numberOfClones'] = array(
      '#type' => 'select',
      '#title' => $this->t('Select number of clones'),
      '#default_value' => 1,
      '#options' => $cloneListElement,
      '#description' => $this->t('Select number of clones to generate.'),
    );

    // Specify the submit button.
    $form['submit'] = [
      '#type' => 'submit',
      '#title' => 'Confirm the number of clones.',
      '#value' => $this->t('Confirm clone'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * @Todo drupal_set_message is a depreciated function in drupal 8 and will be
   * removed in drupal 9 replace with the following
   * \Drupal\Core\Messenger\MessengerInterface::addMessage()
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Array to store given form values.
    $extracted = [];

    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      $extracted[$key] = $value;
    }

    // Get the id of the asset you are trying to currently clone.
    $assetId = $this->getRouteMatch()->getParameter('asset_entity');

    // Load the asset into scope so that cloning can be done on it.
    $originalAsset = AssetEntity::load($assetId);

    // Create a number of clones decided by the user.
    for ($cloneCounter = 0; $cloneCounter < $extracted['numberOfClones']; $cloneCounter++) {
      // Create a clone of the asset.
      $this->cloneAsset($originalAsset);
    }

    drupal_set_message($originalAsset->getName() . ' has been cloned');

  }

  /**
   * Clones an asset.
   *
   * @param \Drupal\service_club_asset\Entity\AssetEntity $asset_entity
   *   A Asset entity object.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function cloneAsset(AssetEntity $asset_entity) {
    $asset_clone = $asset_entity->createDuplicate();

    // Remove children dependencies.
    $asset_clone->setChildRelationships([]);

    // Remove the parent dependency.
    $asset_clone->setParentId('');

    try {
      $asset_clone->save();
    }
    catch (EntityStorageException $e) {
      $this->logger('CloneAssetForm')
        ->error('Failed to save the clone asset');
    }
  }

}
