<?php

namespace App\Models;

use App\Helpers\General;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
	use HasFactory;

	protected $guarded = ['id', 'created_at', 'updated_at'];

	public const ORDERCODE = 'INV';

	public const PAID = 'paid';
	public const UNPAID = 'unpaid';
	public const PENDING = 'pending';
	public const FAILURE = 'failure';

	public const CREATED = 'created';
	public const CONFIRMED = 'confirmed';
	public const DELIVERED = 'delivered';
	public const COMPLETED = 'completed';
	public const CANCELLED = 'cancelled';

	public const STATUSES = [
		self::CREATED => 'Created',
		self::CONFIRMED => 'Confirmed',
		self::DELIVERED => 'Delivered',
		self::COMPLETED => 'Completed',
		self::CANCELLED => 'Cancelled',
	];

	/**
	 * Generate order code
	 *
	 * @return string
	 */
	public static function generateCode()
	{
		$dateCode = 'INV' . date('dmY'); // Mengubah format tanggal menjadi DMYY

		// Ambil order terakhir berdasarkan kode yang sesuai dengan format baru
		$lastOrder = self::select([\DB::raw('MAX(orders.code) AS last_code')])
			->where('code', 'like', $dateCode . '%')
			->first();

		// Dapatkan kode order terakhir jika ada
		$lastOrderCode = !empty($lastOrder) ? $lastOrder['last_code'] : null;

		// Default order code jika tidak ada order sebelumnya
		$orderCode = $dateCode . '00001';
		if ($lastOrderCode) {
			// Ambil bagian terakhir dari order code sebelumnya dan tambahkan 1
			$lastOrderNumber = substr($lastOrderCode, strlen($dateCode));
			$nextOrderNumber = sprintf('%05d', (int)$lastOrderNumber + 1);

			// Gabungkan kembali menjadi kode order yang baru
			$orderCode = $dateCode . $nextOrderNumber;
		}

		// Cek apakah kode order sudah ada atau belum
		if (self::_isOrderCodeExists($orderCode)) {
			// Jika sudah ada, generate ulang
			return self::generateCode();
		}

		return $orderCode;
	}

	/**
	 * Check if the generated order code is exists
	 *
	 * @param string $orderCode order code
	 *
	 * @return void
	 */
	private static function _isOrderCodeExists($orderCode)
	{
		return Order::where('code', '=', $orderCode)->exists();
	}

	public function orderItems()
	{
		return $this->hasMany(OrderItem::class);
	}

	public function shipment()
	{
		return $this->hasOne(Shipment::class);
	}

	public function isPaid()
	{
		return $this->payment_status == self::PAID;
	}

	public function isCreated()
	{
		return $this->status == self::CREATED;
	}

	public function isConfirmed()
	{
		return $this->status == self::CONFIRMED;
	}

	public function isDelivered()
	{
		return $this->status == self::DELIVERED;
	}

	public function isCancelled()
	{
		return $this->status == self::CANCELLED;
	}

	/**
	 * Add full_name custom attribute to order object
	 *
	 * @return boolean
	 */
	public function getCustomerFullNameAttribute()
	{
		return "{$this->customer_first_name} {$this->customer_last_name}";
	}
}
