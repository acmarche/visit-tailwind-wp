<?php

namespace VisitMarche\ThemeTail;

use VisitMarche\ThemeTail\Lib\Mailer;
use VisitMarche\ThemeTail\Lib\RouterPivot;
use VisitMarche\ThemeTail\Lib\Twig;

/**
 * This page is called by symfony @file  functions.php
 */
//$statusCode;  $statusText;
//get_header();

Twig::rend500Page(RouterPivot::getCurrentUrl());
get_footer();

try {
    Mailer::sendError('error visit', "page ".RouterPivot::getCurrentUrl());

} catch (\Exception $exception) {

}