<?php
// Coded by snowbird 2009 Mar
// used www.smsexplorer.net XML API v1.1
// Published under GNU Public Licence - 2011

class SMS {
	var $username, $password, $companycode;
    var $originator;
    var $smsType = 1;
    var $min_num_length = 10;
    var $msg_length_control = false;  // alttaki 2 deðerin kontrolünü yapalýmmý
    var $msg_min_length = 2;
    var $msg_max_length = 160;
    var $msg_length;

	public function __construct($uname,$pword,$ccode = 100) {
		$this->username = $uname; // test için: devtest;
		$this->password = $pword; // test için: devtest;
		$this->companycode = $ccode;  // 100 sabit
    }

	protected function removeTRChar( $input ){
		//Türkçe karakterlerin çevrimi yapýp, büyük harfe çevrim iþlemi		return strtoupper(strtr($input,'ðüþýöçÐÜÞÝÖÇ','GUSIOCGUSIOC'));
	}

	public function getCredit(){		$creditXML = "<MainReportRoot><Command>6</Command><PlatformID>1</PlatformID><UserName>$this->username</UserName><ChannelCode>$this->companycode</ChannelCode><PassWord>$this->password</PassWord></MainReportRoot>";
        $credit = $this->sendXML( $creditXML );
        return number_format(substr($credit, 0, strpos($credit, "\n")),0,"","");
    }

	public function getReportbyID( $msgID ){		$reportXML = "<MainReportRoot><Command>3</Command><PlatformID>1</PlatformID><UserName>$this->username</UserName><ChannelCode>$this->companycode</ChannelCode><PassWord>$this->password</PassWord><MsgID>$msgID</MsgID></MainReportRoot>";
        return $this->sendXML( $reportXML );
    }

	public function getReportbyDate( $sDate, $eDate ){		// sDate, eDate = ddmmyyyy  formatýnda olacak
		$reportXML = "<MainReportRoot><Command>43</Command><PlatformID>1</PlatformID><UserName>$this->username</UserName><ChannelCode>$this->companycode</ChannelCode><PassWord>$this->password</PassWord><Sdate>$sDate</Sdate><Edate>$eDate</Edate></MainReportRoot>";
        return $this->sendXML( $reportXML );
			/*
			SMS Durumu  Açýklama
			1  Operatöre teslim edildi
			3  Baþarýlý olarak iletildi
			5  Ýptal olan ya da GSM NO formatýna uymayan numaralar
			6  Ýþlemde olan ve gönderimi süren numaralar
			9  Geçerlilik süresi boyunca iletilememiþ ve zaman aþýmýna uðramýþ

			RAPORLAMA ESANSINDA DÖNEN HATA DEÐERLERÝ
			Hata Kodlarý   Açýklama
			01  UserName/PassWord (Kullanýcý Adý/Parola) yanlýþ girilmiþ
			02  Ýstekte bulunan kullanýcýya(UserName) ait raporlanmak istenen ID bulunamadý
			03  ID girilmemiþ ya da genel bir hata oluþtu
			04  Ýþlem baþarýsýz olmuþ
			05  Talep edilen ID þuanda iþleniyor(Yazýlýyor,Gönderiliyor ya da Filtreleniyor).
			07  Telep edilen ID ye ait iþlem (SMS gönderimi ) bulunamadý
			08  Ýþlem (SMS gönderimi )kullanýcý tarafýndan iptal edilmiþ
			*/
    }

	public function sendSMS( $number, $msg, $sDate='', $eDate='' ){		// Type = 1 normal, Type=4 flash SMS
		// SDate, EDate: ddmmyyyyhhmm formatý

		$number = preg_replace('/\D/', null, $number); // boþluklarý kaldýralým
		if (strlen($number) < $this->min_num_length) return 997;

        // Mesajý Türkçe karakterden arýndýrp, tek satýr hale getirelim
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
		else if (ini_get('allow_url_fopen')) { // eðer curl çalýþmýyorsa bunu deneyelim
            if (!$fp = @fsockopen('gw.maradit.net', 80, $errno, $errstr)) {
                    trigger_error('SMS gateway baðlantý hatasý. Daha sonra tekrar deneyin', E_USER_ERROR);
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
		GÖNDERÝM ESANSINDA DÖNEN DEÐERLER
		Hata Kodlarý  Açýklama
		01 UserName/PassWord (Kullanýcý Adý/Parola) yanlýþ girilmiþ
		02 Kredi yeterli deðil
		04 Bilinmeyen SMS tipi
		05 Hatalý Gönderen ID (Originator) seçimi yapýlmýþ
		06 Mesaj metni ya da numaralar girilmemiþ.
		09 Hatalý tarih formatý , tarih ddmmyyyyhhmm (gün-ay-yýl-saat-dakika) formatýnda olmalýdýr

		Mesaj baþarýlý ise “ID: 3152005” gibi bir dönüþ

		Hata Kodlari  Açýklama
		20 Bilinmeyen Hata.
		21 XML ifadesi ya da formatý hatalý.
		22 Kullanýcý aktif deðil.
		71 GSM prefixi sistemimizde tanýmlý deðil. (Ülke kodlarý 90 , 46, 41 gibi)
		72 Gönderen Id tanýmlý deðil. (11 karakterlik gönderen baþlýðý)
		74 Kullanýcý ya da kullanýlan ip engellenmiþ

		997 Gönderilen SMS numarasý yanlýþ veya eksik. En az $sms->min_num_length rakam olmalý
		998 Gönderilen SMS mesajý çok kýsa. En az $sms->msg_min_length karakter olmalý
		999 Gönderilen SMS mesajý çok uzun. Ýzin verilen maks $sms->msg_max_length karakter
*/
	}
} //end class sms
?>

<?php
/*
http://www.smsexplorer.com/Shared/Dev/XMLAPI/MARADIT_XML_API.pdf
---------------------------------------------------------------------------------
<Command> Açýklama
0   SMStomany ( Ayný mesajýn birçok farlý numaraya gönderilmesi)
1   SMSmultisenders (Farklý mesajlarýn farklý numaralara gönderilmesi)
43  Reportbydate (Tarihi deðerlendirerek raporlama)
3   ReportbyID ( SMS Id deðerlendirilerek raporlama)
4   Canceljop (Ýleri bir tarihe gönderilmiþ SMS paketin iptali
5   Checkdate (Sunucu tarihinin kontrol edilmesi)
6   Getcredit (Gönderen ID ve kredinin kontrol edilmesi)
---------------------------------------------------------------------------------
*/
?>