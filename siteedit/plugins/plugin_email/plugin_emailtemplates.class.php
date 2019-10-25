<?php

//require_once dirname(__FILE__).'/../plugin_macros/plugin_macros.class.php';
//require_once dirname(__FILE__).'/../plugin_mail/plugin_mail.class.php';
//require_once dirname(__FILE__).'/../plugin_jmail/plugin_jmail.class.php';
//require_once dirname(__FILE__).'/../plugin_sms/plugin_sms.class.php';
//require_once dirname(__FILE__).'/../plugin_qtsms/plugin_qtsms.class.php';

class plugin_emailtemplates
{

    private $typpost;
    private $order_id;
    private $payment_id;
    private $user_id = 0;
    private $email_from = '';
    private $sms_from = '';
    private $sms_sender = '';



    public function __construct($email_from = '', $typpost = "html")
    {
        $app = App::get();
        $this->email_from = ($email_from) ? $email_from : $app['main']['email'];
        $this->sms_from = $app['main']['smsPhone'];
        $this->sms_sender = $app['main']['smsSender'];
        $this->typpost = $typpost;

    }

    public function sendmail($emailcode, $array_change = array(), $userId = 0,  $filename = '')
    {
        //if (empty($this->email_from))
        //    return false;
        $mimetype = "text/{$this->typpost}";
        $auth = new auth();

        $phone_to = str_replace(',', ';', $this->sms_from);

        if (empty($userId)) {
            $email_to = str_replace(',', ';', $this->email_from);
            $phone_to = str_replace(',', ';', $this->sms_from);
        } else {
            $auth = new DB('se_user');
            $auth->select('email, phone, person_name, username');
            $user = $auth->find($userId);
            if (!empty($user['phone']) && $user['phoneConfirm'])
                $phone_to = str_replace(',', ';', $user['phone']);
            if (!empty($user['email']))
                $email_to = str_replace(',', ';', $user['email']);

        }
        $array_change['username']= $user['username'];
        $array_change['personname']= $user['personName'];
        $array_change['email'] = $user['email'];
        $array_change['phone'] = $user['phone'];


        $smail = new DB('se_email_template');
        //$smail->where("`lang`='?'", $this->lang);
        $smail->where("`mailtype`='?'", $emailcode);
        $res = $smail->fetchOne();

        if (!empty($res)) {
            if ($this->typpost != 'html')
                $res['letter'] = str_replace("<br>", "\r\n", $res['letter']);
            else {
                $res['letter'] = str_replace("\r", "", $res['letter']);
                if (strpos($res['letter'], '<') !== false && strpos($res['letter'], '>') !== false) {
                    $res['letter'] = str_replace("\n", "", $res['letter']);
                } else {
                    $res['letter'] = str_replace("\n", "<br>", $res['letter']);
                }
            }
            $res['letter'] = str_replace(array('&quot;', '&amp;', '&#039;', "&lt;", "&gt;"), array('"', '&', "'", "<", ">"), $res['letter']);
            $res['subject'] = str_replace(array('&quot;', '&amp;', '&#039;', "&lt;", "&gt;"), array('"', '&', "'", "<", ">"), $res['subject']);
            foreach ($array_change as $k => $v) {
                $res['letter'] = str_replace("[" . strtoupper($k) . "]", $v, $res['letter']);
                $res['subject'] = str_replace("[" . strtoupper($k) . "]", $v, $res['subject']);
            }

            list($email_from,) = preg_split("/[\s,;]+/", $this->email_from);
            //if ($email_from == $email_to || (strpos($email_from, "@mail.ru")))
            $email_from = 'noreply@' . $_SERVER['HTTP_HOST'];
            if (empty($host))
                $host = (isset($_SERVER['HTTP_HOST'])) ? $_SERVER['HTTP_HOST'] : 'siteedit.ru';

            $from = "=?utf-8?b?" . base64_encode($res['subject']) . "?=  " . $host . " <" . $email_from . '>';
            $emaillist = explode(';', $email_to);
            $result = true;
            foreach ($emaillist as $email_to) {
                $mimetype = "text/{$this->typpost}";
                $mailsend = new plugin_email($res['subject'], $email_to, $from);
                $mailsend->addtext($res['letter'], $mimetype);
                if (!empty($filename)){
                    $silelist = explode(';', $filename);
                    foreach($silelist as $file){
                        $file = trim($file);
                        if (empty($file)) continue;
                        $mailsend->attach($file, '', $mimetype);
                    }
                }
                if (!$mailsend->send()) {
                    $result = false;
                }
                unset($mailsend);
            }
        }
        /*

        // SMS send
        $provider = new seTable('sms_providers', 'sp');
        $provider->where('is_active');
        $provider->fetchOne();
        if ($provider->isFind()) {
            $template = new seTable('sms_templates');
            $template->where("is_active AND `code` = '?'", $mailtype);
            $template->fetchOne();
            if ($template->isFind()) {
                $sender = empty($template->sender) ? $sender : $template->sender;
                $phone_to = empty($template->phone) ? $phone_to : $template->phone;
                $text = strip_tags($macros->execute($template->text));
                $text = str_replace("&nbsp;", "", $text);
                $phones_list = explode(';', $phone_to);
                foreach ($phones_list as $phone_to)
                    $this->smsSend($provider, $phone_to, $text, $sender);
            }
        }
        */
        return $result;
    }

    private function smsSend($provider, $phoneTo, $text, $sender)
    {
        $phoneTo = preg_replace('/[^0-9]/', '', $phoneTo);
        $log = new seTable('sms_log');
        $log->date = date("Y-m-d H:i:s");
        $log->text = $text;
        $log->phone = $phoneTo;
        $log->id_provider = $provider->id;
        $log->id_user = $this->user_id;
        if ($provider->name == "sms.ru") {
            $settings = json_decode($provider->settings, true);
            $sms = new plugin_sms($settings["api_id"]["value"]);
            $response = $sms->sms_send($phoneTo, $text, $sender);
            $log->id_sms = $response["ids"][0];
            $log->code = $response["code"];
            $log->status = $sms->response_code["status"][$log->code];
            $costInfo = $sms->sms_cost($phoneTo, $text);
            $log->cost = $costInfo["price"];
            $log->count = $costInfo["number"];
            $log->save();
        }
        if ($provider->name == "qtelecom.ru") {
            $settings = json_decode($provider->settings, true);
            $sms = new plugin_qtsms($settings["login"]["value"], $settings["password"]["value"]);
            $response = $sms->post_sms($text, $phoneTo, '', $sender);
            $response = $response["result"]["sms"]["@attributes"];
            $log->id_sms = $response["id"];
            $log->status = "Cообщение в очереди отправки";
            $log->cost = 0;
            $log->count = $response["sms_res_count"];
            $log->save();
        }
    }

    /*private function getFileOrderList()
    {
        $xlsFile = SE_ROOT . "/upload/orderlist_xls.php";
        if (!file_exists($xlsFile))
            return null;

        $uploadFile = false;
        $idOrder = $this->order_id;
        include_once $xlsFile;
        if ($uploadFile && file_exists($uploadFile))
            return $uploadFile;

    }
    */
}