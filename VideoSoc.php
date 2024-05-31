<?php
include 'videoConfig.php';

$videoSocProcessor = new videoSocProcessor();

$videoSocProcessor->setFolder($GLOBALS['config']['videoFolder']);
$videoSocProcessor->setDataCsv($GLOBALS['config']['dataCSV']);
$videoSocProcessor->processDataCsv();

foreach ($videoSocProcessor->getVideoFiles($GLOBALS['config']['videoFolder']) as $videoFile) {
    echo "Processing $videoFile" . PHP_EOL;
    $videoSocProcessor->setVideoFile($videoFile);
    $videoSocProcessor->setTimestampsCsv(pathinfo($videoFile, PATHINFO_FILENAME) . '.times.csv');
    $videoSocProcessor->processTimestampsCsv();
    $videoSocProcessor->mapTimingsAndData();
    $videoSocProcessor->interpolateGaps();
    $videoSocProcessor->writeFrameDataCsv(pathinfo($videoFile, PATHINFO_FILENAME) . '.frameData.csv');
}


class videoSocProcessor
{
    protected $folder = 'videos';
    protected $dataCsv = '';
    protected $timestampsCsv = '';
    protected $videoFile = '';

    /** @var dataObject[] $dataObjects */
    protected $dataObjects = [];
    protected $timings = [];
    protected $mappedData = [];

    /**
     * @param int|float $ps
     * @return float
     */
    public static function PsToKw($ps)
    {
        $kw = $ps * 0.735499;
        return round($kw);
    }

    public function setFolder(string $folder)
    {
        $this->folder = $folder;
    }

    public function getVideoFiles(string $folder)
    {
        if (empty($folder)) {
            $folder = $this->folder;
        }
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

    public function setDataCsv(string $dataCsv)
    {
        $this->dataCsv = $dataCsv;
    }

    public function setTimestampsCsv(string $timestampsCsv)
    {
        $this->timestampsCsv = $timestampsCsv;
    }

    public function setVideoFile(string $videoFile)
    {
        $this->videoFile = $videoFile;
    }

    public function processDataCsv()
    {
        // CSV-Datei öffnen
        $fullPath = $this->folder . "/" . $this->dataCsv;
        if (($handle = fopen($fullPath, "r")) !== false) {
            // Erste Zeile (Header) überspringen
            fgetcsv($handle);

            // Zeilen der CSV-Datei durchlaufen und dataObject-Instanzen erstellen
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                if (empty($data) || empty($data[0])) {
                    continue;
                }
                // time,speed,power,range,soc,outside_temp,height,inside_temp,battery_heater,distance,cell_temp
                // Daten aus der CSV-Zeile extrahieren
                $timeString = $data[0];
                $speed = (int)$data[1];
                $power = (float)$data[2];
                $range = (int)$data[3];
                $soc = (int)$data[4];
                $outside_temp = (float)$data[5];
                $height = $data[6];
                $inside_temp = (float)$data[7];
                $battery_heater = $data[8];
                $distance = (float)$data[9];
                $cell_temp = (float)$data[10];

                // dataObject-Instanz mit den Daten füllen
                $obj = new dataObject();
                $obj->setTimeString($timeString);
                $obj->setSpeed($speed);
                $obj->setSoc($soc);
                $obj->setPower($power);
                $obj->setDistance($distance);
                $obj->setRange($range);
                $obj->setOutsideTemp($outside_temp);
                $obj->setHeight($height);
                $obj->setInsideTemp($inside_temp);
                $obj->setBatteryHeater($battery_heater);
                $obj->setCellTemp($cell_temp);

                // dataObject-Instanz dem Array hinzufügen
                $this->dataObjects[$timeString] = $obj;
            }

            // CSV-Datei schließen
            fclose($handle);
        } else {
            throw new Exception("Could not load file $fullPath");
        }
    }

