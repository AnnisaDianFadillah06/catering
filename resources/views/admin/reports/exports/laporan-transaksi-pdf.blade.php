<!DOCTYPE html>
<html>
  	<head>
		<meta charset="utf-8">
		<title>Laporan Transaksi</title>
		<style type="text/css">
			table {
				width: 100%;
			}

			table tr td,
			table tr th {
				font-size: 10pt;
				text-align: left;
			}

			table tr:nth-child(even) {
				background-color: #f2f2f2;
			}

			table th, td {
  				border-bottom: 1px solid #ddd;
			}

			table th {
				border-top: 1px solid #ddd;
				height: 40px;
			}

			table td {
				height: 25px;
			}
		</style>
	</head>
  	<body>
		<h2>Transaction Report</h2>
		<hr>
		<p>Period: {{ $startDate }} - {{ $endDate }}</p>
		<table>
			<thead>
				<tr>
					<th>Date</th>
                                            <th>Customer Name</th>
                                            <th>City ID</th>
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
                                                <td> {{ \App\Helpers\CityHelper::getCityName($data->customer_city_id) }}</td>
                                                <td>{{ $data->product_name }}</td>
                                                <td>{{ $data->total_order }}</td>
                                                <td>{{ $data->sub_total }}</td>
                                            </tr>
                                            @php
                                                $totalSubTotal += $data->sub_total;
                                            @endphp
                                        @endforeach
                                        <tr>
                                            <td colspan="5"><strong>Total</strong></td>
                                            <td><strong>{{ $totalSubTotal }}</strong></td>
                                        </tr>
			</tbody>
		</table>
 	</body>
</html>
