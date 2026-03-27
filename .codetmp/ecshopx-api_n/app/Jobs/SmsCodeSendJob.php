<?php

namespace App\Jobs;

use App\Services\SmsService;
use EspierBundle\Jobs\Job;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class SmsCodeSendJob extends Job
{
    private $phones;
    private $contents;

    public function __construct($phones, $contents)
    {
        $this->phones = $phones;
        $this->contents = $contents;
    }

    public function handle()
    {
        foreach ($this->phones as $phone) {
            if (filter_var($phone, FILTER_VALIDATE_EMAIL)) {
                $ret = $this->sendEmail($phone, $this->contents);
                app('log')->info(sprintf('自定义服务发送邮件: 邮箱地址:%s,邮件内容: %s, 发送结果:%s', $phone, $this->contents, $ret));
            } else {
                $ret = $this->sendSms($phone, $this->contents);
                app('log')->info(sprintf('自定义服务发送短信: 短信地址:%s,短信内容: %s, 发送结果:%s', $phone, $this->contents, $ret));
            }
        }
    }

    private function sendSms($phone, $contents)
    {
        return SmsService::send($phone, $contents);
    }

    /**
     * @throws Exception
     */
    private function sendEmail($phone, $contents)
    {
        $mail = new PHPMailer();
        $mail->IsSMTP(); // Use SMTP
//        $mail->Debugoutput = 1;
        $mail->Hostname = config('mail.host');
//        $mail->Host = config('mail.host'); // Sets SMTP server
        $mail->Host = 'smtp.gmail.com'; // Sets SMTP server
        $mail->SMTPDebug = 4; // 2 to enable SMTP debug information
        $mail->SMTPAuth = true; // enable SMTP authentication
        $mail->SMTPSecure = "ssl"; //Secure conection
        $mail->Port = 465; // set the SMTP port
        $mail->Username = config('mail.username'); // SMTP account username
        $mail->Password = config('mail.password'); // SMTP account password
        $mail->Priority = 1; // Highest priority - Email priority (1 = High, 3 = Normal, 5 = low)
        $mail->CharSet = 'UTF-8';
        $mail->Subject = '智管家商城验证码';
        $mail->From = 'admin@smtengo.com';
        $mail->FromName = '智管家商城';
        $mail->addAddress($phone);
        $mail->WordWrap = 900;
        $mail->Timeout = 5;
        $mail->isHTML(TRUE);
        $mail->Body = $contents;
        $mail->Send();
        $mail->SmtpClose();
        return $mail->isError();
    }
}
