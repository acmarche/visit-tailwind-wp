<?php
namespace VisitMarche\ThemeTail;

use VisitMarche\ThemeTail\Lib\Mailer;

?>
    <div class="mt-6">
        <h1>Oops! An Error Occurred2222</h1>
        <h2>The server returned a "<?= $statusCode; ?> <?= $statusText; ?>".</h2>

        <p>
            Something is broken. Please let us know what you were doing when this error occurred.
            We will fix it as soon as possible. Sorry for any inconvenience caused.
        </p>
    </div>
    <?php
Mailer::sendError('error visit', "page x".$statusCode);
?>