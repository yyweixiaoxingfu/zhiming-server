<?php
namespace app\message\library\driver;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use think\Exception;

/**
 * 发送邮件
 */
class Email extends MessageAbstract
{
    public function setConfig()
    {
        $config = config('message.email');
        if(empty($config)){
            throw new Exception('请先配置邮件发送参数');
        }
        $this->config = $config;
    }

    /**
     * 发送邮件
     * @param $toEmail 收件人邮箱
     * @param $template 模板
     * @param $params 模板参数
     * @param $options 其他配置
     * @return bool
     */
    public function send($toEmail,$template,$params = [],$options = []){
        $config = $this->getConfig();
        $title = $template['title'];
        $content = $template['template'];
        foreach ($params as $key => $value){
            $title = str_replace("{".$key."}", $value, $title);
            $content = str_replace("{".$key."}", $value, $content);
        }
        $mail = new PHPMailer(true);
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $config['port'];
        $mail->CharSet = "utf-8";
        try {
            $mail->setFrom($config['fromEmail'], $config['fromUserName']); // 设置发件人
            $mail->addAddress($toEmail);     // 添加收件人
            // 邮件内容
            $mail->isHTML(true);
            $mail->Subject = $title;
            $mail->Body    = $content;
            $mail->send();
            return true;
        } catch (\Exception $e) {
            $this->error = $mail->ErrorInfo;
            return false;
        }
    }


}