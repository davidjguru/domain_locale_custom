<?php

namespace Drupal\domain_locale_custom;

use Drupal\domain\DomainLoaderInterface;
use Drupal\domain\DomainElementManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Checks the access status of entities based on domain settings.
 */
class DomainLocaleElementManager extends DomainElementManager {

  /**
   * @inheritdoc
   */
  public function setFormOptions(array $form, FormStateInterface $form_state, $field_name, $hide_on_disallow = FALSE) {
    // There are cases, such as Entity Browser, where the form is partially
    // invoked, but without our fields.
    if (!isset($form[$field_name])) {
      return $form;
    }
    $current_language = \Drupal::languageManager()->getCurrentLanguage();
    $domain_by_host = domain_locale_custom_get_active_domain();
    $domain = domain_locale_custom_get_domain_by_langcode($current_language->getId());
    $requested_path = \Drupal::request()->getRequestUri();
    $path_data = explode('/', $requested_path);
    $requested_language = $path_data[1];
    $requested_language_object = \Drupal::languageManager()->getLanguage($requested_language);

    $form['field_domain_all_affiliates']['#access'] = FALSE;

    $node = $form_state->getFormObject()->getEntity();
    $adding_translation = FALSE;
    if(isset($path_data[4]) && isset($path_data[5])) {
      if($path_data[4] == 'translations' && $path_data[5] == 'add') {
        $adding_translation = TRUE;
      }
    }
    if ($node->isNew() || $adding_translation) {

      switch($domain->id()) {
        case DOMAIN_ONE:
          unset($form[$field_name]['widget']['#options'][DOMAIN_TWO]);
          $form[$field_name]['widget']['#default_value'] = [DOMAIN_ONE];
          $form[DOMAIN_SOURCE_FIELD]['widget']['#default_value'] = [DOMAIN_ONE];
          break;
        case DOMAIN_TWO:
          unset($form[$field_name]['widget']['#options'][DOMAIN_ONE]);
          $form[$field_name]['widget']['#default_value'] = [DOMAIN_TWO];
          $form[DOMAIN_SOURCE_FIELD]['widget']['#default_value'] = [DOMAIN_TWO];
          break;
      }
    }

    return $form;
  }
}
