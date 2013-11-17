<?php

require_once './vendor/autoload.php';
require_once './command/FXParser.php';
require_once './classes/Db.php';

use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new FXParser());
$application->run();