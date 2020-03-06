<?php

namespace Hellotreedigital\Cms\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Hellotreedigital\Cms\Models\CmsPage;

class CmsPageController extends Controller
{
    public function index($route)
    {
        $page = CmsPage::where('route', $route)->firstOrFail();
        $page_fields = json_decode($page['fields'], true);

        $model = 'App\\' . $page['model_name'];
        if ($page['single_record']) {
            $row = $model::first();
            if (!$row) abort(403, "Single record page has no record");
            return redirect(config('hellotree.cms_route_prefix') . '/' . $route . '/' . $row['id']);
        }
        $rows = $model::when($page['order_display'], function($query) use($page){
            return $query->orderBy('ht_pos');
        })->get();

        return view('cms::pages/cms-page/index', compact('page', 'page_fields', 'rows'));
    }

    public function getPageExtraVariables($page_fields)
    {
        $extra_variables = [];
        foreach($page_fields as $field) {
            if ($field['form_field'] == 'select' || $field['form_field'] == 'select multiple') {
                // Get model name from database table
                $extra_page = CmsPage::where('database_table', $field['form_field_additionals_1'])->first();
                if (!$extra_page) abort(403, 'Cms page not found for `database_table` ' . $field['form_field_additionals_1']);
                $extra_model = 'App\\' . $extra_page['model_name'];
                $extra_variables[$field['form_field_additionals_1']] = $extra_model::get();
            }
        }
        return $extra_variables;
    }

    public function create($route)
    {
        $page = CmsPage::where('route', $route)->where('add', 1)->firstOrFail();
        $page_fields = json_decode($page['fields'], true);
        $page_translatable_fields = json_decode($page['translatable_fields'], true);
        $extra_variables = $this->getPageExtraVariables($page_fields);

        return view('cms::pages/cms-page/form', compact('page', 'page_fields', 'page_translatable_fields', 'extra_variables'));
    }

    public function storeValidation($page_fields, $page)
    {
        $validation_rules = [];
        foreach($page_fields as $field) {
            $validation_rules[$field['name']] = '';

            if (!$field['nullable']) $validation_rules[$field['name']] .= 'required|';
            if (isset($field['unique']) && $field['unique']) $validation_rules[$field['name']] .= 'unique:' . $page['database_table'] . '|';
            if ($field['form_field'] == 'image') $validation_rules[$field['name']] .= 'image|';
            if ($field['form_field'] == 'password with confirmation') $validation_rules[$field['name']] .= 'confirmed|';

            if (strlen($validation_rules[$field['name']]) > 0)
            $validation_rules[$field['name']] = substr($validation_rules[$field['name']], 0, -1);
        }
        return $validation_rules;
    }

    public function translateOrNew($translatable_fields, $request, $row)
    {
        // Translatable insert query
        if (count($translatable_fields)) {
            foreach (config('translatable.locales') as $locale) {
                if (is_array($locale)) continue;
                foreach($translatable_fields as $field) {
                    if ($field['form_field'] == 'select multiple') continue;
                    elseif ($field['form_field'] == 'password' || $field['form_field'] == 'password with confirmation') {
                        $row->translateOrNew($locale)->{$field['name']} = Hash::make($request[$locale][$field['name']]);
                    } elseif ($field['form_field'] == 'checkbox') {
                        $row->translateOrNew($locale)->{$field['name']} = isset($request[$locale][$field['name']]) ? 1 : 0;
                    } elseif ($field['form_field'] == 'time') {
                        $row->translateOrNew($locale)->{$field['name']} = date('H:i', strtotime($request[$locale][$field['name']]));
                    } elseif ($field['form_field'] == 'image' || $field['form_field'] == 'file') {
                        if ($request[$locale][$field['name']]) {
                            $file_name = time() . '_' . md5(rand()) . '.' . $request[$locale][$field['name']]->getClientOriginalExtension();
                            $request[$locale][$field['name']]->move(storage_path('app/public/' . $request['route']), $file_name);
                            $row->translateOrNew($locale)->{$field['name']} = 'storage/' . $request['route'] . '/' . $file_name;
                        }
                    } else {
                        $row->translateOrNew($locale)->{$field['name']} = $request[$locale][$field['name']];
                    }
                }
            }
            $row->save();
        }
    }

