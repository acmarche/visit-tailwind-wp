<?php

namespace VisitMarche\ThemeTail\Lib;

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

    public function renderWithPlugin(Gpx $gpx): void
    {
        $fileName = $gpx->codeCgt.'.'.phpGPX::XML_FORMAT;
        $filePath = ABSPATH.$this->folder_gpx.$fileName;
        if (!$this->writeTmpFile($filePath, $gpx->url)) {
            $this->elevation($filePath, $gpx);
        }
    }

    public function elevation(string $pathName, Gpx $gpx)
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
        $tab = $this->findElevations($locations);
        $missing = $tab['missing'];
        $locations = $tab['locations'];
        if (count($missing) > 0) {
            $this->requestElevations($missing);
            $tab = $this->findElevations($locations);
            $missing = $tab['missing'];
            $locations = $tab['locations'];
        }
        $a = 1;
        $distances = [0 => 0];
        $countLocations = count($locations);
        foreach ($locations as $location) {
            $b = $a + 1;
            if ($b == $countLocations) {
                break;
            }
            $distances[] = $this->vincentyGreatCircleDistance(
                $locations[$a]['latitude'],
                $locations[$a]['longitude'],
                $locations[$b]['latitude'],
                $locations[$b]['longitude']
            );
            $a++;
        }
        $tab['distances'] = $distances;

        $a = 0;
        $metres = [0 => $distances[$a]];
        $countDistances = count($distances);
        $a++;
        foreach ($distances as $distance) {
            $b = $a + 1;
            if ($b == $countDistances) {
                break;
            }
            $precedent = $a - 1;
            $cal = $metres[$precedent] + $distances[$b];
            $metres[$a] = $cal;
            $a++;
        }

        foreach ($metres as $key => $metre) {
            $metres[$key] = number_format(number_format($metre, 0, '.', '') / 1000, 2, '.', '');
        }

        $tab['metres'] = $metres;

        $gpx->data = $tab;
        $elevationOk = false;

        foreach ($fileGpx->tracks as $track) {
            // Statistics for whole track
            $stats = $track->stats;
            foreach ($track->segments as $segment) {
                // Statistics for segment of track
                foreach ($segment->getPoints() as $point) {
                    $elevationOk = $this->findSegment($point, $locations);
                }
            }
        }
        $fileGpx->metadata->description = htmlentities($fileGpx->metadata->description);
        if ($fileGpx->metadata->author) {
            $fileGpx->metadata->author->name = htmlentities($fileGpx->metadata->author->name);
        }
        if ($elevationOk) {
            $fileGpx->save($pathName, phpGPX::XML_FORMAT);
        }
    }

    private function findSegment(Point $point, array $elevations): bool
    {
        foreach ($elevations as $elevation) {
            if ($elevation['latitude'] === $point->latitude && $elevation['longitude'] == $point->longitude) {
                $point->elevation = $elevation['elevation'];

                return true;
            }
        }

        return false;
    }

    public function requestElevations(array $locations): array
    {
        $tmps = [];
        foreach ($locations as $location) {
            $tmps[] = [$location['latitude'], $location['longitude']];
            if (count($tmps) > 60) {
                $tmp = $this->launchRequest($tmps);
                if (count($tmp) > 0) {
                    $results[] = $tmp;
                }
                $tmps = [];
            }
        }
        $tmp = $this->launchRequest($tmps);
        if (count($tmp) > 0) {
            $results[] = $tmp;
        }

        global $wpdb;
        foreach ($results as $result) {
            foreach ($result as $elevation) {
                $wpdb->insert('pivot_elevation', array(
                    'latitude' => $elevation['latitude'],
                    'longitude' => $elevation['longitude'],
                    'elevation' => $elevation['elevation'],
                ));
            }
        }

        return $results;
    }

    private function launchRequest(array $coordinates): array
    {
        $urlBase = "https://api.opentopodata.org/v1/eudem25m";
        $headers = [
            'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
        ];
        $httpClient = HttpClient::createForBaseUri($urlBase, $headers);
        $query = '';
        foreach ($coordinates as $coordinate) {
            $query .= implode(',', $coordinate).'|';
        }
        $query = substr($query, 0, -1);
        $results = [];
        try {
            $response = $httpClient->request(
                'POST',
                $urlBase, [
                    'json' => [
                        'locations' => $query,
                        'interpolation' => 'bilinear',
                    ],
                    'timeout' => 2.5,
                ]
            );

            $responseString = $response->getContent();

            try {
                if ($result = json_decode($responseString)) {
                    if ($result->status == 'OK') {
                        foreach ($result->results as $result) {
                            $results[] = [
                                'latitude' => $result->location->lat,
                                'longitude' => $result->location->lng,
                                'elevation' => $result->elevation,
                            ];
                        }
                    }
                }
            } catch (Exception $exception) {
                Mailer::sendError('elevation', 'el '.$exception->getMessage());
                dump($exception->getMessage());
            }
        } catch (ClientException|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $exception) {
            Mailer::sendError('elevation', 'el '.$exception->getMessage());
            dump($exception->getMessage());
        }

        return $results;
    }

    private function findElevations(array $locations): array
    {
        global $wpdb;
        $missing = [];
        foreach ($locations as $key => $location) {
            if ($result = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM `pivot_elevation` WHERE `latitude`=%s AND `longitude`=%s",
                    $location['latitude'],
                    $location['longitude']
                )
            )) {
                $location['elevation'] = (float)$result->elevation;
                $locations[$key] = $location;
            } else {
                $missing[] = $location;
            }
        }

        return ['locations' => $locations, 'missing' => $missing];
    }

    public function writeTmpFile(string $filePath, string $url): bool
    {
        if (is_readable($filePath)) {
            return false;
        }
        $httpClient = HttpClient::create();
        try {
            $response = $httpClient->request(
                'GET',
                $url,
            );
            $data_raw = $response->getContent();
        } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
            Mailer::sendError("Visit error get gpx", $e->getMessage());

            return false;
        }

        try {
            $filesystem = new Filesystem();
            $filesystem->dumpFile($filePath, $data_raw);
        } catch (IOExceptionInterface $exception) {
            $error = "An error occurred while creating your directory at ".$exception->getPath();
            Mailer::sendError("Visit error write gpx", $error);
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

    /**
     * Calculates the great-circle distance between two points, with
     * the Vincenty formula.
     * @param float $latitudeFrom Latitude of start point in [deg decimal]
     * @param float $longitudeFrom Longitude of start point in [deg decimal]
     * @param float $latitudeTo Latitude of target point in [deg decimal]
     * @param float $longitudeTo Longitude of target point in [deg decimal]
     * @param int $earthRadius Mean earth radius in [m]
     * @return float|int Distance between points in [m] (same as earthRadius)
     */
    private function vincentyGreatCircleDistance(
        float $latitudeFrom,
        float $longitudeFrom,
        float $latitudeTo,
        float $longitudeTo,
        int $earthRadius = 6371000
    ): float|int {
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

}