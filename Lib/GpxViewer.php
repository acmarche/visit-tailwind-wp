<?php

namespace VisitMarche\ThemeTail\Lib;

use Exception;
use phpGPX\Models\Point;
use phpGPX\phpGPX;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class GpxViewer
{
    private string $folder_gpx = 'var/gpx/';

    public function renderWithPlugin(string $codeCgt, string $url): string
    {
        $fileName = $codeCgt.'xml';
        $filePath = ABSPATH.$this->folder_gpx.$fileName;
        if (!$this->writeTmpFile($filePath, $url)) {
            $this->elevation($filePath);
        }
        $urlLocal = '/'.$this->folder_gpx.$fileName;
        $gpx = gpx_view(array(
                'src' => $urlLocal,
                'title' => 'Gpx',
                'color' => '#00ff00',
                'width' => '5',
                'distance_unit' => 'km',
                "height_unit" => "m",
                "step_min" => "10",
                "icon_url" => RouterPivot::getUrlSite()."/wp-content/plugins/gpx-viewer/images/",
                'download_button' => true,
            )
        );

        return $gpx;
    }

    public function elevation(string $pathName)
    {
        $gpx = new phpGPX();
        $file = $gpx->load($pathName);
        $locations = [];
        foreach ($file->tracks as $track) {
            // Statistics for whole track
            $stats = $track->stats;
            foreach ($track->segments as $segment) {
                // Statistics for segment of track
                foreach ($segment->getPoints() as $point) {
                    $locations[] = ['latitude' => $point->latitude, 'longitude' => $point->longitude];
                }
            }
        }
        $elevationsString = $this->requestElevation($locations);
        $elevations = json_decode($elevationsString);

        foreach ($file->tracks as $track) {
            // Statistics for whole track
            $stats = $track->stats;
            foreach ($track->segments as $segment) {
                // Statistics for segment of track
                foreach ($segment->getPoints() as $point) {
                    if (!$this->findSegment($point, $elevations->results)) {
                        dd($point);
                    }
                }
            }
        }
        $file->save($pathName, phpGPX::XML_FORMAT);
    }

    private function findSegment(Point $point, array $elevations): bool
    {
        foreach ($elevations as $elevation) {
            if ($elevation->latitude === $point->latitude && $elevation->longitude == $point->longitude) {
                $point->elevation = $elevation->elevation;

                return true;
            }
        }

        return false;
    }

    public function requestElevation(array $locations): string
    {
        $url = 'https://api.open-elevation.com/api/v1/lookup';
        $headers = [
            'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
        ];

        $httpClient = HttpClient::create($headers);
        $data = json_encode(['locations' => $locations]);

        try {
            $response = $httpClient->request(
                'POST',
                $url, [
                    'body' => $data,
                ]
            );

            $data_raw = $response->getContent();

            return $data_raw;
        } catch (ClientException|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $exception) {
            throw  new Exception($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function writeTmpFile(string $filePath, string $url): bool
    {
        if (is_readable($filePath)) {
            return true;
        }
        try {
            $filesystem = new Filesystem();
            $filesystem->dumpFile($filePath, file_get_contents($url));
        } catch (IOExceptionInterface $exception) {
            echo "An error occurred while creating your directory at ".$exception->getPath();
        }

        return false;
    }
}