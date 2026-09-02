<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Production;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AIService
{
    /**
     * Analyze menu performance using BCG Matrix algorithm over the last 30 days.
     */
    public function getBcgMenuAnalysis(?int $companyId): array
    {
        if (!$companyId) {
            return [
                'products' => [],
                'avg_sales_threshold' => 0,
                'total_sales_volume' => 0
            ];
        }

        $startDate = Carbon::now()->subDays(30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Fetch all products for the company
        $products = Product::where('company_id', $companyId)->get();
        if ($products->isEmpty()) {
            return [
                'products' => [],
                'avg_sales_threshold' => 0,
                'total_sales_volume' => 0
            ];
        }

        // Fetch total quantity sold for each product in the last 30 days
        $salesData = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.company_id', $companyId)
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->select('sale_items.product_id', DB::raw('SUM(sale_items.quantity) as total_qty'))
            ->groupBy('sale_items.product_id')
            ->pluck('total_qty', 'sale_items.product_id')
            ->all();

        // Calculate average COGS (HPP) for each product
        $cogsData = Production::where('company_id', $companyId)
            ->where('status', 'done')
            ->select('product_id', DB::raw('AVG(unit_hpp_snapshot) as avg_hpp'))
            ->groupBy('product_id')
            ->pluck('avg_hpp', 'product_id')
            ->all();

        // Calculate total sales volume and average sales threshold
        $totalQtySold = array_sum($salesData);
        $totalProductsCount = $products->count();
        $avgSalesThreshold = $totalProductsCount > 0 ? $totalQtySold / $totalProductsCount : 0;

        $classifiedProducts = [];

        foreach ($products as $product) {
            $qtySold = (float) ($salesData[$product->id] ?? 0);
            
            // Get COGS with fallback to 60% of selling price
            $sellingPrice = (float) $product->selling_price;
            $cogs = (float) ($cogsData[$product->id] ?? 0);
            if ($cogs <= 0) {
                $cogs = $sellingPrice * 0.6; // Fallback to 60% if no production data exists
            }

            // Calculate profit margin percentage
            $marginPercent = 0.0;
            if ($sellingPrice > 0) {
                $marginPercent = (($sellingPrice - $cogs) / $sellingPrice) * 100;
            }

            // Classification
            $isHighSales = $qtySold > $avgSalesThreshold;
            $isHighMargin = $marginPercent > 40.0;

            if ($isHighSales && $isHighMargin) {
                $category = 'STAR';
                $badgeColor = 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200/50';
                $icon = 'local_fire_department';
                $recommendation = 'Saran AI: Menu andalan! Pertahankan kualitas dan ekspos di banner utama.';
            } elseif ($isHighSales && !$isHighMargin) {
                $category = 'CASH COW';
                $badgeColor = 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400 border border-indigo-200/50';
                $icon = 'payments';
                $recommendation = 'Saran AI: Pendapatan stabil. Sangat cocok untuk paket bundling promo.';
            } elseif (!$isHighSales && $isHighMargin) {
                $category = 'QUESTION MARK';
                $badgeColor = 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400 border border-amber-200/50';
                $icon = 'help';
                $recommendation = 'Saran AI: Margin tinggi namun kurang populer. Tingkatkan visibilitas promosi.';
            } else {
                $category = 'DOG';
                $badgeColor = 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-400 border border-rose-200/50';
                $icon = 'warning';
                $recommendation = 'Saran AI: Margin tipis & kurang diminati. Evaluasi supplier bahan baku atau modifikasi resep.';
            }

            $classifiedProducts[] = [
                'product' => $product,
                'qty_sold' => $qtySold,
                'margin_percent' => $marginPercent,
                'cogs' => $cogs,
                'category' => $category,
                'badge_color' => $badgeColor,
                'icon' => $icon,
                'recommendation' => $recommendation
            ];
        }

        // Sort by sales volume descending
        usort($classifiedProducts, function ($a, $b) {
            return $b['qty_sold'] <=> $a['qty_sold'];
        });

        return [
            'products' => $classifiedProducts,
            'avg_sales_threshold' => $avgSalesThreshold,
            'total_sales_volume' => $totalQtySold
        ];
    }
}
