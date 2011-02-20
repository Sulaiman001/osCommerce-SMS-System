<html>

<head>
  <title></title>
</head>

<body>

<?php

require 'smsexplorer_class.php';

$sms = new SMS("devtest","devtest");  // test kullanýcýsý. Sýrasýyla kullanýcý_adý ve þifre  yazýlacak içine
$sms->smsType = 1; // 1= Normal sms, 4= Flash sms
//$sms->originator = "xxxxxxx";

$sms->min_num_length = 10;   // Min GSM no karakter sayýsý - varsayýlan: 10

$sms->msg_length_control = false; // alttaki 2 deðerin sýký sýkýya kontrol edilmesi için true yapýnýz
$sms->msg_min_length = 2;    // Girilmesi geren min karakter sayýsý - varsayýlan: 2
$sms->msg_max_length = 160;  // Girilmesi geren max karakter sayýsý - varsayýlan: 160

$mesaj = "Merhaba, dEnemE sms mesajý atýyorum  www.abc.com      araya boþluk";

$sonuc = $sms->sendSMS('05557778572', $mesaj);

switch ($sonuc) {
	case  "01": $sms_sonucu = "Kullanýcý hesap bilgileri yanlýþ ($sonuc)"; $success = false; break;
	case  "02": $sms_sonucu = "Kredi yeterli deðil ($sonuc)"; $success = false; break;
	case  "04": $sms_sonucu = "Bilinmeyen SMS tipi ($sonuc)"; $success = false; break;
	case  "05": $sms_sonucu = "Hatalý Gönderen ID (Originator) seçimi yapýlmýþ ($sonuc)"; $success = false; break;
	case  "06": $sms_sonucu = "Mesaj metni ya da numaralar girilmemiþ ($sonuc)"; $success = false; break;
	case  "20": $sms_sonucu = "Bilinmeyen hata ($sonuc)"; $success = false; break;
	case  "22": $sms_sonucu = "Kullanýcý aktif deðil ($sonuc)"; $success = false; break;
	case  "71": $sms_sonucu = "GSM prefixi sistemimizde tanýmlý deðil. (Ülke kodlarý 90 , 46, 41 gibi) ($sonuc)"; $success = false; break;
	case  "72": $sms_sonucu = "Gönderen Id tanýmlý deðil ($sonuc)"; $success = false; break;
	case  "74": $sms_sonucu = "Kullanýcý ya da kullanýlan ip engellenmiþ ($sonuc)"; $success = false; break;
	case "997": $sms_sonucu = "Gönderilen SMS numarasý yanlýþ veya eksik. En az $sms->min_num_length rakam olmalý "; $success = false; break;
	case "998": $sms_sonucu = "Gönderilen SMS mesajý çok kýsa. En az $sms->msg_min_length karakter olmalý"; $success = false; break;
	case "999": $sms_sonucu = "Gönderilen SMS mesajý çok uzun. Ýzin verilen maks $sms->msg_max_length karakter"; $success = false; break;
	default:    $sms_sonucu = "SMS gönderimi baþarýlý ($sonuc)"; $success = true;
}
if($success == true) {
    // mesaj gönderimi baþarýlý ise yapýlacaklar
	echo $sms_sonucu;
} else {
    // mesaj gönderimi baþarýsýz ise yapýlacaklar
	echo $sms_sonucu;
}

echo '<br>Kalan SMS krediniz: '.$sms->getCredit();  // kalan SMS kredi bilgisini verir

echo '<br>Gönderilen SMS karakter boyutu: '.$sms->msg_length;

//echo '<br>SMS Gönderim Raporu:<br>'.$sms->getReportbyDate( '10012009', '10042009' );	// sDate (baþlangýç tarih), eDate (bitiþ tarih) = ddmmyyyy  formatýnda olacak
// 867193 905322211920 3 1 = ID  GSM-NO SMSDURUMU  MESAJTIPI
/*
SMSDURUMU Açýklama
1  Operatöre teslim edildi
3  Baþarýlý olarak iletildi
5  Ýptal olan ya da GSM NO formatýna uymayan numaralar
6  Ýþlemde olan ve gönderimi süren numaralar
9  Geçerlilik süresi boyunca iletilememiþ ve zaman aþýmýna uðramýþ
*/

// Not: Tek bir gönderimin raporunu almak için aþaðýdaki fonksiyon kullanýlýr. MSGID yerine 'týrnak içinde gönderim ID no yazýlýr)
// $sms->getReportbyID(MSGID);
?>

</body>
</html>