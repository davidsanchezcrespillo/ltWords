<?php

use Symfony\Component\Yaml\Parser;

$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

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

$yamlParser = new Parser();
// Get the list of current languages from the config file
$conf = $yamlParser->parse(file_get_contents(__DIR__ . '/config.yml'));
$app['conf'] = $conf;

//$app['monolog']->addDebug("Languages: $languages");

// Language change
$app->get('/lang/{lang}', function ($lang) use ($app) {
    // check if language exists
    if (is_dir(__DIR__ . '/locale/' . $lang)) {
        // save user selection in session
        $app['session']->set('current_language', $lang);
    }
    return $app->redirect($_SERVER['HTTP_REFERER']);
});

// LT Numbers page
$app->get('/numbers/{name}', function ($name) use ($app) {
    $parameter = $name;
    if ($parameter == '') {
        $parameter = $app['request']->get('searchString');
    }
    $nameStr = $parameter;
    
    $format = $app['request']->get('format');
    
    if (is_numeric($nameStr)) {
        $ltNumbers = new LtWords\LtNumbers\LtNumbers;
        $nameStr = $ltNumbers->numberToText($parameter);
    }

    if ($format == 'json') {
        return $app->json(array('result' => $nameStr));
    }

    return $app['twig']->render('numbers.twig', array(
        'title' => 'LTNumbers',
        'name' => $parameter,
        'result' => $nameStr,
        'conf' => $app['conf'],
    ));
})
->value('name', '');

// LT Numbers game page
$app->get('/game-numbers', function () use ($app) {
    $randomNumber = rand(0, 1000);

    $name = $app['request']->get('searchString');
    $number = $app['request']->get('questionNumber');
    $nameLower = mb_strtolower($name, 'UTF-8');

    $ltNumbers = new LtWords\LtNumbers\LtNumbers;
    $rightReply = $ltNumbers->numberToText($number);

    // Get the collated versions of both replies
    $ltWordTypes = new LtWords\LtWordTypes\LtWordTypes;
    $collatedRightReply = $ltWordTypes->collateWord($rightReply);
    $collatedName = $ltWordTypes->collateWord($nameLower);

    // Retrieve the session information
    $currentNumQuestions = $app['session']->get('current_numquestions');
    if ($currentNumQuestions === null) {
        $currentNumQuestions = 0;
    }
    $currentScore = $app['session']->get('current_score');
    if ($currentScore === null) {
        $currentScore = 0;
    }

    // Check the reply against the right result
    $result = '';
    if ($name != '') {
        $currentNumQuestions++;
        if (($collatedRightReply == $collatedName) || ($rightReply == $nameLower)) {
            $result = 'OK';
            $currentScore++;
        } else {
            $result = 'NoOK';
        }
    }

    // Update the number of questions and current score
    $app['session']->set('current_numquestions', $currentNumQuestions);
    $app['session']->set('current_score', $currentScore);

    // Return the results
    return $app['twig']->render('game-numbers.twig', array(
        'title' => 'Game-LTNumbers',
        'previousNumber' => $number,
        'result' => $result,
        'rightReply' => $rightReply,
        'questionNumber' => $randomNumber,
        'currentNumQuestions' => $currentNumQuestions,
        'currentScore' => $currentScore,
        'conf' => $app['conf'],
    ));
});

// LT Nouns page
$app->get('/nouns/{name}', function ($name) use ($app) {
    $parameter = $name;
    if ($parameter == '') {
        $parameter = $app['request']->get('searchString');
    }

    $format = $app['request']->get('format');

    $ltWordTypes = new LtWords\LtWordTypes\LtWordTypes;
    $ltNouns = new LtWords\LtNouns\LtNouns($ltWordTypes);
    $nounDeclensions = $ltNouns->generateDeclensions($parameter);

    if ($format == 'json') {
        return $app->json($nounDeclensions);
    }

    return $app['twig']->render('nouns.twig', array(
        'title' => 'LTNouns',
        'name' => $parameter,
        'result' => $nounDeclensions,
        'conf' => $app['conf'],
    ));
})
->value('name', '');

// Noun suggestions (AJAX service)
$app->get('/suggestnouns', function () use ($app) {
    $prefix = $app['request']->get('query');

    $ltWordTypes = new LtWords\LtWordTypes\LtWordTypes;
    $ltNouns = new LtWords\LtNouns\LtNouns($ltWordTypes);
    
    $suggestions = $ltNouns->suggestNoun($prefix);
    
    return $app->json($suggestions);
});

// Index page
$app->get('/index', function () use ($app) {
    return $app['twig']->render('index.twig', array(
        'title' => 'LTWords',
        'conf' => $app['conf'],
        'introduction' => 'Introduction',
        'contact' => 'Contact'
    ));
});

$app->run();
