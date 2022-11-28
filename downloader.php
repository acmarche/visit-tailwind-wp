<?php
namespace VisitMarche\ThemeTail;

    echo 'File not found';

use phpGPX\phpGPX;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

define('ABSPATH', dirname(__DIR__).'/../../');
var_dump(ABSPATH);
return;
dd(ABSPATH);
require_once ABSPATH.'vendor/autoload.php';

Debug::enable();
$codeCgt = $_GET['codecgt'];
$filePath = ABSPATH.'var/gpx/'.$codeCgt.'.'.phpGPX::XML_FORMAT;
if (is_readable($filePath)) {
    $response = new BinaryFileResponse($filePath);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

    return $response->send();
} else {
    echo 'File not found';
}