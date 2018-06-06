<?php

namespace Drupal\domain_locale_custom\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\LanguageSelectWidget;

/**
 * Plugin implementation of the 'Language' widget.
 *
 * @FieldWidget(
 *   id = "gfs_language_select",
 *   label = @Translation("GFS Language select"),
 *   field_types = {
 *     "language"
 *   }
 * )
 */
class GfsLanguageSelectWidget extends LanguageSelectWidget {

  /**
   * After-build handler for field elements in a form.
   *
   * For each domain, remove language selections based on
   * the allowed langcodes.
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    $element = parent::afterBuild($element, $form_state);
    $current_language = \Drupal::languageManager()->getCurrentLanguage();
    $domain = domain_locale_custom_get_domain_by_langcode($current_language->getId());

    switch($domain->id()) {
      case DOMAIN_ONE:
        unset($element[0]['value']['#options']['en-ca']);
        unset($element[0]['value']['#options']['fr-ca']);
        break;
      case DOMAIN_TWO:
        unset($element[0]['value']['#options']['en-us']);
        break;
    }
    unset($element[0]['value']['#options']['und']);
    unset($element[0]['value']['#options']['zxx']);
    return $element;
  }

}
