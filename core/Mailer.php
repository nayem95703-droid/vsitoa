<?php

namespace Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private static ?PHPMailer $instance = null;

    /**
     * Get PHPMailer instance
     */
    private static function getInstance(): PHPMailer
    {
        if (self::$instance === null) {
            self::$instance = new PHPMailer(true);
            
            try {
                // Server settings
                self::$instance->SMTPDebug = Config::get('app.debug') ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF;
                self::$instance->isSMTP();
                self::$instance->Host = Config::get('mail.host');
                self::$instance->SMTPAuth = true;
                self::$instance->Username = Config::get('mail.username');
                self::$instance->Password = Config::get('mail.password');
                self::$instance->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                self::$instance->Port = Config::get('mail.port');

                // Recipients
                self::$instance->setFrom(
                    Config::get('mail.from_email'),
                    Config::get('mail.from_name')
                );

                // Content
                self::$instance->isHTML(true);
                self::$instance->CharSet = 'UTF-8';

            } catch (Exception $e) {
                Logger::error("Mailer configuration failed: " . $e->getMessage());
                throw new \Exception("Mailer configuration failed");
            }
        }

        return self::$instance;
    }

    /**
     * Send email
     */
    public static function send(string $to, string $subject, string $body, array $options = []): bool
    {
        try {
            $mail = self::getInstance();

            // Clear previous recipients
            $mail->clearAddresses();
            $mail->clearCCs();
            $mail->clearBCCs();
            $mail->clearReplyTos();

            // Add recipient
            $mail->addAddress($to);

            // Add CC if provided
            if (!empty($options['cc'])) {
                $ccs = is_array($options['cc']) ? $options['cc'] : [$options['cc']];
                foreach ($ccs as $cc) {
                    $mail->addCC($cc);
                }
            }

            // Add BCC if provided
            if (!empty($options['bcc'])) {
                $bccs = is_array($options['bcc']) ? $options['bcc'] : [$options['bcc']];
                foreach ($bccs as $bcc) {
                    $mail->addBCC($bcc);
                }
            }

            // Add reply to if provided
            if (!empty($options['reply_to'])) {
                $mail->addReplyTo($options['reply_to']);
            }

            // Set subject
            $mail->Subject = $subject;

            // Set body
            if (!empty($options['template'])) {
                $body = self::renderTemplate($options['template'], $options['data'] ?? []);
            }

            $mail->Body = $body;

            // Add plain text version
            if (!empty($options['plain_text'])) {
                $mail->AltBody = $options['plain_text'];
            } else {
                $mail->AltBody = strip_tags($body);
            }

            // Add attachments if provided
            if (!empty($options['attachments'])) {
                foreach ($options['attachments'] as $attachment) {
                    if (is_array($attachment)) {
                        $mail->addAttachment(
                            $attachment['path'],
                            $attachment['name'] ?? basename($attachment['path'])
                        );
                    } else {
                        $mail->addAttachment($attachment);
                    }
                }
            }

            $result = $mail->send();

            Logger::info("Email sent successfully to $to: $subject");
            return $result;

        } catch (Exception $e) {
            Logger::error("Email sending failed to $to: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email to multiple recipients
     */
    public static function sendBulk(array $recipients, string $subject, string $body, array $options = []): array
    {
        $results = [];
        
        foreach ($recipients as $recipient) {
            $email = is_array($recipient) ? $recipient['email'] : $recipient;
            $name = is_array($recipient) ? ($recipient['name'] ?? '') : '';
            
            $personalizedBody = $body;
            if ($name) {
                $personalizedBody = str_replace('{name}', $name, $body);
                $personalizedSubject = str_replace('{name}', $name, $subject);
            } else {
                $personalizedSubject = $subject;
            }

            $results[$email] = self::send($email, $personalizedSubject, $personalizedBody, $options);
        }

        return $results;
    }

    /**
     * Send verification email
     */
    public static function sendVerificationEmail(array $user, string $token): bool
    {
        $verificationUrl = Config::get('app.url') . Config::get('app.base_path') . "/verify-email?token=$token";
        
        $subject = 'Verify your email address - ' . Config::get('app.name');
        
        $body = self::renderTemplate('email_verification', [
            'name' => $user['username'],
            'verification_url' => $verificationUrl,
            'app_name' => Config::get('app.name')
        ]);

        return self::send($user['email'], $subject, $body);
    }

    /**
     * Send password reset email
     */
    public static function sendPasswordResetEmail(array $user, string $token): bool
    {
        $resetUrl = Config::get('app.url') . Config::get('app.base_path') . "/reset-password?token=$token";
        
        $subject = 'Reset your password - ' . Config::get('app.name');
        
        $body = self::renderTemplate('password_reset', [
            'name' => $user['username'],
            'reset_url' => $resetUrl,
            'app_name' => Config::get('app.name')
        ]);

        return self::send($user['email'], $subject, $body);
    }

    /**
     * Send welcome email
     */
    public static function sendWelcomeEmail(array $user): bool
    {
        $subject = 'Welcome to ' . Config::get('app.name');
        
        $body = self::renderTemplate('welcome', [
            'name' => $user['username'],
            'app_name' => Config::get('app.name'),
            'login_url' => Config::get('app.url') . Config::get('app.base_path') . '/login'
        ]);

        return self::send($user['email'], $subject, $body);
    }

    /**
     * Send deposit confirmation email
     */
    public static function sendDepositConfirmation(array $user, array $deposit): bool
    {
        $subject = 'Deposit Confirmed - ' . Config::get('app.name');
        
        $body = self::renderTemplate('deposit_confirmation', [
            'name' => $user['username'],
            'amount' => $deposit['amount'],
            'currency' => $deposit['currency'],
            'transaction_id' => $deposit['txid'],
            'app_name' => Config::get('app.name')
        ]);

        return self::send($user['email'], $subject, $body);
    }

    /**
     * Send withdrawal confirmation email
     */
    public static function sendWithdrawalConfirmation(array $user, array $withdrawal): bool
    {
        $subject = 'Withdrawal Processed - ' . Config::get('app.name');
        
        $body = self::renderTemplate('withdrawal_confirmation', [
            'name' => $user['username'],
            'amount' => $withdrawal['amount'],
            'currency' => $withdrawal['currency'],
            'transaction_id' => $withdrawal['txid'],
            'app_name' => Config::get('app.name')
        ]);

        return self::send($user['email'], $subject, $body);
    }

    /**
     * Send notification email
     */
    public static function sendNotification(array $user, string $title, string $message): bool
    {
        $subject = $title . ' - ' . Config::get('app.name');
        
        $body = self::renderTemplate('notification', [
            'name' => $user['username'],
            'title' => $title,
            'message' => $message,
            'app_name' => Config::get('app.name')
        ]);

        return self::send($user['email'], $subject, $body);
    }

    /**
     * Render email template
     */
    private static function renderTemplate(string $template, array $data = []): string
    {
        $templatePath = ROOT_PATH . "/views/emails/{$template}.php";
        
        if (!file_exists($templatePath)) {
            // Fallback to simple template
            return self::renderSimpleTemplate($data);
        }

        // Extract data for template
        extract($data);
        
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    /**
     * Render simple email template
     */
    private static function renderSimpleTemplate(array $data): string
    {
        $name = $data['name'] ?? 'User';
        $message = $data['message'] ?? '';
        $appName = $data['app_name'] ?? Config::get('app.name');

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Email from {$appName}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$appName}</h1>
                </div>
                <div class='content'>
                    <p>Hello {$name},</p>
                    <p>{$message}</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " {$appName}. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";

        return $html;
    }

    /**
     * Test email configuration
     */
    public static function test(string $testEmail = null): array
    {
        $testEmail = $testEmail ?? Config::get('mail.username');
        
        try {
            $mail = self::getInstance();
            
            // Test connection
            if (!$mail->smtpConnect()) {
                throw new \Exception('SMTP connection failed');
            }
            
            $mail->smtpClose();

            // Test sending
            $subject = 'Test Email - ' . Config::get('app.name');
            $body = self::renderSimpleTemplate([
                'name' => 'Test User',
                'message' => 'This is a test email to verify the mail configuration is working correctly.',
                'app_name' => Config::get('app.name')
            ]);

            $result = self::send($testEmail, $subject, $body);

            return [
                'success' => $result,
                'message' => $result ? 'Test email sent successfully' : 'Failed to send test email'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Mail configuration error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get email queue for bulk sending
     */
    public static function queueEmail(string $to, string $subject, string $body, array $options = []): bool
    {
        try {
            return Database::insert('email_queue', [
                'to' => $to,
                'subject' => $subject,
                'body' => $body,
                'options' => json_encode($options),
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]) > 0;
        } catch (\Exception $e) {
            Logger::error("Failed to queue email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process email queue
     */
    public static function processQueue(int $limit = 10): array
    {
        $results = ['sent' => 0, 'failed' => 0, 'errors' => []];

        $limit = max(1, (int) $limit);

        $emails = Database::fetchAll(
            "SELECT * FROM email_queue WHERE status = 'pending' ORDER BY created_at ASC LIMIT {$limit}"
        );

        foreach ($emails as $email) {
            try {
                $options = json_decode($email['options'] ?? '[]', true);
                $success = self::send($email['to'], $email['subject'], $email['body'], $options);

                if ($success) {
                    Database::update(
                        'email_queue',
                        ['status' => 'sent', 'sent_at' => date('Y-m-d H:i:s')],
                        'id = ?',
                        [$email['id']]
                    );
                    $results['sent']++;
                } else {
                    Database::update(
                        'email_queue',
                        ['status' => 'failed', 'error' => 'Unknown error'],
                        'id = ?',
                        [$email['id']]
                    );
                    $results['failed']++;
                }

            } catch (\Exception $e) {
                Database::update(
                    'email_queue',
                    ['status' => 'failed', 'error' => $e->getMessage()],
                    'id = ?',
                    [$email['id']]
                );
                $results['failed']++;
                $results['errors'][] = $e->getMessage();
            }
        }

        return $results;
    }
}
