<?php

require __DIR__.'/vendor/autoload.php';

function dumpCharsArray($array) {
    $out = [];
    $dump = $array;
    array_walk($dump, function($a, $b) use (&$out){
        $out[] = sprintf('%s [%s] (%d)', chr($b), $b, $a);
    });
    dump(implode(', ', $out));
}

use App\Command\LetterPlay;
use Symfony\Component\Console\Application;

$application = new Application('echo', '1.0.0');
$command = new LetterPlay();

$application->add($command);

$application->setDefaultCommand($command->getName(), true);
$application->run();

