<?php

namespace VisitMarche\ThemeTail;

use VisitMarche\ThemeTail\Inc\Menu;
use VisitMarche\ThemeTail\Lib\Twig;

$menu = new Menu();
$items = $menu->getMenuTop();
$icones = $menu->getIcones();
wp_footer();
Twig::rendPage(
    '@VisitTail/_footer.html.twig',
    [
        'items' => $items,
        'icones' => $icones,
    ]
);
echo '
</body>
</html>';