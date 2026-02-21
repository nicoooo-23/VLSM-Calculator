<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VLSM Subnet Calculator</title>
    <link rel="stylesheet" type="text/css" href="assets/style.css">
</head>
<body>
<div class="container">
    <h2>VLSM Calculator</h2>

    <form action="calculate.php" method="POST">
        Base Network (CIDR):<br>
        <input type="text" name="base_network" placeholder="192.168.1.0/24" required>
        <br><br>

        Host Requirements (comma separated):<br>
        <input type="text" name="hosts" placeholder="100,50,20,10" required>
        <br><br>

        <button type="submit">Calculate</button>
    </form>
</div>
</body>
</html>