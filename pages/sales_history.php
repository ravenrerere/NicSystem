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

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$sales = mysqli_query($conn, "SELECT * FROM sales ORDER BY sale_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../resources/css/global.css">
    <title>Sales History - NICS Agri Supply</title>
</head>
<body>
    <div>
        Welcome, <?php echo $_SESSION['admin_username']; ?> | <a href="logout.php">Logout</a>
    </div>
    
    <h1>NICS AGRI SUPPLY</h1>
    <h2>Sales History</h2>
    
    <nav>
        <a href="../index.php">Dashboard</a> | 
        <a href="products.php">Products</a> | 
        <a href="sales.php">New Sale</a> | 
        <a href="sales_history.php">Sales History</a> | 
        <a href="reports.php">Reports</a>
    </nav>
    
    <hr>
    
    <h3>All Transactions</h3>
    <table>
        <tr><th>Invoice #</th><th>Date</th><th>Total Amount</th><th>Payment</th><th>Change</th><th>Actions</th></tr>
        <?php while($row = mysqli_fetch_assoc($sales)): ?>
        <tr>
            <td><?php echo $row['invoice_number']; ?></td>
            <td><?php echo $row['sale_date']; ?></td>
            <td>₱<?php echo number_format($row['total_amount'], 2); ?></td>
            <td>₱<?php echo number_format($row['payment_amount'], 2); ?></td>
            <td>₱<?php echo number_format($row['change_amount'], 2); ?></td>
            <td><a href="receipt.php?invoice=<?php echo $row['invoice_number']; ?>" target="_blank">View Receipt</a></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>