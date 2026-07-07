<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class BulkImportController extends Controller
{
    public function index()
    {
        return view('admin.product.bulk-import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $import = new ProductsImport();
        Excel::import($import, $request->file('file'));

        return redirect()->route('admin.products.index')
            ->with('success', "Bulk import complete! {$import->imported} products imported.");
    }

    /**
     * Download the CSV template for users to fill in
     */
    public function downloadTemplate()
    {
        $headers = [
            'name_en', 'name_bn', 'description_en', 'description_bn',
            'category', 'price', 'discount', 'brand', 'model',
            'warranty_period', 'video_link', 'low_stock_threshold',
            'sku', 'variant_price', 'stock',
            'attribute_color', 'attribute_size', 'attribute_storage',
        ];

        $exampleRows = [
            [
                'Premium T-Shirt', 'প্রিমিয়াম টি-শার্ট', 'High quality cotton t-shirt', 'উচ্চ মানের কটন টি-শার্ট',
                'Clothing', '500', '10', 'Levi\'s', 'Slim Fit',
                'N/A', '', '5',
                'TSH-RED-M', '', '100',
                'Red', 'Medium', '',
            ],
            [
                'Premium T-Shirt', '', '', '',
                'Clothing', '', '', '', '',
                '', '', '',
                'TSH-RED-L', '', '80',
                'Red', 'Large', '',
            ],
            [
                'Samsung Galaxy S24', 'স্যামসাং গ্যালাক্সি এস২৪', 'Flagship Android phone', 'ফ্ল্যাগশিপ অ্যান্ড্রয়েড ফোন',
                'Electronics', '80000', '5', 'Samsung', 'Galaxy S24',
                '1 Year', 'https://youtube.com/...', '3',
                'SAM-S24-256-BLK', '', '15',
                'Black', '', '256GB',
            ],
        ];

        $callback = function () use ($headers, $exampleRows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            foreach ($exampleRows as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="product_import_template.csv"',
        ]);
    }
}
