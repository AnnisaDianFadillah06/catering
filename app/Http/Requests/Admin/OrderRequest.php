<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class OrderRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   *
   * @return bool
   */
  public function authorize()
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array
   */
  public function rules()
  {
    switch ($this->method()) {

      case 'PUT':
      case 'PATCH': {
          return [
            'status' => ['required', 'max:255'],
            'base_total_price' => ['numeric'],
            'shipping_cost' => ['required', 'numeric'],
            'grand_total' => ['numeric'],
          ];
        }
      default:
        break;
    }
  }
}
