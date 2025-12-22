<?php
/**
 * Email utility for sending password to newly approved members
 */

function generateRandomPassword($length = 12) {
    // Use only alphanumeric characters to avoid HTML encoding issues
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

function sendPasswordEmail($email, $firstName, $tempPassword) {
    // Configuration - adjust based on your mail settings
    $from = 'noreply@reboot-philippines.org';
    $subject = 'Your Reboot PH Account Credentials - Set Your Password';
    
    // Create change password link with token (you can use email + timestamp as simple token)
    $token = base64_encode($email . ':' . time());
    $changePasswordUrl = 'https://rebootph-bicol.online/change-password.html?token=' . urlencode($token);
    
    // HTML email template
    $htmlBody = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; }
            .header { background-color: #0b4f86; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background-color: white; padding: 30px; }
            .credentials { background-color: #f0f0f0; padding: 15px; border-left: 4px solid #0b4f86; margin: 20px 0; font-family: monospace; }
            .button { display: inline-block; background-color: #35b34a; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
            .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Welcome to Reboot PH!</h2>
            </div>
            <div class='content'>
                <p>Dear <strong>$firstName</strong>,</p>
                
                <p>Congratulations! Your application for membership with Reboot PH has been <strong>approved</strong>. We're excited to have you join our community of youth-led energy advocates!</p>
                
                <h3>Your Account Credentials</h3>
                <div class='credentials'>
                    <p><strong>Email:</strong> <code>' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</code></p>
                    <p><strong>Temporary Password:</strong> <code>' . htmlspecialchars($tempPassword, ENT_QUOTES, 'UTF-8') . '</code></p>
                </div>
                
                <h3>You Have Two Options:</h3>
                
                <h4 style='color: #035996;'>Option 1: Use Your Temporary Password (Direct Login)</h4>
                <p>You can immediately log in to the dashboard using the temporary password provided above:</p>
                <ul>
                    <li>Visit: <strong>https://springgreen-walrus-657527.hostingersite.com</strong></li>
                    <li>Email: <code>' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</code></li>
                    <li>Password: <code>' . htmlspecialchars($tempPassword, ENT_QUOTES, 'UTF-8') . '</code></li>
                </ul>
                
                <h4 style='color: #035996;'>Option 2: Set Your Own Password First (Recommended)</h4>
                <p>For better security, we recommend setting your own password before logging in. Click the button below:</p>
                <a href='$changePasswordUrl' class='button'>Set Your Password</a>
                
                <p style='margin-top: 20px; word-break: break-all; color: #0b4f86;'><small>Or copy this link: $changePasswordUrl</small></p>
                
                <h3>Next Steps:</h3>
                <ol>
                    <li><strong>If using the temporary password:</strong> Log in directly, then go to your profile settings to change your password</li>
                    <li><strong>If setting a new password:</strong> Use the link above to set your password, then log in with your new credentials</li>
                    <li>Complete your member profile</li>
                    <li>Start participating in our initiatives and events!</li>
                </ol>
                
                <p>If you have any questions or need assistance, please don't hesitate to contact us at <strong>rebootphinstitute@gmail.com</strong></p>
                
                <p>Best regards,<br><strong>The Reboot PH Team</strong></p>
                
                <div class='footer'>
                    <p>This is an automated message. Please do not reply to this email.</p>
                    <p>&copy; 2024 Reboot PH. All rights reserved.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Plain text version
    $textBody = "
    Dear $firstName,
    
    Congratulations! Your application for membership with Reboot PH has been APPROVED. We're excited to have you join our community!
    
    Your Account Credentials:
    Email: $email
    Temporary Password: $tempPassword
    
    YOU HAVE TWO OPTIONS:
    
    OPTION 1: Use Your Temporary Password (Direct Login)
    - Visit: https://springgreen-walrus-657527.hostingersite.com
    - Use the email and temporary password above to log in immediately
    - You can change your password anytime in your profile settings
    
    OPTION 2: Set Your Own Password First (Recommended)
    - Visit this link to set your own password: $changePasswordUrl
    - Then log in with your new credentials
    
    Next Steps:
    1. Choose one of the options above
    2. Log in to the dashboard
    3. Complete your member profile
    4. Start participating in our initiatives!
    
    If you need help, contact us at: rebootphinstitute@gmail.com
    
    Best regards,
    The Reboot PH Team
    ";
    
    // Set email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: <$from>" . "\r\n";
    $headers .= "Reply-To: $from" . "\r\n";
    
    // Send the email
    $result = mail($email, $subject, $htmlBody, $headers);
    
    return $result;
}

?>
