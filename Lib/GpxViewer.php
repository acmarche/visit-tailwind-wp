<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\Entities\Offre\Offre;
use AcMarche\Pivot\Entities\Specification\Gpx;
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
use WP_Post;

class GpxViewer
{
    public string $folder_gpx = 'var/gpx/';

    public function renderWithPlugin(Offre $offre, Gpx $gpx): string
    {
        $fileName = $gpx->codeCgt.'.'.phpGPX::XML_FORMAT;
        $filePath = ABSPATH.$this->folder_gpx.$fileName;
        if (!$this->writeTmpFile($filePath, $gpx->url)) {
            $this->elevation($filePath);
        }
        $urlLocal = '/'.$this->folder_gpx.$fileName;
        $options = [
            'src' => $urlLocal,
            'name' => 'Gpx',
            'color' => '#fd8383',
            'width' => '3',
            'distance_unit' => 'km',
            "height_unit" => "m",
            "step_min" => "300",
            "icon_url" => RouterPivot::getUrlSite()."/wp-content/plugins/gpx-viewer/images/",
            'download_button' => true,
        ];

        $gpx = gpx_view($options);

        return $gpx;
    }

    public function elevation(string $pathName)
    {
        $phpGPX = new phpGPX();
        $fileGpx = $phpGPX->load($pathName);
        $locations = [];
        foreach ($fileGpx->tracks as $track) {
            // Statistics for whole track
            $stats = $track->stats;
            foreach ($track->segments as $segment) {
                // Statistics for segment of track
                foreach ($segment->getPoints() as $point) {
                    $locations[] = ['latitude' => $point->latitude, 'longitude' => $point->longitude];
                }
            }
        }
        $locations = $this->requestElevations($locations);
        echo json_encode($locations);
        dd($locations);
        $elevations = json_decode($elevationsString);
        $elevationOk = false;

        foreach ($fileGpx->tracks as $track) {
            // Statistics for whole track
            $stats = $track->stats;
            foreach ($track->segments as $segment) {
                // Statistics for segment of track
                foreach ($segment->getPoints() as $point) {
                    $elevationOk = $this->findSegment($point, $elevations->results);
                }
            }
        }
        $fileGpx->metadata->description = htmlentities($fileGpx->metadata->description);
        $fileGpx->metadata->author->name = htmlentities($fileGpx->metadata->author->name);

        if ($elevationOk) {
            $fileGpx->save($pathName, phpGPX::XML_FORMAT);
        }
    }

    private function isXmlValid(string $value): bool
    {
        $prev = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($value);
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            dump($error);
        }
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        return false !== $doc && empty($errors);
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

    public function requestElevations(array $locations): array
    {
        $urlBase = 'https://api.opentopodata.org/v1/test-dataset';
        $headers = [
            'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
        ];
        $httpClient = HttpClient::createForBaseUri($urlBase, $headers);

        foreach ($locations as $key => $location) {
            try {
                $elevationsString = $this->requestElevation($httpClient, $urlBase, $location);
                if ($result = json_decode($elevationsString)) {
                    if ($result->status == 'OK') {
                        $location['elevation'] = $result->results[0]->elevation;
                    }
                } else {
                    $location['elevation'] = 0;
                }
            } catch (Exception $e) {
                dump($e);
                $location['elevation'] = 0;
            }
            $locations[$key] = $location;
        }

        return $locations;
    }

    /**
     * @throws Exception
     */
    public function requestElevation($httpClient, $urlBase, array $location): string
    {
        try {
            $response = $httpClient->request(
                'GET',
                $urlBase, [
                    'query' => [
                        'locations' => $location['latitude'].','.$location['longitude'],
                    ],
                    'timeout' => 2.5,
                ]
            );

            return $response->getContent();
        } catch (ClientException|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $exception) {
            Mailer::sendError('elevation', 'el '.$exception->getMessage());
            throw  new Exception($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Calculates the great-circle distance between two points, with
     * the Vincenty formula.
     * @param float $latitudeFrom Latitude of start point in [deg decimal]
     * @param float $longitudeFrom Longitude of start point in [deg decimal]
     * @param float $latitudeTo Latitude of target point in [deg decimal]
     * @param float $longitudeTo Longitude of target point in [deg decimal]
     * @param float $earthRadius Mean earth radius in [m]
     * @return float Distance between points in [m] (same as earthRadius)
     */
    private function vincentyGreatCircleDistance(
        $latitudeFrom,
        $longitudeFrom,
        $latitudeTo,
        $longitudeTo,
        $earthRadius = 6371000
    ) {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $lonDelta = $lonTo - $lonFrom;
        $a = pow(cos($latTo) * sin($lonDelta), 2) +
            pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);

        return $angle * $earthRadius;
    }

    public function requestElevationBroken(array $locations): string
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
                    'timeout' => 2.5,
                ]
            );

            return $response->getContent();
        } catch (ClientException|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $exception) {
            Mailer::sendError('elevation', 'el '.$exception->getMessage());
            throw  new Exception($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function writeTmpFile(string $filePath, string $url): bool
    {
        if (is_readable($filePath)) {
            return false;
        }
        try {
            $filesystem = new Filesystem();
            $filesystem->dumpFile($filePath, file_get_contents($url));
        } catch (IOExceptionInterface $exception) {
            echo "An error occurred while creating your directory at ".$exception->getPath();
        }

        return false;
    }

    public function render(array $args): string
    {
        $fileName = $args['file'] ?? null;
        $fileName2 = $args['file2'] ?? null;

        if (!$fileName) {
            return '<p>Nom de fichier manquant syntax = [gpx_viewer file=VTTBleu]</p>';
        }

        try {
            $attachment = $this->getFile($fileName);
            $file = $attachment->guid;
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $file2 = null;
        if ($fileName2) {
            try {
                $attachment2 = $this->getFile($fileName2);
                $file2 = $attachment2->guid;
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }

        $twig = Twig::LoadTwig();
        $post = get_post();
        $title = $post ? $post->post_title : '';

        return $twig->render(
            '@VisitTail/map/_gpx_viewer.html.twig',
            [
                'title' => $title,
                'latitude' => 50.2268,
                'longitude' => 5.3442,
                'file' => $file,
                'file2' => $file2,
            ]
        );

    }

    /**
     * @throws Exception
     */
    private function getFile(string $fileName): WP_Post
    {
        $args = [
            'post_type' => 'attachment',
            'name' => trim($fileName),
        ];

        $attachments = get_posts($args);
        if (!$attachments || (is_countable($attachments) ? \count($attachments) : 0) === 0) {
            throw new Exception('<p>Gpx  non trouvé : '.$fileName.'</p>');
        }

        return $attachments[0];
    }
}