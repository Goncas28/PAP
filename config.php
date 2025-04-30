<?php

// Database credentials
define('DB_SERVER', 'localhost');  // or 127.0.0.1
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'gcars');

define('SMTP_HOST', 'smtp.gmail.com'); // SMTP server address
define('SMTP_PORT', 587); // SMTP server port
define('SMTP_USER', 'standgcars@gmail.com'); // Your email address
define('SMTP_PASS', 'ichl vvtp dmod pbhc'); // Your email password
define('SMTP_SECURE', 'tls'); // Encryption method (tls or ssl)

// Database connection function
function connect_db() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME,
            DB_USERNAME,
            DB_PASSWORD
        );
        
        // Set PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Email sending function using PHPMailer without Composer
function send_email($to, $subject, $body) {
    // Direct inclusion of PHPMailer files
    require_once 'PHPMailer/src/Exception.php';
    require_once 'PHPMailer/src/PHPMailer.php';
    require_once 'PHPMailer/src/SMTP.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_USER, 'G Cars');
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->CharSet = 'UTF-8'; // Ensure proper character encoding
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>