<?php

namespace Drupal\domain_locale_custom;

use Drupal\Component\Utility\UserAgent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\Routing\Route;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\domain\Entity\Domain;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\core\Language\LanguageInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Class used for keeping track of data about each request
 * during language negotiation.
 */
class MasterRequestData {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The route match object for the current page.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The Symfony route object for the current page.
   *
   * @var \Symfony\Component\Routing\Route | null
   */
  protected $routeObject;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The current active domain.
   *
   * @var \Drupal\domain\Entity\Domain
   */
  protected $currentDomain;

  /**
   * The known langcode id's that our site cares about.
   *
   * @var array
   */
  protected $knownLangcodes;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The browser's langcode.
   *
   * @var string
   */
  protected $browserLangcode;

  /**
   * The domain that the browser's langcode belongs to.
   *
   * @var \Drupal\domain\Entity\Domain
   */
  protected $browserDomain;

  /**
   * The requested path as a string.
   *
   * @var array
   */
  protected $requestedPath;

  /**
   * An array of path data for the current request.
   *
   * @var array
   */
  protected $requestedPathData;

  /**
   * The langcode that was requested in the path.
   *
   * @var string | null
   */
  protected $requestedLangcode;

  /**
   * The language object of the requested langcode id.
   *
   * @var \Drupal\core\Language\LanguageInterface | null
   */
  protected $requestedLanguageObject;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * The langcode that is negotiated in the LanguageNegotiation plugin.
   *
   * @var string
   */
  protected $negotiatedLangcode;

  /**
   * Constructs a new MasterRequestData.
   */
  public function __construct(ConfigFactoryInterface $config, RequestStack $request_stack, RouteMatchInterface $route_match, AdminContext $admin_context, LanguageManagerInterface $language_manager, LoggerChannelFactoryInterface $logger, KillSwitch $kill_switch) {
    $this->config = $config;
    $this->requestStack = $request_stack;
    $this->routeMatch = $route_match;
    $this->adminContext = $admin_context;
    $this->languageManager = $language_manager;
    $this->logger = $logger;
    $this->killSwitch = $kill_switch;
    $this->request();
    $this->routeObject();
    $this->isAdminRoute();
    $this->currentDomain();
    $this->knownLangcodes();
    $this->browserLangcode();
    $this->browserDomain();
    $this->requestedPath();
    $this->requestedPathData();
    $this->requestedLangcode();
    $this->requestedLanguageObject();
  }

  /**
   * Get the current language.
   *
   * @return \Drupal\Core\Language\LanguageInterface
   *   The Drupal language interface.
   */
  public function currentLanguage() {
    return $this->languageManager->getCurrentLanguage();
  }

  /**
   * Get the current request.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   The Symfony request object.
   */
  public function request() {
    if (isset($this->request)) {
      return $request;
    }
    $this->request = $this->requestStack->getCurrentRequest();
    return $this->request;
  }

  /**
   * Get the current route object.
   *
   * @return \Symfony\Component\Routing\Route
   *   The Symfony route object.
   */
  public function routeObject() {
    if ($this->routeObject) {
      return $routeObject;
    }
    $this->routeObject = $this->routeMatch->getRouteObject();
    return $this->routeObject;
  }

  /**
   * Write the request to the Drupal log.
   */
  public function log() {
    if ($this->currentUser() !== null) {
      $current_user = $this->currentUser()->id();
    } else {
      $current_user = 'none';
    }
    if ($this->currentDomain() !== null) {
      $current_domain = $this->currentDomain()->id();
    } else {
      $current_domain = 'none';
    }
    if ($this->browserDomain() !== null) {
      $browser_domain = $this->browserDomain()->id();
    } else {
      $browser_domain = 'none';
    }
    if ($this->requestedLanguageObject() !== null) {
      $requested_language_object = $this->requestedLanguageObject()->getId();
    } else {
      $requested_language_object = 'none';
    }
    $this->logger->get('master request data')->info('
      <strong>Path:</strong> @path 
      <br />
      <strong>Current Domain ID:</strong> @domain_id 
      <br />
      <strong>Current User ID:</strong> @current_user_id
      <br />
      <strong>Browser Langcode:</strong> @browser_langcode
      <br />
      <strong>Browser Domain ID:</strong> @browser_domain_id 
      <br />
      <strong>Requested Path Data:</strong> @requested_path_data 
      <br />
      <strong>Requested Langcode:</strong> @requested_langcode
      <br />
      <strong>Requested Langcode Object ID:</strong> @requested_langcode_object_id
      <br />
      <strong>Negotiated Langcode:</strong> @negotiated_langcode
      ',
      [
        '@path' => $this->request->getRequestUri(),
        '@domain_id' => $current_domain,
        '@current_user_id' => $current_user,
        '@browser_langcode' => $this->browserLangcode(),
        '@browser_domain_id' => $browser_domain,
        '@requested_path_data' => print_r($this->requestedPathData(), TRUE),
        '@requested_langcode' => $this->requestedLangcode(),
        '@requested_langcode_object_id' => $requested_language_object,
        '@negotiated_langcode' => $this->negotiatedLangcode
      ]);
  }

