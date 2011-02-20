<?php
// Coded by snowbird 2009 Mar
// used www.smsexplorer.net XML API v1.1
// Published under GNU Public Licence - 2011

class SMS {
	var $username, $password, $companycode;
    var $originator;
    var $smsType = 1;
    var $min_num_length = 10;
    var $msg_length_control = false;  // alttaki 2 değerin kontrolünü yapalımmı
    var $msg_min_length = 2;
    var $msg_max_length = 160;
    var $msg_length;

	public function __construct($uname,$pword,$ccode = 100) {
		$this->username = $uname; // test için: devtest;
		$this->password = $pword; // test için: devtest;
		$this->companycode = $ccode;  // 100 sabit
    }

	protected function removeTRChar( $input ){
		//Türkçe karakterlerin çevrimi yapıp, büyük harfe çevrim işlemi		return strtoupper(strtr($input,'ğüşıöçĞÜŞİÖÇ','GUSIOCGUSIOC'));
	}

	public function getCredit(){		$creditXML = "<MainReportRoot><Command>6</Command><PlatformID>1</PlatformID><UserName>$this->username</UserName><ChannelCode>$this->companycode</ChannelCode><PassWord>$this->password</PassWord></MainReportRoot>";
        $credit = $this->sendXML( $creditXML );
        return number_format(substr($credit, 0, strpos($credit, "\n")),0,"","");
    }

	public function getReportbyID( $msgID ){		$reportXML = "<MainReportRoot><Command>3</Command><PlatformID>1</PlatformID><UserName>$this->username</UserName><ChannelCode>$this->companycode</ChannelCode><PassWord>$this->password</PassWord><MsgID>$msgID</MsgID></MainReportRoot>";
        return $this->sendXML( $reportXML );
    }

	public function getReportbyDate( $sDate, $eDate ){		// sDate, eDate = ddmmyyyy  formatında olacak
		$reportXML = "<MainReportRoot><Command>43</Command><PlatformID>1</PlatformID><UserName>$this->username</UserName><ChannelCode>$this->companycode</ChannelCode><PassWord>$this->password</PassWord><Sdate>$sDate</Sdate><Edate>$eDate</Edate></MainReportRoot>";
        return $this->sendXML( $reportXML );
			/*
			SMS Durumu  Açıklama
			1  Operatöre teslim edildi
			3  Başarılı olarak iletildi
			5  İptal olan ya da GSM NO formatına uymayan numaralar
			6  İşlemde olan ve gönderimi süren numaralar
			9  Geçerlilik süresi boyunca iletilememiş ve zaman aşımına uğramış

			RAPORLAMA ESANSINDA DÖNEN HATA DEĞERLERİ
			Hata Kodları   Açıklama
			01  UserName/PassWord (Kullanıcı Adı/Parola) yanlış girilmiş
			02  İstekte bulunan kullanıcıya(UserName) ait raporlanmak istenen ID bulunamadı
			03  ID girilmemiş ya da genel bir hata oluştu
			04  İşlem başarısız olmuş
			05  Talep edilen ID şuanda işleniyor(Yazılıyor,Gönderiliyor ya da Filtreleniyor).
			07  Telep edilen ID ye ait işlem (SMS gönderimi ) bulunamadı
			08  İşlem (SMS gönderimi )kullanıcı tarafından iptal edilmiş
			*/
    }

	public function sendSMS( $number, $msg, $sDate='', $eDate='' ){		// Type = 1 normal, Type=4 flash SMS
		// SDate, EDate: ddmmyyyyhhmm formatı

		$number = preg_replace('/\D/', null, $number); // boşlukları kaldıralım
		if (strlen($number) < $this->min_num_length) return 997;

        // Mesajı Türkçe karakterden arındırp, tek satır hale getirelim
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
		else if (ini_get('allow_url_fopen')) { // eğer curl çalışmıyorsa bunu deneyelim
            if (!$fp = @fsockopen('gw.maradit.net', 80, $errno, $errstr)) {
                    trigger_error('SMS gateway bağlantı hatası. Daha sonra tekrar deneyin', E_USER_ERROR);
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
		GÖNDERİM ESANSINDA DÖNEN DEĞERLER
		Hata Kodları  Açıklama
		01 UserName/PassWord (Kullanıcı Adı/Parola) yanlış girilmiş
		02 Kredi yeterli değil
		04 Bilinmeyen SMS tipi
		05 Hatalı Gönderen ID (Originator) seçimi yapılmış
		06 Mesaj metni ya da numaralar girilmemiş.
		09 Hatalı tarih formatı , tarih ddmmyyyyhhmm (gün-ay-yıl-saat-dakika) formatında olmalıdır

		Mesaj başarılı ise “ID: 3152005” gibi bir dönüş

		Hata Kodlari  Açıklama
		20 Bilinmeyen Hata.
		21 XML ifadesi ya da formatı hatalı.
		22 Kullanıcı aktif değil.
		71 GSM prefixi sistemimizde tanımlı değil. (Ülke kodları 90 , 46, 41 gibi)
		72 Gönderen Id tanımlı değil. (11 karakterlik gönderen başlığı)
		74 Kullanıcı ya da kullanılan ip engellenmiş

		997 Gönderilen SMS numarası yanlış veya eksik. En az $sms->min_num_length rakam olmalı
		998 Gönderilen SMS mesajı çok kısa. En az $sms->msg_min_length karakter olmalı
		999 Gönderilen SMS mesajı çok uzun. İzin verilen maks $sms->msg_max_length karakter
*/
	}
} //end class sms
?>

<?php
/*
http://www.smsexplorer.com/Shared/Dev/XMLAPI/MARADIT_XML_API.pdf
---------------------------------------------------------------------------------
<Command> Açıklama
0   SMStomany ( Aynı mesajın birçok farlı numaraya gönderilmesi)
1   SMSmultisenders (Farklı mesajların farklı numaralara gönderilmesi)
43  Reportbydate (Tarihi değerlendirerek raporlama)
3   ReportbyID ( SMS Id değerlendirilerek raporlama)
4   Canceljop (İleri bir tarihe gönderilmiş SMS paketin iptali
5   Checkdate (Sunucu tarihinin kontrol edilmesi)
6   Getcredit (Gönderen ID ve kredinin kontrol edilmesi)
---------------------------------------------------------------------------------
*/
?>