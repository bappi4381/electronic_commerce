<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Moves a database that was created on MyISAM to InnoDB and adds the foreign keys MyISAM silently
 * dropped. Without --apply it only reports what it would do.
 */
class ConvertToInnoDb extends Command
{
    protected $signature = 'db:convert-innodb
        {--apply : Convert the tables and add the foreign keys}
        {--delete-orphans : Delete rows whose parent is gone where the key cascades on delete}';

    protected $description = 'Convert MyISAM tables to InnoDB and restore the missing foreign keys (report-only without --apply)';

    /**
     * Every foreign key the migrations define: [table, column, parent table, on delete].
     * Parents are always referenced by `id`.
     */
    private const FOREIGN_KEYS = [
        ['articles', 'category_id', 'categories', 'cascade'],
        ['attribute_values', 'attribute_id', 'attributes', 'cascade'],
        ['blog_comments', 'article_id', 'articles', 'cascade'],
        ['blog_comments', 'user_id', 'users', 'cascade'],
        ['blog_reactions', 'article_id', 'articles', 'cascade'],
        ['blog_reactions', 'user_id', 'users', 'cascade'],
        ['categories', 'parent_id', 'categories', 'cascade'],
        ['category_attribute', 'attribute_id', 'attributes', 'cascade'],
        ['category_attribute', 'category_id', 'categories', 'cascade'],
        ['coupon_usages', 'coupon_id', 'coupons', 'cascade'],
        ['coupon_usages', 'order_id', 'orders', 'cascade'],
        ['coupon_usages', 'user_id', 'users', 'set null'],
        ['courier_logs', 'order_id', 'orders', 'cascade'],
        ['customer_risk_logs', 'admin_id', 'admins', 'set null'],
        ['customer_risk_logs', 'order_id', 'orders', 'set null'],
        ['messages', 'user_id', 'users', 'cascade'],
        ['model_has_permissions', 'permission_id', 'permissions', 'cascade'],
        ['model_has_roles', 'role_id', 'roles', 'cascade'],
        ['order_couriers', 'admin_id', 'admins', 'set null'],
        ['order_couriers', 'order_id', 'orders', 'cascade'],
        ['order_items', 'order_id', 'orders', 'cascade'],
        ['order_items', 'product_id', 'products', 'set null'],
        ['order_items', 'variant_id', 'product_variants', 'set null'],
        ['order_status_histories', 'admin_id', 'admins', 'set null'],
        ['order_status_histories', 'order_id', 'orders', 'cascade'],
        ['orders', 'coupon_id', 'coupons', 'set null'],
        ['orders', 'risk_reviewed_by', 'admins', 'set null'],
        ['orders', 'user_id', 'users', 'set null'],
        ['payments', 'order_id', 'orders', 'restrict'],
        ['payments', 'user_id', 'users', 'set null'],
        ['phone_blacklists', 'admin_id', 'admins', 'set null'],
        ['product_comments', 'moderated_by', 'admins', 'set null'],
        ['product_comments', 'product_id', 'products', 'cascade'],
        ['product_comments', 'user_id', 'users', 'cascade'],
        ['product_images', 'product_id', 'products', 'cascade'],
        ['product_reactions', 'product_id', 'products', 'cascade'],
        ['product_reactions', 'user_id', 'users', 'cascade'],
        ['product_specifications', 'attribute_value_id', 'attribute_values', 'cascade'],
        ['product_specifications', 'product_id', 'products', 'cascade'],
        ['product_variants', 'product_id', 'products', 'cascade'],
        ['products', 'brand_id', 'brands', 'restrict'],
        ['products', 'category_id', 'categories', 'restrict'],
        ['refunds', 'admin_id', 'admins', 'set null'],
        ['refunds', 'order_id', 'orders', 'restrict'],
        ['refunds', 'payment_id', 'payments', 'set null'],
        ['role_has_permissions', 'permission_id', 'permissions', 'cascade'],
        ['role_has_permissions', 'role_id', 'roles', 'cascade'],
        ['search_logs', 'user_id', 'users', 'set null'],
        ['stock_movements', 'admin_id', 'admins', 'set null'],
        ['stock_movements', 'variant_id', 'product_variants', 'restrict'],
        ['variant_attribute_values', 'attribute_value_id', 'attribute_values', 'cascade'],
        ['variant_attribute_values', 'variant_id', 'product_variants', 'cascade'],
        ['wishlists', 'product_id', 'products', 'cascade'],
        ['wishlists', 'user_id', 'users', 'cascade'],
    ];

