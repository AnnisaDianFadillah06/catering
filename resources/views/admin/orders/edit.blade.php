@extends('layouts.admin')

@section('content')
<div class="container">
  <div class="content mb-4">
    <div class="invoice-wrapper rounded border bg-white py-5 px-3 px-md-4 px-lg-5">
      <div class="d-flex justify-content-between">
        <h2 class="text-dark font-weight-medium">Order ID #{{ $order->code }}</h2>
      </div>
      <div class="row pt-5">
        <div class="col-xl-4 col-lg-4">
          <p class="text-dark mb-2" style="font-weight: normal; font-size:16px; text-transform: uppercase;">Billing Address</p>
          <address>
            {{ $order->customer_first_name }} {{ $order->customer_last_name }}
            <br> {{ $order->customer_address1 }}
            <br> {{ $order->customer_address2 }}
            <br> Email: {{ $order->customer_email }}
            <br> Phone: {{ $order->customer_phone }}
            <br> Postcode: {{ $order->customer_postcode }}
          </address>
        </div>
        <div class="col-xl-4 col-lg-4">
          <p class="text-dark mb-2" style="font-weight: normal; font-size:16px; text-transform: uppercase;">Shipment Address</p>
          <address>
            {{ $order->shipment->first_name }} {{ $order->shipment->last_name }}
            <br> {{ $order->shipment->address1 }}
            <br> {{ $order->shipment->address2 }}
            <br> Email: {{ $order->shipment->email }}
            <br> Phone: {{ $order->shipment->phone }}
            <br> Postcode: {{ $order->shipment->postcode }}
          </address>
        </div>
        <div class="col-xl-4 col-lg-4">
          <p class="text-dark mb-2" style="font-weight: normal; font-size:16px; text-transform: uppercase;">Details</p>
          <address>
            ID: <span class="text-dark">#{{ $order->code }}</span>
            <br> DATE: <span>{{ $order->order_date }}</span>
            <br>
            NOTE: <span>{{ $order->note }}</span>
            <br> Status: {{ $order->status }} {{ $order->cancelled_at }}
            <br> Cancellation Note : {{ $order->cancellation_note}}
            <br> Payment Status: {{ $order->payment_status }}
            <br> Shipped by: {{ $order->shipping_service_name }}
          </address>
        </div>
      </div>
      <table class="table mt-3 table-striped table-responsive table-responsive-large" style="width:100%">
        <thead>
          <tr>
            <th>#</th>
            <th>Item</th>
            <th>Quantity</th>
            <th>Unit Cost</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($order->orderItems as $item)
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $item->name }}</td>
            <td>{{ $item->qty }}</td>
            <td>Rp.{{ number_format($item->base_price) }}</td>
            <td>Rp.{{ number_format($item->sub_total) }}</td>
          </tr>
          @empty
          <tr>
            <td colspan="6">Order item not found!</td>
          </tr>
          @endforelse
        </tbody>
      </table>
      <div class="row justify-content-end">
        <div class="col-lg-5 col-xl-4 col-xl-3 ml-sm-auto">
          <ul class="list-unstyled mt-4">
            <li class="mid pb-3 text-dark">Subtotal
              <span class="d-inline-block float-right text-default">{{ $order->base_total_price }}</span>
            </li>
            <li class="mid pb-3 text-dark">Tax(10%)
              <span class="d-inline-block float-right text-default">{{ $order->tax_amount }}</span>
            </li>
            <li class="mid pb-3 text-dark">Shipping Cost
              <span class="d-inline-block float-right text-default">{{ $order->shipping_cost }}</span>
            </li>
            <li class="pb-3 text-dark">Total
              <span class="d-inline-block float-right">{{ $order->grand_total }}</span>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
  <div class="card shadow mb-4">
    <div class="card-header py-3 d-flex">
      <h6 class="m-0 font-weight-bold text-primary">
        {{ __('Edit orders')}}
      </h6>
      <div class="ml-auto">
        <a href="{{ route('admin.orders.index') }}" class="btn btn-primary">
          <span class="icon text-white-50">
            <i class="fa fa-home"></i>
          </span>
          <span class="text">{{ __('Back to orders') }}</span>
        </a>
      </div>
    </div>
    <div class="card-body">
      <form action="" method="post">
        @csrf
        @method('put')

        <div class="row">
          <div class="col-lg-6 col-md-12 col-12">
            <div class="form-group">
              <label for="shipping_cost">Shipping Price</label>
              <input class="form-control" id="shipping_cost" type="text" name="shipping_cost" value="{{ old('shipping_cost', $order->shipping_cost) }}">
              @error('shipping_cost')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
          </div>
        </div>
        <div class="form-group pt-4">
          <button class="btn btn-primary" type="submit" name="submit">{{ __('Save') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection