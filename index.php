<?php
// Conectarea la baza de date (înlocuiți cu detaliile dvs. de conectare)
$host = "localhost";
$dbname = "nume_baza_de_date";
$username = "utilizator";
$password = "parola";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["cod" => 500, "mesaj" => "Eroare de conectare la baza de date: " . $e->getMessage()]);
    exit;
}

// Funcție pentru a verifica dacă clientul există deja în baza de date
function verificaClient($conn, $telefon, $email)
{
    $stmt = $conn->prepare("SELECT id FROM clienti WHERE telefon = :telefon OR email = :email");
    $stmt->bindParam(":telefon", $telefon);
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Preiați datele de la formularul de contact
    $nume = $_POST["nume"];
    $prenume = $_POST["prenume"];
    $telefon = $_POST["telefon"];
    $email = $_POST["email"];
    $mesaj = $_POST["mesaj"];

    // Verificați dacă clientul există deja
    $clientExistent = verificaClient($conn, $telefon, $email);

    if ($clientExistent) {
        echo json_encode(["cod" => 200, "lead_id" => $clientExistent["id"]]);
    } else {
        // Înregistrați clientul în tabela de clienti
        $stmt = $conn->prepare("INSERT INTO clienti (nume, prenume, telefon, email) VALUES (:nume, :prenume, :telefon, :email)");
        $stmt->bindParam(":nume", $nume);
        $stmt->bindParam(":prenume", $prenume);
        $stmt->bindParam(":telefon", $telefon);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        // Obțineți ID-ul clientului înregistrat
        $clientID = $conn->lastInsertId();

        // Înregistrați lead-ul în tabela pentru leaduri
        $stmt = $conn->prepare("INSERT INTO leaduri (client_id, mesaj) VALUES (:client_id, :mesaj)");
        $stmt->bindParam(":client_id", $clientID);
        $stmt->bindParam(":mesaj", $mesaj);
        $stmt->execute();

        echo json_encode(["cod" => 200, "lead_id" => $clientID]);
    }
} else {
    echo json_encode(["cod" => 500, "mesaj" => "Metoda HTTP incorectă"]);
}
?>