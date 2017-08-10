<?php

namespace TCG\Voyager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use TCG\Voyager\Facades\Voyager;

class VoyagerSettingsController extends Controller
{
    public function index()
    {
        // Check permission
        Voyager::canOrFail('browse_settings');

        $data = Voyager::model('Setting')->orderBy('order', 'ASC')->get();

        $settings = [];
        $settings[__('voyager.settings.group_general')] = [];
        foreach ($data as $d) {
            if ($d->group == '' || $d->group == __('voyager.settings.group_general')) {
                $settings[__('voyager.settings.group_general')][] = $d;
            } else {
                $settings[$d->group][] = $d;
            }
        }
        if (count($settings[__('voyager.settings.group_general')]) == 0) {
            unset($settings[__('voyager.settings.group_general')]);
        }

        $groups_data = Voyager::model('Setting')->select('group')->distinct()->get();
        $groups = [];
        $groups[] = __('voyager.settings.group_general');
        foreach ($groups_data as $group) {
            if ($group->group != __('voyager.settings.group_general') && $group->group != '') {
                $groups[] = $group->group;
            }
        }

        return Voyager::view('voyager::settings.index', compact('settings', 'groups'));
    }

    public function store(Request $request)
    {
        // Check permission
        Voyager::canOrFail('browse_settings');

        $lastSetting = Voyager::model('Setting')->orderBy('order', 'DESC')->first();

        if (is_null($lastSetting)) {
            $order = 0;
        } else {
            $order = intval($lastSetting->order) + 1;
        }

        $request->merge(['order' => $order]);
        $request->merge(['value' => '']);
        $request->merge(['key' => implode('.', array(str_slug($request->input('group')), $request->input('key')))]);

        Voyager::model('Setting')->create($request->all());

        return back()->with([
            'message'    => __('voyager.settings.successfully_created'),
            'alert-type' => 'success',
        ]);
    }

    public function update(Request $request)
    {
        // Check permission
        Voyager::canOrFail('browse_settings');

        $settings = Voyager::model('Setting')->all();

        foreach ($settings as $setting) {
            $content = $this->getContentBasedOnType($request, 'settings', (object) [
                'type'    => $setting->type,
                'field'   => $setting->key,
                'details' => $setting->details,
                'group'   => $setting->group,
            ]);

            if ($content === null && isset($setting->value)) {
                $content = $setting->value;
            }

            $key = preg_replace('/^'.str_slug($setting->group).'./i', '', $setting->key);

            $setting->group = $request->input(str_replace('.', '_', $setting->key).'_group');
            $setting->key = implode('.', array(str_slug($setting->group), $key));
            $setting->value = $content;
            $setting->save();
        }

        return back()->with([
            'message'    => __('voyager.settings.successfully_saved'),
            'alert-type' => 'success',
        ]);
    }

    public function delete($id)
    {
        Voyager::canOrFail('browse_settings');

        // Check permission
        Voyager::canOrFail('visit_settings');

        Voyager::model('Setting')->destroy($id);

        return back()->with([
            'message'    => __('voyager.settings.successfully_deleted'),
            'alert-type' => 'success',
        ]);
    }

    public function move_up($id)
    {
        $setting = Voyager::model('Setting')->find($id);
        $swapOrder = $setting->order;
        $previousSetting = Voyager::model('Setting')
                            ->where('order', '<', $swapOrder)
                            ->where('group', $setting->group)
                            ->orderBy('order', 'DESC')->first();
        $data = [
            'message'    => __('voyager.settings.already_at_top'),
            'alert-type' => 'error',
        ];

        if (isset($previousSetting->order)) {
            $setting->order = $previousSetting->order;
            $setting->save();
            $previousSetting->order = $swapOrder;
            $previousSetting->save();

            $data = [
                'message'    => __('voyager.settings.moved_order_up', ['name' => $setting->display_name]),
                'alert-type' => 'success',
            ];
        }

        return back()->with($data);
    }

    public function delete_value($id)
    {
        // Check permission
        Voyager::canOrFail('browse_settings');

        $setting = Voyager::model('Setting')->find($id);

        if (isset($setting->id)) {
            // If the type is an image... Then delete it
            if ($setting->type == 'image') {
                if (Storage::disk(config('voyager.storage.disk'))->exists($setting->value)) {
                    Storage::disk(config('voyager.storage.disk'))->delete($setting->value);
                }
            }
            $setting->value = '';
            $setting->save();
        }

        return back()->with([
            'message'    => __('voyager.settings.successfully_removed', ['name' => $setting->display_name]),
            'alert-type' => 'success',
        ]);
    }

    public function move_down($id)
    {
        $setting = Voyager::model('Setting')->find($id);
        $swapOrder = $setting->order;

        $previousSetting = Voyager::model('Setting')
                            ->where('order', '>', $swapOrder)
                            ->where('group', $setting->group)
                            ->orderBy('order', 'ASC')->first();
        $data = [
            'message'    => __('voyager.settings.already_at_bottom'),
            'alert-type' => 'error',
        ];

        if (isset($previousSetting->order)) {
            $setting->order = $previousSetting->order;
            $setting->save();
            $previousSetting->order = $swapOrder;
            $previousSetting->save();

            $data = [
                'message'    => __('voyager.settings.moved_order_down', ['name' => $setting->display_name]),
                'alert-type' => 'success',
            ];
        }

        return back()->with($data);
    }
}
