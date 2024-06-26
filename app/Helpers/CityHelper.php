<?php

namespace App\Helpers;

class CityHelper
{
  public static function getCityName($code)
  {
    $cities = [
      'jaktim' => 'Jakarta Timur',
      'jakbar' => 'Jakarta Barat',
      'jakpus' => 'Jakarta Pusat',
      'jaksel' => 'Jakarta Selatan',
      'jakut' => 'Jakarta Utara',
    ];

    return $cities[$code] ?? 'Unknown City';
  }
}
