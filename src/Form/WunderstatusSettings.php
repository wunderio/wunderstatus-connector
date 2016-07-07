<?php

namespace Drupal\wunderstatus\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class WunderstatusSettings.
 *
 * @package Drupal\wunderstatus\Form
 */
class WunderstatusSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['wunderstatus.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wunderstatus_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $state = \Drupal::state();
    
    $form['authentication'] = array(
      '#type' => 'fieldset',
      '#title' => t('Authentication'),
    );
    
    $form['authentication']['wunderstatus_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Wunderstatus authentication key'),
      '#maxlength' => 60,
      '#size' => 65,
      '#required' => TRUE,
      '#default_value' => $state->get('wunderstatus_key'),
    );

    $form['manager_site'] = array(
      '#type' => 'fieldset',
      '#title' => t('Wunderstatus manager'),
    );

    $form['manager_site']['manager_site_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#maxlength' => 60,
      '#size' => 65,
      '#default_value' => $state->get('manager_site_url'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    \Drupal::state()->set('wunderstatus_key', $form_state->getValue('wunderstatus_key'));
    \Drupal::state()->set('manager_site_url', $form_state->getValue('manager_site_url'));
  }
}
