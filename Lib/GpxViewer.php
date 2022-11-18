<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\Entities\Specification\Gpx;
use Exception;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use WP_Post;

class GpxViewer
{
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

    public function renderWithPlugin(string $filePath): string
    {
        // $filePath = '/wp-content/uploads/gpx/non-classifiee/Cirkwi-Marche-en-Famenne_-_Circuit_VTT_Vert.gpx';
        //ko elevation
        //$filePath = 'https://visit.marche.be/output/ANX-01-09DV-003E.gpx';
        //cleaning elevation ok
        //$filePath = 'https://visit.marche.be/wp-content/uploads/gpx/non-classifiee/Cirkwi-Marche-en-Famenne_-_Circuit_VTT_Vert.gpx';

        $gpx = gpx_view(array(
                'src' => $filePath,
                'title' => 'Gpx',
                'color' => '#00ff00',
                'width' => '5',
                'distance_unit' => 'km',
                "height_unit" => "m",
                "step_min" => "10",
                "icon_url" => "https://visit.marche.be/wp-content/plugins/gpx-viewer/images/",
                'download_button' => true,
            )
        );

        return $gpx;
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