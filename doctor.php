<?php

include("connection.php");

$name = $_POST ['name'] ;
$speciality= $_POST ['speciality'] ;
$sex = $_POST ['sex'];
$address = $_POST ['address'] ;
$phone_number = $_POST['phone_number'];






$sql = "INSERT INTO doctors(name, speciality, sex, address,phone_number) VALUES('$name','$speciality','$sex','$address','$phone_number')";

$responds = mysqli_query($conn,$sql);

if($responds){
    echo "insert";
    header("location: dashboard.html");
}
else{
    echo "failed";
      header("location: index.php");
}

?>