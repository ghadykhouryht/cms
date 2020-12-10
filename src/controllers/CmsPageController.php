<?php

namespace Hellotreedigital\Cms\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Hellotreedigital\Cms\Models\CmsPage;
use Illuminate\Support\Facades\Storage;
use Hash;
use Illuminate\Support\Str;

class CmsPageController extends Controller
{
    public $appends_to_query;

    public function __construct()
    {
        $this->appends_to_query = '';
        if (
            request('page') ||
            request('per_page') ||
            request('custom_search') ||
            request('sort_by') ||
            request('sort_by_direction')
        ) $this->appends_to_query .= '?';
        if (request('page')) $this->appends_to_query .= 'page=' . request('page') . '&';
        if (request('per_page')) $this->appends_to_query .= 'per_page=' . request('per_page') . '&';
        if (request('custom_search')) $this->appends_to_query .= 'custom_search=' . request('custom_search') . '&';
        if (request('sort_by')) $this->appends_to_query .= 'sort_by=' . request('sort_by') . '&';
        if (request('sort_by_direction')) $this->appends_to_query .= 'sort_by_direction=' . request('sort_by_direction') . '&';
    }

    public function index($route)
    {
        $page = CmsPage::where('route', $route)->firstOrFail();
        $page_fields = json_decode($page['fields'], true);
        $extra_variables = $this->getPageExtraVariables($page_fields);

        $model = 'App\\' . $page['model_name'];
        if ($page['single_record']) {
            $row = $model::first();
            if (!$row) abort(403, "Single record page has no record");
            return redirect(config('hellotree.cms_route_prefix') . '/' . $route . '/' . $row['id']);
        }

        // Default order
        $order_by = 'id';
        $order_direction = 'desc';

        if (request('sort_by')) {
            $order_by = request('sort_by');
            $order_direction = request('sort_by_direction');
        } else {
            if ($page['sort_by']) {
                $order_by = $page['sort_by'];
                $order_direction = $page['sort_by_direction'];
            } elseif ($page['order_display']) {
                $order_by = 'ht_pos';
                $order_direction = 'asc';
            }
        }

        $rows = $model::orderBy($order_by, $order_direction)
            ->when($page['order_display'], function ($query) use ($page) {
                return $query->orderBy('ht_pos');
            })
            ->when(request('custom_validation'), function ($query) {
                foreach (request('custom_validation') as $validation) {
                    if ($validation['constraint'] == 'whereHas' && isset($validation['value'][1]) && count($validation['value'][1])) {
                        $query->whereHas($validation['value'][0], function ($query) use ($validation) {
                            return $query->whereIn($validation['value'][0] . '.id', $validation['value'][1]);
                        });
                    } else {
                        if (isset($validation['value'][1]) && $validation['value'][1]) {
                            $query = call_user_func_array([$query, $validation['constraint']], $validation['value']);
                        }
                    }
                }
                return $query;
            })
            ->when(request('custom_search'), function ($query) use ($page_fields) {
                foreach ($page_fields as $field) {
                    if (
                        $field['form_field'] == 'password' ||
                        $field['form_field'] == 'password with confirmation' ||
                        $field['form_field'] == 'select' ||
                        $field['form_field'] == 'select multiple' ||
                        $field['form_field'] == 'checkbox' ||
                        $field['form_field'] == 'image' ||
                        $field['form_field'] == 'multiple images' ||
                        $field['form_field'] == 'file' ||
                        $field['form_field'] == 'map coordinates'
                    ) continue;
                    $query->orWhere($field['name'], 'like', '%' . request('custom_search') . '%');
                }
                return $query;
            })
            ->when($page['server_side_pagination'], function ($query) {
                return $query->paginate(request('per_page') ? request('per_page') : 10);
            }, function ($query) {
                return $query->get();
            });

        $appends_to_query = $this->appends_to_query;

        $view = view()->exists('cms::pages/' . $route . '/index') ? 'cms::pages/' . $route . '/index' : 'cms::pages/cms-page/index';
        return view($view, compact('page', 'page_fields', 'rows', 'extra_variables', 'appends_to_query'));
    }

