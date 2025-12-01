<?php

include("connection.php");

$name = $_POST ['name'] ;
$age = $_POST ['age'] ;
$sex = $_POST ['sex'];
$phone = $_POST['phone'];
$medical_report=$_POST['medical-history'];
$address = $_POST ['address'] ;




$sql = "INSERT INTO patients(name, age, sex, medical_report,address,phone) VALUES('$name','$age','$sex','$medical_report','$address','$phone')";


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