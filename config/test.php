<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include($_SERVER['DOCUMENT_ROOT'] . "/RMS/config/database.php");

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "INSERT INTO login (email, pass) VALUES ('$email', '$password')";
    if ($conn->query($sql) === TRUE) {
        echo "<p>Inserted successfully!</p>";
    } else {
        echo "<p>Error: " . $conn->error . "</p>";
    }
}
?>

<form method="POST">
  <input type="email" name="email" placeholder="Email" required>
  <input type="text" name="password" placeholder="Password" required>
  <button type="submit">Insert</button>
</form>

</body>
</html>