<?php
/**
 * Created by PhpStorm.
 * User: Kevin Quiatkowski
 * Date: 30.03.2018
 * Time: 18:08
 */

class Video
{
    protected $folder = '';
    protected $offsetStart = 0;
    protected $offsetEnd = 0;
    #protected $ffmpegPath = 'C:\Programme\ffmpeg\bin\ffmpeg.exe';
    protected $ffmpegPath = 'ffmpeg';

    /**
     * Video constructor.
     * @param string $folder Folder
     * @param int $offsetStart Offset Start
     * @param int $offsetEnd Offset End
     */
    public function __construct(string $folder, int $offsetStart, int $offsetEnd)
    {
        $this->folder = $folder;
        $this->offsetStart = $offsetStart;
        $this->offsetEnd = $offsetEnd;
    }

    /**
     * @param string $folder
     * @return array
     */
    public function getGpsDataByFolder(string $folder = ''): array
    {
        if ($folder == '') {
            $folder = $this->folder;
        }
        $files = $this->getVideoFiles($folder);
        $gpsData = array();
        foreach ($files as $file) {
            $gpsData[$file] = $this->getGpsDataByFile($file);
        }
        return $gpsData;
    }

    /**
     * @param string $folder
     */
    public function mergeVideos(string $folder = '')
    {
        if ($folder == '') {
            $folder = $this->folder;
        }
        $this->generateFileList($folder);

        $command = $this->ffmpegPath . " -f concat -safe 0 -i " . $folder . DIRECTORY_SEPARATOR . "filelist.txt -c copy " . $folder . DIRECTORY_SEPARATOR . "all.mp4";
        echo $command;
        exec($command);
    }

    /**
     * @param string $folder
     * @return void
     */
    private function generateFileList(string $folder = '')
    {
        if ($folder == '') {
            $folder = $this->folder;
        }
        $files = $this->getVideoFiles($folder);
        $fileList = '';
        foreach ($files as $file) {
            $fileList .= 'file \''.realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $file . "'\n";
        }
        $handler = fopen($folder.'/filelist.txt', 'w');
        fwrite($handler, $fileList);
        fclose($handler);
    }

    /**
     * @param string $file
     * @return string
     */
    private function getGpsDataByFile(string $file)
    {
        $gpsFile = explode('.', $file);
        $gpsFile = substr($gpsFile[0], 0, -1) . ".gps";
        echo $this->folder . '/' . $gpsFile . "\n";
        if (file_exists($this->folder . '/' . $gpsFile)){
            return $this->readGpsDataFromGpsFile($gpsFile);
        }
        else {
            return $this->readGpsDataFromVideo($file);
        }
    }

    private function readGpsDataFromGpsFile(string $gpsfile){
        echo "GPS File: ".$gpsfile;
        $fileHandler = fopen($this->folder . '/' . $gpsfile, 'r');
        $gpsData = fread($fileHandler, filesize($this->folder . '/' . $gpsfile));
        fclose($fileHandler);
        return trim($gpsData);
    }

    private function readGpsDataFromVideo(string $videofile){
        echo "Videofile: ".$videofile;
        $start = $this->offsetStart;
        $end = $this->offsetEnd;
        $length = $end - $start;

        $fileHandler = fopen($this->folder . '/' . $videofile, 'r');
        fseek($fileHandler, $start, SEEK_SET);
        $gpsData = fread($fileHandler, $length);
        fclose($fileHandler);
        return trim($gpsData);
    }

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
}
