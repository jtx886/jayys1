<?php
require_once dirname(__FILE__) . '/../config.php';

class Mailer {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $from;
    private $fromName;
    private $debug = false;

    public function __construct() {
        $this->host = SMTP_HOST;
        $this->port = SMTP_PORT;
        $this->user = SMTP_USER;
        $this->pass = SMTP_PASS;
        $this->from = SMTP_FROM;
        $this->fromName = SMTP_FROM_NAME;
    }

    public function send($to, $subject, $body, $isHtml = true) {
        // 尝试使用fsockopen连接SMTP服务器
        $timeout = 30;
        $ssl = ($this->port == 465) ? 'ssl://' : '';
        $fp = @fsockopen($ssl . $this->host, $this->port, $errno, $errstr, $timeout);
        if (!$fp) {
            // 如果fsockopen失败，使用PHP内置mail函数作为备选
            return $this->sendByMail($to, $subject, $body, $isHtml);
        }

        stream_set_blocking($fp, true);
        stream_set_timeout($fp, $timeout);
        $this->debugLog($this->getResponse($fp));

        $this->sendCommand($fp, "EHLO " . $_SERVER['HTTP_HOST']);
        $this->sendCommand($fp, "AUTH LOGIN");
        $this->sendCommand($fp, base64_encode($this->user));
        $this->sendCommand($fp, base64_encode($this->pass));
        $this->sendCommand($fp, "MAIL FROM:<{$this->from}>");
        $this->sendCommand($fp, "RCPT TO:<{$to}>");
        $this->sendCommand($fp, "DATA");

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: " . ($isHtml ? "text/html; charset=UTF-8" : "text/plain; charset=UTF-8") . "\r\n";
        $headers .= "From: {$this->fromName} <{$this->from}>\r\n";
        $headers .= "To: <{$to}>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "Date: " . date("r") . "\r\n";

        $message = $headers . "\r\n" . $body . "\r\n.\r\n";
        $this->sendCommand($fp, $message);
        $this->sendCommand($fp, "QUIT");
        fclose($fp);
        return true;
    }

    private function sendCommand($fp, $cmd) {
        fputs($fp, $cmd . "\r\n");
        $this->debugLog($this->getResponse($fp));
    }

    private function getResponse($fp) {
        $data = '';
        while ($str = fgets($fp, 515)) {
            $data .= $str;
            if (isset($str[3]) && $str[3] == ' ') break;
        }
        return $data;
    }

    private function debugLog($msg) {
        if ($this->debug) error_log("SMTP: $msg");
    }

    private function sendByMail($to, $subject, $body, $isHtml) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: " . ($isHtml ? "text/html; charset=UTF-8" : "text/plain; charset=UTF-8") . "\r\n";
        $headers .= "From: {$this->fromName} <{$this->from}>\r\n";
        // 生产 sendmail 不可用时要彻底静默：避免 PHP shell_exec sendmail 报错污染 stdout/stderr
        if (function_exists('mail')) {
            $prev = error_reporting(0);
            $oldDisplay = ini_get('display_errors');
            ini_set('display_errors', '0');
            $ok = @mail($to, $subject, $body, $headers);
            error_reporting($prev);
            ini_set('display_errors', $oldDisplay);
            return $ok;
        }
        return false;
    }

    // 发送邮箱验证码
    public function sendVerificationCode($email, $code, $username = '') {
        $subject = '【Jay影视】邮箱验证码';
        $body = $this->renderEmailTemplate('邮箱验证码', [
            'username' => $username,
            'code' => $code,
            'message' => '您正在进行邮箱验证操作，此验证码5分钟内有效。',
            'tips' => '如果这不是您本人的操作，请忽略此邮件。'
        ]);
        return $this->send($email, $subject, $body);
    }

    // 发送封禁通知
    public function sendBanNotification($email, $username, $banTime, $unbanTime, $reason) {
        $subject = '【Jay影视】账号封禁通知';
        $banStr = $banTime ? date('Y-m-d H:i:s', strtotime($banTime)) : '-';
        $unbanStr = $unbanTime ? date('Y-m-d H:i:s', strtotime($unbanTime)) : '永久封禁';
        $body = $this->renderEmailTemplate('账号封禁通知', [
            'username' => $username,
            'code' => null,
            'message' => '很抱歉，您的账号因违规已被封禁：',
            'tips' => "封禁时间：$banStr<br>解封时间：$unbanStr<br>封禁原因：$reason<br><br>如有疑问请通过反馈功能联系管理员。"
        ]);
        return $this->send($email, $subject, $body);
    }

    // 发送自定义通知
    public function sendCustomNotification($email, $username, $title, $content) {
        $subject = '【Jay影视】' . $title;
        $body = $this->renderEmailTemplate($title, [
            'username' => $username,
            'code' => null,
            'message' => $content,
            'tips' => '感谢您的关注与支持！'
        ]);
        return $this->send($email, $subject, $body);
    }

    // 漂亮的邮件模板
    private function renderEmailTemplate($title, $data) {
        $themeColor = defined('THEME_COLOR') ? THEME_COLOR : '#6366f1';
        $codeHtml = '';
        if (!empty($data['code'])) {
            $codeHtml = '<div style="margin: 30px 0; padding: 25px; background: linear-gradient(135deg, ' . $themeColor . '15, ' . $themeColor . '08); border-radius: 12px; border: 2px dashed ' . $themeColor . '50;">
                <div style="font-size: 14px; color: #6b7280; margin-bottom: 10px; text-align: center;">您的验证码</div>
                <div style="font-size: 36px; font-weight: bold; color: ' . $themeColor . '; text-align: center; letter-spacing: 8px; font-family: monospace;">' . $data['code'] . '</div>
            </div>';
        }
        $greeting = !empty($data['username']) ? '您好，' . htmlspecialchars($data['username']) . '：' : '您好：';
        return '<!DOCTYPE html>
<html><body style="margin: 0; padding: 0; background: #f5f6fa; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;">
<table cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: 0 auto;">
<tr><td style="padding: 40px 20px;">
  <table cellpadding="0" cellspacing="0" width="100%" style="background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
    <tr><td style="background: linear-gradient(135deg, ' . $themeColor . ', ' . $themeColor . 'cc); padding: 35px 30px; text-align: center;">
      <div style="color: #ffffff; font-size: 26px; font-weight: 700; letter-spacing: 2px;">🎬 Jay影视</div>
      <div style="color: rgba(255,255,255,0.85); margin-top: 6px; font-size: 14px;">' . htmlspecialchars($title) . '</div>
    </td></tr>
    <tr><td style="padding: 35px 30px;">
      <div style="color: #111827; font-size: 16px; line-height: 1.8;">
        <p style="margin: 0 0 15px;">' . $greeting . '</p>
        <p style="margin: 0 0 10px; color: #374151;">' . $data['message'] . '</p>
        ' . $codeHtml . '
        <p style="margin: 15px 0 0; color: #6b7280; font-size: 14px; line-height: 1.8;">' . $data['tips'] . '</p>
      </div>
    </td></tr>
    <tr><td style="background: #f9fafb; padding: 25px 30px; text-align: center; border-top: 1px solid #f3f4f6;">
      <div style="color: #9ca3af; font-size: 13px; line-height: 1.7;">
        此邮件由 Jay影视 系统自动发送，请勿直接回复。<br>
        © ' . date('Y') . ' Jay影视. All Rights Reserved.
      </div>
    </td></tr>
  </table>
</td></tr>
</table>
</body></html>';
    }
}
?>
