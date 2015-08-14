<?php 
date_default_timezone_set ("America/Los_Angeles");

$date1 = new DateTime("08/13/2015 11:28 AM");
$date2 = new Datetime(date("m/d/Y g:i A",time()));
//print_r($date1);
//print '<br/>';
print_r($date2);
print '<br/>';

$interval = $date1->diff($date2);
print_r($interval);