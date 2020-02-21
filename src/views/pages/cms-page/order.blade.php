@extends('cms::/layouts/dashboard')

@section('breadcrumb')
	<ul class="breadcrumbs list-inline font-weight-bold text-uppercase m-0">
		<li><a href="{{ url(config('hellotree.cms_route_prefix') . '/' . $page['route']) }}">{{ $page['display_name_plural'] }}</a></li>
		<li>order</li>
	</ul>
@endsection

@section('dashboard-content')

	<div class="card p-4 mx-2 mx-sm-5">
		@if (count($rows))
			<form method="post">
				@csrf
				<ul class="sortable list-inline m-0">
					@foreach($rows as $row)
						<li class="sortable-row d-block bg-white border px-3 py-2 mb-2">
							<input type="hidden" name="ht_pos[{{ $row['id'] }}]" value="{{ $row['ht_pos'] }}">
							@foreach($page_fields as $field)
								@if ($field['name'] == $page['order_display'])
									@if ($field['form_field'] == 'select')
										{{ $row[str_replace('_id', '', $field['name'])][$field['form_field_additionals_2']] }}
									@elseif ($field['form_field'] == 'select multiple')
										@foreach($row[$field['form_field_additionals_1']] as $i => $second_table_row)
											@if ($i) , @endif
											{{ $second_table_row[$field['form_field_additionals_2']] }}
										@endforeach
									@else
										{{ $row[$page['order_display']] }}
									@endif
								@endif
							@endforeach
						</li>
					@endforeach
				</ul>
				<div class="text-right">
					<button class="btn btn-sm btn-primary">Submit</button>
				</div>
			</form>
		@else
			<h5 class="text-center m-0 py-4">No record found for sorting</h5>
		@endif
	</div>

@endsection