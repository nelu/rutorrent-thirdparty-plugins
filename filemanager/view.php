<?php


if(!isset($_GET['ses'])) {die('404 bUfU');}
$oldses = session_id();
if(!empty($oldses)) {die('404 Its not for you');}

session_id($_GET['ses']);
session_start();

$_SERVER['REMOTE_USER'] = $_SESSION['uname'];


unset($_POST); $_POST = $_GET;
include('flm.php');

?>