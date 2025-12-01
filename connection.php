<?php

$host = "localhost";
$user = "root";
$password ="";
$database ="hospital";

//$conn = new mysqli("localhost","root"," ","hospital");

$conn = new mysqli($host,$user,$password,$database);

if($conn){
    echo "connection successfull";
}

else{
    echo "connection failed";
}

?>