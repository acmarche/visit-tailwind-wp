<?php

namespace VisitMarche\ThemeTail;

//use Symfony\Component\ErrorHandler\Debug;
use VisitMarche\ThemeTail\Lib\Mailer;
use VisitMarche\ThemeTail\Lib\RouterPivot;
use VisitMarche\ThemeTail\Lib\Twig;

//$statusCode;  $statusText;
//Debug::enable();

//get_header();
Twig::rend500Page();
get_footer();

try {
    Mailer::sendError('error visit', "page ".RouterPivot::getCurrentUrl());

} catch (\Exception $exception) {

}