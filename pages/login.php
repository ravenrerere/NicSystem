<?php
session_start();

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'nics_db';

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: ../index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    $query = "SELECT * FROM admin_users WHERE username = '$username' AND password = MD5('$password')";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        header("Location: ../index.php");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../resources/css/global.css">
    <title>Admin Login - NICS Agri Supply</title>
</head>
<body>
    <div>
        <h2>NICS AGRI SUPPLY</h2>
        <h3>Admin Login</h3>
        
        <?php if($error): ?>
            <p><?php echo $error; ?></p>
        <?php endif; ?>
        
        <form method="POST" action="">
            <table>
                <tr>
                    <td>Username: </td>
                    <td><input type="text" name="username" required> </td>
                </tr>
                <tr>
                    <td>Password: </td>
                    <td><input type="password" name="password" required> </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="submit" value="Login">
                    </td>
                </tr>
            </table>
        </form>
        
        <p>
            Default: admin / admin123
        </p>
    </div>
</body>
</html>