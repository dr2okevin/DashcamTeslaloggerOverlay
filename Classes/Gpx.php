<?php
/**
 * Created by PhpStorm.
 * User: Kevin Quiatkowski
 * Date: 30.03.2018
 * Time: 18:09
 */

class Gpx
{
    const GPS_BABEL = 'gpsbabel'; # C:\Programme\GPSBabel\gpsbabel.exe

    public function convert(string $folder)
    {
        $files = $this->getFiles($folder);
        foreach ($files as $file) {
            $this->convertFile($folder . '/' . $file, $folder . '/' . $file . '.gpx');
        }
    }

    /**
     * @param string $folder
     * @return string[]
     */
    private function getFiles(string $folder)
    {
        $files = scandir($folder);
        $fileExtensions = ['nmea'];
        foreach ($files as $filekey => $file) {
            foreach ($fileExtensions as $fileExtension) {
                if (substr($file, -strlen($fileExtension)) !== $fileExtension) {
                    unset($files[$filekey]);
                }
            }
        }
        return $files;
    }

    private function convertFile(string $input, string $output)
    {
        $command = self::GPS_BABEL . ' -i nmea -f "' . $input . '" -o gpx,gpxver=1.1 -F "' . $output . '"';
        echo $command."\n";
        exec($command);
    }
}
