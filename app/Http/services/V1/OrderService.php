<?php 

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

                if ($item['quantity'] > 10) {
                    $finalAmount = $totalAmount - ($totalAmount * 5 / 100);
                    $discountType = 'quantity';
                    $discountAmount = $totalAmount * 5 / 100;
                    $discountPercentage = 5;
                    $discountCondition = 'Quantity is greater than 10';
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
        $order = Order::findOrFail($orderId);
        if ($order->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Order is already cancelled',
            ], 400);
        }

        $order->status = 'cancelled';
        $order->save();

        $product = Product::findOrFail($order->product_id);
        $product->stock += $order->quantity;
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully',
        ], 200);
    }
}