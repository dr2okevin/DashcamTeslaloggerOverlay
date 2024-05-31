<?php

include 'videoConfig.php';

//const VIDEO_FOLDER = 'video/2024-05-13';
const TIME_OFFSET = 2 * 60 * 60;

$videoTimes = new VideoTimes();
$videos = $videoTimes->getVideoFiles($GLOBALS['config']['videoFolder']);

foreach ($videos as $video) {
    $videoTimes->convertGPXTimeToUTCPlus2($GLOBALS['config']['videoFolder'] . '/' . $video);
}

class VideoTimes
{

    /**
     * @param string $folder
     * @return array
     */
    public function getVideoFiles(string $folder)
    {
        $files = scandir($folder);
        $fileExtensions = $this->getFileExtensions();
        foreach ($files as $filekey => $file) {
            foreach ($fileExtensions as $fileExtension) {
                if (substr($file, -strlen($fileExtension)) !== $fileExtension) {
                    unset($files[$filekey]);
                }
            }
        }
        return $files;
    }

    /**
     * @return array
     */
    private function getFileExtensions()
    {
        return ['mp4'];
    }

    public function convertGPXTimeToUTCPlus2($videoPath)
    {
        $gpxPath = $videoPath . ".nmea.gpx";

        echo "Zum video " . $videoPath . " gehört " . $gpxPath . "\n";

        // Lade den Inhalt der GPX-Datei und dekodiere sie
        $gpxContent = simplexml_load_file($gpxPath);

        // Erstelle ein Array zur Speicherung der Zeitstempel und GPS-Positionen aus der GPX-Datei

        $timestamps = array();
        $firstTrackpointTime = null;
        /*
        foreach ($gpxContent->trk->trkseg->trkpt as $trackpoint) {
            $timestamp = (string)$trackpoint->time; // Extrahiere den Zeitstempel als String

            // Füge den Zeitstempel und die GPS-Position in das Array hinzu
            $timestamps[] = $timestamp;
        }*/
//        if($gpxContent->trk->trkseg->trkpt[0]) {
//            $firstTrackpointTime = $gpxContent->trk->trkseg->trkpt[0]->time;
//        }
//        echo $firstTrackpointTime;
        if($firstTrackpointTime) {
            $firstTrackpointTime = strtotime($firstTrackpointTime) + TIME_OFFSET;
        } else {
            //startzeit vom Dateinamen ableiten
            $name = pathinfo($videoPath,  PATHINFO_FILENAME);
            $re = '/^(20\d\d)(\d\d)(\d\d)_(\d\d)(\d\d)(\d\d)_(NF|PF|EF|NR|PR|ER)/m';
            preg_match($re, $name, $matches);
            $date = new DateTime();
            $date->setDate($matches[1],$matches[2],$matches[3]);
            $date->setTime($matches[4],$matches[5],$matches[6]);
            $firstTrackpointTime = $date->getTimestamp();
        }

        // Öffne das Video-Datei im binären Modus
        //$videoFile = fopen($videoPath, 'rb');

        // Erhalte die Gesamtdauer des Videos in Sekunden
        $videoDuration = intval(exec("ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 $videoPath"));
        $videoFrames = intval(exec("ffprobe -v error -select_streams v:0 -count_packets -show_entries stream=nb_read_packets -of csv=p=0 $videoPath"));
        $framerate = exec("ffprobe -v error -select_streams v -of default=noprint_wrappers=1:nokey=1 -show_entries stream=r_frame_rate $videoPath");

        if (strpos($framerate, '/') !== false) {
            // Wenn es einen Schrägstrich enthält, zerlege den Bruch in Zähler und Nenner
            list($numerator, $denominator) = explode('/', $framerate);
            // Berechne die Framerate als Gleitkommazahl
            $framerate = $numerator / $denominator;
        } else {
            // Wenn es keine Bruch ist, konvertiere es direkt in eine ganze Zahl
            $framerate = intval($framerate);
            if($framerate > 1000){
                $framerate = $framerate / 1000;
            }
        }

        echo "Duration: " . $videoDuration . PHP_EOL;
        echo "Frames: " . $videoFrames . PHP_EOL;
        echo "Frame Rate: " . $framerate . PHP_EOL;

        // Bestimme den Dateinamen für die Ausgabedatei
        $outputFileName = $GLOBALS['config']['videoFolder'] . "/" . pathinfo($videoPath, PATHINFO_FILENAME) . ".times.csv";

        // Öffne die Ausgabedatei im Schreibmodus
        $outputFile = fopen($outputFileName, 'w');

        // Schleife über jedes frame im Video
        for ($frame = 1; $frame <= $videoFrames; $frame++) {
            $timeOffset = floor($frame / $framerate);
            $frameTime = $firstTrackpointTime + $timeOffset;
            $frameTime = new DateTime("@" . $frameTime);
            $frameTime = $frameTime->format("Y-m-d H:i:s");
            //$frameTime = gmdate("Y-m-d\TH:i:s", $frameTime); // Konvertiere die Sekunde in das Format des GPX-Zeitstempels

            $output = (string)$frame . ',' . $frameTime . PHP_EOL;
            fwrite($outputFile, $output);

        }

        // Schließe die Video- und Ausgabedateien
        //fclose($videoFile);
        fclose($outputFile);
    }
}
