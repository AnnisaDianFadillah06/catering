<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LaporanKasExport implements FromCollection, WithHeadings
{
  protected $data;

  public function __construct($data)
  {
    $this->data = $data;
  }

  public function collection()
  {
    return collect($this->data);
  }

  public function headings(): array
  {
    return [
      'Date',
      'Customer First Name',
      'Customer Last Name',
      'City',
      'Product Name',
      'Total Orders',
      'Subtotal',
    ];
  }
}
