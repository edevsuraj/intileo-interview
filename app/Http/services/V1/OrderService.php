<?php 

namespace App\Http\Services\V1;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Models\Order;
use App\Models\Product;
use Auth;
use App\Http\Services\V1\OrderService;

class OrderService
{
    public function createOrder($request)
    {
        DB::beginTransaction();
        try{
            foreach($request->items as $item) {
                $checkProduct = Product::findOrFail($item['product_id']);
                if($checkProduct->stock < $item['quantity']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Product stock is not enough',
                    ], 400);
                }

                $totalAmount = $checkProduct->amount * $item['quantity'];
                if($totalAmount > 5000){
                    $finalAmount = $totalAmount - ($totalAmount * 10 / 100);
                    $discountType = 'amount';
                    $discountAmount = $totalAmount * 10 / 100;
                    $discountPercentage = 10;
                    $discountCondition = 'Total amount is greater than 5000';
                }

                else if ($item['quantity'] > 10) {
                    $finalAmount = $totalAmount - ($totalAmount * 5 / 100);
                    $discountType = 'quantity';
                    $discountAmount = $totalAmount * 5 / 100;
                    $discountPercentage = 5;
                    $discountCondition = 'Quantity is greater than 10';
                } else {
                    $finalAmount = $totalAmount;
                    $discountType = null;
                    $discountAmount = 0;
                    $discountPercentage = 0;
                    $discountCondition = null;
                }

                $order = Order::create([
                    'user_id' => Auth::id(),
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'status' => 'confirmed',
                    'total_amount' => $totalAmount,
                    'discount_type' => $discountType,
                    'disount_condition' => $discountCondition,
                    'discount_amount' => $discountAmount,
                    'discount_percentage' => $discountPercentage,
                    'final_amount' => $finalAmount,
                ]);

                $checkProduct->stock = $checkProduct->stock - $item['quantity'];
                $checkProduct->save();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Order creation failed: '.$e->getMessage(),
            ], 500);
        }
        DB::commit();
        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
        ], 201);
    }

    public function cancelOrder($orderId)
    {
        DB::beginTransaction();
        try{
            $order = Order::findOrFail($orderId);
            if($order->user_id != Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to cancel this order',
                ], 403);
            }
            if($order->status == 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is already cancelled',
                ], 400);
            }
            $order->status = 'cancelled';
            $order->save();

            $product = Product::findOrFail($order->product_id);
            $product->stock = $product->stock + $order->quantity;
            $product->save();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
            ], 200);
        }
        catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function getOrders(Request $request, $userId)
    {
        try{
        $orders = Order::where('user_id', $userId);
        if($request->has('status')) {
            $orders = $orders->where('status', $request->status);
        } else if($request->has('start_date') && $request->has('end_date')) {
            $orders = $orders->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }
        $orders = $orders->paginate(10);
            return response()->json([
                'success' => true,
                'message' => 'Orders retrieved successfully',
                'data' => $orders,
            ], 200);
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function generateReport(Request $request)
    {
        try{
            $orders = Order::query();
            if($request->has('start_date') && $request->has('end_date')) {
                $orders = $orders->whereBetween('created_at', [$request->start_date, $request->end_date]);
            }
            $totalOrders = $orders->count();
            $totalRevenue = $orders->sum('final_amount');
            $totalDiscounts = $orders->sum('discount_amount'); 

            $mostOrderedProduct =  $orders->select('product_id', DB::raw('count(*) as total_count'))
                ->groupBy('product_id')
                ->orderByDesc('total_count')
                ->first();
                
            $mostOrderedProductName = $mostOrderedProduct ? Product::find($mostOrderedProduct->product_id)->name : null; 
            $mostOrderedProduct = $mostOrderedProductName ? [
                'product_id' => $mostOrderedProduct->product_id,
                'product_name' => $mostOrderedProductName,
                'total_count' => $mostOrderedProduct->total_count,
            ] : null;   
            
            return response()->json([
                'success' => true,
                'message' => 'Report generated successfully',
                'data' => [
                    'total_orders' => $totalOrders,
                    'total_revenue' => $totalRevenue,
                    'total_discounts' => $totalDiscounts,
                    'most_ordered_product' => $mostOrderedProduct,
                ],
            ], 200);
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}