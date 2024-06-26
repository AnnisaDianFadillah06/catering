<?php

namespace App\Helpers;

class WhatsAppHelper
{
  public static function generateWhatsAppMessage($link)
  {
    $message = "Halo,\n\n" .
      "Terima kasih telah melakukan pemesanan di website kami. Kami ingin menginformasikan bahwa pesanan Anda telah dikonfirmasi oleh Admin. Silakan menuju halaman berikut untuk melakukan proses pembayaran:\n\n" .
      "$link\n\n" .
      "Jika Anda memiliki pertanyaan atau membutuhkan bantuan lebih lanjut, jangan ragu untuk menghubungi kami.\n\n" .
      "Terima kasih!";

    return $message;
  }

  public static function formatPhoneNumber($phoneNumber)
  {
    // Menghapus semua karakter selain angka
    $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

    // Jika nomor dimulai dengan 0, ganti dengan 62
    if (substr($phoneNumber, 0, 1) === '0') {
      $phoneNumber = '62' . substr($phoneNumber, 1);
    }

    // Jika nomor sudah dimulai dengan 62, biarkan
    if (substr($phoneNumber, 0, 2) === '62') {
      return $phoneNumber;
    }

    // Jika nomor dimulai dengan +62, ganti dengan 62
    if (substr($phoneNumber, 0, 3) === '+62') {
      return substr($phoneNumber, 1);
    }

    // Jika nomor tidak sesuai dengan format apapun, kembalikan sebagai nomor asli
    return $phoneNumber;
  }

  public static function generateWhatsAppLink($link, $phoneNumber)
  {
    $formattedPhoneNumber = self::formatPhoneNumber($phoneNumber);
    $message = self::generateWhatsAppMessage($link);
    $encodedMessage = urlencode($message);
    return "https://wa.me/{$formattedPhoneNumber}?text={$encodedMessage}";
  }
}