  /**
   * Sets the known langcodes.
   *
   * @return array
   *   The array of known langcodes.
   */
  public function knownLangcodes() {
    if ($this->knownLangcodes) {
      return $this->knownLangcodes;
    }
    $this->knownLangcodes = [
      'en',
      'fr',
      'en-us',
      'en-ca',
      'fr-ca'
    ];
    return $this->knownLangcodes;
  }

  /**
   * Sets the browser langcode automatically.
   *
   * @return string
   *   The browser's langcode id.
   */
  public function browserLangcode() {
    if ($this->browserLangcode) {
      return $this->browserLangcode;
    }
    if (!isset($this->request)) {
      return $this->browserLangcode;
    }
    $http_accept_language = $this->request->server->get('HTTP_ACCEPT_LANGUAGE');
    $langcodes = array_keys($this->languageManager->getLanguages());
    $mappings = $this->config->get('language.mappings')->get('map');
    $this->browserLangcode = UserAgent::getBestMatchingLangcode($http_accept_language, $langcodes, $mappings);
    return $this->browserLangcode;
  }

  /**
   * Gets the current active user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   */
  public function currentUser() {
    return $this->currentUser;
  }

  /**
   * Sets the current active user.
   *
   * @param AccountProxyInterface $current_user
   *   The user to set.
   */
  public function setCurrentUser(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
    if (!$this->currentUser()->isAnonymous()) {
      // Don't cache the request for logged in users.
      $this->killSwitch->trigger();
    }
  }

  /**
   * Sets the current active user.
   *
   * @param string $langcode
   *   The negotiated langcode.
   */
  public function setNegotiatedLangcode($langcode) {
    $this->negotiatedLangcode = $langcode;
  }

  /**
   * Determine if the current route is an admin route.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  public function isAdminRoute() {
    return $this->adminContext->isAdminRoute($this->routeObject);
  }

  /**
   * Set the current domain.
   *
   * @return \Drupal\domain\Entity\Domain
   *   The currently active domain in Drupal.
   */
  public function currentDomain() {
    if ($this->currentDomain) {
      return $this->currentDomain;
    }
    $this->currentDomain = domain_locale_custom_get_active_domain();
    return $this->currentDomain;
  }

  /**
   * Set the browser domain.
   *
   * @return \Drupal\domain\Entity\Domain | null
   *   The domain that belongs to the browser's current language.
   */
  public function browserDomain() {
    if ($this->browserDomain) {
      return $this->browserDomain;
    }
    $this->browserDomain = domain_locale_custom_get_domain_by_langcode($this->browserLangcode);
    return $this->browserDomain;
  }

  /**
   * Set the requested path.
   *
   * @return string
   *   The requested path.
   */
  public function requestedPath() {
    if ($this->requestedPath) {
      return $this->requestedPath;
    }
    if (!isset($this->request)) {
      return $this->requestedPath;
    }
    $this->requestedPath = $this->request->getRequestUri();
    return $this->requestedPath;
  }

  /**
   * Set the requested path data as a PHP array.
   *
   * @return array
   *   The array of requested path data as strings.
   */
  public function requestedPathData() {
    if ($this->requestedPathData) {
      return $this->requestedPathData;
    }
    if (!isset($this->request)) {
      return $this->requestedPathData;
    }
    $requested_path = $this->request->getRequestUri();
    $this->requestedPathData = explode('/', $requested_path);
    return $this->requestedPathData;
  }

  /**
   * Set the requested langcode on the path data.
   *
   * @return string
   *   The requested langcode from the path.
   */
  public function requestedLangcode() {
    if ($this->requestedLangcode) {
      return $this->requestedLangcode;
    }
    if (isset($this->requestedPathData[1])) {
      $this->requestedLangcode = $this->requestedPathData[1];
    } else {
      $this->requestedLangcode = null;
    }
    return $this->requestedLangcode;
  }

  /**
   * Set the requested language object automatically.
   *
   * @return \Drupal\core\Language\LanguageInterface | null
   *   The language object requested based on path langcode.
   */
  public function requestedLanguageObject() {
    if ($this->requestedLanguageObject) {
      return $this->requestedLanguageObject;
    }
    $this->requestedLanguageObject = $this->languageManager->getLanguage($this->requestedLangcode);
    return $this->requestedLanguageObject;
  }

  /**
   * Validate the path langcode and return as a string if it is a real langcode.
   *
   * @return string | null
   *   The valid language code or null if it can't be determined.
   */
  public function pathLangcode() {
    $langcode = NULL;
    if ($this->request && $this->languageManager) {
      $languages = $this->languageManager->getLanguages();
      $config = $this->config->get('language.negotiation')->get('url');
      if (isset($this->requestedPathData[0])) {
        $requested_prefix = array_slice($this->requestedPathData, 1)[0];
      } else {
        return $langcode;
      }

      // Search prefix within added languages.
      $valid_language = FALSE;
      foreach ($languages as $language) {
        if (isset($config['prefixes'][$language->getId()]) && $config['prefixes'][$language->getId()] == $this->requestedLangcode) {
          $valid_language = $language;
          break;
        }
      }
      if ($valid_language) {
        $langcode = $valid_language->getId();
      }
    }
    return $langcode;
  }

}
