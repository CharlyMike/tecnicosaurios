<?php
# FerozoSite.com Form Email
# CopyRights reserved

# Validacion de Datos
function ValidarDatos($campo){
	//Array con las posibles cabeceras a utilizar por un spammer
	$badHeads = array("Content-Type:",
	"MIME-Version:",
	"Content-Transfer-Encoding:",
	"Return-path:",
	"Subject:",
	"From:",
	"Envelope-to:",
	"To:",
	"bcc:",
	"cc:");
	
	//Comprobamos que entre los datos no se encuentre alguna de
	//las cadenas del array. Si se encuentra alguna cadena se
	//dirige a una pï¿½ina de Forbidden
	foreach($badHeads as $valor){
		if(strpos(strtolower($campo), strtolower($valor)) !== false){
			return false;
		}
	}
	
	return true;
}

function ReemplazarHeader($campo){
	//Array con las posibles cabeceras a utilizar por un spammer
	$badHeads = array(
	array(0=>"[ \t]{0,3}Content-Type:[ \t]{1}",1=>" '*Content-Type:*' "),
	array(0=>"[ \t]{0,3}MIME-Version:[ \t]{1}",1=>" '*MIME-Version:*' "),
	array(0=>"[ \t]{0,3}Content-Transfer-Encoding:[ \t]{1}",1=>" '*Content-Transfer-Encoding:*' "),
	array(0=>"[ \t]{0,3}Return-path:[ \t]{1}",1=>" '*Return-path:*' "),
	array(0=>"[ \t]{0,3}Subject:[ \t]{1}",1=>" '*Subject:*' "),
	array(0=>"[ \t]{0,3}From:[ \t]{1}",1=>" '*From:*' "),
	array(0=>"[ \t]{0,3}Envelope-to:[ \t]{1}",1=>" '*Envelope-to:*' "),
	array(0=>"[ \t]{0,3}(T|t)o:[ \t]{1}",1=>" '*To:*' "),
	array(0=>"[ \t]{0,3}bcc:[ \t]{1}",1=>" '*bcc:*' "),
	array(0=>"[ \t]{0,3}cc:[ \t]{1}",1=>" '*cc:*' ")
	);
	
	//Comprobamos que entre los datos no se encuentre alguna de
	//las cadenas del array. Si se encuentra alguna cadena se
	//dirige a una pï¿½ina de Forbidden
	foreach($badHeads as $a_badheader){
		$campo = eregi_replace($a_badheader[0], $a_badheader[1], $campo);
	}
	
	return $campo;
}


function CodificarQP ($str) {
	global $eol;
	
	$encoded = ArreglarEOL($str);
	if (substr($encoded, -(strlen($eol))) != $eol)
		$encoded .= $eol;
	
	// Reemplazar cada caracter ascii alto, control e =
	$encoded = preg_replace('/([\000-\010\013\014\016-\037\075\177-\377])/e',
			"'='.sprintf('%02X', ord('\\1'))", $encoded);
	// Reemplazar cada espacio y tabulador cuando es el último caracter en una línea
	$encoded = preg_replace("/([\011\040])".$eol."/e",
			"'='.sprintf('%02X', ord('\\1')).'".$eol."'", $encoded);
	
	// Máximo largo de línea de 76 caracteres antes de retorno de carro y nueva línea (74 + space + '=')
	$encoded = AdaptarTexto($encoded, 74, true);
	
	return $encoded;
}


function ArreglarEOL($str) {
	global $eol;
	
	$str = str_replace("\r\n", "\n", $str);
	$str = str_replace("\r", "\n", $str);
	$str = str_replace("\n", $eol, $str);
	return $str;
}


