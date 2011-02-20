<?php
// Coded by snowbird 2009 Mar
// used www.smsexplorer.net XML API v1.1
// Published under GNU Public Licence - 2011

class SMS {
	var $username, $password, $companycode;
    var $originator;
    var $smsType = 1;
    var $min_num_length = 10;
    var $msg_length_control = false;  // alttaki 2 de�erin kontrol�n� yapal�mm�
    var $msg_min_length = 2;
    var $msg_max_length = 160;
    var $msg_length;

	public function __construct($uname,$pword,$ccode = 100) {
		$this->username = $uname; // test i�in: devtest;
		$this->password = $pword; // test i�in: devtest;
		$this->companycode = $ccode;  // 100 sabit
    }

	protected function removeTRChar( $input ){
		//T�rk�e karakterlerin �evrimi yap�p, b�y�k harfe �evrim i�lemi		return strtoupper(strtr($input,'������������','GUSIOCGUSIOC'));
	}

	public function getCredit(){		$creditXML = "<MainReportRoot><Command>6</Command><PlatformID>1</PlatformID><UserName>$this->username</UserName><ChannelCode>$this->companycode</ChannelCode><PassWord>$this->password</PassWord></MainReportRoot>";
        $credit = $this->sendXML( $creditXML );
        return number_format(substr($credit, 0, strpos($credit, "\n")),0,"","");
    }

	public function getReportbyID( $msgID ){		$reportXML = "<MainReportRoot><Command>3</Command><PlatformID>1</PlatformID><UserName>$this->username</UserName><ChannelCode>$this->companycode</ChannelCode><PassWord>$this->password</PassWord><MsgID>$msgID</MsgID></MainReportRoot>";
        return $this->sendXML( $reportXML );
    }

	public function getReportbyDate( $sDate, $eDate ){		// sDate, eDate = ddmmyyyy  format�nda olacak
		$reportXML = "<MainReportRoot><Command>43</Command><PlatformID>1</PlatformID><UserName>$this->username</UserName><ChannelCode>$this->companycode</ChannelCode><PassWord>$this->password</PassWord><Sdate>$sDate</Sdate><Edate>$eDate</Edate></MainReportRoot>";
        return $this->sendXML( $reportXML );
			/*
			SMS Durumu  A��klama
			1  Operat�re teslim edildi
			3  Ba�ar�l� olarak iletildi
			5  �ptal olan ya da GSM NO format�na uymayan numaralar
			6  ��lemde olan ve g�nderimi s�ren numaralar
			9  Ge�erlilik s�resi boyunca iletilememi� ve zaman a��m�na u�ram��

			RAPORLAMA ESANSINDA D�NEN HATA DE�ERLER�
			Hata Kodlar�   A��klama
			01  UserName/PassWord (Kullan�c� Ad�/Parola) yanl�� girilmi�
			02  �stekte bulunan kullan�c�ya(UserName) ait raporlanmak istenen ID bulunamad�
			03  ID girilmemi� ya da genel bir hata olu�tu
			04  ��lem ba�ar�s�z olmu�
			05  Talep edilen ID �uanda i�leniyor(Yaz�l�yor,G�nderiliyor ya da Filtreleniyor).
			07  Telep edilen ID ye ait i�lem (SMS g�nderimi ) bulunamad�
			08  ��lem (SMS g�nderimi )kullan�c� taraf�ndan iptal edilmi�
			*/
    }

	public function sendSMS( $number, $msg, $sDate='', $eDate='' ){		// Type = 1 normal, Type=4 flash SMS
		// SDate, EDate: ddmmyyyyhhmm format�

		$number = preg_replace('/\D/', null, $number); // bo�luklar� kald�ral�m
		if (strlen($number) < $this->min_num_length) return 997;

        // Mesaj� T�rk�e karakterden ar�nd�rp, tek sat�r hale getirelim
		$msg = $this->removeTRChar( $msg );
        $msg = preg_replace('/[\r\n\t]+/', ' ', trim(strval( $msg )));
        $msg = preg_replace('/\s{2,}/', ' ', $msg);

		$this->msg_length = strlen($msg);
        if ($this->msg_length < $this->msg_min_length and $this->msg_length_control == true) return 998;
        if ($this->msg_length > $this->msg_max_length and $this->msg_length_control == true) return 999;

		$msgXML= "<MainmsgBody>
					<Command>0</Command>
					<PlatformID>1</PlatformID>
					<UserName>$this->username</UserName>
					<PassWord>$this->password</PassWord>
					<ChannelCode>$this->companycode</ChannelCode>
					<Mesgbody>$msg</Mesgbody>
					<Numbers>$number</Numbers>
					<Type>$this->smsType</Type>
					<Originator>$this->originator</Originator>
					<SDate></SDate>
					<EDate></EDate>
				</MainmsgBody>";
		return $this->sendXML( $msgXML );
	}

