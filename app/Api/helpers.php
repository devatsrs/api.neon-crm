<?php
    function check_date_format_db($date){
        $datefomated = date('Y-m-d H:i:s',strtotime($date));
        if(date('Y', strtotime($datefomated)) == '1970'){
            throw new Exception('Invalid Date Format!!');
        }
        return $datefomated;
    }
    function uploaded_File_Handler($fileArray){
        $files_array = [];
        foreach($fileArray as $Index=>$file){
            $decoded_file = base64_decode($file['file']);
            $fileName = $file['fileName'];
            $tempPath = getenv('TEMP_PATH');
            $stamp = date_timestamp_get(date_create());
            $path = $tempPath.'/'.$stamp.$fileName;
            file_put_contents($path, $decoded_file);
            $files_array[] = ['fileName'=>$file['fileName'],
                                'file'=>$path,
                                'Extension'=>$file['fileExtension']];
        }
        return $files_array;
    }
    function rename_win($oldfile,$newfile) {
        //if (!rename($oldfile,$newfile)) {
            if (copy ($oldfile,$newfile)) {
                unlink($oldfile);
                return TRUE;
            }
            //return FALSE;
        //}
        return TRUE;
    }

    function sendMail($view,$data){
        $status = array('status' => 0, 'message' => 'Something wrong with sending mail.');
        $mandrill =0;
        if(isset($data['mandrill']) && $data['mandrill'] ==1){
            $mandrill = 1;
        }
        $mail = Helper::setMailConfig($data['CompanyID'],$mandrill);
        $mail->isHTML(true);
        if(isset($data['isHTML']) && $data['isHTML'] == 'false'){
            $mail->isHTML(false);
        }
        $body = htmlspecialchars_decode(View::make($view,compact('data'))->render());
        if(!is_array($data['EmailTo']) && strpos($data['EmailTo'],',') !== false){
            $data['EmailTo']  = explode(',',$data['EmailTo']);
        }
        if(is_array($data['EmailTo'])){
            foreach((array)$data['EmailTo'] as $email_address){
                $mail->addAddress(trim($email_address));
            }
        }else{
            $mail->addAddress(trim($data['EmailTo']),$data['EmailToName']);
        }
        if(isset($data['attach'])){
            $mail->addAttachment($data['attach']);
        }
        if(isset($data['EmailFrom'])){
            $mail->From = $data['EmailFrom'];
            if(isset($data['EmailFromName'])){
                $mail->FromName = $data['EmailFromName'];
            }
        }
        if(env('APP_ENV') != 'Production'){
            $data['Subject'] = 'Test Mail '.env('RMArtisanFileLocation').' '.$data['Subject'];
        }
        $mail->Body = $body;
        $mail->Subject = $data['Subject'];
        if (!$mail->send()) {
            $status['status'] = 0;
            $status['message'] .= $mail->ErrorInfo;
            $status['body'] = '';
            return $status;
        } else {
            $status['status'] = 1;
            $status['message'] = 'Email has been sent';
            $status['body'] = $body;
            return $status;
        }
    }
    function setMailConfig($CompanyID,$mandrill){
        $result = Company::select('SMTPServer','SMTPUsername','CompanyName','SMTPPassword','Port','IsSSL','EmailFrom')->where("CompanyID", '=', $CompanyID)->first();
        if($mandrill == 1) {
            Config::set('mail.host', getenv("MANDRILL_SMTP_SERVER"));
            Config::set('mail.port', getenv("MANDRILL_PORT"));
            Config::set('mail.from.address', $result->EmailFrom);
            Config::set('mail.from.name', $result->CompanyName);
            Config::set('mail.encryption', (getenv("MADRILL_SSL") == 1 ? 'SSL' : 'TLS'));
            Config::set('mail.username', getenv("MANDRILL_SMTP_USERNAME"));
            Config::set('mail.password', getenv("MANDRILL_SMTP_PASSWORD"));
        }else{
            Config::set('mail.host', $result->SMTPServer);
            Config::set('mail.port', $result->Port);
            Config::set('mail.from.address', $result->EmailFrom);
            Config::set('mail.from.name', $result->CompanyName);
            Config::set('mail.encryption', ($result->IsSSL == 1 ? 'SSL' : 'TLS'));
            Config::set('mail.username', $result->SMTPUsername);
            Config::set('mail.password', $result->SMTPPassword);
        }
        extract(Config::get('mail'));

        $mail = new \PHPMailer();
        //$mail->SMTPDebug = 3;                               // Enable verbose debug output
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = $host;  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = $username;                 // SMTP username

        $mail->Password = $password;                           // SMTP password
        $mail->SMTPSecure = $encryption;                            // Enable TLS encryption, `ssl` also accepted

        $mail->Port = $port;                                    // TCP port to connect to

        $mail->From = $from['address'];
        $mail->FromName = $from['name'];
        return $mail;

    }

    function email_log($data){
        $status = array('status' => 0, 'message' => 'Something wrong with Saving log.');
        if(!isset($data['EmailTo']) && empty($data['EmailTo'])){
            $status['message'] = 'Email To not set in Account mail log';
            return $status;
        }
        if(!isset($data['AccountID']) && empty($data['AccountID'])){
            $status['message'] = 'AccountID not set in Account mail log';
            return $status;
        }
        if(!isset($data['Subject']) && empty($data['Subject'])){
            $status['message'] = 'Subject not set in Account mail log';
            return $status;
        }
        if(!isset($data['Message']) && empty($data['Message'])){
            $status['message'] = 'Message not set in Account mail log';
            return $status;
        }

        if(is_array($data['EmailTo'])){
            $data['EmailTo'] = implode(',',$data['EmailTo']);
        }

        $logData = ['EmailFrom'=>\Api\Model\User::get_user_email(),
            'EmailTo'=>$data['EmailTo'],
            'Subject'=>$data['Subject'],
            'Message'=>$data['Message'],
            'AccountID'=>$data['AccountID'],
            'CompanyID'=>\Api\Model\User::get_companyID(),
            'UserID'=>\Api\Model\User::get_userID(),
            'CreatedBy'=>\Api\Model\User::get_user_full_name()];
        if(\Api\Model\AccountEmailLog::Create($logData)){
            $status['status'] = 1;
        }
        return $status;
    }
