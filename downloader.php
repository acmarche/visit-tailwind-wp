<?php
namespace VisitMarche\ThemeTail;

use phpGPX\phpGPX;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

//define('ABSPATH', dirname(__DIR__).'/../../');//local
define('ABSPATH', dirname(__DIR__).'/../../../');//ovh
define('PATHGPX', dirname(__DIR__).'/../../');//ovh

require_once ABSPATH.'vendor/autoload.php';

$codeCgt = $_GET['codecgt'];
$filePath = PATHGPX.'var/gpx/'.$codeCgt.'.'.phpGPX::XML_FORMAT;
var_dump($filePath);

if (is_readable($filePath)) {
    $response = new BinaryFileResponse($filePath);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT);

    return $response->send();
} else {
    echo 'File not found2';
}