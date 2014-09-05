<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

$app['debug'] = true;

// Register the Twig service provider
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

$app->get('/hello/{name}', function($name) use ($app) {
    return 'Hello ' . $app->escape($name) . '!';
})
->value('name', 'world');

$app->get('/search/{name}', function ($name) use ($app) {
    return $app['twig']->render('search.twig', array(
        'title' => 'Search',
        'name' => $name,
    ));
})
->value('name', '');

$app->get('/', function() use($app) {
	return 'Home page!';
});

$app->run();
