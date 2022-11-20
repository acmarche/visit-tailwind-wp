<?php

namespace VisitMarche\ThemeTail;

use VisitMarche\ThemeTail\Lib\Mailer;
use VisitMarche\ThemeTail\Lib\RouterPivot;
use VisitMarche\ThemeTail\Lib\Twig;

//$statusCode;  $statusText;
Twig::rendPage(
    '@VisitTail/errors/500.html.twig',
    [
        'excerpt' => null,
        'image' => get_template_directory_uri().'/assets/images/error500.jpg',
        'urlBack' => '/',
        'categoryName' => 'Accueil',
        'nameBack' => 'Acceuil',
    ]
);

get_footer();
Mailer::sendError('error visit', "page ".RouterPivot::getCurrentUrl());
?>