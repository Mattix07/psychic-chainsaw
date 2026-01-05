<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>Tabella manifestazioni</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>

<h1>Tabella manifestazioni</h1>

<?php
$servername = "localhost";
$dbname = "5cit_eventsmaster";
$username = "root";
$pwd = "1234";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // FORM
    echo "
        <form method='post'>
            <input type='text' name='testo' placeholder='Inserisci il nome della manifestazione'>
            <input type='submit' value='Invia'>
        </form>
    ";

    // SOLO SE IL FORM È STATO INVIATO
    if (isset($_POST['testo']) && $_POST['testo'] !== "") {

        $in = $_POST['testo'];

        $sql = "
            SELECT e.nome AS eNome, e.OraI AS oraI, e.OraF AS oraF
            FROM eventi e
            JOIN manifestazioni m ON e.idManifestazione = m.id
            WHERE m.nome = :nome
            ORDER BY e.OraI ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":nome", $in, PDO::PARAM_STR);
        $stmt->execute();

        $tab = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th colspan='3'>".strtoupper(htmlspecialchars($in))."</th></tr>";

        if (count($tab) > 0) {
                    echo "<tr>
                        <th>Evento</th>
                        <th>Inizio</th>
                        <th>Fine</th>
                    </tr>";

            foreach ($tab as $riga) {
                echo "<tr>
                        <td>{$riga['eNome']}</td>
                        <td>{$riga['oraI']}</td>
                        <td>{$riga['oraF']}</td>
                      </tr>";
            }

            echo "</table>";

        } else {
            echo "<p>Nessun evento trovato per questa manifestazione.</p>";
        }
    }

} catch (PDOException $e) {
    echo "<p style='color:red; font-weight:bold'>" . $e->getMessage() . "</p>";
}

?>
</body>
</html>