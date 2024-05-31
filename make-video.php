<?php

include 'videoConfig.php';

$videoGenerator = new videoGenerator();
$videoGenerator->setVideoFolder($GLOBALS['config']['videoFolder']);
foreach ($videoGenerator->getOriginalVideos() as $originalVideo) {
    echo "Processing video " . $originalVideo . PHP_EOL;
    $videoGenerator->setOriginalVideoPath($originalVideo);
    $videoGenerator->setOverlayVideoPath($GLOBALS['config']['videoFolder'] . '/output/' . pathinfo($originalVideo)['filename'] . '.overlay.mp4');
    $dataLines = $videoGenerator->getDataForVideo();
    #foreach ($dataLines as $dataLine) {
        #list($frameNumber, $text) = explode(',', $dataLine);
        #$videoGenerator->generateImage($frameNumber, $text);
    #}
    if (!file_exists($videoGenerator->getOverlayVideoPath())) {
        $videoGenerator->createOverlayVideo();
    } else {
        echo "Datei " . $videoGenerator->getOverlayVideoPath() . " existiert bereits und wird übersprungen.";
    }
    #$videoGenerator->deleteImages();
}


class videoGenerator
{
    protected $videoFolder = '';
    protected $imagesFolder = 'video/2024-05-13/images';
    protected $originalVideoPath = '';
    protected $overlayVideoPath = '';
    protected $frameDataPath = '';

    public function getOriginalVideos()
    {
        $files = scandir($this->videoFolder);
        $fileExtensions = ['mp4'];
        foreach ($files as $filekey => $file) {
            foreach ($fileExtensions as $fileExtension) {
                if (substr($file, -strlen($fileExtension)) !== $fileExtension) {
                    unset($files[$filekey]);
                } elseif (str_replace('overlay', '', $file) !== $file) {
                    unset($files[$filekey]);
                } else {
                    $files[$filekey] = $this->videoFolder . '/' . $file;
                }
            }
        }
        #var_dump($files);
        return $files;
    }

    public function getDataForVideo()
    {
        $filename = pathinfo($this->originalVideoPath, PATHINFO_FILENAME);
        $dirname = pathinfo($this->originalVideoPath, PATHINFO_DIRNAME);
        $this->frameDataPath = $dirname . '/' . $filename . '.frameData.csv';
        echo "Frame Data Path is " . $this->frameDataPath . PHP_EOL;

        // Read the text data file
        $lines = file($this->frameDataPath, FILE_IGNORE_NEW_LINES);
        return $lines;
    }

    public function generateImage(int $frameNumber, string $text)
    {
        $command = "convert -size 1920x1080 xc:none -gravity south -pointsize 24 -fill white -annotate +0+5 \"$text\" $this->imagesFolder/frame_" . sprintf('%04d', $frameNumber) . ".png";
        echo $command . PHP_EOL;
        exec($command);
    }

    public function createOverlayVideo()
    {
        #$ffmpegCommand = "ffmpeg -i $this->originalVideoPath -pattern_type glob -framerate 30 -i \"$this->imagesFolder/*.png\" -filter_complex overlay $this->overlayVideoPath";
        #$ffmpegCommand = "ffmpeg -i $this->originalVideoPath -vf \"sendcmd=f=$this->frameDataPath\" -c:a copy $this->overlayVideoPath";
        #$ffmpegCommand = "ffmpeg -i $this->originalVideoPath -c:v libx264 -crf 18 -vf \"sendcmd=f=$this->frameDataPath,drawtext=x=0:y=H-th-30:fontcolor=white:fontsize=28:fontfile=/usr/share/fonts/truetype/roboto/hinted/Roboto-Regular.ttf:box=1:boxcolor=black:expansion=none:text=''\" -c:a copy $this->overlayVideoPath";
        #$ffmpegCommand = "ffmpeg -i $this->originalVideoPath -c:v h264_nvenc -cq 18 -vf \"sendcmd=f=$this->frameDataPath,drawtext=x=0:y=H-th-30:fontcolor=white:fontsize=28:fontfile=/usr/share/fonts/truetype/roboto/hinted/Roboto-Regular.ttf:box=1:boxcolor=black:expansion=none:text=''\" -c:a copy $this->overlayVideoPath";
        $ffmpegCommand = "ffmpeg -i $this->originalVideoPath -c:v h264_nvenc -cq 18 -vf \"scale=2560:1440,sendcmd=f=$this->frameDataPath,drawtext=x=0:y=H-th-40:fontcolor=white:fontsize=36:fontfile=/usr/share/fonts/truetype/roboto/hinted/Roboto-Regular.ttf:box=1:boxcolor=black@0.9:expansion=none:text=''\" -c:a copy $this->overlayVideoPath";
        echo $ffmpegCommand . PHP_EOL;
        exec($ffmpegCommand);
    }

    public function deleteImages()
    {
    }

    /**
     * @param string $originalVideoPath
     */
    public function setOriginalVideoPath(string $originalVideoPath): void
    {
        $this->originalVideoPath = $originalVideoPath;
    }

    public function getOriginalVideoPath(): string
    {
        return $this->originalVideoPath;
    }

    /**
     * @param string $overlayVideoPath
     */
    public function setOverlayVideoPath(string $overlayVideoPath): void
    {
        echo "setting video output to " . $overlayVideoPath . PHP_EOL;
        $this->overlayVideoPath = $overlayVideoPath;
    }

    public function getOverlayVideoPath(): string
    {
        return $this->overlayVideoPath;
    }

    /**
     * @return string
     */
    public function getVideoFolder(): string
    {
        return $this->videoFolder;
    }

    /**
     * @param string $videoFolder
     */
    public function setVideoFolder(string $videoFolder): void
    {
        $this->videoFolder = $videoFolder;
    }
}
