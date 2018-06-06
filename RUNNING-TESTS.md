# Testing Domain Negotiation

Assumes you have a simpletest configuration enabled on your site according to https://www.drupal.org/docs/8/phpunit/running-phpunit-tests

When you are inside the site's git root, run the following:

`vendor/bin/phpunit -c app/core --debug --verbose app/modules/custom/domain_locale_custom/tests/src/Unit/DomainNegotiatorCurlTest.php`
