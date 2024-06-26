@extends('layouts.admin')

@section('content')
    <div class="container">
        <div class="content">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card card-default">
                        <div class="card-header card-header-border-bottom">
                            <h2>Cash Report</h2>
                        </div>
                        <div class="card-body">
                            <form action="" class="mb-5">
                                <div class="row">
                                    <div class="col-lg-3">
                                        <div class="form-group mb-2">
                                            <input type="text" class="form-control datepicker" readonly="" value="{{ !empty(request()->input('start')) ? request()->input('start') : '' }}" name="start" placeholder="from">
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="form-group mx-sm-3 mb-2">
                                            <input type="text" class="form-control datepicker" readonly="" value="{{ !empty(request()->input('end')) ? request()->input('end') : '' }}" name="end" placeholder="to">
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="form-group mx-sm-3 mb-2">
                                            <select name="export" class="form-control">
                                                <option value="xlsx">excel</option>
                                                <option value="pdf">pdf</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="form-group mx-sm-3 mb-2">
                                            <button type="submit" class="btn btn-primary btn-default">Go</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Customer Name</th>
                                            <th>Product Name</th>
                                            <th>Total Orders</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $totalSubTotal = 0;
                                        @endphp
                                        @foreach ($report as $data)
                                            <tr>    
                                                <td>{{ $data->date }}</td>
                                                <td>{{ $data->customer_first_name }} {{ $data->customer_last_name }}</td>
                                                <td>{{ $data->product_name }}</td>
                                                <td>{{ $data->total_order }}</td>
                                                <td>{{ $data->sub_total }}</td>
                                            </tr>
                                            @php
                                                $totalSubTotal += $data->sub_total;
                                            @endphp
                                        @endforeach
                                        <tr>
                                            <td colspan="4"><strong>Total</strong></td>
                                            <td><strong>{{ $totalSubTotal }}</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-alt')
<script src="{{ asset('backend/plugins/bootstrap-datepicker.min.js') }}"></script>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
@endpush