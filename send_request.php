<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Récupération des données ---
    $name = htmlspecialchars($_POST['fullName'] ?? '');
    $email = htmlspecialchars($_POST['emailAddr'] ?? '');
    $phone = htmlspecialchars($_POST['phoneNum'] ?? '');
    $service = htmlspecialchars($_POST['service_name'] ?? '');
    $price = htmlspecialchars($_POST['service_price'] ?? '');
    $tx_ref = htmlspecialchars($_POST['txRef'] ?? '');

    // --- Gestion du fichier uploadé ---
    $upload_dir = "uploads/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_attached = false;
    $file_path = '';

    if (isset($_FILES["receiptFile"]) && $_FILES["receiptFile"]["error"] == 0) {
        $file_name = time() . "_" . basename($_FILES["receiptFile"]["name"]);
        $target_file = $upload_dir . $file_name;
        
        // Vérifier que c'est bien une image
        $check = getimagesize($_FILES["receiptFile"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["receiptFile"]["tmp_name"], $target_file)) {
                $file_attached = true;
                $file_path = $target_file;
            }
        }
    }

    // --- Construction du message email ---
    $to = "xenaryclub@gmail.com";
    $subject = "Nouvelle demande de service - Xynera Club";

    $message = "🟢 NOUVELLE DEMANDE DE SERVICE\n\n";
    $message .= "Nom : $name\n";
    $message .= "Email : $email\n";
    $message .= "Téléphone : $phone\n";
    $message .= "Service : $service\n";
    $message .= "Prix : $price\n";
    $message .= "Référence Tx : $tx_ref\n";

    // --- Envoi de l'email avec pièce jointe ---
    $boundary = "----=" . md5(uniqid());
    $headers = "From: " . $email . "\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

    // Corps du message
    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $message . "\r\n";

    // Pièce jointe
    if ($file_attached && file_exists($file_path)) {
        $file_content = file_get_contents($file_path);
        $file_content = chunk_split(base64_encode($file_content));
        $filename = basename($file_path);
        
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: image/jpeg; name=\"$filename\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"$filename\"\r\n\r\n";
        $body .= $file_content . "\r\n";
    }

    $body .= "--$boundary--";

    // Envoi
    if (mail($to, $subject, $body, $headers)) {
        // Supprimer le fichier après envoi (pour ne pas encombrer le serveur)
        if ($file_attached && file_exists($file_path)) {
            unlink($file_path);
        }
        echo "✅ Votre demande a bien été envoyée ! Nous vous répondrons sous 24h.";
    } else {
        echo "❌ Une erreur est survenue lors de l'envoi. Veuillez réessayer.";
    }

} else {
    header("Location: index.html");
    exit;
}
?>
