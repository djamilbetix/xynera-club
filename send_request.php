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
        // Vérifier la taille max (5 Mo)
        if ($_FILES["receiptFile"]["size"] > 5 * 1024 * 1024) {
            die("❌ Le fichier est trop volumineux (max 5 Mo).");
        }
        // Vérifier le type MIME
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES["receiptFile"]["tmp_name"]);
        finfo_close($finfo);
        if (!in_array($mime, $allowed)) {
            die("❌ Type de fichier non autorisé. Seules les images (JPEG, PNG, GIF, WEBP) sont acceptées.");
        }

        $file_name = time() . "_" . basename($_FILES["receiptFile"]["name"]);
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES["receiptFile"]["tmp_name"], $target_file)) {
            $file_attached = true;
            $file_path = $target_file;
        } else {
            die("❌ Erreur lors du téléchargement du fichier.");
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
    if ($file_attached) {
        $message .= "Pièce jointe : $file_name\n";
    }

    // --- Envoi de l'email avec pièce jointe ---
    $boundary = "----=" . md5(uniqid());
    $headers = "From: " . $email . "\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $body .= $message . "\r\n";

    if ($file_attached && file_exists($file_path)) {
        $file_content = file_get_contents($file_path);
        $file_content = chunk_split(base64_encode($file_content));
        $filename = basename($file_path);
        
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: $mime; name=\"$filename\"\r\n";
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
        // Si mail() échoue, on vérifie si la fonction est désactivée
        if (!function_exists('mail')) {
            echo "❌ La fonction mail() est désactivée sur votre hébergement. Contactez votre hébergeur pour l'activer ou utilisez un plugin SMTP.";
        } else {
            echo "❌ Une erreur est survenue lors de l'envoi. Vérifiez les paramètres de votre serveur mail.";
        }
    }

} else {
    // Si quelqu'un accède directement au fichier sans POST
    header("Location: index.html");
    exit;
}
?>