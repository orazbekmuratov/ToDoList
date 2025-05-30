<?php 
$host="localhost";
$user="root";
$password="root";
$db_name="tododp";

$conn=new mysqli($host,$user,$password,$db_name);

if($conn->connect_error) {
    die ("baza menen jalganiwda qatelik" . $conn->connect_error);
} 

// else {
//     echo "bazaga awmetli jalgandi";
// }




?>