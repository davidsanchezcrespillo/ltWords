<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

$app['appName'] = 'ltWords';
$app['debug'] = true;

// Register the Twig service provider
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

$app->get('/numbers/{name}', function ($name) use ($app) {
    $parameter = $name;
    if ($parameter == '') {
        $parameter = $app['request']->get('searchString');
    }
    $nameStr = $parameter;
    if (is_numeric($nameStr)) {
        $ltNumbers = new LtWords\LtNumbers\LtNumbers;
        $nameStr = $ltNumbers->numberToText($parameter);
    }

    return $app['twig']->render('numbers.twig', array(
        'title' => 'LTNumbers',
        'name' => $parameter,
        'result' => $nameStr
    ));
})
->value('name', '');

$app->get('/nouns/{name}', function ($name) use ($app) {
    $parameter = $name;
    if ($parameter == '') {
        $parameter = $app['request']->get('searchString');
    }
    $ltNouns = new LtWords\LtNouns\LtNouns;
    $nounDeclensions = $ltNouns->generateDeclensions($parameter);

    return $app['twig']->render('nouns.twig', array(
        'title' => 'LTNumbers',
        'name' => $parameter,
        'result' => $nounDeclensions
    ));
})
->value('name', '');

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.twig', array(
        'title' => $app['appName']
    ));
});

$app->run();
