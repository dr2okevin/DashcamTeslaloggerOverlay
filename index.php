<?php
/**
 * Created by PhpStorm.
 * User: Kevin Quiatkowski
 * Date: 30.03.2018
 * Time: 18:09
 */

include 'videoConfig.php';

//const VIDEO_FOLDER = 'video/2024-05-13';
const GPS_OFFSET_START = 82150; //some dashcams store the positions inside the video file. Try a hexeditor so find the correct offsets
const GPS_OFFSET_END = 245968;

require_once('Classes/Gpx.php');
require_once('Classes/Nmea.php');
require_once('Classes/Video.php');

$videoClass = new Video($GLOBALS['config']['videoFolder'], GPS_OFFSET_START, GPS_OFFSET_END);
$nmeaClass = new Nmea();
$gpxClass = new Gpx();

$gpsData = $videoClass->getGpsDataByFolder($GLOBALS['config']['videoFolder']);
$gpsData = $nmeaClass->converFromArray($gpsData);
$nmeaClass->saveArray($gpsData, $GLOBALS['config']['videoFolder']);
$gpxClass->convert($GLOBALS['config']['videoFolder']);

#$videoClass->mergeVideos(VIDEO_FOLDER);
