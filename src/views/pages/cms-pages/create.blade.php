@extends('cms::layouts/dashboard')

@section('breadcrumb')
	<ul class="breadcrumbs list-inline font-weight-bold text-uppercase m-0">
		<li><a href="{{ url(config('hellotree.cms_route_prefix') . '/cms-pages') }}">CMS Pages</a></li>
		@if (isset($cms_page))
			<li>{{ $cms_page['id'] }}</li>
			<li>Edit</li>
		@else
			<li>Create</li>
		@endif
	</ul>
@endsection

@section('dashboard-content')

	<form method="post" action="{{ isset($cms_page) ? url(config('hellotree.cms_route_prefix') . '/cms-pages/' . $cms_page['id']) : url(config('hellotree.cms_route_prefix') . '/cms-pages') }}">

		<div class="card p-4 mx-2 mx-sm-5">
			<p class="font-weight-bold text-uppercase mb-4">Instructions</p>
			<div class="form-group m-0">
				<label class="m-0">
					<ul>
						<li>The CMS will automatically generate the database. All you have to do is to write the database table name</li>
						<li>Model name, display name, display name plural are auto-generated. You can still change them.</li>
						<li>The Model is also automatically generated by the CMS with the needed relationships.</li>
						<li>The display name is the name you see in create, edit and show pages.</li>
						<li>Display name plural is the name you see on the menu and browse pages.</li>
						<li>The icon is the class of a font-awesome icon, you will see the icon in the menu <a href="{{ url(config('hellotree.cms_route_prefix') . '/cms-pages/icons') }}" target="_blank" class="text-secondary">click here to see all the icons</a>.</li>
						<li>If you want to add drag and drop order for your table, write down the column that you want to see while ordering. `Order display column` column must be added to the fields table. Then the CMS will automatically generate `ht_pos` column containing the row position.</li>
						<li>You can also choose what to create for the page (create, edit, delete and show), uncheck one of the option and admins will not have access to it.</li>
						<li>Single record page will not have browse, create and delete pages. This page is used for the generated pages with one row only.</li>
						<li>Fields are the database table columns and the fields shown in browse, create, edit and show. Each field will have a name, migration type (Laravel), nullable (required or not), unique (If it is unique in its table)</li>
						<li>Translatable fields are fields having multiple languages. Same options for the regular fields</li>
						<li>If you want your slug to be generated from the translatable fields, here is the syntax: locale[field_name]. e.g. en[title]</li>
						<li>To manage languages, change locales array in config/translatable.php</li>
					</ul>
				</label>
			</div>
		</div>

		<div class="card p-4 mx-2 mx-sm-5">
			<p class="font-weight-bold text-uppercase mb-4">{{ isset($cms_page) ? 'Edit CMS page #' . $cms_page['id'] : 'Add CMS page' }}</p>

			@if (isset($cms_page))
				@method('put')
			@endif

			@if ($errors->any())
				<div class="alert alert-danger">
					@foreach ($errors->all() as $error)
						<p class="m-0">{{ $error }}</p>
					@endforeach
				</div>
			@endif

			@include('cms::components/form-fields/input', [
				'label' => 'Database table',
				'name' => 'database_table',
				'type' => 'text',
				'value' => isset($cms_page) ? $cms_page['database_table'] : '',
				'locale' => null,
			])
			@include('cms::components/form-fields/input', [
				'label' => 'Model',
				'name' => 'model_name',
				'type' => 'text',
				'value' => isset($cms_page) ? $cms_page['model_name'] : '',
				'locale' => null,
			])
			@include('cms::components/form-fields/input', [
				'label' => 'Display name',
				'name' => 'display_name',
				'type' => 'text',
				'value' => isset($cms_page) ? $cms_page['display_name'] : '',
				'locale' => null,
			])
			@include('cms::components/form-fields/input', [
				'label' => 'Display name plural',
				'name' => 'display_name_plural',
				'type' => 'text',
				'value' => isset($cms_page) ? $cms_page['display_name_plural'] : '',
				'locale' => null,
			])
			@include('cms::components/form-fields/input', [
				'label' => 'Icon',
				'name' => 'icon',
				'type' => 'text',
				'value' => isset($cms_page) ? $cms_page['icon'] : '',
				'locale' => null,
			])
			@include('cms::components/form-fields/input', [
				'label' => 'Order display column',
				'name' => 'order_display',
				'type' => 'text',
				'value' => isset($cms_page) ? $cms_page['order_display'] : '',
				'locale' => null,
			])

			<div class="form-group">
				<label class="d-block mb-1">Other options</label>
				<div class="mb-">
					<label class="checkbox-wrapper">
						@php
						$checked = $errors->any() ? old('add') : (isset($cms_page) ? $cms_page['add'] : 1);
						@endphp
						<input type="checkbox" class="form-control" name="add" {!! $checked ? 'checked=""' : '' !!}>
						<div></div>
						<span class="d-inline-block align-middle mb-0 ml-1">With Add</span>
					</label>
				</div>
				<div class="mb-">
					<label class="checkbox-wrapper">
					@php
					$checked = $errors->any() ? old('edit') : (isset($cms_page) ? $cms_page['edit'] : 1);
					@endphp
						<input type="checkbox" class="form-control" name="edit" {!! $checked ? 'checked=""' : '' !!}>
						<div></div>
						<span class="d-inline-block align-middle mb-0 ml-1">With Edit</span>
					</label>
				</div>
				<div class="mb-">
					<label class="checkbox-wrapper">
					@php
					$checked = $errors->any() ? old('delete') : (isset($cms_page) ? $cms_page['delete'] : 1);
					@endphp
						<input type="checkbox" class="form-control" name="delete" {!! $checked ? 'checked=""' : '' !!}>
						<div></div>
						<span class="d-inline-block align-middle mb-0 ml-1">With Delete</span>
					</label>
				</div>
				<div class="mb-">
					<label class="checkbox-wrapper">
					@php
					$checked = $errors->any() ? old('show') : (isset($cms_page) ? $cms_page['show'] : 1);
					@endphp
						<input type="checkbox" class="form-control" name="show" {!! $checked ? 'checked=""' : '' !!}>
						<div></div>
						<span class="d-inline-block align-middle mb-0 ml-1">With Show</span>
					</label>
				</div>
			</div>

			<div class="form-group">
				<label class="d-block mb-1">Single record page</label>
				@include('cms::/components/form-fields/checkbox', [
					'label' => 'Yes',
					'name' => 'single_record',
					'inline_label' => true,
					'checked' => isset($cms_page['single_record']) ? $cms_page['single_record'] : '',
					'locale' => null,
				])
			</div>

		</div>

		<div class="card py-4 mx-2 mx-sm-5">
			<div class="px-4 pb-4">
				<p class="font-weight-bold text-uppercase mb-4">Fields</p>
				<button class="btn-add btn btn-sm btn-primary px-5" type="button" data-type="">Add</button>
			</div>

			<div class="table-responsive">
				<table class="fields table">
					<thead>
						<tr>
							<th class="text-center">NAME</th>
							<th class="text-center">Migration Type</th>
							<th class="text-center">Form Field</th>
							<th class="text-center">Nullable</th>
							<th class="text-center">Unique</th>
							<th class="text-center">Remove</th>
						</tr>
					</thead>
					<tbody class="sortable">
						@if (old('name'))
							@foreach(old('name') as $field_key => $field)
								@include('cms::pages/cms-pages/table-field', ['field_type' => ''])
							@endforeach
						@elseif (isset($cms_page))
							@php
							$fields = json_decode($cms_page['fields'], TRUE);
							@endphp
							@foreach($fields as $field_key => $field)
								@include('cms::pages/cms-pages/table-field', ['field_type' => ''])
							@endforeach
						@else
							@include('cms::pages/cms-pages/table-field', ['field_type' => ''])
						@endif
					</tbody>
				</table>
			</div>
		</div>

		<div class="card py-4 mx-2 mx-sm-5">
			<div class="px-4 pb-4">
				<p class="font-weight-bold text-uppercase mb-4">Translatable Fields</p>
				<button class="btn-add btn btn-sm btn-primary px-5" type="button" data-type="translatable">Add</button>
			</div>

			<div class="table-responsive">
				<table class="fields table">
					<thead>
						<tr>
							<th class="text-center">NAME</th>
							<th class="text-center">Migration Type</th>
							<th class="text-center">Form Field</th>
							<th class="text-center">Nullable</th>
							<th class="text-center">Remove</th>
						</tr>
					</thead>
					<tbody class="sortable">
						@if (old('translatable_name'))
							@foreach(old('translatable_name') as $field_key => $field)
								@include('cms::pages/cms-pages/table-field', ['field_type' => 'translatable'])
							@endforeach
						@elseif (isset($cms_page))
							@php
							$fields = json_decode($cms_page['translatable_fields'], TRUE);
							@endphp
							@foreach($fields as $field_key => $field)
								@include('cms::pages/cms-pages/table-field', ['field_type' => 'translatable'])
							@endforeach
						@else
							@include('cms::pages/cms-pages/table-field', ['field_type' => 'translatable'])
						@endif
					</tbody>
				</table>
			</div>

			<div class="px-4 text-right">
				@csrf
				<button type="submit" class="btn btn-sm btn-primary">Submit</button>
			</div>
		</div>

	</form>

