<?php

namespace App\Http\Controllers;
use App\Http\Resources\OrderCollection;

use App\Order;
use Illuminate\Support\Facades\DB;
use Yajra\Datatables\Datatables;
use Illuminate\Http\Request;

class UserOrderPageController extends Controller
{
    public function index(){

        return view('profile.user-order');
    }
    public function userOrders(){
        $orders=Order::where('user_id',auth()->user()->id);
        return Datatables::of($orders)
        ->addColumn('action', function ($order) {
            return '<a href="/dashboard/orders/view/'.$order->id.'" class="btn btn-xs btn-primary">View</a>';
        })
        ->editColumn('created_at', function ($order) {
            return $order->created_at->diffForHumans();
        })
        ->editColumn('status', function ($order) {
            if($order->status=='processing'){
                return '<span class="text-warning">'.$order->status.'</span>';
            }
            else if($order->status=='canceled'){
                return '<span class="text-error">'.$order->status.'</span>';
            }
            else{
                return '<span class="text-success">'.$order->status.'</span>';
            }
        })
        ->rawColumns(['status','action'])
        ->make(true);
    }
    public function viewOrderDetails($id){
        $order=Order::where('id',$id)->firstorfail();
        if($order->user_id!=auth()->user()->id){
            return abort('404');
        }

        return view('profile.order-details',compact('order'));
    }
    public function apiUserOrders(){
        $user_id=auth()->user()->id;
        // $orders=Order::all();
        // $orders=Order::where('user_id',auth()->user()->id)->all();

        //return new ProductCollection(Product::where( 'brand', '=',$id )->get());
        // return new OrderCollection($orders);
        return OrderCollection(Order::where( 'user_id', '=',1 )->get());
    }
    public function apiOrderDetails($id){
        $order = DB::table("orders")
            ->select("id","ordered_items")
            ->where('id','=',$id)
            ->get();
        //$order=Order::where('id',$id)->firstorfail();
        json_encode($order);
        $order[0]->ordered_items =unserialize(base64_decode($order[0]->ordered_items));
        return $order[0]->ordered_items;
    }
}
