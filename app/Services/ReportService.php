<?php
// filepath: app/Services/ReportService.php

namespace App\Services;

use App\Models\Order;
use App\Models\Staff;
use App\Models\MenuItem;
use Infrastructure\Tenancy\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReportService
{
    public function getSalesReport($startDate, $endDate, $groupBy = 'hour')
    {
        $query = Order::where('restaurant_id', Tenant::id())
            ->where('status', 'paid')
            ->whereBetween('placed_at', [$startDate, $endDate]);

        return match($groupBy) {
            'hour' => $query->select(
                DB::raw('EXTRACT(HOUR FROM placed_at) as period'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('AVG(total) as average_order_value')
            )->groupBy('period')->orderBy('period')->get(),
            
            'day' => $query->select(
                DB::raw('DATE(placed_at) as period'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('AVG(total) as average_order_value')
            )->groupBy('period')->orderBy('period')->get(),
            
            'week' => $query->select(
                DB::raw('EXTRACT(WEEK FROM placed_at) as period'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('AVG(total) as average_order_value')
            )->groupBy('period')->orderBy('period')->get(),
            
            'month' => $query->select(
                DB::raw('EXTRACT(MONTH FROM placed_at) as period'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('AVG(total) as average_order_value')
            )->groupBy('period')->orderBy('period')->get(),
        };
    }

    public function exportReport($type, $format, $startDate, $endDate)
    {
        $data = $this->getReportData($type, $startDate, $endDate);
        
        if ($format === 'pdf') {
            return $this->exportToPdf($type, $data, $startDate, $endDate);
        }
        
        return $this->exportToExcel($type, $data, $startDate, $endDate);
    }

    private function getReportData($type, $startDate, $endDate)
    {
        return match($type) {
            'sales' => $this->getSalesData($startDate, $endDate),
            'items' => $this->getItemsData($startDate, $endDate),
            'staff' => $this->getStaffData($startDate, $endDate),
        };
    }

    private function getSalesData($startDate, $endDate)
    {
        return Order::where('restaurant_id', Tenant::id())
            ->where('status', 'paid')
            ->whereBetween('placed_at', [$startDate, $endDate])
            ->with(['table', 'waiter'])
            ->get();
    }

    private function getItemsData($startDate, $endDate)
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->join('categories', 'menu_items.category_id', '=', 'categories.id')
            ->where('orders.restaurant_id', Tenant::id())
            ->where('orders.status', 'paid')
            ->whereBetween('orders.placed_at', [$startDate, $endDate])
            ->select(
                'menu_items.name',
                'categories.name as category_name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.quantity * order_items.unit_price) as total_revenue')
            )
            ->groupBy('menu_items.id', 'menu_items.name', 'categories.name')
            ->orderBy('total_quantity', 'desc')
            ->get();
    }

    private function getStaffData($startDate, $endDate)
    {
        return Staff::with(['attendances', 'assignedOrders'])
            ->where('restaurant_id', Tenant::id())
            ->get()
            ->map(function ($staff) use ($startDate, $endDate) {
                $attendances = $staff->attendances()
                    ->whereBetween('clock_in', [$startDate, $endDate])
                    ->whereNotNull('clock_out')
                    ->get();

                $orders = $staff->assignedOrders()
                    ->whereBetween('placed_at', [$startDate, $endDate])
                    ->where('status', 'paid')
                    ->get();

                return [
                    'name' => $staff->full_name,
                    'position' => $staff->position,
                    'hours_worked' => $attendances->sum('hours_worked'),
                    'orders_served' => $orders->count(),
                    'revenue_generated' => $orders->sum('total'),
                ];
            });
    }

    private function exportToPdf($type, $data, $startDate, $endDate)
    {
        $pdf = Pdf::loadView("reports.{$type}", [
            'data' => $data,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedAt' => now(),
        ]);

        $filename = "reports/{$type}-report-" . now()->format('Y-m-d-H-i-s') . '.pdf';
        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }

    private function exportToExcel($type, $data, $startDate, $endDate)
    {
        // You'll need to create Excel export classes
        $filename = "reports/{$type}-report-" . now()->format('Y-m-d-H-i-s') . '.xlsx';
        
        // Simplified export - you might want to use Laravel Excel package
        $csvContent = $this->convertToCsv($data);
        Storage::disk('public')->put($filename, $csvContent);

        return $filename;
    }

    private function convertToCsv($data)
    {
        $output = fopen('php://temp', 'r+');
        
        if (!empty($data)) {
            // Write headers
            fputcsv($output, array_keys((array) $data[0]));
            
            // Write data
            foreach ($data as $row) {
                fputcsv($output, (array) $row);
            }
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}