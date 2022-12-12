<?php

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\ArrayLoader;

require(__DIR__ . '/vendor/autoload.php');

$locale = 'zh_CN';

$translator = new Translator($locale);

if (file_exists(__DIR__ . '/languages/' . $locale . '.php')) {
    $translator->addLoader('array', new ArrayLoader());
    $translator->addResource(
        'array',
        require_once(__DIR__ . '/languages/' . $locale . '.php'),
        $locale
    );
}




echo $translator->trans('Hello World!'); // outputs « Bonjour ! »
