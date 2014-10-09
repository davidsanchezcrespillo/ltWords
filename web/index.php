<?php

use Symfony\Component\Yaml\Parser;

$app = require __DIR__ . '/bootstrap.php';

// Get the default language from the session
$lang = "en";
if ($app['session']->get('current_language')) {
    $lang = $app['session']->get('current_language');
}
$app['monolog']->addDebug("Language: $lang");
 
foreach (glob(__DIR__ . '/locale/'. $lang . '/*.yml') as $locale) {
    $app['monolog']->addDebug("Locale: $locale");
    $app['translator']->addResource('yaml', $locale, $lang);
}

// Set the current language
$app['translator']->setLocale($lang);

// Get the list of current languages from the config file
$yamlParser = new Parser();
$images = $yamlParser->parse(file_get_contents(__DIR__ . '/images.yml'));
$app['images'] = $images;

//$app['monolog']->addDebug("Languages: $languages");

$app->get('/lang/{lang}', function ($lang) use ($app) {
    // check if language exists
    if (is_dir(__DIR__ . '/locale/' . $lang)) {
        // save user selection in session
        $app['session']->set('current_language', $lang);
    }
    return $app->redirect($_SERVER['HTTP_REFERER']);
});

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
        'result' => $nameStr,
        'images' => $app['images'],
    ));
})
->value('name', '');

$app->get('/nouns/{name}', function ($name) use ($app) {
    $parameter = $name;
    if ($parameter == '') {
        $parameter = $app['request']->get('searchString');
    }
    $ltWordTypes = new LtWords\LtWordTypes\LtWordTypes;
    $ltNouns = new LtWords\LtNouns\LtNouns($ltWordTypes);
    $nounDeclensions = $ltNouns->generateDeclensions($parameter);

    return $app['twig']->render('nouns.twig', array(
        'title' => 'LTNouns',
        'name' => $parameter,
        'result' => $nounDeclensions,
        'images' => $app['images'],
    ));
})
->value('name', '');

$app->get('/img/{name}', function ($name) use ($app) {
    $fullName = __DIR__ . '/img/' . $name;
    
    $app['monolog']->addDebug("Image name: $fullName");

    return $app->sendFile($fullName);
});

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.twig', array(
        'title' => $app['appName'],
    ));
});

$app->run();
