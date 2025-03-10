<?php

namespace App\Http\Controllers;

use Exception;
use Midtrans\Snap;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Helpers\CityHelper;

class OrderController extends Controller
{
	public function process()
	{
		if (\Cart::isEmpty()) {
			return redirect()->route('cart.index');
		}

		\Cart::removeConditionsByType('shipping');

		$items = \Cart::getContent();

		$totalWeight = 0;
		foreach ($items as $item) {
			$totalWeight += ($item->quantity * $item->associatedModel->weight);
		}

		$provinces = $this->getProvinces();
		$cities = isset(auth()->user()->city_id) ? $this->getCities(auth()->user()->province_id) : [];

		return view('frontend.orders.checkout', compact('items', 'totalWeight', 'provinces', 'cities'));
	}

	public function cities(Request $request)
	{
		$cities = $this->getCities($request->query('province_id'));
		return response()->json(['cities' => $cities]);
	}

	public function shippingCost(Request $request)
	{
		$items = \Cart::getContent();

		$totalWeight = 0;
		foreach ($items as $item) {
			$totalWeight += ($item->quantity * $item->associatedModel->weight);
		}

		$destination = $request->input('city_id');
		return $this->getShippingCost($destination, $totalWeight);
	}

	private function getShippingCost($destination, $weight)
	{
		$params = [
			'origin' => env('RAJAONGKIR_ORIGIN'),
			'destination' => $destination,
			'weight' => $weight,
		];

		$results = [];
		foreach ($this->couriers as $code => $courier) {
			$params['courier'] = $code;

			$response = $this->rajaOngkirRequest('cost', $params, 'POST');

			if (!empty($response['rajaongkir']['results'])) {
				foreach ($response['rajaongkir']['results'] as $cost) {
					if (!empty($cost['costs'])) {
						foreach ($cost['costs'] as $costDetail) {
							$serviceName = strtoupper($cost['code']) . ' - ' . $costDetail['service'];
							$costAmount = $costDetail['cost'][0]['value'];
							$etd = $costDetail['cost'][0]['etd'];

							$result = [
								'service' => $serviceName,
								'cost' => $costAmount,
								'etd' => $etd,
								'courier' => $code,
							];

							$results[] = $result;
						}
					}
				}
			}
		}

		$response = [
			'origin' => $params['origin'],
			'destination' => $destination,
			'weight' => $weight,
			'results' => $results,
		];

		return $response;
	}

	public function setShipping(Request $request)
	{
		\Cart::removeConditionsByType('shipping');

		$items = \Cart::getContent();

		$totalWeight = 0;
		foreach ($items as $item) {
			$totalWeight += ($item->quantity * $item->associatedModel->weight);
		}

		$shippingService = $request->get('shipping_service');
		$destination = $request->get('city_id');

		$shippingOptions = $this->getShippingCost($destination, $totalWeight);

		$selectedShipping = null;
		if ($shippingOptions['results']) {
			foreach ($shippingOptions['results'] as $shippingOption) {
				if (str_replace(' ', '', $shippingOption['service']) == $shippingService) {
					$selectedShipping = $shippingOption;
					break;
				}
			}
		}

		$status = null;
		$message = null;
		$data = [];
		if ($selectedShipping) {
			$status = 200;
			$message = 'Success set shipping cost';

			$this->addShippingCostToCart($selectedShipping['service'], $selectedShipping['cost']);

			$data['total'] = number_format(\Cart::getTotal());
		} else {
			$status = 400;
			$message = 'Failed to set shipping cost';
		}

		$response = [
			'status' => $status,
			'message' => $message
		];

		if ($data) {
			$response['data'] = $data;
		}

		return $response;
	}

	private function addShippingCostToCart($serviceName, $cost)
	{
		$condition = new \Darryldecode\Cart\CartCondition(
			[
				'name' => $serviceName,
				'type' => 'shipping',
				'target' => 'total',
				'value' => '+' . $cost,
			]
		);

		\Cart::condition($condition);
	}

	private function getSelectedShipping($destination, $totalWeight, $shippingService)
	{
		$shippingOptions = $this->getShippingCost($destination, $totalWeight);

		$selectedShipping = null;
		if ($shippingOptions['results']) {
			foreach ($shippingOptions['results'] as $shippingOption) {
				if (str_replace(' ', '', $shippingOption['service']) == $shippingService) {
					$selectedShipping = $shippingOption;
					break;
				}
			}
		}

		return $selectedShipping;
	}

