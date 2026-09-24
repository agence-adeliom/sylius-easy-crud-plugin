<?php

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Formatter\PrettyFormatter;
use Behat\Config\Profile;
use Behat\Config\TesterOptions;
use Behat\MinkExtension\ServiceContainer\MinkExtension;
use DMore\ChromeExtension\Behat\ServiceContainer\ChromeExtension;
use FriendsOfBehat\MinkDebugExtension\ServiceContainer\MinkDebugExtension;
use FriendsOfBehat\SymfonyExtension\ServiceContainer\SymfonyExtension;
use FriendsOfBehat\VariadicExtension\ServiceContainer\VariadicExtension;
use Robertfausk\Behat\PantherExtension\ServiceContainer\PantherExtension;
use SyliusLabs\SuiteTagsExtension\ServiceContainer\SuiteTagsExtension;

return (new Config())
    ->import([
        'vendor/sylius/sylius/src/Sylius/Behat/Resources/config/suites.php',
        'tests/Behat/Resources/suites.php',
    ])
    ->withProfile(
        (new Profile('default'))
        ->withFormatter(new PrettyFormatter(paths: false))
        ->withTesterOptions((new TesterOptions())
            ->withErrorReporting(\E_ALL & ~(\E_DEPRECATED | \E_USER_DEPRECATED)))
        ->withExtension(new Extension(ChromeExtension::class))
        ->withExtension(new Extension(PantherExtension::class))
        ->withExtension(new Extension(MinkDebugExtension::class, [
            'directory' => 'etc/build',
            'clean_start' => false,
            'screenshot' => true,
        ]))
        ->withExtension(new Extension(MinkExtension::class, [
            'files_path' => '%paths.base%/vendor/sylius/sylius/src/Sylius/Behat/Resources/fixtures/',
            'base_url' => 'https://127.0.0.1:8080/',
            'default_session' => 'symfony',
            'javascript_session' => 'panther',
            'sessions' => [
                'symfony' => [
                    'symfony' => null,
                ],
                'chrome' => [
                    'chrome' => [
                        'api_url' => 'http://127.0.0.1:9222',
                        'validate_certificate' => false,
                    ],
                ],
                'panther' => [
                    'panther' => [
                        'manager_options' => [
                            'connection_timeout_in_ms' => 5000,
                            'request_timeout_in_ms' => 120000,
                            'chromedriver_arguments' => [
                                '--log-path=etc/build/chromedriver.log',
                                '--verbose',
                            ],
                            'capabilities' => [
                                'acceptSslCerts' => true,
                                'acceptInsecureCerts' => true,
                                'unexpectedAlertBehaviour' => 'accept',
                            ],
                        ],
                        'options' => [
                            'webServerDir' => '%paths.base%/vendor/sylius/test-application/public',
                            'browser_arguments' => [
                                '--window-size=1200,1000',
                                '--headless',
                                '--no-sandbox',
                                '--disable-dev-shm-usage',
                                '--disable-gpu',
                                '--disable-infobars',
                                '--disable-features=TranslateUI',
                                '--disable-translate',
                                '--disable-popup-blocking',
                                '--disable-blink-features=AutomationControlled',
                                '--disable-component-extensions-with-background-pages',
                                '--disable-background-networking',
                                '--disable-dev-tools',
                                '--disable-extensions',
                                '--disable-password-manager-leak-detection',
                            ],
                        ],
                    ],
                ],
            ],
            'show_auto' => false,
        ]))
        ->withExtension(new Extension(SymfonyExtension::class, [
            'bootstrap' => 'vendor/sylius/test-application/config/bootstrap.php',
            'kernel' => [
                'class' => 'Sylius\TestApplication\Kernel',
                'environment' => 'test',
                'debug' => false,
            ],
        ]))
        ->withExtension(new Extension(VariadicExtension::class))
        ->withExtension(new Extension(SuiteTagsExtension::class)),
    )
;
