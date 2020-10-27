@extends('cms::layouts/dashboard')

@section('breadcrumb')
	<ul class="breadcrumbs list-inline font-weight-bold text-uppercase m-0">
		<li>Admins</li>
	</ul>
@endsection

@section('dashboard-content')

	<div class="card py-4 mx-2 mx-lg-5">
		<div class="actions">
			@if (request()->get('admin')['cms_pages']['admins']['permissions']['add'])
				<a href="{{ url(config('hellotree.cms_route_prefix') . '/admins/create') }}" class="btn btn-primary btn-sm">Add</a>
			@endif
			@if (request()->get('admin')['cms_pages']['admins']['permissions']['delete'])
				<form method="post" action="{{ url(config('hellotree.cms_route_prefix') . '/admins/') }}" class="d-block d-md-inline-block bulk-delete" onsubmit="return confirm('Are you sure?')">
					@csrf
					<input type="hidden" name="_method" value="DELETE">
					<button type="submit" class="btn btn-danger btn-sm w-100">Bulk Delete</button>
				</form>
			@endif
		</div>
		<div class="datatable-wrapper">
			<table class="datatable no-export">
				<thead>
					<tr>
						<th></th>
						<th>#</th>
						<th>Name</th>
						<th>Image</th>
						<th>Email</th>
						<th>Role</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					@foreach($rows as $row)
						<tr>
							<td>
								<label class="checkbox-wrapper delete-checkbox">
									<input type="checkbox" value="{{ $row['id'] }}">
									<div></div>
								</label>
							</td>
							<td>
								{{ $row['id'] }}
							</td>
							<td>{{ $row['name'] }}</td>
							<td>
								@if ($row->image)
									<img src="{{ Storage::url($row->image) }}" class="img-thumbnail">
								@endif
							</td>
							<td>{{ $row['email'] }}</td>
							<td>{{ $row->role['title'] }}</td>

							<td class="actions-wrapper text-right">
								@if (request()->get('admin')['cms_pages']['admins']['permissions']['read'])
									<a href="{{ url(config('hellotree.cms_route_prefix') . '/admins/' . $row['id']) }}" class="mb-2 btn btn-secondary btn-sm">View</a>
								@endif
								@if (request()->get('admin')['cms_pages']['admins']['permissions']['edit'])
									<a href="{{ url(config('hellotree.cms_route_prefix') . '/admins/' . $row['id'] . '/edit') }}" class="mb-2 btn btn-primary btn-sm">Edit</a>
								@endif
								@if (request()->get('admin')['cms_pages']['admins']['permissions']['delete'])
									<form class="d-inline" onsubmit="return confirm('Are you sure?')" method="post" action="{{ url(config('hellotree.cms_route_prefix') . '/admins/' . $row['id']) }}">
										@csrf
										<input type="hidden" name="_method" value="DELETE">
										<button class="mb-2 btn btn-danger btn-sm">Delete</button>
									</form>
								@endif
							</td>
						</tr>
						@endforeach
				</tbody>
			</table>
		</div>
	</div>

@endsection