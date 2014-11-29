<?php 

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

$app['debug'] = true;

// Register the Twig service provider
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
    'twig.options' => array('cache' => __DIR__.'/../cache'),
));

// Register the session service provider
$app->register(new Silex\Provider\SessionServiceProvider());

// Register the translation service provider
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'locale_fallback' => 'en',
));

// Add the YAML file loader to the translator
$app['translator'] = $app->share(
    $app->extend('translator', function ($translator, $app) {
        $translator->addLoader('yaml', new Symfony\Component\Translation\Loader\YamlFileLoader());
 
        return $translator;
    })
);

// Register the Monolog service provider
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.name' => 'ltWords',
    'monolog.logfile' => __DIR__.'/ltWords.log',
    'monolog.level' => Monolog\Logger::DEBUG,
));

return $app;
