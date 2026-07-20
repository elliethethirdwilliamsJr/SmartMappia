<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    /**
     * Send verification code email
     */
    public static function sendVerificationCode(string $email, string $name, string $code): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = env('MAIL_HOST', 'smtp.gmail.com');
            $mail->SMTPAuth = true;
            $mail->Username = env('MAIL_USERNAME'); // Your Gmail
            $mail->Password = env('MAIL_PASSWORD'); // Your App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = env('MAIL_PORT', 587);

            // Recipients
            $mail->setFrom(env('MAIL_FROM_ADDRESS', 'noreply@smartmappia.com'), env('MAIL_FROM_NAME', 'Smart Mappia'));
            $mail->addAddress($email, $name);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Verify Your Email - Smart Mappia';
            $mail->Body = self::getEmailTemplate($name, $code);
            $mail->AltBody = "Hello $name,\n\nYour verification code is: $code\n\nThis code will expire in 10 minutes.\n\nThank you,\nSmart Mappia Team";

            $mail->send();
            return true;
        } catch (Exception $e) {
            \Log::error("Email sending failed: {$mail->ErrorInfo}");
            return false;
        }
    }

    /**
     * Get email HTML template
     */
    private static function getEmailTemplate(string $name, string $code): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; background-color: #f7f7f8; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); }
                .header { background: linear-gradient(135deg, #FF6B35 0%, #FF8C61 100%); padding: 40px 30px; text-align: center; }
                .header h1 { color: #ffffff; margin: 0; font-size: 28px; font-weight: 900; }
                .content { padding: 40px 30px; }
                .greeting { font-size: 18px; color: #1a202c; margin-bottom: 20px; font-weight: 600; }
                .message { font-size: 15px; color: #4a5568; line-height: 1.6; margin-bottom: 30px; }
                .code-container { background-color: #fff4ed; border: 2px dashed #FF6B35; border-radius: 12px; padding: 30px; text-align: center; margin: 30px 0; }
                .code { font-size: 36px; font-weight: 900; color: #FF6B35; letter-spacing: 8px; font-family: 'Courier New', monospace; }
                .code-label { font-size: 13px; color: #6b7280; margin-top: 12px; font-weight: 600; }
                .warning { background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 16px; border-radius: 8px; margin: 20px 0; }
                .warning p { margin: 0; font-size: 14px; color: #991b1b; }
                .footer { background-color: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #e5e7eb; }
                .footer p { margin: 5px 0; font-size: 13px; color: #6b7280; }
                .button { display: inline-block; background-color: #FF6B35; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 10px; font-weight: 700; font-size: 15px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Smart Mappia</h1>
                </div>
                <div class='content'>
                    <p class='greeting'>Hello $name,</p>
                    <p class='message'>Thank you for signing up for Smart Mappia! To complete your registration, please verify your email address using the code below:</p>
                    
                    <div class='code-container'>
                        <div class='code'>$code</div>
                        <div class='code-label'>VERIFICATION CODE</div>
                    </div>
                    
                    <p class='message'>Enter this code in the Smart Mappia app to verify your email and activate your account.</p>
                    
                    <div class='warning'>
                        <p><strong>Important:</strong> This code will expire in 10 minutes. If you didn't request this code, please ignore this email.</p>
                    </div>
                </div>
                <div class='footer'>
                    <p><strong>Smart Mappia</strong></p>
                    <p>Your all-in-one platform for rides, food, and shopping</p>
                    <p style='margin-top: 15px; color: #9ca3af; font-size: 12px;'>© 2026 Smart Mappia. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