    public function processTimestampsCsv()
    {
        $fullPath = $this->folder . "/" . $this->timestampsCsv;
        if (($handle = fopen($fullPath, "r")) !== false) {
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $frame = (int)$data[0];
                $timeString = $data[1];
                $this->timings[$frame] = $timeString;
            }
        } else {
            throw new Exception("Could not load file $fullPath");
        }
    }

    public function mapTimingsAndData()
    {
        foreach ($this->timings as $frame => $timing) {
            if (isset($this->dataObjects[$timing])) {
                $this->mappedData[$frame] = $this->dataObjects[$timing];
            } else {
                $this->mappedData[$frame] = null;
                #trigger_error('data gab found at ' . $timing, E_USER_NOTICE);
                echo "Notice: data gab found at '$timing' for file $this->timestampsCsv" . PHP_EOL;
            }
        }
    }

    public function interpolateGaps()
    {
        foreach ($this->mappedData as $frame => $mappedData) {
            if (!($mappedData instanceof dataObject)) {
                $max = count($this->mappedData);
                for ($i = 1; $i <= $max; $i++) {
                    $object = $this->findObjectByDistance($frame, $i);
                    if ($object) {
                        $this->mappedData[$frame] = $object;
                        echo "Filled gab at distance $i" . PHP_EOL;
                        break;
                    }
                }
            }
        }
    }

    protected function findObjectByDistance(int $frame, int $distance = 1)
    {
        $previous = $frame - $distance;
        if ($previous < 1) {
            $previous = 1;
        }
        $next = $frame + $distance;
        if (isset($this->mappedData[$previous]) && $this->mappedData[$previous] instanceof dataObject) {
            return $this->mappedData[$previous];
        } elseif (isset($this->mappedData[$next]) && $this->mappedData[$next] instanceof dataObject) {
            return $this->mappedData[$next];
        } else {
            return false;
        }
    }

    public function writeFrameDataCsv(string $exportFileName)
    {
        if (($handle = fopen($this->folder . "/" . $exportFileName, "w")) !== false) {
            foreach ($this->mappedData as $frame => $mappedData) {
                if ($mappedData instanceof dataObject) {
                    $text = 'T# ';
                    $text .= ''
                        . $mappedData->getTimeString() . ' - '
                        . $mappedData->getSpeed() . ' km/h - SoC: '
                        . $mappedData->getSoc() . '\% - Power: '
                        . $mappedData->getPower() . " kW - Distance: "
                        . $mappedData->getDistance() . 'km - Bat temp: '
                        . $mappedData->getCellTemp() . '°C - Outside temp: '
                        . $mappedData->getOutsideTemp() . ' °C - Battery heater: '
                        . $mappedData->getBatteryHeater() . ' '; //- Height: ';
                        //. $mappedData->getHeight() . ' m';
                } else {
                    $text = 'T# ';
                    $text .= ''
                        . '?' . ' - '
                        . '?' . ' km/h - SoC: '
                        . '?' . '\% - Power: '
                        . '?' . " kW - Distance: " 
                        . '?' . 'km - Battery temp: '
                        . '?' . ' °C - Outside temp: '
                        . '?' . ' °C - Battery heater: ?';
                        //. '?' . ' \% - Height: ';
                        //. '?' . ' m';
                }

                $ffmpegCmdLine = $this->createFfmpegCmdLine($frame, str_replace(':', '\:', $text));
                if (!empty($ffmpegCmdLine)) {
                    echo "Debug: Writing '" . $ffmpegCmdLine . "'".PHP_EOL;
                    fwrite($handle, $ffmpegCmdLine);
                }
            }
        }
    }

    public function createFfmpegCmdLine($frame, $text)
    {
        $ffmpegLine = '';
        if ($frame % 30 == 0 || $frame == 1) {
            if ($frame <= 1) {
                $frame = 0;
            }
            $second = $frame / 30;
            $endSecond = $second + 1;

            $ffmpegLine = "$second" . "-" . "$endSecond drawtext reinit text='$text';" . PHP_EOL;

        }
        return $ffmpegLine;
    }

}

class dataObject
{
    protected string $timeString = '1970-01-01 00:00:00';
    protected ?int $speed = 0;
    protected ?int $soc = 0;
    protected ?float $power = 0;
    protected ?float $distance = 0;
    protected ?int $range = 0;
    protected ?float $outsideTemp = 0;
    protected ?int $height = 0;
    protected ?float $insideTemp = 0;
    protected ?int $batteryHeater = 0;
    protected ?float $cellTemp = null;
    protected ?float $wattPedal = null;

