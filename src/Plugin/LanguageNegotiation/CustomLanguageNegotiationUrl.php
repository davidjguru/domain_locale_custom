<?php

namespace Drupal\domain_locale_custom\Plugin\LanguageNegotiation;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcher;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\language\LanguageSwitcherInterface;
use Drupal\Component\Utility\UserAgent;
use Drupal\domain_locale_custom\DomainLocaleEntityFinder;
use Drupal\domain_locale_custom\MasterRequestData;

/**
 * Class for identifying language via URL prefix or domain.
 *
 * @LanguageNegotiation(
 *   id = \Drupal\domain_locale_custom\Plugin\LanguageNegotiation\CustomLanguageNegotiationUrl::METHOD_ID,
 *   types = {\Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE,
 *   \Drupal\Core\Language\LanguageInterface::TYPE_CONTENT,
 *   \Drupal\Core\Language\LanguageInterface::TYPE_URL},
 *   weight = -9,
 *   name = @Translation("Domain Locale Custom URL"),
 *   description = @Translation("Custom Domain-aware Language from the URL (Path prefix or domain)."),
 *   config_route_name = "domain_locale_custom.negotiation_url"
 * )
 */
class CustomLanguageNegotiationUrl extends LanguageNegotiationUrl implements LanguageSwitcherInterface, ContainerFactoryPluginInterface {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-url-domain-locale-custom';

  /**
   * The master request data class.
   *
   * @var Drupal\domain_locale_custom\MasterRequestData
   */
  protected $data;

  /**
   * The current path stack.
   *
   * @var Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * The path matcher.
   *
   * @var Drupal\Core\Path\PathMatcher
   */
  protected $pathMatcher;

  /**
   * The custom entity finder.
   *
   * @var Drupal\domain_locale_custom\DomainLocaleEntityFinder
   */
  protected $entityFinder;

  /**
   * Construct a new CustomLanguageNegotiationUrl.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MasterRequestData $data, CurrentPathStack $current_path_stack, PathMatcher $path_matcher, DomainLocaleEntityFinder $entity_finder) {
    $this->data = $data;
    $this->currentPathStack = $current_path_stack;
    $this->pathMatcher = $path_matcher;
    $this->entityFinder = $entity_finder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('domain_locale_custom.master_request_data'),
      $container->get('path.current'),
      $container->get('path.matcher'),
      $container->get('domain_locale_custom.entity_finder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $this->data->setCurrentUser($this->currentUser);
    $langcode = parent::getLangcode($request);
    $this->data->setNegotiatedLangcode($langcode);
    return $langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageSwitchLinks(Request $request, $type, Url $url) {
    $links = [];
    $query = $request->query->all();
    $host = $request->getHost();
    $active_domain = $this->data->currentDomain();

    foreach ($this->languageManager->getNativeLanguages() as $language) {
      $current_language = $this->data->currentLanguage();
      $domain = domain_locale_custom_get_domain_by_langcode($language->getId());
      $path = $this->currentPathStack->getPath();

      if(domain_locale_custom_language_mismatches_domain($domain, $current_language)) {
        $new_uri = $domain->getPath() . $language->getId() . $path;
        $switcher_url = Url::fromUri($new_uri);
      } else {
        $switcher_url = clone $url;
      }

      $options['active_domain'] = $active_domain;
      $entity = $this->entityFinder->findEntity($path, $options);

      if(!empty($entity) && !$this->pathMatcher->isFrontPage()) {
        if(!$entity->hasTranslation($language->getId())) {
          continue;
        }
      }

      if($this->pathMatcher->isFrontPage()) {
        if($current_language->getId() != $language->getId()) {
          $switcher_url = Url::fromUri($domain->getPath() . $language->getId());
        } else {
          $switcher_url = Url::fromRoute('<front>');
        }
      }

      switch($current_language->getId()) {
        case 'en-us':
        case 'en-ca':
          $translated_names = [
            'en-us' => 'English (US)',
            'en-ca' => 'English (CA)',
            'fr-ca' => 'French (CA)'
          ];
          break;
        case 'fr-ca':
          $translated_names = [
            'en-us' => 'Anglais (US)',
            'en-ca' => 'Anglais (CA)',
            'fr-ca' => 'Français (CA)'
          ];
          break;
      }
      $language->setName($translated_names[$language->getId()]);
      $links[$language->getId()] = [
        'url' => $switcher_url,
        'title' => $language->getName(),
        'language' => $language,
        'attributes' => ['class' => ['language-link']],
        'query' => $query,
      ];
    }

    return $links;
  }

}
