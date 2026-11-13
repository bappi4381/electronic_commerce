<?php

namespace App\Support;

class AdminPermissions
{
    public const GUARD = 'admin';
    public const SUPER_ADMIN_ROLE = 'Super Admin';

    /**
     * @return list<string> every core permission name, e.g. "orders.edit"
     */
    public static function all(): array
    {
        $names = [];
        foreach (config('admin_permissions.modules') as $module => $definition) {
            foreach ($definition['actions'] as $action) {
                $names[] = "{$module}.{$action}";
            }
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    public static function forModule(string $module): array
    {
        return array_map(
            fn (string $action) => "{$module}.{$action}",
            config("admin_permissions.modules.{$module}.actions", [])
        );
    }

    public static function isCore(string $name): bool
    {
        return in_array($name, self::all(), true);
    }

    /**
     * Groups permission models (or names) by module for the role matrix.
     *
     * @param  iterable<\Spatie\Permission\Models\Permission>  $permissions
     * @return array<string, array{label: string, permissions: array}>
     */
    public static function grouped(iterable $permissions): array
    {
        $groups = [];
        foreach (config('admin_permissions.modules') as $module => $definition) {
            $groups[$module] = ['label' => $definition['label'], 'permissions' => []];
        }
        $groups['custom'] = ['label' => 'Custom Permissions', 'permissions' => []];

        foreach ($permissions as $permission) {
            $module = str_contains($permission->name, '.') ? strstr($permission->name, '.', true) : 'custom';
            $groups[isset($groups[$module]) && self::isCore($permission->name) ? $module : 'custom']['permissions'][] = $permission;
        }

        return array_filter($groups, fn (array $group) => $group['permissions'] !== []);
    }

    public static function actionLabel(string $permissionName): string
    {
        $action = str_contains($permissionName, '.') ? substr(strrchr($permissionName, '.'), 1) : $permissionName;

        return config("admin_permissions.action_labels.{$action}", ucwords(str_replace(['-', '.'], ' ', $action)));
    }
}
