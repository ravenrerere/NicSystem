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

$products = mysqli_query($conn, "SELECT * FROM products WHERE quantity > 0 ORDER BY product_name");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_sale'])) {
    $payment_amount = (int)$_POST['payment_amount'];
    $total_amount = (int)$_POST['total_amount'];
    $change_amount = $payment_amount - $total_amount;
    
    if ($change_amount < 0) {
        $_SESSION['error'] = "Insufficient payment!";
        header("Location: sales.php");
        exit();
    }
    
    $invoice_number = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
    
    $query = "INSERT INTO sales (invoice_number, total_amount, payment_amount, change_amount) VALUES ('$invoice_number', $total_amount, $payment_amount, $change_amount)";
    
    if (mysqli_query($conn, $query)) {
        $sales_id = mysqli_insert_id($conn);
        
        $product_ids = $_POST['product_id'];
        $quantities = $_POST['quantity'];
        $prices = $_POST['price'];
        
        for ($i = 0; $i < count($product_ids); $i++) {
            if (!empty($product_ids[$i]) && $quantities[$i] > 0) {
                $product_id = (int)$product_ids[$i];
                $quantity = (int)$quantities[$i];
                $price = (int)$prices[$i];
                $subtotal = $quantity * $price;
                
                $item_query = "INSERT INTO sales_items (sales_id, product_id, quantity, price, subtotal) VALUES ($sales_id, $product_id, $quantity, $price, $subtotal)";
                mysqli_query($conn, $item_query);
                
                $update_stock = "UPDATE products SET quantity = quantity - $quantity WHERE product_id = $product_id";
                mysqli_query($conn, $update_stock);
            }
        }
        
        $_SESSION['message'] = "Sale completed! Invoice #: $invoice_number";
        $_SESSION['last_invoice'] = $invoice_number;
        header("Location: receipt.php?invoice=$invoice_number");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../resources/css/global.css">
    <title>New Sale - NICS Agri Supply</title>
    <script>
        let itemCount = 1;
        
        function addItem() {
            itemCount++;
            const container = document.getElementById('items-container');
            const newItem = document.createElement('div');
            newItem.id = 'item-' + itemCount;
            newItem.innerHTML = `
                <hr><h4>Item ${itemCount}</h4>
                <select name="product_id[]" onchange="updatePrice(this, ${itemCount})" required>
                    <option value="">Select Product</option>
                    <?php 
                    mysqli_data_seek($products, 0);
                    while($row = mysqli_fetch_assoc($products)): 
                    ?>
                    <option value="<?php echo $row['product_id']; ?>" data-price="<?php echo $row['price']; ?>"><?php echo $row['product_name']; ?> - ₱<?php echo number_format($row['price'], 2); ?> (Stock: <?php echo $row['quantity']; ?>)</option>
                    <?php endwhile; ?>
                </select>
                Quantity: <input type="number" name="quantity[]" min="1" onchange="calculateTotal()" required>
                Price: <input type="text" name="price[]" readonly>
                <input type="button" value="Remove" onclick="removeItem(${itemCount})">
            `;
            container.appendChild(newItem);
        }
        
        function removeItem(id) {
            document.getElementById('item-' + id).remove();
            calculateTotal();
        }
        
        function updatePrice(select, itemId) {
            const price = select.options[select.selectedIndex].getAttribute('data-price');
            document.querySelector(`#item-${itemId} input[name="price[]"]`).value = price;
            calculateTotal();
        }
        
        function calculateTotal() {
            let total = 0;
            const quantities = document.querySelectorAll('input[name="quantity[]"]');
            const prices = document.querySelectorAll('input[name="price[]"]');
            
            for (let i = 0; i < quantities.length; i++) {
                const qty = parseInt(quantities[i].value) || 0;
                const price = parseInt(prices[i].value) || 0;
                total += qty * price;
            }
            
            document.getElementById('total_amount').value = total;
            document.getElementById('total_display').innerText = '₱' + total.toLocaleString();
            calculateChange();
        }
        
        function calculateChange() {
            const total = parseInt(document.getElementById('total_amount').value) || 0;
            const payment = parseInt(document.getElementById('payment_amount').value) || 0;
            const change = payment - total;
            document.getElementById('change_display').innerText = change >= 0 ? '₱' + change.toLocaleString() : 'Insufficient payment!';
        }
    </script>
</head>
<body>
    <div>
        Welcome, <?php echo $_SESSION['admin_username']; ?> | <a href="logout.php">Logout</a>
    </div>
    
    <h1>NICS AGRI SUPPLY</h1>
    <h2>New Sale Transaction</h2>
    
    <nav>
        <a href="../index.php">Dashboard</a> | 
        <a href="products.php">Products</a> | 
        <a href="sales.php">New Sale</a> | 
        <a href="sales_history.php">Sales History</a> | 
        <a href="reports.php">Reports</a>
    </nav>
    
    <hr>
    
    <?php if(isset($_SESSION['error'])): ?>
        <p><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
    <?php endif; ?>
    
    <form method="POST" action="" onsubmit="return confirm('Complete this sale?')">
        <div id="items-container">
            <div id="item-1">
                <h4>Item 1</h4>
                <select name="product_id[]" onchange="updatePrice(this, 1)" required>
                    <option value="">Select Product</option>
                    <?php 
                    mysqli_data_seek($products, 0);
                    while($row = mysqli_fetch_assoc($products)): 
                    ?>
                    <option value="<?php echo $row['product_id']; ?>" data-price="<?php echo $row['price']; ?>"><?php echo $row['product_name']; ?> - ₱<?php echo number_format($row['price'], 2); ?> (Stock: <?php echo $row['quantity']; ?>)</option>
                    <?php endwhile; ?>
                </select>
                Quantity: <input type="number" name="quantity[]" min="1" onchange="calculateTotal()" required>
                Price: <input type="text" name="price[]" readonly>
            </div>
        </div>
        
        <br>
        <input type="button" value="Add Another Item" onclick="addItem()">
        
        <hr>
        
        <h3>Total: <span id="total_display">₱0</span></h3>
        <input type="hidden" id="total_amount" name="total_amount">
        
        <table>
            <tr>
                <td>Payment Amount: </td>
                <td><input type="number" id="payment_amount" name="payment_amount" onchange="calculateChange()" required></td>
            </tr>
            <tr>
                <td>Change: </td>
                <td><span id="change_display">₱0</span></td>
            </tr>
            <tr>
                <td></td>
                <td><input type="submit" name="complete_sale" value="Complete Sale"></td>
            </tr>
        </table>
    </form>
</body>
</html>