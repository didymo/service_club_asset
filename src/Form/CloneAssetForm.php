<?php

namespace Drupal\service_club_asset\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

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
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm clone'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm clone'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  function test() {

    print_r('hi');

    \Drupal::logger('submitForm channel')->error('hit the submitForm Section');
    //$entity = $this->entity;
    //$form_state->setRedirect('entity.asset_entity.canonical', ['asset_entity' => $entity->id()]);
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
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
    }

    \Drupal::logger('submitForm channel')->error('hit the submitForm Section');
    //$entity = $this->entity;
    //$form_state->setRedirect('entity.asset_entity.canonical', ['asset_entity' => $entity->id()]);

  }

}
