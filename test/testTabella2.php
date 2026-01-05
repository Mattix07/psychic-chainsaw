<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title></title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="">
</head>
<body>

<h1>Tabella manifestazioni</h1>

<?php
$servername = "localhost";
$dbname = "5cit_eventsmaster";
$username = "root";
$pwd = "1234";
try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "select m.nome as mNome, e.nome as eNome, e.OraI as oraI, e.OraF as oraF from eventi e, manifestazioni m where e.idManifestazione=m.id order by e.OraI asc";
    $results = $conn->query($sql);
    $tab = $results->fetchAll(PDO::FETCH_ASSOC);
    foreach($tab as $riga){

        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th colspan='3'>".$riga["mNome"]."</th></tr>";
        echo "<tr>
            <th>Evento</th>
            <th>Inizio</th>
            <th>Fine</th>    
        </tr>";
        echo "<tr>
        <td>".$riga["eNome"]."</td>
        <td>".$riga["oraI"]."</td>
        <td>".$riga["oraF"]."</td>
        </tr>";
    }
    echo "</table>";
    

} catch (PDOException $e) {
    echo "<h2 style='color:red'; font-weight:bold'>".$e->getMessage()."</h2>";
}

?>
</body>
</html>