<?php
/**
 * Created by PhpStorm.
 * User: Kevin Quiatkowski
 * Date: 30.03.2018
 * Time: 18:09
 */

class Nmea
{
    /**
     * @param string[] $gpsData
     * @return string[]
     */
    public function converFromArray(array $gpsData): array
    {
        foreach ($gpsData as $key => $value) {
            $gpsData[$key] = $this->convertFromString($value);
        }
        return $gpsData;
    }

    /**
     * @param string $gpsData
     * @return string
     */
    public function convertFromString(string $gpsData): string
    {
        $nmeaData = preg_replace('/^\[\d*\]/m', '', $gpsData);
        return $nmeaData;
    }

    public function saveArray(array $dataArray, string $folder)
    {
        $allData = '';
        foreach ($dataArray as $name => $content) {
            $allData .= $content;
            $this->save($content, $folder, $name);
        }
        $this->save($allData, $folder, 'all');
    }

    public function save(string $data, string $folder, string $filename)
    {
        $filename = $folder . '/' . $filename . '.nmea';
        $fileHandler = fopen($filename, 'w');
        fwrite($fileHandler, $data);
        fclose($fileHandler);
    }
}