	public function checkout(Request $request)
	{
		$params = $request->except('_token');

		$order = \DB::transaction(function () use ($params) {
			$items = \Cart::getContent();

			$totalWeight = 0;
			foreach ($items as $item) {
				$totalWeight += ($item->quantity * $item->associatedModel->weight);
			}

			$baseTotalPrice = \Cart::getSubTotal();
			$shippingCost = 0; // Placeholder for shipping cost
			$discountAmount = 0;
			$discountPercent = 0;
			$grandTotal = ($baseTotalPrice + $shippingCost) - $discountAmount;

			$orderDate = date('Y-m-d H:i:s');
			$paymentDue = (new \DateTime($orderDate))->modify('+3 day')->format('Y-m-d H:i:s');

			$user_profile = [
				'username' => $params['username'],
				'first_name' => $params['first_name'],
				'last_name' => $params['last_name'],
				'address1' => $params['address1'],
				'address2' => $params['address2'],
				'phone' => $params['phone'],
				'email' => $params['email'],
			];

			auth()->user()->update($user_profile);

			$orderParams = [
				'user_id' => auth()->id(),
				'code' => Order::generateCode(),
				'status' => Order::CREATED,
				'order_date' => $orderDate,
				'payment_due' => $paymentDue,
				'payment_status' => Order::UNPAID,
				'base_total_price' => $baseTotalPrice,
				'discount_amount' => $discountAmount,
				'discount_percent' => $discountPercent,
				'shipping_cost' => $shippingCost,
				'grand_total' => $grandTotal,
				'customer_first_name' => $params['first_name'],
				'customer_last_name' => $params['last_name'],
				'customer_address1' => $params['address1'],
				'customer_address2' => $params['address2'],
				'customer_city_id' => $params['city_id'],
				'customer_postcode' => $params['postcode'],
				'customer_phone' => $params['phone'],
				'customer_email' => $params['email'],
				'note' => $params['note'],
				'shipping_courier' => 'N/A', // Placeholder for shipping courier
				'shipping_service_name' => $params['service'], // Placeholder for shipping service name
			];

			$order = Order::create($orderParams);

			$cartItems = \Cart::getContent();

			if ($order && $cartItems) {
				foreach ($cartItems as $item) {
					$itemDiscountAmount = 0;
					$itemDiscountPercent = 0;
					$itemBaseTotal = $item->quantity * $item->price;
					$itemSubTotal = $itemBaseTotal - $itemDiscountAmount;

					$product = $item->associatedModel;

					$orderItemParams = [
						'order_id' => $order->id,
						'product_id' => $item->associatedModel->id,
						'qty' => $item->quantity,
						'base_price' => $item->price,
						'base_total' => $itemBaseTotal,
						'discount_amount' => $itemDiscountAmount,
						'discount_percent' => $itemDiscountPercent,
						'sub_total' => $itemSubTotal,
						'name' => $item->name,
						'weight' => $item->associatedModel->weight,
					];

					$orderItem = OrderItem::create($orderItemParams);

					if ($orderItem) {
						$product = Product::findOrFail($product->id);
						$product->quantity -= $item->quantity;
						$product->save();
					}
				}
			}

			$shippingFirstName = isset($params['ship_to']) ? $params['shipping_first_name'] : $params['first_name'];
			$shippingLastName = isset($params['ship_to']) ? $params['shipping_last_name'] : $params['last_name'];
			$shippingAddress1 = isset($params['ship_to']) ? $params['shipping_address1'] : $params['address1'];
			$shippingAddress2 = isset($params['ship_to']) ? $params['shipping_address2'] : $params['address2'];
			$shippingPhone = isset($params['ship_to']) ? $params['shipping_phone'] : $params['phone'];
			$shippingEmail = isset($params['ship_to']) ? $params['shipping_email'] : $params['email'];
			$shippingCity = isset($params['ship_to']) ? $params['shipping_city_id'] : $params['city_id'];
			$shippingPostCode = isset($params['ship_to']) ? $params['shipping_postcode'] : $params['postcode'];

			$shipmentParams = [
				'user_id' => auth()->id(),
				'order_id' => $order->id,
				'status' => Shipment::PENDING,
				'total_qty' => \Cart::getTotalQuantity(),
				'total_weight' => $totalWeight,
				'first_name' => $shippingFirstName,
				'last_name' => $shippingLastName,
				'address1' => $shippingAddress1,
				'address2' => $shippingAddress2,
				'phone' => $shippingPhone,
				'email' => $shippingEmail,
				'city_id' => $shippingCity,
				'postcode' => $shippingPostCode,
			];
			Shipment::create($shipmentParams);

			return $order;
		});

		if (!isset($order)) {
			return redirect()->back()->with([
				'message' => 'something went wrong !',
				'alert-type' => 'danger'
			]);
		}

		\Cart::clear();

		return redirect()->route('checkout.received', $order->id);
	}


