<!DOCTYPE html>
<html>
<head>
    <title>VLSM Subnet Calculator</title>
</head>
<body>

<h2>VLSM Calculator</h2>

<form action="calculate.php" method="POST">
    Base Network (CIDR):<br>
    <input type="text" name="base_network" placeholder="192.168.1.0/24" required>
    <br><br>

    Host Requirements (comma separated):<br>
    <input type="text" name="hosts" placeholder="100,50,20,10" required>
    <br><br>

    <input type="submit" value="Calculate">
</form>

</body>
</html>