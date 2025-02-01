<?php
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'database_connection.php';

$input = json_decode(file_get_contents('php://input'), true);

// Handle user actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Case: User clicks "Send OTP"
    if (isset($input['send_otp'])) {
        
        // Check if all required fields are set
        if (isset($input['username'], $input['email'], $input['password'], $input['confirm_psw'], $input['country_code'], $input['phone'], $input['tele_user_name'])) {
            
            // Sanitize and validate inputs
            $username = htmlspecialchars(trim($input['username']));
            $email = htmlspecialchars(trim($input['email']));
            $password = trim($input['password']);
            $confirm_psw = trim($input['confirm_psw']);
            $country_code = isset($input['country_code']) ? $input['country_code'] : null;
            $phone = isset($input['phone']) ? $input['phone'] : null;
            $tele_user_name = isset($input['tele_user_name']) ? $input['tele_user_name'] : null;

            // Validation checks
            if (!preg_match("/^[a-zA-Z\s]+$/", $username)) {
                echo json_encode(["error" => "Invalid username. Only alphabets and spaces are allowed."]);
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(["error" => "Invalid email format."]);
                exit;
            }

            if (!preg_match("/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $password)) {
                echo json_encode(["error" => "Password must be at least 8 characters long, include at least one uppercase letter, one number, and one special character."]);
                exit;
            }

            if (!preg_match("/^\d{2,15}$/", $phone)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid phone number format.']);
                exit;
            }

            if ($password !== $confirm_psw) {
                echo json_encode(["error" => "Passwords do not match. Please try again."]);
                exit;
            }

            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Check if email already exists
            $stmt = $conn->prepare("SELECT email FROM user WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                echo json_encode(["error" => "This email is already registered."]);
                exit;
            }

            // Create Telegram phone number
            $tele_phone = $country_code . $phone;

            // Validate Telegram username
            if (!preg_match("/^@[a-zA-Z][a-zA-Z0-9_]{4,31}$/", $tele_user_name)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid Telegram username. It must start with "@" and be 5-32 characters long.']);
                exit;
            }

            // Check if Telegram phone number already exists
            $stmt = $conn->prepare("SELECT telegram_phone FROM user WHERE telegram_phone = ?");
            $stmt->bind_param("s", $tele_phone);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                echo json_encode(['status' => 'error', 'message' => 'This Telegram phone number is already registered.']);
                exit;
            }

            // Insert user data into database (without OTP yet)
            $stmt = $conn->prepare("INSERT INTO user (username, email, password, telegram_phone, telegram_username) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $email, $hashed_password, $tele_phone, $tele_user_name);
            if (!$stmt->execute()) {
                echo json_encode(["error" => "Database error. Please try again."]);
                exit;
            }

            $user_id = $stmt->insert_id;

            // Generate OTP for email verification
            $otp = random_int(100000, 999999);
            $otp_expiry = date("Y-m-d H:i:s", strtotime('+5 minutes'));

            // Update OTP in the database
            $stmt = $conn->prepare("UPDATE user SET otp = ?, otp_expiry = ? WHERE user_id = ?");
            $stmt->bind_param("ssi", $otp, $otp_expiry, $user_id);
            $stmt->execute();

            // Send OTP to the email
            sendOTPEmail($email, $otp);

            echo json_encode([
                "success" => "User data saved! OTP has been sent to your email. Please check your inbox to complete registration."
            ]);
        }
    }
    

    // Case: User clicks "Register" (Verify OTP)
    elseif (isset($input['register'])) {

        // Check if OTP and email are provided
        if (isset($input['email'], $input['otp'])) {
            $email = htmlspecialchars(trim($input['email']));
            $otp = htmlspecialchars(trim($input['otp']));

            // Fetch user by email and verify OTP
            $stmt = $conn->prepare("SELECT user_id, otp, otp_expiry FROM user WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($user_id, $db_otp, $otp_expiry);
            $stmt->fetch();

            // Check if OTP is expired
            if (strtotime($otp_expiry) < time()) {
                echo json_encode(["error" => "OTP has expired. Please request a new OTP."]);
                exit;
            }

            // Check if OTP matches
            if ((string)$otp === (string)$db_otp) {
                echo json_encode(["success" => "Registration successful! You are now verified."]);
            } else {
                echo json_encode(["error" => "Invalid OTP. Please try again."]);
            }            
        }
    }

    // Case: User clicks "Resend OTP" (OTP Expired)
    elseif (isset($input['resend_otp'])) {
        if (isset($input['email'])) {
            $email = htmlspecialchars(trim($input['email']));

            // Fetch user by email to check OTP expiry and resend if necessary
            $stmt = $conn->prepare("SELECT user_id, otp, otp_expiry FROM user WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($user_id, $db_otp, $otp_expiry);
            $stmt->fetch();

            // Check if OTP is expired
            if (strtotime($otp_expiry) < time()) {
                // Generate a new OTP and update expiry time
                $otp = random_int(100000, 999999);
                $otp_expiry = date("Y-m-d H:i:s", strtotime('+5 minutes'));

                // Update OTP in the database
                $stmt = $conn->prepare("UPDATE user SET otp = ?, otp_expiry = ? WHERE user_id = ?");
                $stmt->bind_param("ssi", $otp, $otp_expiry, $user_id);
                $stmt->execute();

                // Send new OTP to the email
                sendOTPEmail($email, $otp);

                echo json_encode(["success" => "OTP has been resent to your email. Please check your inbox."]);
            } else {
                echo json_encode(["error" => "Your OTP is still valid. Please use the current OTP to complete registration."]);
            }
        }
    }
}

// Function to send OTP via email
function sendOTPEmail($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'theinminhtet712005@gmail.com';
        $mail->Password = 'pvcn nkcf pewr qmcq';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('theinminhtet712005@gmail.com', 'Skill Swap');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your One-Time Password (OTP) for Registration';
        $mail->Body = " <div style='font-family: Arial, sans-serif; max-width: 500px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px; background-color: #f9f9f9;'>
                            <h2 style='text-align: center; color: #007BFF; font-size: 24px;'>Skill Swap OTP Verification</h2>
                            <p style='color: #333; font-size: 16px;'>Dear User,</p>
                            <p style='color: #333; font-size: 16px;'>Thank you for registering with us! Please use the following One-Time Password (OTP) to complete your registration:</p>
                            <div style='text-align: center; background-color: #007BFF; color: #fff; font-size: 22px; font-weight: bold; padding: 10px; border-radius: 5px; display: inline-block; margin: 10px auto;'>
                                $otp
                            </div>
                            <p style='color: #333; font-size: 16px;'>This OTP is valid for the next <strong>5 minutes</strong>. For security purposes, please do not share this code with anyone.</p>
                            <p style='color: #333; font-size: 16px;'>If you did not request this OTP, you can safely ignore this message.</p>
                            <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
                            <p style='text-align: center; font-size: 14px; color: #555;'>Best regards,<br><strong>Skill Swap Team</strong></p>
                        </div>
                      ";


        $mail->send();
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to send OTP email. Please try again later."]);
    }
}
?>
