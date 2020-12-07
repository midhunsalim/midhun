<?php

namespace App\Http\Controllers;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Visitors;
use App\User;   
use App\Product;
use App\Cart;
use Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Auth;


class ProductController extends Controller
{


    public function __construct()
    {
        
    }
    protected function addtocart(Request $request,$id)
    {

       
        

        $quantity=$request->input('quantity');
        $product = Product::find($id);

       
 
        if(!$product) {
 
            abort(404);
 
        }

       
        if (Auth::check())
        {
        $user = Auth::user();

        $cart=Cart::where('user_id', $user->id)->where('product_id', $id)->exists();
        if($cart) 
        {
            $cart=Cart::where('user_id', $user->id)->where('product_id', $id)->first();

            if(($cart->quantity+$quantity)>$product->count) 
               {
                $quantity=$product->count-$cart->quantity;
               }
            Cart::where('user_id', $user->id)->where('product_id', $id)->increment('quantity',$quantity);
            Session::flash('message', "Cart Updated !");
     
        }
        else
        { 
            Cart::insert(
            ['user_id' =>$user->id,'product_id' =>$id,
            'quantity' =>$quantity,'created_at' =>now()
            ]
        ); 
            
        Session::flash('message', "Cart Updated !");
        }

        return redirect()->back();

        }


        else
        {
            session(['link' => url()->previous()]);
            return redirect('login');
        }

    }

    protected function updatecart(Request $request,$id)
    {

       
        

        $quantity= $original=$request->input('quantity');
        $quantity1=$request->input('quantity1');
        $quantity2=$request->input('quantity2');

        if($original!=$quantity1)
        {
            $quantity=$quantity1; 
        }
        if($original!=$quantity2)
        {
            $quantity=$quantity2;  
        }


        $cart = Cart::find($id);
 
        if(!$cart) {
 
            abort(404);
 
        }
        if (Auth::check())
        {
        $user = Auth::user();

        if($id) {

        $cart=Cart::where('user_id', $user->id)->where('id', $id)->exists();
        if($cart) 
        {
            $cart=Cart::where('user_id', $user->id)->where('id', $id)->first();
            $product = Product::find($cart->product_id);

            if($quantity>$product->count) 
               {
                $quantity=$product->count;
               }
            
            Cart::where('user_id', $user->id)->where('id', $id)
            ->update(
                ['quantity' =>$quantity,'updated_at' =>now()
                ]
            ); 

            if(!$quantity) 
            {
                
                Cart::where('user_id', $user->id)->where('id', $id)->delete();
                
         
            }
     
        }
        
        session()->flash('success', 'Cart Updated successfully');
        return redirect()->back();

        }
    }


        else
        {
            session(['link' => url()->previous()]);
            return redirect('login');
        }

    }

    protected function deletefromcart(Request $request)
    {

    
        if (Auth::check())
        {
        $user = Auth::user();

        if($id=$request->id) {

        $cart=Cart::where('user_id', $user->id)->where('id', $id)->exists();
        if($cart) 
        {
            
            Cart::where('user_id', $user->id)->where('id', $id)->delete();
            
     
        }
       

        //
        session()->flash('success', 'Product removed successfully');


        }
    }


        else
        {
            session(['link' => url()->previous()]);
            return redirect('login');
        }

    }
}