	public function received($orderId)
	{
		$order = Order::where('id', $orderId)
			->where('user_id', auth()->id())
			->firstOrFail();

		// Menampilkan view dengan data order
		return view('frontend.orders.received', compact('order'));
	}

	public function payment($orderId)
	{
		$order = Order::where('id', $orderId)
			->where('user_id', auth()->id())
			->firstOrFail();

		// Inisialisasi payment gateway
		$this->initPaymentGateway();

		// Mendapatkan detail pelanggan
		$customerDetails = [
			'first_name' => $order->customer_first_name,
			'last_name' => $order->customer_last_name,
			'email' => $order->customer_email,
			'phone' => $order->customer_phone,
		];

		// Menyiapkan detail transaksi untuk payment gateway
		$transaction_details = [
			'enable_payments' => Payment::PAYMENT_CHANNELS,
			'transaction_details' => [
				'order_id' => $order->code,
				'gross_amount' => $order->grand_total,
			],
			'customer_details' => $customerDetails,
			'expiry' => [
				'start_time' => date('Y-m-d H:i:s T'),
				'unit' => Payment::EXPIRY_UNIT,
				'duration' => Payment::EXPIRY_DURATION,
			]
		];

		try {
			// Membuat transaksi menggunakan Snap
			$snap = Snap::createTransaction($transaction_details);

			// Menyimpan token dan URL pembayaran ke order
			$order->payment_token = $snap->token;
			$order->payment_url = $snap->redirect_url;
			$order->save();

			// Redirect ke URL pembayaran
			header('Location: ' . $order->payment_url);
			exit;
		} catch (Exception $e) {
			// Menampilkan pesan error jika terjadi kesalahan
			echo $e->getMessage();
		}
	}

	public function index()
	{
		$orders = Order::where('user_id', auth()->id())
			->paginate(10);

		return view('frontend.orders.index', compact('orders'));
	}

	public function show($id)
	{
		$order = Order::where('user_id', auth()->id())->findOrFail($id);

		return view('frontend.orders.show', compact('order'));
	}

	public function checkAndUpdatePaymentStatus(Request $request, Order $order)
	{
		$orderCode = $order->code;
		$url = "https://api.sandbox.midtrans.com/v2/{$orderCode}/status";

		// AUTH_STRING yang telah di-encode
		$authString = 'U0ItTWlkLXNlcnZlci10MlhmRzBqMTQwUU9KWGdxQjNKOXRrRkg6';

		// Menggunakan GuzzleHttp untuk melakukan request ke API Midtrans
		$client = new \GuzzleHttp\Client();
		$response = $client->request('GET', $url, [
			'headers' => [
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'Authorization' => 'Basic ' . $authString,
			],
		]);

		$responseBody = json_decode($response->getBody(), true);
		$transactionStatus = $responseBody['transaction_status'];
		$fraudStatus = $responseBody['fraud_status'] ?? null; // Fraud status mungkin tidak selalu ada

		if ($transactionStatus == 'capture') {
			if ($fraudStatus == 'accept') {
				// Set transaction status on your database to 'success'
				$order->update([
					'payment_status' => Order::PAID,
				]);
			}
		} else if ($transactionStatus == 'settlement') {
			// Set transaction status on your database to 'success'
			$order->update([
				'payment_status' => Order::PAID,
			]);
		} else if (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
			// Set transaction status on your database to 'failure'
			$order->update([
				'payment_status' => Order::FAILURE,
			]);
		} else if ($transactionStatus == 'pending') {
			// Set transaction status on your database to 'pending' / waiting payment
			$order->update([
				'payment_status' => Order::PENDING,
			]);
		}

		return response()->json(['message' => 'Payment status updated successfully!', 'payment_status' => $order->payment_status]);
	}
}
