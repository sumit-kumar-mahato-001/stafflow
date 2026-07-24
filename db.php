<?php
$connection = mysqli_connect('localhost', 'root', '', 'ems_db', 3307);
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($connection, "utf8mb4");
?>
