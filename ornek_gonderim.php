<html>

<head>
  <title></title>
</head>

<body>

<?php

require 'smsexplorer_class.php';

$sms = new SMS("devtest","devtest");  // test kullan�c�s�. S�ras�yla kullan�c�_ad� ve �ifre  yaz�lacak i�ine
$sms->smsType = 1; // 1= Normal sms, 4= Flash sms
//$sms->originator = "xxxxxxx";

$sms->min_num_length = 10;   // Min GSM no karakter say�s� - varsay�lan: 10

$sms->msg_length_control = false; // alttaki 2 de�erin s�k� s�k�ya kontrol edilmesi i�in true yap�n�z
$sms->msg_min_length = 2;    // Girilmesi geren min karakter say�s� - varsay�lan: 2
$sms->msg_max_length = 160;  // Girilmesi geren max karakter say�s� - varsay�lan: 160

$mesaj = "Merhaba, dEnemE sms mesaj� at�yorum  www.abc.com      araya bo�luk";

$sonuc = $sms->sendSMS('05557778572', $mesaj);

switch ($sonuc) {
	case  "01": $sms_sonucu = "Kullan�c� hesap bilgileri yanl�� ($sonuc)"; $success = false; break;
	case  "02": $sms_sonucu = "Kredi yeterli de�il ($sonuc)"; $success = false; break;
	case  "04": $sms_sonucu = "Bilinmeyen SMS tipi ($sonuc)"; $success = false; break;
	case  "05": $sms_sonucu = "Hatal� G�nderen ID (Originator) se�imi yap�lm�� ($sonuc)"; $success = false; break;
	case  "06": $sms_sonucu = "Mesaj metni ya da numaralar girilmemi� ($sonuc)"; $success = false; break;
	case  "20": $sms_sonucu = "Bilinmeyen hata ($sonuc)"; $success = false; break;
	case  "22": $sms_sonucu = "Kullan�c� aktif de�il ($sonuc)"; $success = false; break;
	case  "71": $sms_sonucu = "GSM prefixi sistemimizde tan�ml� de�il. (�lke kodlar� 90 , 46, 41 gibi) ($sonuc)"; $success = false; break;
	case  "72": $sms_sonucu = "G�nderen Id tan�ml� de�il ($sonuc)"; $success = false; break;
	case  "74": $sms_sonucu = "Kullan�c� ya da kullan�lan ip engellenmi� ($sonuc)"; $success = false; break;
	case "997": $sms_sonucu = "G�nderilen SMS numaras� yanl�� veya eksik. En az $sms->min_num_length rakam olmal� "; $success = false; break;
	case "998": $sms_sonucu = "G�nderilen SMS mesaj� �ok k�sa. En az $sms->msg_min_length karakter olmal�"; $success = false; break;
	case "999": $sms_sonucu = "G�nderilen SMS mesaj� �ok uzun. �zin verilen maks $sms->msg_max_length karakter"; $success = false; break;
	default:    $sms_sonucu = "SMS g�nderimi ba�ar�l� ($sonuc)"; $success = true;
}
if($success == true) {
    // mesaj g�nderimi ba�ar�l� ise yap�lacaklar
	echo $sms_sonucu;
} else {
    // mesaj g�nderimi ba�ar�s�z ise yap�lacaklar
	echo $sms_sonucu;
}

echo '<br>Kalan SMS krediniz: '.$sms->getCredit();  // kalan SMS kredi bilgisini verir

echo '<br>G�nderilen SMS karakter boyutu: '.$sms->msg_length;

//echo '<br>SMS G�nderim Raporu:<br>'.$sms->getReportbyDate( '10012009', '10042009' );	// sDate (ba�lang�� tarih), eDate (biti� tarih) = ddmmyyyy  format�nda olacak
// 867193 905322211920 3 1 = ID  GSM-NO SMSDURUMU  MESAJTIPI
/*
SMSDURUMU A��klama
1  Operat�re teslim edildi
3  Ba�ar�l� olarak iletildi
5  �ptal olan ya da GSM NO format�na uymayan numaralar
6  ��lemde olan ve g�nderimi s�ren numaralar
9  Ge�erlilik s�resi boyunca iletilememi� ve zaman a��m�na u�ram��
*/

// Not: Tek bir g�nderimin raporunu almak i�in a�a��daki fonksiyon kullan�l�r. MSGID yerine 't�rnak i�inde g�nderim ID no yaz�l�r)
// $sms->getReportbyID(MSGID);
?>

</body>
</html>