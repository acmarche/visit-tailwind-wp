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
            'color' => '#00ff00',
            'width' => '5',
            'distance_unit' => 'km',
            "height_unit" => "m",
            "step_min" => "10",
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
        $elevationsString = $this->requestElevation($locations);
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

            return $response->getContent();
        } catch (ClientException|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $exception) {
            Mailer::sendError('elevation', 'el '.$exception->getMessage());
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