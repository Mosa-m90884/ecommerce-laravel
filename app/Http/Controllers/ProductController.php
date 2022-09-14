<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductCollection;
use App\Order;
use App\Product;
use App\ProductTage;
use App\User;
use Darryldecode\Cart\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      return new ProductCollection(Product::all());
    //    return response(['prod'=>Product::all(),'kkk'=>'nnn'],'222');
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required',
            'address' => 'required',
            'country' => 'required',
            'state' => 'required',
            'zip' => 'required',
            'shipping_address' => 'required_if:shipping_address_different,==,on',
            'shipping_city' => 'required_if:shipping_address_different,==,on',
            'shipping_state' => 'required_if:shipping_address_different,==,on',
            'shipping_zip' => 'required_if:shipping_address_different,==,on',
        ]);
        if (Auth::user()) {
            $user_id = Auth::user()->id;
        } else {
            $user_id = User::where('email', $request['email'])->first()->id;
        }
        if ($validator->fails()) {
            return response(['errors' => $validator->errors()], 422);
        }
        $items = $request['items'];
        echo json_encode($items);
        foreach ($items as $data) {
            $id = $data['id'];
            $qty = $data['quantity'];
            $product = Product::find($id);
            $product_stock = $product->quantity - $qty;
            if ($product_stock < 0) {
                notify()->error('You cannot add that amount. Only ' . $product->quantity . ' items left');
                return back();
            }

            if ($product->discounted_price > 0) {
                $discount = $product->regular_price - $product->discounted_price;
                $itemCondition = new \Darryldecode\Cart\CartCondition(array(
                    'name' => $product->title . ' discount',
                    'type' => 'product discount',
                    'value' => '-' . $discount,
                ));
                Cart::add(array(
                    array(
                        'id' => $product->id,
                        'name' => $product->title,
                        'price' => $product->regular_price,
                        'quantity' => $qty,
                        'associatedModel' => 'App\Product',
                        'conditions' => [$itemCondition]
                    ),
                ));
            } else {
                Cart::add(array(
                    array(
                        'id' => $product->id,
                        'name' => $product->title,
                        'price' => $product->regular_price,
                        'quantity' => $qty,
                        'associatedModel' => 'App\Product',
                    ),
                ));
            }

        }
        echo Cart::getContent();

        if ($request['shipping_address_different'] == 'on') {
            $shipping_country = $request['shipping_country'];
            $shipping_address = $request['shipping_address'];
            $shipping_city = $request['shipping_city'];
            $shipping_state = $request['shipping_state'];
            $shipping_zip = $request['shipping_zip'];
        } else {
            $shipping_country = NULL;
            $shipping_address = NULL;
            $shipping_city = NULL;
            $shipping_state = NULL;
            $shipping_zip = NULL;
        }

        //payment method
        $payment_method = 'cod';
        $pp_id = null;


        //calculate discount
        $sum = 0;
        foreach (Cart::getContent() as $row) {
            $sum = $sum + $row->getPriceSumWithConditions();
        }

        function twoDecimal($number)
        {
            return number_format((float)$number, 2, '.', '');
        }

        if (count(Cart::getConditionsByType('coupon')) != 0) {
            $discount = twoDecimal(Cart::getConditionsByType('coupon')->first()->getCalculatedValue($sum));
        } else {
            $discount = 0;
        }

        $latest_order = Order::latest()->first();
        if (is_null($latest_order)) {
            Order::truncate();
            $invoice = '0001';
        } else {
            $invoice = $latest_order->id + 1;
        }

        // $invoice=uniqueInvoice($invoice);

        Order::create([
            'billing_country' => $request['country'],
            'billing_address' => $request['address'],
            'billing_city' => $request['city'],
            'billing_state' => $request['state'],
            'billing_zip' => $request['zip'],
            'shipping_country' => $shipping_country,
            'shipping_address' => $shipping_address,
            'shipping_city' => $shipping_city,
            'shipping_state' => $shipping_state,
            'shipping_zip' => $shipping_zip,
            'billing_phone' => $request['phone'],
            'billing_name' => $request['name'],
            'billing_email' => $request['email'],
            'status' => 'processing',
            'ordered_items' => base64_encode(serialize(Cart::getContent())),
            'invoice_id' => $invoice,
            'user_id' => $user_id,
            'total_amount' => Cart::getTotal(),
            'conditions' => base64_encode(serialize(Cart::getConditions())),
            'payment_method' => $payment_method,
            'pp_invoice_id' => $pp_id,
            'discount' => $discount,
            'discounted_subtotal' => Cart::getSubTotal(),
        ]);
        foreach ($items as $data) {
            $id = $data['id'];
            $qty = $data['quantity'];
            // DB::insert('insert into ord_item(p_id,quantity) values('||$id||','||$qty||')');
            DB::insert('insert into ord_item(p_id,quant) values(?,?)',[$id,$qty]);
        }



        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    function uniqueInvoice($invoice)
    {
        $i = 1;

        $invoice_with_zero = sprintf('%04d', $invoice);

        $check_invoice = Order::where('invoice_id', $invoice_with_zero)->first();

        while (!$check_invoice) {
            $invoice_new = $invoice + $i;
            $invoice_new_with_zero = sprintf('%04d', $invoice_new);
            $check_invoice = Order::where('invoice_id', $invoice_new_with_zero)->first();
            if (!$check_invoice) {
                return $invoice_new_with_zero;
            }

            $i++;
        }
    }

    public function apiDesacount()
    {
        $ff = collect();
        $products = DB::select("SELECT id,title,quantity,tag,regular_price,discounted_price,category_id  ,small_description,
          large_description,primary_image,other_image,0 as tred, 
                (regular_price-discounted_price)*100/regular_price as rate,created_at,updated_at,featured FROM `products` 
                 GROUP BY id HAVING discounted_price is not null and  discounted_price!=0 ");
        // DB::insert('insert into ord_item(p_id,quantity) values(?,?)',[1,5]);

        /**   $products = DB::table("products")
         * ->select("id","title","category_id","small_description","large_description","regular_price"
         * ,"discounted_price","primary_image","other_image")->groupBy(id)
         * ->having("discounted_price","is not","null")
         * ->get();**/
        //$order=Order::where('id',$id)->firstorfail();
        // $order[0]->ordered_items =unserialize(base64_decode($order[0]->ordered_items));
        return new ProductCollection($products);

        //  return json_encode($products);

    }
    public function apitred()
    {
        $ff = collect();
        $products = DB::select("SELECT products.id,0 as rate,tag,quantity,title,regular_price,
        discounted_price,category_id,small_description,created_at,updated_at,featured,
          large_description,primary_image,other_image,sum(ord_item.quant) as tred FROM `ord_item`,products
           WHERE products.id=ord_item.p_id GROUP BY p_id HAVING tred>=3 ");
        //DB::insert('insert into ord_item(p_id,quantity) values(?,?)',[1,5]);
        $g='[';
        $comm=',';
        $cot='"';
        $e=']';
        $sl='\\';
        $op1='1';
        $op2='';
        $f="products$sl\June2022$sl\\5anrbVDfF25HDVYywzzh.jpg$cot$comm";
        $p2="products$sl\June2022$sl\WltuTExj4Tgyx0UZmqsz.jpg$cot$comm";
        $p3="products$sl\June2022$sl\FSGzuv9JojFcVgva2NL2.jpg$cot";
        //$p4="products$sl\June2022$sl\\6CgCs6Dcv1nlVlfQnwTi.jpg$cot$comm";
        //$p5="products$sl\June2022$sl\GZRnjeJv6qzgEcrM3X0l.jpg$cot";

        $res="$g$cot$f$cot$p2$cot$p3$e";
        $nn ="kk $g";
        //DB::update('UPDATE products SET other_image=');
        /**  DB::table('products')
        ->where('title', 'like','tv%')
        ->update(['other_image' => $res]);**/
        return new ProductCollection($products);
    }
    public function newArr()
    {
        $dt  =Carbon::now();

        $products = Product::where( 'created_at', '>', Carbon::now()->subDays(30))
            ->get();
        return new ProductCollection($products);
    }
    public function gettages()
    {
        $dt  =Carbon::now();

        echo $dt->subDay();                      // 2012-03-03 00:00:00
        echo $dt->subDays(29);
        $uss = ProductTage::all();
        return json_encode($uss);
    }
    public function gettageproduct($id)
    {
        return new ProductCollection(Product::where( 'tag', '=',$id )->get());
        //$uss = Product::where( 'tag', '=',$id )->get();
        //return json_encode($uss);
    }
    public function getBrandproduct($id)
    {
        return new ProductCollection(Product::where( 'brand', '=',$id )->get());

        //$uss = Product::where( 'tag', '=',$id )->get();
        //return json_encode($uss);
    }
    public function getctgproduct($id)
    {
        return new ProductCollection(Product::where( 'category_id', '=',$id )->get());

        //$uss = Product::where( 'tag', '=',$id )->get();
        //return json_encode($uss);
    }
}
