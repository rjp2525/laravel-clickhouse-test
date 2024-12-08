<?php

namespace App\Services;

use DB;
use PDF;

class ReportService
{
    public function generateReport($startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? now()->startOfQuarter()->format('Y-m-d');
        $endDate = $endDate ?? now()->format('Y-m-d');

        //$mariadbData = $this->getReportData('mysql', $startDate, $endDate);
        $clickhouseData = $this->getReportData('clickhouse', $startDate, $endDate);

        /**$pdf = PDF::loadView('reports.daily', [
            //'mariadbData' => $mariadbData['data'],
            'clickhouseData' => $clickhouseData['data'],
            //'mariadbTime' => $mariadbData['time'],
            'clickhouseTime' => $clickhouseData['time'],
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);**/

        //$pdf->save(storage_path('app/public/reports/daily_report.pdf'));

        return $clickhouseData;
    }

    private function getReportData($connection, $startDate, $endDate)
    {
        $startTime = microtime(true);

        if ($connection === 'mysql') {
            $orders = DB::connection($connection)->table('sales_orders')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as order_count')
                ->groupBy('date')
                ->get();

            $topLocations = DB::connection($connection)->table('sales_orders')
                ->join('addresses', 'sales_orders.shipping_address_id', '=', 'addresses.id')
                ->whereBetween('sales_orders.created_at', [$startDate, $endDate])
                ->selectRaw('DATE(sales_orders.created_at) as date, addresses.country, addresses.state, addresses.city, COUNT(*) as location_count')
                ->groupBy(['date', 'addresses.country', 'addresses.state', 'addresses.city'])
                ->orderBy('location_count', 'desc')
                ->get();

            $productSales = DB::connection($connection)->table('sales_order_rows')
                ->join('product_variants', 'sales_order_rows.product_variant_id', '=', 'product_variants.id')
                ->whereBetween('sales_order_rows.created_at', [$startDate, $endDate])
                ->selectRaw('DATE(sales_order_rows.created_at) as date, product_variants.sku, SUM(sales_order_rows.quantity) as total_quantity')
                ->groupBy(['date', 'product_variants.sku'])
                ->get();
        } elseif ($connection === 'clickhouse') {
            $startDate = date('Y-m-d', strtotime($startDate));
            $endDate = date('Y-m-d', strtotime($endDate));

            $orders = DB::connection($connection)->select("
                SELECT toDate(created_at) as date, COUNT(*) as order_count
                FROM sales_orders
                WHERE created_at BETWEEN '$startDate' AND '$endDate'
                GROUP BY date
                ORDER BY date ASC
            ");

            $topLocations = DB::connection($connection)->select("
                SELECT toDate(sales_orders.created_at) as date, addresses.country, addresses.state, addresses.city, COUNT(*) as location_count
                FROM sales_orders
                INNER JOIN addresses ON sales_orders.shipping_address_id = addresses.id
                WHERE sales_orders.created_at BETWEEN '$startDate' AND '$endDate'
                GROUP BY date, addresses.country, addresses.state, addresses.city
                ORDER BY location_count DESC
            ");

            $productSales = DB::connection($connection)->select("
                SELECT
                    toDate(sales_orders.created_at) as date,
                    product_variants.sku,
                    SUM(sales_order_rows.quantity) as total_quantity
                FROM sales_order_rows
                INNER JOIN product_variants
                    ON sales_order_rows.product_variant_id = product_variants.id
                INNER JOIN sales_orders
                    ON sales_order_rows.sales_order_id = sales_orders.id
                WHERE sales_orders.created_at BETWEEN '$startDate' AND '$endDate'
                GROUP BY date, product_variants.sku
                ORDER BY date ASC
            ");
        } else {
            throw new \Exception("Unsupported database connection: $connection");
        }

        $endTime = microtime(true);

        return [
            'data' => [
                'orders' => $orders,
                'topLocations' => $topLocations,
                'productSales' => $productSales,
            ],
            'time' => round(($endTime - $startTime) * 1000, 2).' ms',
        ];
    }
}
