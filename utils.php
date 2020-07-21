<?php

function getVerify($digits=5){
    return str_pad(rand(0, pow(10, $digits)-1), $digits, '0', STR_PAD_LEFT);
}

function generateRandomString($length = 10,$forceMixed=true) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return ($forceMixed===true && is_numeric($randomString))?generateRandomString($length,$forceMixed):$randomString;
}

function strstart($string, $query)
{
    return substr($string, 0, strlen($query)) === $query;
}

function optimiseFileName(&$fileName){
    if($fileName === null || strlen($fileName) < 5) return $fileName;
    $i_slash = strripos($fileName,'/');
    $i_dot = strripos($fileName, '.');
    $i_slash += $i_slash>0?1:0;
    $i_dot=$i_dot > 0 ? $i_dot : strlen($fileName);
    $i_dot -= $i_slash;
    $fileName = substr($fileName,$i_slash,$i_dot);
    return $fileName;
}

function getRequestHeaders() {
    return getallheaders();
    //return apache_request_headers();
    /*$headers = array();
    foreach($_SERVER as $key => $value) {
        if (substr($key, 0, 5) <> 'HTTP_') {
            continue;
        }
        $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
        $headers[$header] = $value;
    }
    return $headers;*/
}

function sendMail($from,$to,$subject,$htmlTitle,$htmlMsg){
// To send HTML mail, the Content-type header must be set
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

// Create email headers
    $headers .= 'From: '.$from."\r\n".
        'Reply-To: '.$from."\r\n" .
        'X-Mailer: PHP/' . phpversion();

// Compose a simple HTML email message
    $message = '<html lang=""><body>';
    $message .= '<div style="color:#000b63;">' .$htmlTitle.'</div>';
    $message .= '<div style="color:#00b7e8;font-size:18px;">' .$htmlMsg.'</div>';
    $message .= '</body></html>';

// Sending email
    return mail($to, $subject, $message, $headers);
}

function asArray($sql_num_rows,&$data){
    $tmp_0=[];
    if($sql_num_rows===1)$tmp_0[]=$data;
    elseif($sql_num_rows>1)$tmp_0=$data;
    $data=$tmp_0;
    return $tmp_0;
}
