#! /usr/bin/env php
<?php

$maindir = __DIR__;

if (!file_exists($maindir.'/config.ini')) die ('config.ini not found!!!');
$settings = parse_ini_file($maindir.'/config.ini', true);
foreach ($settings["env"] AS $key => $value) {
    putenv($key."=".$value);
}

$silent = false;
if (php_sapi_name() == 'cli') {
    if (isset($_SERVER['TERM'])) {
        //echo "The script was run from a manual invocation on a shell".PHP_EOL;
    } else {
        $silent = true;
    }
} else {
    die("muss auf der Console aufgerufen werden!!!");
}

require_once $maindir.'/vendor/autoload.php';
use \BigBlueButton\BigBlueButton;

$bbb                 = new BigBlueButton();
$response            = $bbb->getMeetings();

$participantCount = 0;
$voiceParticipantCount = 0;
$videoCount = 0;

if ($response->getReturnCode() == 'SUCCESS') {
    //print_r($response);
    foreach ($response->getRawXml()->meetings->meeting as $meeting) {
        $participantCount += $meeting->participantCount;
        $voiceParticipantCount += $meeting->voiceParticipantCount;
        $videoCount += $meeting->videoCount;
    }

    $meeetingscount = count($response->getRawXml()->meetings->meeting);
    $meeetingsperfdata = ', "performance_data": [ "Meetings='.$meeetingscount.'" ]';
    $meetings = '{ "exit_status": 0, "plugin_output": "Laufende Meetings: '.$meeetingscount.'"'.$meeetingsperfdata.'}';


    $participantsperfdata = ', "performance_data": [ "Teilnehmer='.$participantCount.'", "Voice='.$voiceParticipantCount.'", "Video='.$videoCount.'" ]';
    $participants = '{ "exit_status": 0, "plugin_output": "Teilnehmer='.$participantCount.' Voice='.$voiceParticipantCount.' Video='.$videoCount.' "'.$participantsperfdata.'}';

    if (!$silent) {
        echo "Meetings: " . $meeetingscount . PHP_EOL;
        echo "Teilnehmer: " . $participantCount . PHP_EOL;
        echo "Teilnehmer Voice: " . $voiceParticipantCount . PHP_EOL;
        echo "Teilnehmer Video: " . $videoCount . PHP_EOL;
    }

    //echo $data.PHP_EOL;
    CallAPI(
        "POST",
        getenv('ICINGAURL').'/v1/actions/process-check-result?service=bbb.fh-potsdam.de!Meetings',
        $meetings)
    ;
    CallAPI(
        "POST",
        getenv('ICINGAURL').'/v1/actions/process-check-result?service=bbb.fh-potsdam.de!Teilnehmer',
        $participants)
    ;
}





function CallAPI($method, $url, $data = false)
{
    $curl = curl_init();

    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    // Optional Authentication:
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($curl, CURLOPT_USERPWD, getenv('ICINGALOGIN'));

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    //curl_setopt($curl, CURLOPT_VERBOSE, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYSTATUS, FALSE);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json'));

    $result = curl_exec($curl);
    if ($result === false)
    {
        echo 'Curl error: ' . curl_error($curl).PHP_EOL;
    }
    curl_close($curl);

    return $result;
}