    public function getPageExtraVariables($page_fields)
    {
        $extra_variables = [];
        foreach ($page_fields as $field) {
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

        $view = view()->exists('cms::pages/' . $route . '/form') ? 'cms::pages/' . $route . '/form' : 'cms::pages/cms-page/form';
        return view($view, compact('page', 'page_fields', 'page_translatable_fields', 'extra_variables'));
    }

    public function storeValidation($page_fields, $page)
    {
        $validation_rules = [];
        foreach ($page_fields as $field) {
            $validation_rules[$field['name']] = '';

            if (!$field['nullable']) $validation_rules[$field['name']] .= 'required|';
            if (isset($field['unique']) && $field['unique']) $validation_rules[$field['name']] .= 'unique:' . $page['database_table'] . '|';
            if ($field['form_field'] == 'image') $validation_rules[$field['name']] .= 'image|';
            if ($field['form_field'] == 'multiple images') $validation_rules[$field['name']] .= 'array|';
            if ($field['form_field'] == 'password with confirmation') $validation_rules[$field['name']] .= 'confirmed|';
            if ($field['form_field'] == 'number') $validation_rules[$field['name']] .= 'numeric|';
            if ($field['form_field'] == 'number' && $field['nullable']) $validation_rules[$field['name']] .= 'nullable|';
            if ($field['migration_type'] == 'string' && ($field['form_field'] != 'number' && $field['form_field'] != 'image' && $field['form_field'] != 'file')) $validation_rules[$field['name']] .= 'max:191|';

            if (strlen($validation_rules[$field['name']]) > 0) $validation_rules[$field['name']] = substr($validation_rules[$field['name']], 0, -1);
        }
        return $validation_rules;
    }

    public function translateOrNew($translatable_fields, $request, $row)
    {
        // Translatable insert query
        if (count($translatable_fields)) {
            foreach (config('translatable.locales') as $locale) {
                if (is_array($locale)) continue;
                foreach ($translatable_fields as $field) {
                    if ($field['form_field'] == 'select multiple') continue;
                    elseif ($field['form_field'] == 'password' || $field['form_field'] == 'password with confirmation') {
                        $row->translateOrNew($locale)->{$field['name']} = Hash::make($request[$locale][$field['name']]);
                    } elseif ($field['form_field'] == 'checkbox') {
                        $row->translateOrNew($locale)->{$field['name']} = isset($request[$locale][$field['name']]) ? 1 : 0;
                    } elseif ($field['form_field'] == 'time') {
                        $row->translateOrNew($locale)->{$field['name']} = date('H:i', strtotime($request[$locale][$field['name']]));
                    } elseif ($field['form_field'] == 'image' || $field['form_field'] == 'file') {
                        if (isset($request[$locale][$field['name']]) && $request[$locale][$field['name']]) {
                            $row->translateOrNew($locale)->{$field['name']} = $this->uploadFile($request->file($locale . '.' . $field['name']), $request['route']);
                        } elseif (isset($request[$locale]['remove_file_' . $field['name']]) && $request[$locale]['remove_file_' . $field['name']]) {
                            $row->translateOrNew($locale)->{$field['name']} = null;
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
        foreach ($page_fields as $field) {
            if ($field['form_field'] == 'select multiple') continue;
            elseif ($field['form_field'] == 'password' || $field['form_field'] == 'password with confirmation') {
                $query[$field['name']] = Hash::make($request[$field['name']]);
            } elseif ($field['form_field'] == 'checkbox') {
                $query[$field['name']] = isset($request[$field['name']]) ? 1 : 0;
            } elseif ($field['form_field'] == 'time') {
                $query[$field['name']] = date('H:i', strtotime($request[$field['name']]));
            } elseif ($field['form_field'] == 'image' || $field['form_field'] == 'file') {
                if ($request[$field['name']]) {
                    $query[$field['name']] = $this->uploadFile($request->file($field['name']), $route);
                }
            } elseif ($field['form_field'] == 'multiple images') {
                $query[$field['name']] = $request[$field['name']] ? json_encode($request[$field['name']]) : '[]';
            } else {
                $query[$field['name']] = $request[$field['name']];
            }
        }

        $model = 'App\\' . $page['model_name'];
        $row = $model::create($query);

        // Select multiple insert query
        foreach ($page_fields as $field) {
            if ($field['form_field'] == 'select multiple') {
                $row->{str_replace('_id', '', $field['name'])}()->sync($request[$field['name']]);
            }
        }

        $this->translateOrNew($translatable_fields, $request, $row);

        $request->session()->flash('success', 'Record added successfully');
        return url(config('hellotree.cms_route_prefix') . '/' . $route);
    }

    public function show($id, $route)
    {
        $page = CmsPage::where('route', $route)->where('show', 1)->firstOrFail();
        $page_fields = json_decode($page['fields'], true);
        $translatable_fields = json_decode($page['translatable_fields'], true);

        $model = 'App\\' . $page['model_name'];
        $row = $model::findOrFail($id);

        $view = view()->exists('cms::pages/' . $route . '/show') ? 'cms::pages/' . $route . '/show' : 'cms::pages/cms-page/show';
        return view($view, compact('page', 'page_fields', 'translatable_fields', 'row'));
    }

    public function edit($id, $route)
    {
        $page = CmsPage::where('route', $route)->where('edit', 1)->firstOrFail();
        $page_fields = json_decode($page['fields'], true);
        $page_translatable_fields = json_decode($page['translatable_fields'], true);
        $extra_variables = $this->getPageExtraVariables($page_fields);

        $model = 'App\\' . $page['model_name'];
        $row = $model::findOrFail($id);

        $appends_to_query = $this->appends_to_query;

        $view = view()->exists('cms::pages/' . $route . '/form') ? 'cms::pages/' . $route . '/form' : 'cms::pages/cms-page/form';
        return view($view, compact('page', 'page_fields', 'page_translatable_fields', 'row', 'extra_variables', 'appends_to_query'));
    }

    public function updateValiation($page_fields, $database_table, $id, $row)
    {
        $validation_rules = [];
        foreach ($page_fields as $field) {
            if ($field['form_field'] == 'slug' && !$field['form_field_additionals_2']) continue;

            $validation_rules[$field['name']] = '';
            if (!$field['nullable'] && ($field['form_field'] != 'image' && $field['form_field'] != 'file' && $field['form_field'] != 'password with confirmation')) $validation_rules[$field['name']] .= 'required|';
            if (!$field['nullable'] && ($field['form_field'] == 'image' || $field['form_field'] == 'file')) $validation_rules[$field['name']] .= 'required_with:remove_file_' . $field['name'] . '|';
            if (isset($field['unique']) && $field['unique']) $validation_rules[$field['name']] .= 'unique:' . $database_table . ',' . $field['name'] . ',' . $id . '|';
            if ($field['form_field'] == 'image') $validation_rules[$field['name']] .= 'image|';
            if ($field['form_field'] == 'password with confirmation') $validation_rules[$field['name']] .= 'confirmed|';
            if ($field['form_field'] == 'number') $validation_rules[$field['name']] .= 'numeric|';
            if ($field['form_field'] == 'number' && $field['nullable']) $validation_rules[$field['name']] .= 'nullable|';
            if ($field['form_field'] == 'multiple images') $validation_rules[$field['name']] .= 'array|';
            if ($field['migration_type'] == 'string' && ($field['form_field'] != 'number' && $field['form_field'] != 'image' && $field['form_field'] != 'file')) $validation_rules[$field['name']] .= 'max:191|';

            if (strlen($validation_rules[$field['name']]) > 0) $validation_rules[$field['name']] = substr($validation_rules[$field['name']], 0, -1);
        }
        return $validation_rules;
    }

    public function update(Request $request, $id, $route)
    {
        $page = CmsPage::where('route', $route)->where('edit', 1)->firstOrFail();
        $page_fields = json_decode($page['fields'], true);
        $page_translatable_fields = json_decode($page['translatable_fields'], true);
        $translatable_fields = json_decode($page['translatable_fields'], true);

        // Get row
        $model = 'App\\' . $page['model_name'];
        $row = $model::findOrFail($id);

        // Request validations
        $field_validation_rules = $this->updateValiation($page_fields, $page['database_table'], $id, $row);
        $translatable_field_validation_rules = $this->updateValiation($translatable_fields, $page['database_table'] . '_translations', $id, $row);

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

            if (($field['form_field'] == 'password' || $field['form_field'] == 'password with confirmation')) {
                if ($request[$field['name']]) {
                    $query[$field['name']] = Hash::make($request[$field['name']]);
                }
            } elseif ($field['form_field'] == 'checkbox') {
                $query[$field['name']] = isset($request[$field['name']]) ? 1 : 0;
            } elseif ($field['form_field'] == 'time') {
                $query[$field['name']] = date('H:i', strtotime($request[$field['name']]));
            } elseif ($field['form_field'] == 'image' || $field['form_field'] == 'file') {
                if ($request[$field['name']]) {
                    $query[$field['name']] = $this->uploadFile($request->file($field['name']), $route);
                } elseif ($request['remove_file_' . $field['name']]) {
                    $query[$field['name']] = null;
                }
            } elseif ($field['form_field'] == 'multiple images') {
                $query[$field['name']] = $request[$field['name']] ? json_encode($request[$field['name']]) : '[]';
            } else {
                $query[$field['name']] = $request[$field['name']];
            }
        }
        $row->update($query);

        // Select multiple update query
        foreach ($page_fields as $field) {
            if ($field['form_field'] == 'select multiple') {
                $row->{str_replace('_id', '', $field['name'])}()->sync($request[$field['name']]);
            }
        }

        // Translatable update query
        $this->translateOrNew($translatable_fields, $request, $row);

        $request->session()->flash('success', 'Record edited successfully');
        return url(config('hellotree.cms_route_prefix') . '/' . $route . $this->appends_to_query);
    }

    public function uploadImages(Request $request, $route)
    {
        $files = [];
        if ($request['images']) {
            foreach ($request['images'] as $file) {
                $file_path = $this->uploadFile($file, $route);
                $files[] = [
                    'path' => $file_path,
                    'url' => Storage::url($file_path),
                ];
            }
        }
        return $files;
    }

    public function destroy($id, $route)
    {
        $page = CmsPage::where('route', $route)->where('delete', 1)->firstOrFail();
        $model = 'App\\' . $page['model_name'];

        $array = explode(',', $id);
        foreach ($array as $id) $model::destroy($id);

        $appends_to_query = $this->appends_to_query;

        return redirect(config('hellotree.cms_route_prefix') . '/' . $route . $appends_to_query)->with('success', 'Record deleted successfully');
    }

    public function order($route)
    {
        $page = CmsPage::where('route', $route)->whereNotNull('order_display')->firstOrFail();
        $page_fields = json_decode($page['fields'], true);
        $page_translatable_fields = json_decode($page['translatable_fields'], true);

        $model = 'App\\' . $page['model_name'];

        if (!$page['order_display']) abort(404);

        $rows = $model::orderBy('ht_pos')->get();

        $view = view()->exists('cms::pages/' . $route . '/order') ? 'cms::pages/' . $route . '/order' : 'cms::pages/cms-page/order';
        return view($view, compact('page', 'page_fields', 'page_translatable_fields', 'rows'));
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

    public function uploadFile($file, $route)
    {
        if (config('hellotree.use_original_name')) {
            $name = $file->getClientOriginalName();
            return $file->storeAs($route . '/' . Str::uuid(), $name);
        } else {
            return $file->store($route);
        }
    }
}
