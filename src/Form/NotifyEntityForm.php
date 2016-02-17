<?php

namespace Drupal\notify_entity\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class NotifyEntityForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'notify_entity_settings';
  }


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'notify_entity.settings',
    ];
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['#tree'] = TRUE;

    $entities = \Drupal::entityManager()->getAllBundleInfo();
    foreach ($entities as $entity_type => $bundles) {
      foreach ($bundles as $bundle_type => $bundle_info) {
        $form['settings'][$entity_type][$bundle_type] = $this->formCreateRow(
          $entity_type, $entity_type, $bundle_type, $bundle_info['label']
        );
      }
    }
    return $form;
  }


  /**
   * Create a setting row fore each entity/bundle type.
   * @param string $entity_type   The entity type machine name; eg 'node'
   * @param string $entity_name   The entity type human name; eg 'Node'
   * @param string $bundle_type   The bundle machine name; eg 'article'
   * @param string $bundle_name   The bundle human name; eg 'Article'
   * @return array
   */
  protected function formCreateRow($entity_type, $entity_name, $bundle_type, $bundle_name) {
    $config = $this->config('notify_entity.settings');

    // If the entity and bundle types match, lets just assume it's a single-bundle entity type.
    if ($bundle_type == $entity_type) {
      $title = $entity_name;
    }
    else {
      $title = $this->t('@entity_name: %bundle_name', [
        '%bundle_name' => $bundle_name,
        '@entity_name' => $entity_name,
      ]);
    }
    $key = _notify_entity_make_key($entity_type, $bundle_type);


    $defaults = (array)$config->get($key) + [
      'email' => '',
      'insert' => TRUE,
      'update' => FALSE,
      'delete' => FALSE,
    ];

    return [
      '#type' => 'details',
      '#title' => $title,
      '#open' => !empty($defaults['email']),
      'email' => [
        '#title' => $this->t('Email'),
        '#type' => 'email',
        '#default_value' => $defaults['email'],
      ],
      'insert' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Trigger on insert'),
        '#default_value' => $defaults['insert'],
      ],
      'update' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Trigger on update'),
        '#default_value' => $defaults['update'],
      ],
      'delete' => [
        '#type' => 'checkbox',
        '#title' => $this->t('Trigger on delete'),
        '#default_value' => $defaults['delete'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('notify_entity.settings');
    $input = $form_state->getValue('settings');

    foreach ($input as $entity_type => $entity_settings) {
      foreach ($entity_settings as $bundle_type => $bundle_settings) {
        $key = _notify_entity_make_key($entity_type, $bundle_type);


        $bundle_settings['email'] = trim($bundle_settings['email']);

        if (empty($bundle_settings['email'])) {
          $config->clear($key);
        }
        else {
          $config->set($key, $bundle_settings);
        }
      }
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }
}
