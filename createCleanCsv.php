<?php

include 'videoConfig.php';

$csvCleaner = new CsvCleaner();
$csvCleaner->loadCsv();
$csvCleaner->processCsv();
$csvCleaner->writeProcessedCsv();

class CsvCleaner
{
    protected $inputFile = 'Verbrauch-data-2024-05-30 10 09 03.csv';
    protected $outputFile = 'cleanTeslaloggerData.csv';

    protected array $arrayMap = [
        'time' => 0,
        'speed' => 1,
        'power' => 2,
        'range' => 3,
        'soc' => 4,
        'outside_temp' => 5,
        'height' => 6,
        'inside_temp' => 7,
        'battery_heater' => 8,
        'preconditioning' => 9,
        'sentry_mode' => 10,
        'distance' => 11,
        'cell_temp' => 12,
        'charger_soc' => 17,
        'battery_heater_2' => 21,
        'charger_power' => 22
    ];

    protected array $cleanArrayMap = [
        'time' => ['time'],
        'speed' => ['speed'],
        'power' => ['power', 'charger_power'],
        'range' => ['range'],
        'soc' => ['soc', 'charger_soc'],
        'outside_temp' => ['outside_temp'],
        'height' => ['height'],
        'inside_temp' => ['inside_temp'],
        'battery_heater' => ['battery_heater', 'battery_heater_2'],
        'distance' => ['distance'],
        'cell_temp' => ['cell_temp'],
    ];

    protected array $rawCsvData = [];
    protected array $processedCsvData = [];

    public function processCsv()
    {
        //First set fixed start data
        $time = "1970-01-01 00:00:00";
        $speed = "0";
        $power = "0";
        $range = "";
        $soc = "";
        $outside_temp = "";
        $height = "0";
        $inside_temp = "";
        $battery_heater = "0";
        $distance = "0";
        $cell_temp = "";

        foreach ($this->rawCsvData as $rawCsvRow) {
            $time = $rawCsvRow[$this->cleanArrayMap['time'][0]];
            if (empty($time)) {
                continue;
            }

            if ($rawCsvRow[$this->cleanArrayMap['speed'][0]] !== null && $rawCsvRow[$this->cleanArrayMap['speed'][0]] !== '') {
                $speed = $rawCsvRow[$this->cleanArrayMap['speed'][0]];
            }

            if ($rawCsvRow[$this->cleanArrayMap['power'][1]] !== null && $rawCsvRow[$this->cleanArrayMap['power'][1]] !== '') {
                $power = round((float)$rawCsvRow[$this->cleanArrayMap['power'][1]] * -1, 1);
            } elseif ($rawCsvRow[$this->cleanArrayMap['power'][0]] !== null && $rawCsvRow[$this->cleanArrayMap['power'][0]] !== '') {
                $power = round(self::PsToKw($rawCsvRow[$this->cleanArrayMap['power'][0]]), 1);
            }

            if ($rawCsvRow[$this->cleanArrayMap['range'][0]] !== null && $rawCsvRow[$this->cleanArrayMap['range'][0]] !== '') {
                $range = $rawCsvRow[$this->cleanArrayMap['range'][0]];
            }

            if ($rawCsvRow[$this->cleanArrayMap['soc'][1]] !== null && $rawCsvRow[$this->cleanArrayMap['soc'][1]] !== '') {
                $soc = $rawCsvRow[$this->cleanArrayMap['soc'][1]];
            } elseif ($rawCsvRow[$this->cleanArrayMap['soc'][0]] !== null && $rawCsvRow[$this->cleanArrayMap['soc'][0]] !== '') {
                $soc = $rawCsvRow[$this->cleanArrayMap['soc'][0]];
            }

            if ($rawCsvRow[$this->cleanArrayMap['outside_temp'][0]] !== null && $rawCsvRow[$this->cleanArrayMap['outside_temp'][0]] !== '') {
                $outside_temp = $rawCsvRow[$this->cleanArrayMap['outside_temp'][0]];
            }

            if ($rawCsvRow[$this->cleanArrayMap['height'][0]] !== null && $rawCsvRow[$this->cleanArrayMap['height'][0]] !== '') {
                $height = $rawCsvRow[$this->cleanArrayMap['height'][0]];
            }

            if ($rawCsvRow[$this->cleanArrayMap['inside_temp'][0]] !== null && $rawCsvRow[$this->cleanArrayMap['inside_temp'][0]] !== '') {
                $inside_temp = $rawCsvRow[$this->cleanArrayMap['inside_temp'][0]];
            }

            if ($rawCsvRow[$this->cleanArrayMap['battery_heater'][0]] !== null && $rawCsvRow[$this->cleanArrayMap['battery_heater'][0]] !== '') {
                $battery_heater = $rawCsvRow[$this->cleanArrayMap['battery_heater'][0]];
            } elseif ($rawCsvRow[$this->cleanArrayMap['battery_heater'][1]] !== null && $rawCsvRow[$this->cleanArrayMap['battery_heater'][1]] !== '') {
                $battery_heater = $rawCsvRow[$this->cleanArrayMap['battery_heater'][1]];
            }

            if ($rawCsvRow[$this->cleanArrayMap['distance'][0]] !== null && $rawCsvRow[$this->cleanArrayMap['distance'][0]] !== '') {
                $distance = $rawCsvRow[$this->cleanArrayMap['distance'][0]];
            }

            if ($rawCsvRow[$this->cleanArrayMap['cell_temp'][0]] !== null && $rawCsvRow[$this->cleanArrayMap['cell_temp'][0]] !== '') {
                $cell_temp = $rawCsvRow[$this->cleanArrayMap['cell_temp'][0]];
            }

            $this->processedCsvData[] = [
                'time' => $time,
                'speed' => $speed,
                'power' => $power,
                'range' => $range,
                'soc' => $soc,
                'outside_temp' => $outside_temp,
                'height' => $height,
                'inside_temp' => $inside_temp,
                'battery_heater' => $battery_heater,
                'distance' => $distance,
                'cell_temp' => $cell_temp
            ];
        }
    }