    public function store(Request $request, $route)
    {
        $page = CmsPage::where('route', $route)->where('add', 1)->firstOrFail();
        $page_fields = json_decode($page['fields'], true);
        $translatable_fields = json_decode($page['translatable_fields'], true);

        // Request validation
        $field_validation_rules = $this->storeValidation($page_fields, $page);
        $translatable_field_validation_rules = $this->storeValidation($translatable_fields, $page);

        $translatable_field_validation_rules_languages = [];
        foreach ($translatable_field_validation_rules as $translatable_field => $translatable_rule) {
            foreach (config('translatable.locales') as $locale) {
                if (is_array($locale)) continue;
                $translatable_field_validation_rules_languages[$locale . '.' . $translatable_field] = $translatable_rule;
            }
        }

        $validation_rules = array_merge($field_validation_rules, $translatable_field_validation_rules_languages);
        $request->validate($validation_rules);

        // Insert query
        $query = [];
        foreach($page_fields as $field) {
            if ($field['form_field'] == 'select multiple') continue;
            elseif ($field['form_field'] == 'password' || $field['form_field'] == 'password with confirmation') {
                $query[$field['name']] = Hash::make($request[$field['name']]);
            } elseif ($field['form_field'] == 'checkbox') {
                $query[$field['name']] = isset($request[$field['name']]) ? 1 : 0;
            } elseif ($field['form_field'] == 'time') {
                $query[$field['name']] = date('H:i', strtotime($request[$field['name']]));
            } elseif ($field['form_field'] == 'image' || $field['form_field'] == 'file') {
                if ($request[$field['name']]) {
                    $file_name = time() . '_' . md5(rand()) . '.' . $request[$field['name']]->getClientOriginalExtension();
                    $request[$field['name']]->move(storage_path('app/public/' . $route), $file_name);
                    $query[$field['name']] = 'storage/' . $route . '/' . $file_name;
                }
            } else {
                $query[$field['name']] = $request[$field['name']];
            }
        }

        $model = 'App\\' . $page['model_name'];
        $row = $model::create($query);

        // Select multiple insert query
        foreach ($page_fields as $field) {
            if ($field['form_field'] == 'select multiple') {
                $row->{str_replace('_id', '', $field['form_field_additionals_1'])}()->sync($request[$field['name']]);
            }
        }

        $this->translateOrNew($translatable_fields, $request, $row);

        return redirect(config('hellotree.cms_route_prefix') . '/' . $route)->with('success', 'Record added successfully');
    }

    public function show($id, $route)
    {
        $page = CmsPage::where('route', $route)->where('show', 1)->firstOrFail();
        $page_fields = json_decode($page['fields'], true);
        $translatable_fields = json_decode($page['translatable_fields'], true);

        $model = 'App\\' . $page['model_name'];
        $row = $model::findOrFail($id);

        return view('cms::pages/cms-page/show', compact('page', 'page_fields', 'translatable_fields', 'row'));
    }

    public function edit($id, $route)
    {
        $page = CmsPage::where('route', $route)->where('edit', 1)->firstOrFail();
        $page_fields = json_decode($page['fields'], true);
        $page_translatable_fields = json_decode($page['translatable_fields'], true);
        $extra_variables = $this->getPageExtraVariables($page_fields);

        $model = 'App\\' . $page['model_name'];
        $row = $model::findOrFail($id);

        return view('cms::pages/cms-page/form', compact('page', 'page_fields', 'page_translatable_fields', 'row', 'extra_variables'));
    }

    public function updateValiation($database_table, $page_fields)
    {
        $validation_rules = [];
        foreach ($page_fields as $field) {
            if ($field['form_field'] == 'slug' && !$field['form_field_additionals_2']) continue;

            $validation_rules[$field['name']] = '';
            if (!$field['nullable'] && ($field['form_field'] != 'image' && $field['form_field'] != 'file' && $field['form_field'] != 'password with confirmation')) $validation_rules[$field['name']] .= 'required|';
            if (!$field['nullable'] && ($field['form_field'] == 'image' || $field['form_field'] == 'file')) $validation_rules[$field['name']] .= 'required_with:remove_file_' . $field['name'] . '|';
            if (isset($field['unique']) && $field['unique']) $validation_rules[$field['name']] .= 'unique:' . $database_table . ',' . $field['name'] . ',' . "' . " . '$row->id|';
            if ($field['form_field'] == 'image') $validation_rules[$field['name']] .= 'image|';
            if ($field['form_field'] == 'password with confirmation') $validation_rules[$field['name']] .= 'confirmed|';

            if (strlen($validation_rules[$field['name']]) > 0) $validation_rules[$field['name']] = substr($validation_rules[$field['name']], 0, -1);
        }
        return $validation_rules;
    }

