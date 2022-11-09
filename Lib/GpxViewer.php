<?php

namespace VisitMarche\ThemeTail\Lib;

use AcMarche\Pivot\Entities\Specification\Gpx;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

class GpxViewer
{
    public function render(Gpx $gpx): string
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
}