@endsection

@section('scripts')
	<script>
		var field_html = $('[name="name[]"]').closest('.field').last().html();
		$('[name="translatable_form_field[]"]').find('[value="select"]').remove();
		$('[name="translatable_form_field[]"]').find('[value="select multiple"]').remove();
		var translatable_field_html = $('[name="translatable_name[]"]').closest('.field').last().html();
		@if (!isset($cms_page))
			$('[name="translatable_name[]"]').closest('.field').remove();
		@endif

		$('input[name="database_table"]').on("keyup", function () {
			var v = $(this).val();
			$('input[name="display_name"]').val(displayName(v));
			$('input[name="display_name_plural"]').val(displayNamePlural(v));
			$('input[name="model_name"]').val(modelName(v));
			$('input[name="controller_name"]').val(controllerName(v));
		});

		$(document).on('change', '[name="form_field[]"]', function(){
			var select = $(this);
			var additional_field = select.closest('td').find('.form-field-additionals');
			var additional_input_1 = additional_field.find('input[name="form_field_additionals_1[]"');
			var additional_input_2 = additional_field.find('input[name="form_field_additionals_2[]"');

			additional_input_1.prop('required', false);
			additional_input_2.prop('required', false);


			additional_field.slideUp(function(){
				additional_input_1.hide();
				additional_input_2.hide();

				if (select.val() == 'slug') {
					additional_input_1.prop('required', true);
					additional_input_1.attr('placeholder', 'Slug origin name');

					additional_input_2.prop('required', true);
					additional_input_2.attr('placeholder', 'Editable');
					additional_input_2.attr('min', '0');
					additional_input_2.attr('max', '1');
					additional_input_2.attr('type', 'number');

					additional_input_1.val('');
					additional_input_2.val('');

					additional_input_1.show();
					additional_input_2.show();

					additional_field.slideDown();
				} else if (select.val() == 'select') {
					additional_input_1.prop('required', true);
					additional_input_1.attr('placeholder', 'Database table');

					additional_input_2.prop('required', true);
					additional_input_2.attr('placeholder', 'Display column');
					additional_input_2.attr('type', 'text');

					additional_input_1.val('');
					additional_input_2.val('');

					additional_input_1.show();
					additional_input_2.show();

					additional_field.slideDown();
				} else if (select.val() == 'select multiple') {
					additional_input_1.prop('required', true);
					additional_input_1.attr('placeholder', 'Database table');

					additional_input_2.prop('required', true);
					additional_input_2.attr('placeholder', 'Display column');
					additional_input_2.attr('type', 'text');

					additional_input_1.val('');
					additional_input_2.val('');

					additional_input_1.show();
					additional_input_2.show();

					additional_field.slideDown();
				}
			});
		});

		$('.btn-add').on('click', function () {
			var fields_wrapper = $(this).closest('.card').find('.fields');
			fields_wrapper.append('<tr class="field">' + ($(this).attr('data-type') == 'translatable' ? translatable_field_html : field_html) + '</tr>');
			fields_wrapper.find('.field:last input').val('');
			fields_wrapper.find('.field:last input[type="number"]').val(0);
			fields_wrapper.find('.field:last select').val('');
			fields_wrapper.find('.field:last').find('.form-field-additionals').hide();
		});


		function ucwords(str){
			return str.replace(/(\b)([a-zA-Z])/g,
				function (firstLetter){
					return firstLetter.toUpperCase();
				});
		}

		function displayNamePlural(v) {
			v = v.replace(/_/g, ' ');
			v = ucwords(v);
			return v;
		}

		function displayName(v) {
			v = displayNamePlural(v);
			if (v.substr(-3) == 'ies') v = v.slice(0, -3) + 'y';
			else
				if (v.substr(-1) == 's') v = v.slice(0, -1);
			return v;
		}

		function modelNamePlural(v) {
			v = v.replace(/_/g, ' ');
			v = ucwords(v);
			v = v.replace(/ /g, '');
			return v;
		}

		function modelName(v) {
			v = modelNamePlural(v);
			if (v.substr(-3) == 'ies') v = v.slice(0, -3) + 'y';
			else
				if (v.substr(-1) == 's') v = v.slice(0, -1);
			return v;
		}

		function controllerName(v) {
			v = (v != '') ? modelNamePlural(v) + 'Controller' : '';
			return v;
		}

		function removeField(btn) {
			$(btn).closest('.field').remove();
		}
	</script>
@endsection