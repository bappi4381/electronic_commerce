{{-- Permission matrix grouped by module. Expects $groupedPermissions and optional $selected (ids). --}}
@php
    $selected = collect(old('permissions', $selected ?? []))->map(fn ($id) => (int) $id)->all();
    $editor = Auth::guard('admin')->user();
    $isSuper = $editor?->hasRole(\App\Support\AdminPermissions::SUPER_ADMIN_ROLE);
    $actions = config('admin_permissions.action_labels');
    $isLocked = fn ($permission) => !$isSuper && !$editor?->can($permission->name) && !in_array($permission->id, $selected, true);
    $custom = $groupedPermissions['custom'] ?? null;
    $modules = collect($groupedPermissions)->except('custom');
    $checkbox = 'permission-checkbox size-4 rounded border-gray-300 text-brand-600 focus:ring-2 focus:ring-brand-500/40 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-600 dark:bg-gray-800';
@endphp

<div x-data="{
        set(scope, on) { scope.querySelectorAll('.permission-checkbox:not(:disabled)').forEach(cb => cb.checked = on); },
        column(action, on) { this.$root.querySelectorAll(`.permission-checkbox[data-action='${action}']:not(:disabled)`).forEach(cb => cb.checked = on); },
     }" class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            @unless($isSuper)
                Greyed-out boxes are permissions you don't have yourself, so you can't grant them.
            @else
                Tick what this role can do in each area.
            @endunless
        </p>
        <div class="flex gap-2">
            <x-admin.button variant="secondary" size="sm" @click="set($root, true)">Select all</x-admin.button>
            <x-admin.button variant="ghost" size="sm" @click="set($root, false)">Clear all</x-admin.button>
        </div>
    </div>

    @if($modules->isNotEmpty())
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
            <table class="min-w-full text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/40">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Area</th>
                        @foreach($actions as $action => $label)
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <button type="button" class="rounded uppercase hover:text-gray-900 focus-ring dark:hover:text-white" @click="column(@js($action), true)" title="Grant {{ strtolower($label) }} everywhere">{{ $label }}</button>
                            </th>
                        @endforeach
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Row actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($modules as $module => $group)
                        @php($byAction = collect($group['permissions'])->keyBy(fn ($p) => \Illuminate\Support\Str::after($p->name, '.')))
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                            <th scope="row" class="px-4 py-3 text-left font-medium text-gray-900 dark:text-white">{{ $group['label'] }}</th>
                            @foreach($actions as $action => $label)
                                <td class="px-4 py-3 text-center">
                                    @if($permission = $byAction->get($action))
                                        @php($locked = $isLocked($permission))
                                        <label class="inline-flex items-center justify-center" title="{{ $locked ? 'You do not have this permission yourself' : $permission->name }}">
                                            <span class="sr-only">{{ $label }} {{ $group['label'] }}</span>
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" data-action="{{ $action }}" class="{{ $checkbox }}"
                                                   @checked(in_array($permission->id, $selected, true)) @disabled($locked)>
                                        </label>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-700" aria-label="Not applicable">—</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <button type="button" @click="set($el.closest('tr'), true)" class="rounded text-xs font-medium text-brand-600 hover:underline focus-ring dark:text-brand-400">All</button>
                                <button type="button" @click="set($el.closest('tr'), false)" class="ml-2 rounded text-xs font-medium text-gray-500 hover:underline focus-ring dark:text-gray-400">None</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if($custom && count($custom['permissions']))
        <fieldset class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
            <legend class="px-1 text-sm font-medium text-gray-700 dark:text-gray-300">{{ $custom['label'] }}</legend>
            <div class="flex flex-wrap gap-2">
                @foreach($custom['permissions'] as $permission)
                    @php($locked = $isLocked($permission))
                    <label @class(['inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm', 'cursor-not-allowed border-gray-100 opacity-50 dark:border-gray-800' => $locked, 'cursor-pointer border-gray-200 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800' => !$locked])
                           title="{{ $locked ? 'You do not have this permission yourself' : $permission->name }}">
                        <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="{{ $checkbox }}" @checked(in_array($permission->id, $selected, true)) @disabled($locked)>
                        <span class="font-mono text-xs text-gray-700 dark:text-gray-200">{{ $permission->name }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
    @endif

    @if($modules->isEmpty() && !$custom)
        <x-admin.empty-state title="No permissions found" description="Run the permission seeder to create the core permissions." icon="bi-shield-slash" />
    @endif
</div>