function AdaptarTexto($message, $length, $qp_mode = false) {
	global $eol;

        $soft_break = ($qp_mode) ? sprintf(" =%s", $eol) : $eol;

        $message = ArreglarEOL($message);
        if (substr($message, -1) == $eol)
            $message = substr($message, 0, -1);

        $line = explode($eol, $message);
        $message = "";
        for ($i=0 ;$i < count($line); $i++)
        {
          $line_part = explode(" ", $line[$i]);
          $buf = "";
          for ($e = 0; $e<count($line_part); $e++)
          {
              $word = $line_part[$e];
              if ($qp_mode and (strlen($word) > $length))
              {
                $space_left = $length - strlen($buf) - 1;
                if ($e != 0)
                {
                    if ($space_left > 20)
                    {
                        $len = $space_left;
                        if (substr($word, $len - 1, 1) == "=")
                          $len--;
                        elseif (substr($word, $len - 2, 1) == "=")
                          $len -= 2;
                        $part = substr($word, 0, $len);
                        $word = substr($word, $len);
                        $buf .= " " . $part;
                        $message .= $buf . sprintf("=%s", $eol);
                    }
                    else
                    {
                        $message .= $buf . $soft_break;
                    }
                    $buf = "";
                }
                while (strlen($word) > 0)
                {
                    $len = $length;
                    if (substr($word, $len - 1, 1) == "=")
                        $len--;
                    elseif (substr($word, $len - 2, 1) == "=")
                        $len -= 2;
                    $part = substr($word, 0, $len);
                    $word = substr($word, $len);

                    if (strlen($word) > 0)
                        $message .= $part . sprintf("=%s", $eol);
                    else
                        $buf = $part;
                }
              }
              else
              {
                $buf_o = $buf;
                $buf .= ($e == 0) ? $word : (" " . $word); 

                if (strlen($buf) > $length and $buf_o != "")
                {
                    $message .= $buf_o . $soft_break;
                    $buf = $word;
                }
              }
          }
          $message .= $buf . $eol;
        }

        return $message;
}


$s_mailer_type = 'mail';

if (!file_exists('E:/php5/PhpCommon/fzo.mail.php') && $s_mailer_type == 'smtp') {
	$s_mailer_type = 'mail';
}

if ($s_mailer_type == 'smtp') {
	function _fzo_mail( $s_to, $s_subject, $s_message, $s_additional_headers='') {

		require_once("fzo.mail.php");

		global $eol;

		if (empty($eol)) {
			$eol = "\n";
		}

		$mail = new SMTP("localhost",'<!--%smtp_user%-->','<!--%smtp_pass%-->');

		$s_from = 'no-reply@tecnicosaurios.com';

		if (empty($s_from) || $s_from == ('<!--%' . 'email_from_address' . '%-->')) {
			$s_host = $_SERVER["HTTP_HOST"];
			$s_host = ereg_replace("www\.", "", $s_host);
			//$s_host = ereg_replace(":2085", "", $s_host);
			$s_from = "no-reply@" . $s_host;
		}

		$s_header = $mail->make_header(
					$s_from,
					$s_to,
					$s_subject,
					'3',
					'', 
					''
				);

		$s_additional_headers = trim($s_header) . $eol . $s_additional_headers;

		// Se envia el correo y se verifica el error
		$error = $mail->smtp_send($s_from, $s_to, $s_additional_headers, $s_message, '', '');
	
		if ($error == "0") {
			//echo "E-mail enviado correctamente";
			return true;
		}
		else {
			//echo "Error al enviar email: " . $error . "\n";
			return false;
		}
	
	}
}


# Is the OS Windows or Mac or Linux
if (strtoupper(substr(PHP_OS,0,3)=='WIN')) {
  $eol="\n";
} elseif (strtoupper(substr(PHP_OS,0,3)=='MAC')) {
  $eol="\r";
} else {
  $eol="\n";
}

$now = date("YmdHis");

$s_mailer_enabled = 'TRUE';

# To Email Address
$emailaddress="publicidad@tecnicosaurios.com";
$emailaddressfrom="no-reply@tecnicosaurios.com";
# Message Subject
$emailsubject="email de contacto desde ".$_SERVER['HTTP_HOST'];
# Message Body
$body_txt_prefix_data="";
$body_txt_separator_data=": ";
$body_txt_sufix_data=$eol;

$body_txt_prefix="Datos del Formulario:".$eol;
$body_txt_data="";
$body_txt_sufix=$eol."FerozoSite.com!";


$body_html_prefix_data = 
		"<tr>
			<td>";
$body_html_separator_data=": </td><td>";
$body_html_sufix_data="</td>
		</tr>
