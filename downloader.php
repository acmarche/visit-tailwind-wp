<?php

namespace VisitMarche\ThemeTail;

use phpGPX\phpGPX;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
Debug::enable();

define('ABSPATH', dirname(__DIR__).'/../../');
dd(ABSPATH);
require_once ABSPATH.'vendor/autoload.php';

$codeCgt = $_GET['codecgt'];
$filePath = ABSPATH.'var/gpx/'.$codeCgt.'.'.phpGPX::XML_FORMAT;
if (is_readable($filePath)) {
    $response = new BinaryFileResponse($filePath);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

    return $response->send();
} else {
    echo 'File not found';
}