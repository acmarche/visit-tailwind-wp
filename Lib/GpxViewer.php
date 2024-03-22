<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\Entities\Specification\Gpx;
use Exception;
use phpGPX\Models\Point;
use phpGPX\phpGPX;
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
        if (!is_readable($filePath)) {
            if ($gpx->data_raw) {
                $this->elevation($filePath, $gpx);
            }
        }
    }

    public function elevation(string $filePath, Gpx $gpx)
    {
        $fileGpx = phpGPX::parse($gpx->data_raw);
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
        if (count($tab['missing']) > 0) {
            $this->requestElevations($tab['missing']);
            $tab = $this->findElevations($locations);
        }

        $firstItem = 1;
        $distances = [0 => 0];
        $locationsWithElevations = $tab['locations'];
        $countLocations = count($locationsWithElevations);
        foreach ($locationsWithElevations as $location) {
            $nextItem = $firstItem + 1;
            if ($nextItem == $countLocations) {
                break;
            }
            $distances[] = $this->vincentyGreatCircleDistance(
                $locations[$firstItem]['latitude'],
                $locations[$firstItem]['longitude'],
                $locations[$nextItem]['latitude'],
                $locations[$nextItem]['longitude']
            );
            $firstItem++;
        }
        $tab['distances'] = $distances;

        $firstItem = 0;
        $metres = [0 => $distances[$firstItem]];
        $countDistances = count($distances);
        $firstItem++;
        foreach ($distances as $distance) {
            $nextItem = $firstItem + 1;
            if ($nextItem == $countDistances) {
                break;
            }
            $precedent = $firstItem - 1;
            $cal = $metres[$precedent] + $distances[$nextItem];
            $metres[$firstItem] = $cal;
            $firstItem++;
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
        if ($fileGpx->metadata) {
            $fileGpx->metadata->description = htmlentities($fileGpx->metadata->description);
            if ($fileGpx->metadata->author) {
                $fileGpx->metadata->author->name = htmlentities($fileGpx->metadata->author->name);
            }
        }
        if ($elevationOk) {
            try {
                $fileGpx->save($filePath, phpGPX::XML_FORMAT);
            } catch (Exception $exception) {
                Mailer::sendError('save gpx file', 'el '.$exception->getMessage());
            }
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