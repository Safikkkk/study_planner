<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__.'/vendor/autoload.php';

function getSetting($conn,$key,$default=''){
    $stmt=$conn->prepare(
        "SELECT setting_value
         FROM site_settings
         WHERE setting_key=?
         LIMIT 1"
    );

    if(!$stmt) return $default;

    $stmt->bind_param("s",$key);
    $stmt->execute();

    $row=$stmt->get_result()->fetch_assoc();

    $stmt->close();

    return $row['setting_value'] ?? $default;
}

function sendMail($conn,$to,$toName,$subject,$htmlBody){

    $mail=new PHPMailer(true);

    try{

        $mail->isSMTP();

        $mail->Host=
            getSetting($conn,'smtp_host');

        $mail->SMTPAuth=true;

        $mail->Username=
            getSetting($conn,'smtp_username');

        $mail->Password=
            getSetting($conn,'smtp_password');

        $mail->SMTPSecure=
            PHPMailer::ENCRYPTION_STARTTLS;

        $mail->Port=
            getSetting($conn,'smtp_port',587);

        $mail->setFrom(
            getSetting($conn,'smtp_username'),
            getSetting($conn,'smtp_from_name')
        );

        $mail->addAddress($to,$toName);

        $mail->isHTML(true);

        $mail->Subject=$subject;
        $mail->Body=$htmlBody;

        return $mail->send();

    }catch(Exception $e){

        error_log("StudyPlanner mailer: ".$mail->ErrorInfo);

        return false;
    }
}
function sendContactConfirmation($conn, $name, $email, $subject)
{
    $siteName = getSetting($conn, 'site_name', 'Study Planner');

    $html = "
    <h2>Thank You for Contacting {$siteName}</h2>
    <p>Hello <b>" . htmlspecialchars($name) . "</b>,</p>
    <p>We have received your message regarding:</p>
    <p><b>" . htmlspecialchars($subject) . "</b></p>
    <p>Our team will review it and get back to you within 24–48 hours.</p>
    <br>
    <p>Regards,<br>{$siteName}</p>
    ";

    return sendMail(
        $conn,
        $email,
        $name,
        "We received your message - {$siteName}",
        $html
    );
}

function sendAdminNotification($conn, $senderName, $senderEmail, $subject, $message)
{
    $adminEmail = getSetting($conn, 'contact_email', '');

    if (!$adminEmail) {
        return false;
    }

    $siteName = getSetting($conn, 'site_name', 'Study Planner');

    $html = "
    <h2>New Contact Message</h2>

    <p><strong>Name:</strong> " . htmlspecialchars($senderName) . "</p>

    <p><strong>Email:</strong> " . htmlspecialchars($senderEmail) . "</p>

    <p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>

    <p><strong>Message:</strong></p>

    <div style='padding:10px;border:1px solid #ddd'>
        " . nl2br(htmlspecialchars($message)) . "
    </div>
    ";

    return sendMail(
        $conn,
        $adminEmail,
        'Admin',
        'New Contact Message: ' . $subject,
        $html
    );
}