    public function update(Request $request, $id, $route)
    {
        $page = CmsPage::where('route', $route)->where('edit', 1)->firstOrFail();
        $page_fields = json_decode($page['fields'], true);$page_translatable_fields = json_decode($page['translatable_fields'], true);
        $translatable_fields = json_decode($page['translatable_fields'], true);

        // Request validations
        $field_validation_rules = $this->updateValiation($page['database_table'], $page_fields);
        $translatable_field_validation_rules = $this->updateValiation($page['database_table'] . '_translations', $translatable_fields);

        $translatable_field_validation_rules_languages = [];
        foreach ($translatable_field_validation_rules as $translatable_field => $translatable_rule) {
            foreach (config('translatable.locales') as $locale) {
                if (is_array($locale)) continue;
                $translatable_field_validation_rules_languages[$locale . '.' . $translatable_field] = $translatable_rule;
            }
        }

        $validation_rules = array_merge($field_validation_rules, $translatable_field_validation_rules_languages);
        $request->validate($validation_rules);

        // Update query
        $query = [];
        foreach ($page_fields as $field) {
            if (($field['form_field'] == 'slug' && !$field['form_field_additionals_2']) || $field['form_field'] == 'select multiple') continue;

            if (($field['form_field'] == 'password' || $field['form_field'] == 'password with confirmation') && $request[$field['name']]) {
                $query[$field['name']] = Hash::make($request[$field['name']]);
            } elseif ($field['form_field'] == 'checkbox') {
                $query[$field['name']] = isset($request[$field['name']]) ? 1 : 0;
            } elseif ($field['form_field'] == 'time') {
                $query[$field['name']] = date('H:i', strtotime($request[$field['name']]));
            } elseif ($field['form_field'] == 'image' || $field['form_field'] == 'file') {
                if ($request['remove_file_' . $field['name']]) {
                    $query[$field['name']] = null;
                } elseif ($request[$field['name']]) {
                    $file_name = time() . '_' . md5(rand()) . '.' . $request[$field['name']]->getClientOriginalExtension();
                    $request[$field['name']]->move(storage_path('app/public/' . $route), $file_name);
                    $query[$field['name']] = 'storage/' . $route . '/' . $file_name;
                }
            } else {
                $query[$field['name']] = $request[$field['name']];
            }
        }

        $model = 'App\\' . $page['model_name'];
        $row = $model::findOrFail($id);
        $row->update($query);

        // Select multiple update query
        foreach($page_fields as $field) {
            if ($field['form_field'] == 'select multiple') {
                $row->{str_replace('_id', '', $field['form_field_additionals_1'])}()->sync($request[$field['name']]);
            }
        }

        // Translatable update query
        $this->translateOrNew($translatable_fields, $request, $row);

        return redirect(config('hellotree.cms_route_prefix') . '/' . $route)->with('success', 'Record edited successfully');
    }

    public function destroy($id, $route)
    {
        $page = CmsPage::where('route', $route)->where('delete', 1)->firstOrFail();
        $model = 'App\\' . $page['model_name'];

        $array = explode(',', $id);
        foreach ($array as $id) $model::destroy($id);
        return redirect(config('hellotree.cms_route_prefix') . '/' . $route)->with('success', 'Record deleted successfully');
    }

    public function order($route)
    {
        $page = CmsPage::where('route', $route)->whereNotNull('order_display')->firstOrFail();
        $page_fields = json_decode($page['fields'], true);

        $model = 'App\\' . $page['model_name'];

        if (!$page['order_display']) abort(404);

        $rows = $model::orderBy('ht_pos')->get();

        return view('cms::pages/cms-page/order', compact('page', 'page_fields', 'rows'));
    }

    public function changeOrder(Request $request, $route)
    {
        $page = CmsPage::where('route', $route)->firstOrFail();
        $model = 'App\\' . $page['model_name'];

        foreach ($request['ht_pos'] as $id => $pos) {
            $row = $model::findOrFail($id);
            $row->ht_pos = $pos;
            $row->save();
        }

        return redirect(config('hellotree.cms_route_prefix') . '/' . $route)->with('success', 'Records ordered successfully');
    }
}