    /**
     * @param int|float $ps
     * @return float
     */
    public static function PsToKw($ps)
    {
        $kw = $ps * 0.735499;
        return round($kw);
    }

    public function loadCsv()
    {
        if (($handle = fopen($this->inputFile, "r")) !== false) {
            // Erste Zeile (Header) überspringen
            fgetcsv($handle);

            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                if (empty($data)) {
                    continue;
                }
                // Daten aus der CSV-Zeile extrahieren
                $csvRow = [
                    'time' => $data[$this->arrayMap['time']],
                    'speed' => $data[$this->arrayMap['speed']],
                    'power' => $data[$this->arrayMap['power']],
                    'range' => $data[$this->arrayMap['range']],
                    'soc' => $data[$this->arrayMap['soc']],
                    'outside_temp' => $data[$this->arrayMap['outside_temp']],
                    'height' => $data[$this->arrayMap['height']],
                    'inside_temp' => $data[$this->arrayMap['inside_temp']],
                    'battery_heater' => $data[$this->arrayMap['battery_heater']],
                    'battery_heater_2' => $data[$this->arrayMap['battery_heater_2']],
                    'preconditioning' => $data[$this->arrayMap['preconditioning']],
                    'sentry_mode' => $data[$this->arrayMap['sentry_mode']],
                    'distance' => $data[$this->arrayMap['distance']],
                    'cell_temp' => $data[$this->arrayMap['cell_temp']],
                    'charger_soc' => $data[$this->arrayMap['charger_soc']],
                    'charger_power' => $data[$this->arrayMap['charger_power']],
                ];

                $this->rawCsvData[] = $csvRow;
            }
        }
    }

    public function writeProcessedCsv()
    {
        $handle = fopen($this->outputFile, "w");
        $header = array_keys($this->processedCsvData[0]);
        fputcsv($handle, $header);
        foreach ($this->processedCsvData as $csvRowData) {
            #echo serialize($csvRowData);
            fputcsv($handle, $csvRowData);
        }
        fclose($handle);
    }

    /**
     * @param string $inputFile
     * @return CsvCleaner
     */
    public function setInputFile(string $inputFile): CsvCleaner
    {
        $this->inputFile = $inputFile;
        return $this;
    }

    /**
     * @param string $outputFile
     * @return CsvCleaner
     */
    public
    function setOutputFile(string $outputFile): CsvCleaner
    {
        $this->outputFile = $outputFile;
        return $this;
    }
}
