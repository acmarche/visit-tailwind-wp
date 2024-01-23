<?php

namespace VisitMarche\ThemeTail;

use VisitMarche\ThemeTail\Lib\Twig;

get_header();
Twig::rend404Page();
get_footer();