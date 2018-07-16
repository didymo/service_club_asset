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
  function test() {

    print_r('hi');

    $this->logger('submitForm channel')->error('hit the submitForm Section');
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
    // Array to store given form values.
    $extracted = [];

    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
      $extracted[$key] = $value;
    }

    $this->logger('submitForm channel')->error('the value: ' . $extracted['numberOfClones']);

  }

}
