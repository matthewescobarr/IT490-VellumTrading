<?php

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendTradeConfirmation($email, $symbol, $action, $quantity, $price)
{
    $mail = new PHPMailer(true);

    try {
        // Load SMTP settings from the server's configuration file.
	$config = parse_ini_file(__DIR__ . '/email.ini', true);

        if (!$config) {
            error_log("Unable to load email configuration.");
            return false;
        }

        // Gmail SMTP settings.
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['email'];
        $mail->Password   = $config['app_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Sender and recipient.
        $mail->setFrom($config['email'], 'Vellum Trading');
        $mail->addAddress($email);

        // Email content.
        $mail->isHTML(true);
        $mail->Subject = 'Vellum Trading - Trade Confirmation';

        $total = $quantity * $price;

        $mail->Body = "
            <h2>Trade Confirmation</h2>
            <p>Your trade was successfully completed.</p>
            <p><strong>Action:</strong> " . htmlspecialchars($action) . "</p>
            <p><strong>Stock:</strong> " . htmlspecialchars($symbol) . "</p>
            <p><strong>Quantity:</strong> " . (int)$quantity . "</p>
            <p><strong>Price per share:</strong> $" . number_format($price, 2) . "</p>
            <p><strong>Total:</strong> $" . number_format($total, 2) . "</p>
            <p>Thank you for using Vellum Trading!</p>
        ";

        $mail->AltBody =
            "Your trade was successfully completed.\n" .
            "Action: $action\n" .
            "Stock: $symbol\n" .
            "Quantity: $quantity\n" .
            "Price per share: $" . number_format($price, 2) . "\n" .
            "Total: $" . number_format($total, 2);

        $mail->send();

        return true;

    } catch (Exception $e) {
        error_log("Trade confirmation email failed: " . $mail->ErrorInfo);
        return false;
    }
}
?>