";

$body_html_prefix="<html>
<body>
	<table>
		<tr>
			<td colspan=\"2\">Datos del Formulario:</td>
		</tr>";
$body_html_data="";
$body_html_sufix="		<tr>
			<td colspan=\"2\"><br/>FerozoSite.com!</td>
		</tr>
</table>
</body>
</html>";

# Get Data
foreach($_POST as $s_name => $s_value) {
	if (strtolower($s_name) != 'submit') {
		$s_name = ucwords(str_replace("_"," ",$s_name));
		$s_value = stripslashes ($s_value);
		$body_txt_data.=$body_txt_prefix_data.$s_name.$body_txt_separator_data.$s_value.$body_txt_sufix_data;
		$body_html_data.=$body_html_prefix_data.$s_name.$body_html_separator_data.$s_value.$body_html_sufix_data;
	}
}

# Merge Data
$body_txt=$body_txt_prefix.$body_txt_data.$body_txt_sufix;
$body_html=$body_html_prefix.$body_html_data.$body_html_sufix;

$body_txt = ReemplazarHeader($body_txt);
$body_html = ReemplazarHeader($body_html);

# Common Headers
$headers .= 'From: '.$emailaddressfrom.$eol;
$headers .= 'Reply-To: '.$emailaddress.$eol;
$headers .= 'Return-Path: '.$emailaddress.$eol;    // these two to set reply address
$headers .= "X-Mailer: PHP v".phpversion().$eol;   // These two to help avoid spam-filters
# Boundry for marking the split & Multitype Headers
$mime_boundary=md5(time());
$headers .= 'MIME-Version: 1.0'.$eol;
# Setup for text OR html
$headers .= "Content-Type: multipart/alternative; boundary=\"".$mime_boundary."\"".$eol;
$headers .= "Message-ID: <".$now.".TheSystem@".$_SERVER['SERVER_NAME'].">".$eol;
$msg = "";
# Text Version
$msg .= "--".$mime_boundary.$eol;
$msg .= "Content-Type: text/plain; charset=iso-8859-1".$eol;
$msg .= "Content-Transfer-Encoding: quoted-printable".$eol.$eol;
$msg .= CodificarQP($body_txt).$eol;
# HTML Version
$msg .= "--".$mime_boundary.$eol;
$msg .= "Content-Type: text/html; charset=iso-8859-1".$eol;
$msg .= "Content-Transfer-Encoding: quoted-printable".$eol;
$msg .= CodificarQP("<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 3.2//EN\">").$eol;
$msg .= CodificarQP($body_html).$eol.$eol;

/*
# Setup for attachment
$msg .= "Content-Type: multipart/related".$eol;

# Attachment
$msg .= "--".$mime_boundary.$eol;
$msg .= "Content-Type: application/pdf; name=\"".$letter."\"".$eol;  // sometimes i have to send MS Word, use 'msword' instead of 'pdf'
$msg .= "Content-Transfer-Encoding: base64".$eol;
$msg .= "Content-Disposition: attachment; filename=\"".$letter."\"".$eol.$eol; // !! This line needs TWO end of lines !! IMPORTANT !!
$msg .= $f_contents.$eol.$eol;
*/

# Finished
$msg .= "--".$mime_boundary."--".$eol.$eol;  // finish with two eol's for better security. see Injection.

//Ejemplo de llamadas a la funcion
if(ValidarDatos($emailaddress) && ValidarDatos($emailsubject) /*&& ValidarDatos($body_txt) && ValidarDatos($body_html)*/){
	# SEND THE EMAIL
	if ($s_mailer_enabled == 'TRUE') {
		if ($s_mailer_type=='smtp') {
			$resp = _fzo_mail($emailaddress, $emailsubject, $msg, $headers);
		}
		else {
			$resp = mail($emailaddress, $emailsubject, $msg, $headers);
		}
	}
	else {
		$resp = false;
	}
}
else {
	$resp = false;
}


if($resp) {
	$s_location = "fs_ok_form.html";
}
else {
	$s_location = "fs_error_form.html";
}

header("Location: ./$s_location");

?>