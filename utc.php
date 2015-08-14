<?php

$utc_date = DateTime::createFromFormat(
                'Y-m-d G:i', 
                '2011-04-27 02:45', 
                new DateTimeZone('UTC')
);

$nyc_date = $utc_date;
$nyc_date->setTimeZone(new DateTimeZone('America/New_York'));

//echo $nyc_date->format('Y-m-d g:i A'); // output: 2011-04-26 10:45 PM


date_default_timezone_set ("America/Los_Angeles");
$yourDateString = date("d-m-Y h:i:s A", '1439335474');
print $yourDateString;
?>