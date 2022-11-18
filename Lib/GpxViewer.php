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
    private string $folder_gpx = 'var/gpx/';

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

    public function renderByGpx(Gpx $gpx): string
    {
        $twig = Twig::LoadTwig();
        $post = get_post();
        $title = $post ? $post->post_title : '';

        try {
            $this->writeTmpFile($gpx);
        } catch (\Exception $exception) {

        }

        return $twig->render(
            '@VisitTail/map/_gpx_viewer.html.twig',
            [
                'title' => $title,
                'latitude' => 50.2268,
                'longitude' => 5.3442,
                'file' => 'https://visitmarche.be/var/'.$gpx->code.'.xml',
                //'file' => $gpx->url,
                'file2' => null,
            ]
        );
    }

    public function renderWithPlugin(string $codeCgt, string $url): string
    {
        $fileName = $codeCgt.'xml';
        $filePath = ABSPATH.$this->folder_gpx.$fileName;
        if (!$this->writeTmpFile2($filePath, $url)) {
            $this->elevation($filePath);
        }
        $urlLocal = RouterPivot::getUrlSite().'/'.$this->folder_gpx.$fileName;
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

        $this->httpClient = HttpClient::create($headers);
        $data = json_encode(['locations' => $locations]);

        try {
            $response = $this->httpClient->request(
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

    public function writeTmpFile2(string $filePath, string $url): bool
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

    public function writeTmpFile(Gpx $gpx): string
    {
        try {
            $filesystem = new Filesystem();
            //$filesystem->dumpFile(sys_get_temp_dir().'/'.'file.gpx', file_get_contents($gpx->url));
            $filesystem->dumpFile('/homez.1029/visitmp/www/var/'.$gpx->code.'.xml', file_get_contents($gpx->url));
        } catch (IOExceptionInterface $exception) {
            echo "An error occurred while creating your directory at ".$exception->getPath();
        }

        return sys_get_temp_dir().'/'.'file.gpx';
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