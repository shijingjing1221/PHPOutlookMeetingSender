<?php
require_once("sendMeeting.php");
$meetingSender = new sendMeeting();

$meetingSender->setFrom("cgordon@163.com", "cgordon@163.com", "S, JJ");
echo "setFrom!</br>";
$meetingSender->setTo("jshi@microstrategy.com", "jshi@microstrategy.com", "SS, JJJJ");

$startTime = '20130626T150500Z';
$endTime = '20130626T160500Z';
$location = "DefaultMeetingRoom";
$uid = date("Ymd\TGis", strtotime($startTime)).rand()."@phpmeetingsender";
$isCancal = FALSE;
$subject="This is a test of meeint";
$message="Dear ****, this is a soft reminder of ******.";
$meetingSender->setMail($startTime, $endTime, $location, $uid, 5, $isCancal, $subject,$message);
echo "setTo!</br>";
echo "SEND RESULT:" .$meetingSender->send(). "<br>";
echo "Sent!</br>";
?>