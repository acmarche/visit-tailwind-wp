<?php

namespace VisitMarche\ThemeTail;

use VisitMarche\ThemeTail\Lib\Mailer;
use VisitMarche\ThemeTail\Lib\RouterPivot;
use VisitMarche\ThemeTail\Lib\Twig;

//$statusCode;  $statusText;
Twig::rend500Page();

get_footer();

try {
    Mailer::sendError('error visit', "page ".RouterPivot::getCurrentUrl().' '.$_SERVER['HTTP_REFERER']);

} catch (\Exception $exception) {

}

