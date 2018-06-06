<?php

namespace Drupal\domain_locale_custom\HttpKernel;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Drupal\domain_locale_custom\MasterRequestData;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;

/**
 * Processes the outbound path and redirect prior to processing if needed.
 */
class DomainLocaleFrontPagePathProcessor implements OutboundPathProcessorInterface {

  /**
   * The kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * The master request data.
   *
   * @var \Drupal\domain_locale_custom\MasterRequestData
   */
  protected $data;

  /**
   * Constructs a DomainSourcePathProcessor object.
   */
  public function __construct(KillSwitch $kill_switch, MasterRequestData $data) {
    $this->killSwitch = $kill_switch;
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {

    $french_redirect_langcodes = [
      'fr',
      'fr-ca'
    ];

    // Browser: any language
    // Path: /
    // Domain: gfs.ca
    // Without this special case, visiting gfs.ca with no explicit
    // langcode in the path redirects to gfs.com/en-us.
    if ($this->data->currentDomain()->id() == 'gfs_ca' && $this->data->requestedPath() == '/') {
      $this->killSwitch->trigger();
      $options['prefix'] = 'en-ca/';
    }

    // Browser: fr-ca
    // Path: /
    // Domain: gfs.ca
    // Without this special case, visiting gfs.ca with no explicit
    // langcode in the path redirects to en-ca.
    if ($this->data->requestedPath() == '/' && in_array($this->data->browserLangcode(), $french_redirect_langcodes)) {
      $this->killSwitch->trigger();
      $options['prefix'] = 'fr-ca/';
    }
    return $path;
  }

}

