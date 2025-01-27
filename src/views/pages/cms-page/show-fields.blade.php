@if ($field['form_field'] == 'password' || $field['form_field'] == 'password with confirmation' || $field['hide_show'])

@elseif ($field['form_field'] == 'image')
    @include('cms::/components/show-fields/image', ['label' => ucwords(str_replace('_', ' ', $field['name'])), 'image' => $locale ? $row->translate($locale)[$field['name']] : $row[$field['name']] ])
@elseif ($field['form_field'] == 'multiple images')
    @include('cms::/components/show-fields/images', ['label' => ucwords(str_replace('_', ' ', $field['name'])), 'images' => $locale ? $row->translate($locale)[$field['name']] : $row[$field['name']] ])
@elseif ($field['form_field'] == 'file')
    @include('cms::/components/show-fields/file', ['label' => ucwords(str_replace('_', ' ', $field['name'])), 'file' => $locale ? $row->translate($locale)[$field['name']] : $row[$field['name']] ])
@elseif ($field['form_field'] == 'files')
    @include('cms::/components/show-fields/files', ['label' => ucwords(str_replace('_', ' ', $field['name'])), 'files' => $locale ? $row->translate($locale)[$field['name']] : $row[$field['name']] ])
@elseif ($field['form_field'] == 'select')
    @if ($row[str_replace('_id', '', $field['name'])])
        @include('cms::/components/show-fields/text', ['label' => ucwords(str_replace(['_id', '_'], ['', ' '], $field['name'])), 'text' => $locale ? $row->translate($locale)[$field['name']] : $row[str_replace('_id', '', $field['name'])][$field['form_field_additionals_2']] ])
    @endif
@elseif ($field['form_field'] == 'select multiple')
    @include('cms::/components/show-fields/text-multiple', ['label' => ucwords(str_replace(['_id', '_'], ['', ' '], $field['name'])), 'texts' => $locale ? $row->translate($locale)[$field['name']] : $row[$field['name']], 'display_column' => $field['form_field_additionals_2'] ])
@elseif ($field['form_field'] == 'checkbox')
    @include('cms::/components/show-fields/boolean', ['label' => ucwords(str_replace('_', ' ', $field['name'])), 'value' => $locale ? $row->translate($locale)[$field['name']] : $row[$field['name']] ])
@elseif ($field['form_field'] == 'map coordinates')
    @include('cms::/components/show-fields/map', ['label' => ucwords(str_replace('_', ' ', $field['name'])), 'name' => $field['name'], 'value' => $locale ? $row->translate($locale)[$field['name']] : $row[$field['name']] ])
@else
    @include('cms::/components/show-fields/text', ['label' => ucwords(str_replace('_', ' ', $field['name'])), 'text' => $locale ? $row->translate($locale)[$field['name']] : $row[$field['name']] ])
@endif