    /**
     * @return string
     */
    public function getTimeString(): string
    {
        return $this->timeString;
    }

    /**
     * @param string $timeString
     */
    public function setTimeString($timeString): void
    {
        $this->timeString = $timeString;
    }

    /**
     * @return string
     */
    public function getSpeed()
    {
        return self::normaliseStringLenght($this->speed, 3);
    }

    /**
     * @param int $speed
     */
    public function setSpeed(int $speed): void
    {
        $this->speed = $speed;
    }

    /**
     * @return int
     */
    public function getSoc(): int
    {
        return self::formatNumber($this->soc);
    }

    /**
     * @param int $soc
     */
    public function setSoc(int $soc): void
    {
        $this->soc = $soc;
    }

    /**
     * @return string
     */
    public function getPower()
    {
        return self::normaliseStringLenght(self::formatNumber($this->power), 4);
    }

    /**
     * @param float|int $power
     */
    public function setPower($power): void
    {
        $this->power = $power;
    }

    /**
     * @return float|int
     */
    public function getDistance()
    {
        return self::normaliseStringLenght($this->distance, 5);
    }

    /**
     * @param float|int $distance
     */
    public function setDistance($distance): void
    {
        $this->distance = $distance;
    }

    /**
     * @return int
     */
    public function getRange(): int
    {
        return $this->range;
    }

    /**
     * @param int $range
     */
    public function setRange(int $range): void
    {
        $this->range = $range;
    }

    /**
     * @return float|int
     */
    public function getOutsideTemp()
    {
        return $this->outsideTemp;
    }

    /**
     * @param float|int $outsideTemp
     */
    public function setOutsideTemp($outsideTemp): void
    {
        $this->outsideTemp = $outsideTemp;
    }

    /**
     * @return
     */
    public function getHeight()
    {
        return self::normaliseStringLenght($this->height, 3);
    }

    /**
     * @param int $height
     */
    public function setHeight(int $height): void
    {
        $this->height = $height;
    }

    /**
     * @return float|int
     */
    public function getInsideTemp()
    {
        return $this->insideTemp;
    }

    /**
     * @param float|int $insideTemp
     */
    public function setInsideTemp($insideTemp): void
    {
        $this->insideTemp = $insideTemp;
    }

    /**
     * @return string
     */
    public function getBatteryHeater()
    {
        //return self::normaliseStringLenght($this->batteryHeater, 3);
        if($this->batteryHeater > 0){
            return 'On';
        } elseif ($this->batteryHeater == 0){
            return 'Off';
        } else {
            return '?';
        }
    }

    /**
     * @param int $batteryHeater
     */
    public function setBatteryHeater(int $batteryHeater): void
    {
        $this->batteryHeater = $batteryHeater;
    }

    /**
     * @return string
     */
    public function getCellTemp()
    {
        return self::normaliseStringLenght($this->cellTemp, 3);
    }

    /**
     * @param float|int $cellTemp
     */
    public function setCellTemp($cellTemp): void
    {
        $this->cellTemp = $cellTemp;
    }

    static function formatNumber($number)
    {
        if (is_int($number)) {
            $string = sprintf("%03d", $number);
        } elseif (is_float($number)) {
            $string = sprintf("%01.1f", $number);
        } else {
            // Return the input as-is if it's not a float or integer
            $string = $number;
        }
        return $string;
    }

    static function normaliseStringLenght(string $string, int $length, string $spaceChar = ' ')
    {
        //mit leerzeichen auffüllen
        $stringLength = strlen($string);
        if ($stringLength < $length) {
            $spacesToAdd = $length - $stringLength;
            $padding = str_repeat($spaceChar, $spacesToAdd);
            $paddedString = $padding . $string;
            return $paddedString;
        }
        return $string;
    }

    /**
     * @return string|null
     */
    public function getWattPedal(): ?string
    {
        if($this->wattPedal){
            return self::normaliseStringLenght($this->wattPedal, 3);
        } else {
            return null;
        }
    }

    /**
     * @param float|null $wattPedal
     */
    public function setWattPedal(?float $wattPedal): void
    {
        $this->wattPedal = $wattPedal;
    }
}
