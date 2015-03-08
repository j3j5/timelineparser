<?php

require_once dirname(__DIR__) . '/vendor/autoload.php'; // Autoload files using Composer autoload

use j3j5\TimelineParser;

$parser = new TimelineParser();

$parser->get_timeline();

return;
