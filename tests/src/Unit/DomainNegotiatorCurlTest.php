<?php

namespace Drupal\Tests\domain_locale_custom\Unit;

use Drupal\Tests\UnitTestCase;
use DrupalProjectStub\Settings\ProjectSettings;

/**
 * Unit tests that run curl against the current site.
 *
 * @group GFS
 */
class DomainNegotiatorCurlTest extends UnitTestCase {

  /**
   * The project's settings.
   *
   * @var DrupalProjectStub\Settings\ProjectSettings
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->settings = ProjectSettings::getSettings();
    $this->baseUrlUs = $this->settings['base_url_us'];
    $this->baseUrlCa = $this->settings['base_url_ca'];
  }

  /**
   * Fetch a remote URL in a specific browser language.
   *
   * @param string $url
   *   The URL to fetch.
   * @param string $browser_langcode
   *   The browser language to request as.
   *
   * @return string
   *   The URL the user will land on.
   */
  protected function getRedirectedUrl($url, $browser_langcode = 'en-us') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept-Language: ' . $browser_langcode]);
    $html = curl_exec($ch);
    $redirected_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return $redirected_url;
  }

  /**
   * Get the status code for a URL.
   *
   * @param string $url
   *   The URL to fetch.
   * @param string $browser_langcode
   *   The browser language to request as.
   *
   * @return string
   *   The status code.
   */
  protected function getStatusCode($url, $browser_langcode = 'en-us') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept-Language: ' . $browser_langcode]);
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code;
  }

  /**
   * Tests gfs.com scenarios as the en-us browser langcode.
   */
  public function testGfsComAsAmerican() {
    $final_url = $this->getRedirectedUrl($this->baseUrlUs);
    $this->assertEquals($this->baseUrlUs .'/en-us', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlUs .'/en-us');
    $this->assertEquals($this->baseUrlUs .'/en-us', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlUs .'/en');
    $this->assertEquals($this->baseUrlUs .'/en-us', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlUs .'/en/solutions');
    $this->assertEquals($this->baseUrlUs .'/en-us/solutions/online-tools', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlUs .'/en-ca');
    $this->assertEquals($this->baseUrlCa .'/en-ca', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlUs .'/fr-ca');
    $this->assertEquals($this->baseUrlCa .'/fr-ca', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlUs .'/fr');
    $this->assertEquals($this->baseUrlCa .'/fr-ca', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlUs .'/fr/solutions');
    $this->assertEquals($this->baseUrlCa .'/fr-ca/solutions', $final_url);
  }

  /**
   * Tests gfs.com scenarios as the en-ca browser langcode.
   */
  public function testGfsComAsCanadian() {
    $final_url = $this->getRedirectedUrl($this->baseUrlUs, 'en-ca');
    $this->assertEquals($this->baseUrlUs .'/en-us', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlUs .'/en-us', 'en-ca');
    $this->assertEquals($this->baseUrlUs .'/en-us', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlUs .'/en', 'en-ca');
    $this->assertEquals($this->baseUrlUs .'/en-us', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlUs .'/en-ca', 'en-ca');
    $this->assertEquals($this->baseUrlCa .'/en-ca', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlUs .'/fr-ca', 'en-ca');
    $this->assertEquals($this->baseUrlCa .'/fr-ca', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlUs .'/fr', 'en-ca');
    $this->assertEquals($this->baseUrlCa .'/fr-ca', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlUs .'/fr/solutions', 'en-ca');
    $this->assertEquals($this->baseUrlCa .'/fr-ca/solutions', $final_url);
  }

  /**
   * Tests gfs.ca scenarios as the en-us browser langcode.
   */
  public function testGfsCaAsAmerican() {
    $final_url = $this->getRedirectedUrl($this->baseUrlCa);
    $this->assertEquals($this->baseUrlCa .'/en-ca', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/en-us');
    $this->assertEquals($this->baseUrlUs .'/en-us', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/en');
    $this->assertEquals($this->baseUrlCa .'/en-ca', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/en-ca');
    $this->assertEquals($this->baseUrlCa .'/en-ca', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/fr-ca');
    $this->assertEquals($this->baseUrlCa .'/fr-ca', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/fr');
    $this->assertEquals($this->baseUrlCa .'/fr-ca', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/fr/solutions');
    $this->assertEquals($this->baseUrlCa .'/fr-ca/solutions', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/en/solutions');
    $this->assertEquals($this->baseUrlCa .'/en-ca/solutions', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/ideas');
    $this->assertEquals($this->baseUrlCa .'/en-ca/ideas/home', $final_url);

  }

  /**
   * Tests gfs.ca scenarios as the en-ca browser langcode.
   */
  public function testGfsCaAsCanadianCa() {
    $final_url = $this->getRedirectedUrl($this->baseUrlCa, 'en-ca');
    $this->assertEquals($this->baseUrlCa .'/en-ca', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/en-us', 'en-ca');
    $this->assertEquals($this->baseUrlUs .'/en-us', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/en', 'en-ca');
    $this->assertEquals($this->baseUrlCa .'/en-ca', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/en-ca', 'en-ca');
    $this->assertEquals($this->baseUrlCa .'/en-ca', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/fr-ca', 'en-ca');
    $this->assertEquals($this->baseUrlCa .'/fr-ca', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/fr', 'en-ca');
    $this->assertEquals($this->baseUrlCa .'/fr-ca', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/fr/solutions', 'en-ca');
    $this->assertEquals($this->baseUrlCa .'/fr-ca/solutions', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/en/solutions', 'en-ca');
    $this->assertEquals($this->baseUrlCa .'/en-ca/solutions', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/ideas', 'en-ca');
    $this->assertEquals($this->baseUrlCa .'/en-ca/ideas/home', $final_url);

  }

  /**
   * Tests gfs.ca scenarios as the fr-ca browser langcode.
   */
  public function testGfsCaAsCanadianFr() {
    $final_url = $this->getRedirectedUrl($this->baseUrlCa, 'fr-ca');
    $this->assertEquals($this->baseUrlCa .'/fr-ca', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/en-us', 'fr-ca');
    $this->assertEquals($this->baseUrlUs .'/en-us', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/en', 'fr-ca');
    $this->assertEquals($this->baseUrlCa .'/en-ca', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/en-ca', 'fr-ca');
    $this->assertEquals($this->baseUrlCa .'/en-ca', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/fr-ca', 'fr-ca');
    $this->assertEquals($this->baseUrlCa .'/fr-ca', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/fr', 'fr-ca');
    $this->assertEquals($this->baseUrlCa .'/fr-ca', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/fr/solutions', 'fr-ca');
    $this->assertEquals($this->baseUrlCa .'/fr-ca/solutions', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/en/solutions', 'fr-ca');
    $this->assertEquals($this->baseUrlCa .'/en-ca/solutions', $final_url);

    $final_url = $this->getRedirectedUrl($this->baseUrlCa .'/idees', 'fr-ca');
    $this->assertEquals($this->baseUrlCa .'/fr-ca/idees/accueil', $final_url);

  }

  public function testLocationsJsonEndpoint() {
    $code = $this->getStatusCode($this->baseUrlUs . '/en-us/service/locations/area?_format=json');
    $this->assertEquals(200, $code);
    $code = $this->getStatusCode($this->baseUrlCa . '/en-ca/service/locations/area?_format=json');
    $this->assertEquals(200, $code);
    $code = $this->getStatusCode($this->baseUrlCa . '/fr-ca/service/locations/area?_format=json');
    $this->assertEquals(200, $code);
  }

}
