<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

$app['appName'] = 'ltWords';
$app['debug'] = true;

// Register the Twig service provider
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

$app->get('/search/{name}', function ($name) use ($app) {
	$parameter = $name;
	if ($parameter == '') {
		$parameter = $app['request']->get('searchString');
	}
	$nameStr = $parameter;
	if (is_numeric($nameStr)) {
		$ltNumbers = new LtWords\LtNumbers\LtNumbers;
		$nameStr = $ltNumbers->numberToText($parameter);
	}

    return $app['twig']->render('search.twig', array(
        'title' => 'Search',
        'name' => $parameter,
        'result' => $nameStr
    ));
})
->value('name', '');

$app->get('/', function() use($app) {
    return $app['twig']->render('index.twig', array(
        'title' => $app['appName']
    ));
});

$app->run();