    public function handle(): int
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->error('This command only applies to MySQL / MariaDB.');

            return self::FAILURE;
        }

        $support = DB::scalar("SELECT SUPPORT FROM information_schema.ENGINES WHERE ENGINE = 'InnoDB'");
        if (! in_array($support, ['YES', 'DEFAULT'], true)) {
            $this->error('InnoDB is not enabled on this MySQL server. Ask the host to enable it.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $deleteOrphans = (bool) $this->option('delete-orphans');

        $failedTables = $this->convertTables($apply);
        $skipped = $this->restoreForeignKeys($apply, $deleteOrphans, $failedTables);

        if (! $apply) {
            $this->newLine();
            $this->line('Nothing was changed. Take a database backup, then run with --apply.');
        } elseif ($failedTables || $skipped) {
            $this->newLine();
            $this->warn('Finished with skipped items (listed above). Fix them and run the command again; finished work is not repeated.');
        } else {
            $this->newLine();
            $this->info('All tables are InnoDB and every foreign key is in place.');
        }

        return $failedTables ? self::FAILURE : self::SUCCESS;
    }

    /** @return list<string> tables that could not be converted */
    private function convertTables(bool $apply): array
    {
        $tables = DB::select(
            "SELECT TABLE_NAME AS name, ENGINE AS engine FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' AND ENGINE <> 'InnoDB'
             ORDER BY TABLE_NAME"
        );

        if (! $tables) {
            $this->info('All tables already use InnoDB.');

            return [];
        }

        $this->info(count($tables) . ' table(s) are not InnoDB' . ($apply ? '; converting:' : ':'));
        $failed = [];

        foreach ($tables as $table) {
            if (! $apply) {
                $this->line("  {$table->name} ({$table->engine})");
                continue;
            }

            try {
                DB::statement('ALTER TABLE ' . DB::getQueryGrammar()->wrapTable($table->name) . ' ENGINE = InnoDB');
                $this->line("  <info>✓</info> {$table->name}");
            } catch (Throwable $e) {
                $failed[] = $table->name;
                $this->line("  <error>✗</error> {$table->name}: " . $e->getMessage());
            }
        }

        return $failed;
    }

    /**
     * @param  list<string>  $failedTables
     * @return int number of foreign keys left out
     */
    private function restoreForeignKeys(bool $apply, bool $deleteOrphans, array $failedTables): int
    {
        $missing = [];
        foreach (self::FOREIGN_KEYS as [$table, $column, $parent, $onDelete]) {
            if (! Schema::hasTable($table) || ! Schema::hasTable($parent) || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            $exists = collect(Schema::getForeignKeys($table))
                ->contains(fn (array $foreignKey) => $foreignKey['columns'] === [$column]);
            if (! $exists) {
                $missing[] = [$table, $column, $parent, $onDelete];
            }
        }

        $this->newLine();
        if (! $missing) {
            $this->info('No foreign keys are missing.');

            return 0;
        }

        // Deleting orphans can orphan grandchildren, so repeat until a pass deletes nothing.
        if ($apply && $deleteOrphans) {
            do {
                $deleted = 0;
                foreach ($missing as [$table, $column, $parent, $onDelete]) {
                    if ($onDelete === 'cascade') {
                        $deleted += $this->deleteOrphans($table, $column, $parent);
                    }
                }
            } while ($deleted > 0);
        }

        $this->info(count($missing) . ' foreign key(s) are missing' . ($apply ? '; adding:' : ':'));
        $rows = [];
        $skipped = 0;

        foreach ($missing as [$table, $column, $parent, $onDelete]) {
            $orphans = $this->countOrphans($table, $column, $parent);
            $plan = match (true) {
                $orphans === 0 => 'add',
                $onDelete === 'set null' => "clear {$orphans} dangling value(s), then add",
                $onDelete === 'cascade' && $deleteOrphans => "delete {$orphans} orphan row(s), then add",
                $onDelete === 'cascade' => "SKIP: {$orphans} orphan row(s); rerun with --delete-orphans",
                default => "SKIP: {$orphans} row(s) point to a missing {$parent} record; fix them by hand",
            };

            if (! $apply) {
                $rows[] = ["{$table}.{$column}", "{$parent}.id", $onDelete, $plan];
                continue;
            }

            if (in_array($table, $failedTables, true) || in_array($parent, $failedTables, true)) {
                $rows[] = ["{$table}.{$column}", "{$parent}.id", $onDelete, 'SKIP: table is not InnoDB'];
                $skipped++;
                continue;
            }

            if ($orphans > 0 && $onDelete === 'set null') {
                $this->clearDanglingValues($table, $column, $parent);
            } elseif ($orphans > 0) {
                $rows[] = ["{$table}.{$column}", "{$parent}.id", $onDelete, $plan];
                $skipped++;
                continue;
            }

            try {
                Schema::table($table, function (Blueprint $blueprint) use ($column, $parent, $onDelete) {
                    $blueprint->foreign($column)->references('id')->on($parent)->onDelete($onDelete);
                });
                $rows[] = ["{$table}.{$column}", "{$parent}.id", $onDelete, 'added'];
            } catch (Throwable $e) {
                $rows[] = ["{$table}.{$column}", "{$parent}.id", $onDelete, 'FAILED: ' . $e->getMessage()];
                $skipped++;
            }
        }

        $this->table(['Column', 'References', 'On delete', $apply ? 'Result' : 'Plan'], $rows);

        return $skipped;
    }

    private function orphanCondition(string $table, string $column, string $parent): string
    {
        $grammar = DB::getQueryGrammar();

        return $grammar->wrap($column) . ' IS NOT NULL AND NOT EXISTS (SELECT 1 FROM '
            . $grammar->wrapTable($parent) . ' AS parent_row WHERE parent_row.id = '
            . $grammar->wrapTable($table) . '.' . $grammar->wrap($column) . ')';
    }

    private function countOrphans(string $table, string $column, string $parent): int
    {
        return (int) DB::scalar(
            'SELECT COUNT(*) FROM ' . DB::getQueryGrammar()->wrapTable($table)
            . ' WHERE ' . $this->orphanCondition($table, $column, $parent)
        );
    }

    private function deleteOrphans(string $table, string $column, string $parent): int
    {
        $wrapped = DB::getQueryGrammar()->wrapTable($table);

        // MySQL cannot delete from a table it also reads in a subquery (self-referencing keys).
        if ($table === $parent) {
            $ids = DB::table($table)->whereNotNull($column)->whereNotIn($column, DB::table($table)->select('id'))->pluck('id');

            return $ids->isEmpty() ? 0 : DB::table($table)->whereIn('id', $ids)->delete();
        }

        return DB::delete("DELETE FROM {$wrapped} WHERE " . $this->orphanCondition($table, $column, $parent));
    }

    private function clearDanglingValues(string $table, string $column, string $parent): void
    {
        $grammar = DB::getQueryGrammar();

        DB::update(
            'UPDATE ' . $grammar->wrapTable($table) . ' SET ' . $grammar->wrap($column) . ' = NULL WHERE '
            . $this->orphanCondition($table, $column, $parent)
        );
    }
}
