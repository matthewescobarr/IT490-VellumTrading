<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// Sends a trade confirmation email
function sendTradeConfirmation($email, $tradeDetails)
{
    // Set timezone
    date_default_timezone_set('America/New_York');

    // Load PHPMailer
    require_once __DIR__ . '/vendor/autoload.php';

    // Load email configuration
    $configPath = __DIR__ . '/private/email.ini';

    if (!file_exists($configPath)) {
        error_log("Email configuration file not found.");
        return false;
    }

    $config = parse_ini_file($configPath, true);

    if (
        !$config ||
        !isset(
            $config['smtp']['host'],
            $config['smtp']['port'],
            $config['smtp']['username'],
            $config['smtp']['password'],
            $config['smtp']['encryption']
        )
    ) {
        error_log("Invalid email configuration.");
        return false;
    }

    // Validate recipient
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid recipient email address.");
        return false;
    }

    // Validate trade information
    $requiredFields = [
        'symbol',
        'action',
        'quantity',
        'price'
    ];

    foreach ($requiredFields as $field) {
        if (!isset($tradeDetails[$field])) {
            error_log("Missing trade information: " . $field);
            return false;
        }
    }

    // Format trade information
    $symbol = htmlspecialchars(
        $tradeDetails['symbol'],
        ENT_QUOTES,
        'UTF-8'
    );

    $action = htmlspecialchars(
        strtoupper($tradeDetails['action']),
        ENT_QUOTES,
        'UTF-8'
    );

    $quantity = htmlspecialchars(
        (string) $tradeDetails['quantity'],
        ENT_QUOTES,
        'UTF-8'
    );

    $price = number_format(
        (float) $tradeDetails['price'],
        2
    );

    $total = number_format(
        (float) $tradeDetails['quantity'] *
        (float) $tradeDetails['price'],
        2
    );

    // Trade date and time
    $tradeDate = date('F j, Y');
    $tradeTime = date('g:i:s A');

    // Create PHPMailer instance
    $mail = new PHPMailer(true);

    try {

        // SMTP configuration
        $mail->isSMTP();

        $mail->Host = $config['smtp']['host'];
        $mail->SMTPAuth = true;

        $mail->Username = $config['smtp']['username'];
        $mail->Password = $config['smtp']['password'];

        // Use encryption from email.ini
        $mail->SMTPSecure = strtolower(
            $config['smtp']['encryption']
        ) === 'tls'
            ? PHPMailer::ENCRYPTION_STARTTLS
            : PHPMailer::ENCRYPTION_SMTPS;

        $mail->Port = (int) $config['smtp']['port'];

        // Sender and recipient
        $mail->setFrom(
            $config['smtp']['username'],
            'Vellum Trading'
        );

        $mail->addAddress($email);

        // Email content
        $mail->isHTML(true);

        $mail->Subject = "Vellum Trading - Trade Confirmation";

        $mail->Body = "
            <h2>Trade Confirmation</h2>

            <p>Your simulated trade was successfully executed.</p>

            <table cellpadding='8' cellspacing='0' border='1'>

                <tr>
                    <td><strong>Action:</strong></td>
                    <td>{$action}</td>
                </tr>

                <tr>
                    <td><strong>Stock Symbol:</strong></td>
                    <td>{$symbol}</td>
                </tr>

                <tr>
                    <td><strong>Quantity:</strong></td>
                    <td>{$quantity}</td>
                </tr>

                <tr>
                    <td><strong>Price per Share:</strong></td>
                    <td>\${$price}</td>
                </tr>

                <tr>
                    <td><strong>Total:</strong></td>
                    <td>\${$total}</td>
                </tr>

                <tr>
                    <td><strong>Trade Date:</strong></td>
                    <td>{$tradeDate}</td>
                </tr>

                <tr>
                    <td><strong>Trade Time:</strong></td>
                    <td>{$tradeTime}</td>
                </tr>

            </table>

            <p>Thank you for using Vellum Trading!</p>
        ";

        // Plain-text alternative
        $mail->AltBody =
            "Trade Confirmation\n\n" .
            "Action: {$action}\n" .
            "Stock: {$symbol}\n" .
            "Quantity: {$quantity}\n" .
            "Price Per Share: \${$price}\n" .
            "Total Value: \${$total}\n" .
            "Trade Date: {$tradeDate}\n" .
            "Trade Time: {$tradeTime}\n\n" .
            "Thank you for using Vellum Trading!";

        // Send email
        $mail->send();

        return true;

    } catch (Exception $e) {

        error_log(
            "Trade confirmation email failed: " .
            $mail->ErrorInfo
        );

        return false;
    }
}