	private function sendXML( $xml ){
		if (function_exists('curl_init') and $ch = @curl_init('http://gw.maradit.net/default.aspx')) {
            curl_setopt($ch, CURLOPT_POST, true);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: text/xml'));
	        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
	        $result = curl_exec($ch);
	        curl_close($ch);
		}
		else if (ini_get('allow_url_fopen')) { // e�er curl �al��m�yorsa bunu deneyelim
            if (!$fp = @fsockopen('gw.maradit.net', 80, $errno, $errstr)) {
                    trigger_error('SMS gateway ba�lant� hatas�. Daha sonra tekrar deneyin', E_USER_ERROR);
                    return 20;
            }

            $header  = "POST / HTTP/1.1\r\n";
            $header .= "Host: gw.maradit.net\r\n";
            $header .= "User-Agent: HTTP/1.1\r\n";
            $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
            $header .= "Content-Length: " . strlen( $xml ) . "\r\n";
            $header .= "Connection: close\r\n\r\n";
            $header .= "{$xml}\r\n";

            fputs($fp, $header);
            $result_ = array();

            while(!feof($fp))
            {
                    $result_[] = fgets($fp);
            }

            fclose($fp);
            $result = $result_[8];
  		}
    	else { trigger_error('Server does not support HTTP(S) requests.', E_USER_ERROR); return 20; }
        return ($result);

/*
		G�NDER�M ESANSINDA D�NEN DE�ERLER
		Hata Kodlar�  A��klama
		01 UserName/PassWord (Kullan�c� Ad�/Parola) yanl�� girilmi�
		02 Kredi yeterli de�il
		04 Bilinmeyen SMS tipi
		05 Hatal� G�nderen ID (Originator) se�imi yap�lm��
		06 Mesaj metni ya da numaralar girilmemi�.
		09 Hatal� tarih format� , tarih ddmmyyyyhhmm (g�n-ay-y�l-saat-dakika) format�nda olmal�d�r

		Mesaj ba�ar�l� ise �ID: 3152005� gibi bir d�n��

		Hata Kodlari  A��klama
		20 Bilinmeyen Hata.
		21 XML ifadesi ya da format� hatal�.
		22 Kullan�c� aktif de�il.
		71 GSM prefixi sistemimizde tan�ml� de�il. (�lke kodlar� 90 , 46, 41 gibi)
		72 G�nderen Id tan�ml� de�il. (11 karakterlik g�nderen ba�l���)
		74 Kullan�c� ya da kullan�lan ip engellenmi�

		997 G�nderilen SMS numaras� yanl�� veya eksik. En az $sms->min_num_length rakam olmal�
		998 G�nderilen SMS mesaj� �ok k�sa. En az $sms->msg_min_length karakter olmal�
		999 G�nderilen SMS mesaj� �ok uzun. �zin verilen maks $sms->msg_max_length karakter
*/
	}
} //end class sms
?>

<?php
/*
http://www.smsexplorer.com/Shared/Dev/XMLAPI/MARADIT_XML_API.pdf
---------------------------------------------------------------------------------
<Command> A��klama
0   SMStomany ( Ayn� mesaj�n bir�ok farl� numaraya g�nderilmesi)
1   SMSmultisenders (Farkl� mesajlar�n farkl� numaralara g�nderilmesi)
43  Reportbydate (Tarihi de�erlendirerek raporlama)
3   ReportbyID ( SMS Id de�erlendirilerek raporlama)
4   Canceljop (�leri bir tarihe g�nderilmi� SMS paketin iptali
5   Checkdate (Sunucu tarihinin kontrol edilmesi)
6   Getcredit (G�nderen ID ve kredinin kontrol edilmesi)
---------------------------------------------------------------------------------
*/
?>