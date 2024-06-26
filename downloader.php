<?php

namespace VisitMarche\ThemeTail;

use phpGPX\phpGPX;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

define('ABSPATH', dirname(__DIR__).'/../../');

require_once ABSPATH.'vendor/autoload.php';

$codeCgt = $_GET['codecgt'];
$filePath = ABSPATH.'var/gpx/'.$codeCgt.'.'.phpGPX::XML_FORMAT;
$fileName = $codeCgt.'-file.gpx';

if (is_readable($filePath)) {
    $response = new BinaryFileResponse($filePath);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName);

    return $response->send();
} else {
    echo 'File not found';
}