<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Visitors; 
use App\User;
use App\Product;
use Session;
use App\Cart;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Auth;
use DB;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;
use App\Order;   

class UserController extends Controller
{
    public function __construct()
    {
      
       
        $this->middleware('auth');
        $user = Auth::user();
        
        
    }

    
    protected function dashboard()
    {
        Visitors::_save_visitors();
        $user = Auth::user();
        $default_address=  DB::table('addresses')->where('user_id',$user->id)->where('default_address',1)->first();
        return view('user.dashboard',compact(['user','default_address']));
    }


    protected function myprofile(Request $request)
    {
        Visitors::_save_visitors();
        $user = Auth::user();

        
        if ($request->isMethod('post')) 
        {

    
            $rules = array(
                'name'                  => 'required|string|max:100',                       
                'dob'                   => 'required|date|before:-18 years',
                'email'                 => 'unique:users,email,'.$user->id,
                'phone'                 => 'unique:users,phone,'.$user->id               
                                
            );
    
    
    
    
            $validator = validator()->make(request()->all(), $rules);
    
            // check if the validator failed -----------------------
            if ($validator->fails()) 
            {
        
                // get the error messages from the validator
               
              return back()->withErrors($validator)->withInput();

              
            } 
            else 
            
            {
                if($request->phone!=$user->phone)
                {
                    User::where('id',$user->id)->update( ['phone_verified_at' =>NULL]);
                }

               if(User::where('id',$user->id)->update( ['name' =>$request->name,'email'=>$request->email,
               'phone'=>$request->phone,'dob' =>$request->dob])) 

                return back()->with('success', " Profile Updated! ");
                  
        
            } 
        
            }
        $default_address=  DB::table('addresses')->where('user_id',$user->id)->where('default_address',1)->first();
        return view('user.myprofile',compact(['user','default_address']));
    }


    protected function changepassword(Request $request)
    {
        Visitors::_save_visitors();
        $user = Auth::user();


        if ($request->isMethod('post')) 
    {
        
        $rules = array(
              
            'currentpassword'       => 'required',
            'password'              => 'min:6|required_with:password_confirmation|same:password_confirmation',
            'password_confirmation' => 'min:6'
            
        );



        $validator = validator()->make(request()->all(), $rules);
        
        // check if the validator failed -----------------------
        if ($validator->fails()) {
    
            // get the error messages from the validator
           
            return back()->withErrors($validator);
          
        } 
       
        else 
        
        {
           

            if($request->password_confirmation==$request->password && $request->password==$request->currentpassword && Hash::check($request->currentpassword, $user->password) )
           {
            return redirect()->back()->withErrors("Current Password and New Passwords entered by you are same ");
           }

           if($request->password_confirmation==$request->password && Hash::check($request->currentpassword, $user->password) )
           {
            
            User::where('id', $user->id)->update(['password'=> Hash::make($request->password)]);
           
            return back()->with('success', " Your password has been changed successfully! ");
            
           }

           else
           {
            return back()->withErrors("Current Password entered is wrong !");
           }

            

        }

    }
        
            $default_address=  DB::table('addresses')->where('user_id',$user->id)->where('default_address',1)->first();
        
            return view('user.changepassword',compact(['user','default_address']));
    }

    protected function deleteprofile(Request $request)
    {
        Visitors::_save_visitors();
        $user = Auth::user();


        if ($request->isMethod('post')) 
    {
        
        $rules = array(
              
            'currentpassword'       => 'required'
            
        );



        $validator = validator()->make(request()->all(), $rules);
        
        // check if the validator failed -----------------------
        if ($validator->fails()) {
    
            // get the error messages from the validator
           
            return back()->withErrors($validator);
          
        } 
       
        else 
        
        {
                      

           if(Hash::check($request->currentpassword, $user->password) )
           {
        
            DB::table('deleted_users')->insert(['user_id'=>$user->id,'name'=>$user->name,'email'=>$user->email,
            'phone'=>$user->phone,'dob'=>$user->dob,'created_at'=>$user->created_at,
            'login_count'=>$user->login_count,'last_active'=>$user->last_active,'deleted_date'=>now()]); 

            User::where('id',$user->id)->delete();
            return back()->withErrors("Your Profile deleted successfully! ");
           }

           else
           {
            return back()->withErrors("Current Password entered is wrong !");
           }

            

        }

    }
        
            $default_address=  DB::table('addresses')->where('user_id',$user->id)->where('default_address',1)->first();
        
            return view('user.deleteprofile',compact(['user','default_address']));
    }


    protected function myaddresses()
    {
        Visitors::_save_visitors();
        $user = Auth::user();
        $addresses=DB::table('addresses')->where('user_id',$user->id)->orderby('default_address','desc')->get();
        return view('user.myaddresses',compact(['user','addresses']));
    }

    protected function addaddress(Request $request)
    {
        Visitors::_save_visitors();
        $user = Auth::user();


        
        if ($request->isMethod('post')) 
        {
    
            $rules = array(
                'name'                  => 'required|string|max:100',                       
                'phone'                 => 'required|numeric|digits:10',      
                'pincode'               => 'required|numeric|digits:6',
                'address'               => 'required|string|max:2000',                
                                
            );
    
    
    
    
            $validator = validator()->make(request()->all(), $rules);
    
            // check if the validator failed -----------------------
            if ($validator->fails()) 
            {
        
                // get the error messages from the validator
               
              return back()->withErrors($validator)->withInput();

              
            } 
            else 
            
            {


                DB::table('addresses')->insert(
                    ['user_name' =>$request->name,'phone' =>$request->phone,
                    'pincode' =>$request->pincode,'address'=>$request->address,
                    'user_id'=>$user->id
                    ]
                ); 

                  
        
            } 
        
            }
       
       return back();
    }
    protected function editaddress(Request $request,$id)
    {
        Visitors::_save_visitors();
        $user = Auth::user();


        
        if ($request->isMethod('post')) 
        {
    
            $rules = array(
                'name'                  => 'required|string|max:100',                       
                'phone'                 => 'required|numeric|digits:10',      
                'pincode'               => 'required|numeric|digits:6',
                'address'               => 'required|string|max:2000',                
                                
            );
    
    
    
    
            $validator = validator()->make(request()->all(), $rules);
    
            // check if the validator failed -----------------------
            if ($validator->fails()) 
            {
        
                // get the error messages from the validator
               
              return back()->withErrors($validator)->withInput();

              
            } 
            else 
            
            {


                DB::table('addresses')->where('id',$id)->where('user_id',$user->id)
                ->update(
                    ['user_name' =>$request->name,'phone' =>$request->phone,
                    'pincode' =>$request->pincode,'address'=>$request->address
                    
                    ]
                ); 

                  
        
            } 
        
            }
       
       return back();
    }

    protected function makedefault($id)
    {
        Visitors::_save_visitors();
        $user = Auth::user();
        if($addresses=DB::table('addresses')->where('id',$id)->where('user_id',$user->id)->update(['default_address'=>1]))
        $addresses=DB::table('addresses')->where('id','!=',$id)->where('user_id',$user->id)->update(['default_address'=>NULL]);
        return back();
    }

    protected function deleteaddress($id)
    {
        Visitors::_save_visitors();
        $user = Auth::user();
       
        $addresses=DB::table('addresses')->where('default_address',NULL)->where('id',$id)->where('user_id',$user->id)->delete();
        return back();
    }

    protected function orders()
    {
        Visitors::_save_visitors();
        $user = Auth::user();
       
        return view('user.orders',compact(['user']));
    }


    protected function orders_list()
    {
        $user = Auth::user();
        $order = Order::where('user_id',$user->id)->select('*');
        return datatables()->of($order)
        ->editColumn('created_at', function ($order) 
        {
           
            return date( 'jS M Y  h:i:s a', strtotime($order->created_at));
        })->editColumn('updated_at', function ($order) 
        {   
           if($order->updated_at==NULL){
               return "";
           }
            return date( 'jS M Y- h:i:s a', strtotime($order->updated_at));
        })->editColumn('total', function ($order) 
        {
          
           return '<i class="fa fa-inr" aria-hidden="true"> </i>'.$order->total;
        })->editColumn('payment_type', function ($order) 
        {
           if($order->payment_type=='pod')
           return "Pay On Delivery";

           if($order->payment_type=='cfs1')
           return "Collect from Store <small>(Cherunniyoor) </small>";

           if($order->payment_type=='cfs2')
           return "Collect from Store <small>(Varkala) </small>";
        })->editColumn('id', function ($order) 
        {
         
           $link=url('user/vieworder/'.$order->id);
            return "<a target=_blank  href=$link>". $order->id.'</a>';
        })->escapeColumns([])->make(true);

        
    } 


    protected function vieworder($id)
    {
        Visitors::_save_visitors();
        $user = Auth::user();
        $carts=Cart::where('user_id', $user->id)->get();
        $total=0;
        $order = Order::where('user_id',$user->id)->where('id',$id)->first();
        $order_items=DB::table('order_items')->where('order_id',$id)->get();
        
        return view('user.vieworder',compact(['user','carts','total','order','order_items','order']));
    }


    protected function cart()
    {
        Visitors::_save_visitors();
        $user = Auth::user();
        $carts=Cart::where('user_id', $user->id)->get();
        $total=0;
        $addresses=DB::table('addresses')->where('user_id',$user->id)->orderby('default_address','desc')->get();

        $service="";

        if(time() < strtotime("06:00:00") or time() > strtotime("18:00:00") )
        {
            $service="disabled";
        }
      
        return view('user.cart',compact(['user','carts','total','addresses','service']));


    }

    protected function checkout(Request $request)
    {
        Visitors::_save_visitors();
        $user = Auth::user();
        $carts=Cart::where('user_id', $user->id)->get();
        $subtotal=$total=$freedelivery=0;

        if(time() < strtotime("06:00:00") or time() > strtotime("18:00:00") )
        {
            return redirect('user/cart');
        }

        DB::table('final_carts')->where('user_id',$user->id)->delete();

        foreach($carts as $cart)
        {    
            $subtotal=$cart->quantity*Product::where('id', $cart->product_id)->value('price');
            $total+=$subtotal;

            DB::table('final_carts')->insert(
                ['user_id' =>$user->id,'product_id' =>$cart->product_id,
                'quantity' =>$cart->quantity,'subtotal'=>$subtotal,'created_at'=>now()
                ]
            ); 

           
        }
     
        if($total>199)
        {
         $freedelivery=1;
        }

        if($total==0)
        {
            return redirect('products')->withErrors("Empty Cart");
        }
       $final_carts= DB::table('final_carts')->where('user_id',$user->id)->get();

        $address_id=$request->input('address_id');

        $address= DB::table('addresses')->where('user_id',$user->id)->where('id',$address_id)->first();
        
        return view('user.checkout',compact(['user','final_carts','total','address','freedelivery']));
    }


    protected function orderprocessing(Request $request)
    {
        Visitors::_save_visitors();
        $user = Auth::user();
        

        if ($request->isMethod('post')) 
        {
    
            $rules = array(
                'name'                  => 'required|string|max:100',                       
                
                'phone'                 => 'required|numeric|digits:10',      
                'pincode'               => 'required|numeric|digits:6',
                'address'               => 'required|string|max:2000',                
                'payment_type'          => 'min:3',
                
            );
    
    
    
    
            $validator = validator()->make(request()->all(), $rules);
    
            // check if the validator failed -----------------------
            if ($validator->fails()) 
            {
       
                // get the error messages from the validator
               
               return redirect('user/cart')->withErrors($validator)->withInput();

              
            } 
            else 
            
            {


                $final_carts= DB::table('final_carts')->where('user_id',$user->id)->get();
                $total=0;

                if($final_carts->count()==0)
                {
                    abort(404);

                    return redirect('/');
                }

                else
                {
                    
              
                     
                    $data = new Order;
                    $data->name = $request->input('name');
                    $data->phone = $request->input('phone');
                    $data->email = $request->input('email');
                    $data->pincode = $request->input('pincode');
                    $data->address = $request->input('address');
                    $data->payment_type = $request->input('payment_type');
                    $data->created_at = date('Y-m-d H:i:s');
                    $data->updated_at = NULL;
                    $data->user_id = $user->id;
                    $data->total = 0;

                   if( $data->save())
                   {
                       $id=$data->id;
                    $total=0;
                    foreach($final_carts as $cart)
                    {    
                        
                        $total+=$cart->subtotal;

                        DB::table('order_items')->insert(
                            ['order_id' =>$id,'product_id' =>$cart->product_id,
                            'quantity' =>$cart->quantity,'total'=>$cart->subtotal
                            ]
                        ); 

                        Cart::where('user_id', $user->id)->where('product_id', $cart->product_id)->delete();
                        DB::table('final_carts')->where('user_id',$user->id)->delete();

                    }

                    if($total<499)
                    {
                        $total+=50;
                    }
                     Order::where('id',$id)->update(['total'=>$total,'updated_at'=>NULL]);

                     $message="Ladiez Kitchen :: New Order, Orderid:".$id.". Amount: INR ".$total.". Phone:".$request->phone;

                     $sms_api = new SmsController;
         
                     $sms_api->sms($message,9846478486);
                     $sms_api->sms($message,9895265589);

                     $order = Order::where('id',$id)->first();
                     $order_items=DB::table('order_items')->where('order_id',$id)->get();

                     foreach($order_items as $order_item)
                     { 
         
                     Product::where('id',$order_item->product_id)->decrement('count',$order_item->quantity); //decrementing stock
                     }

                    
                     return redirect('user/vieworder/'.$id);
                    
                    }

                   
                  
              
               
                }   
        
            } 
        
            }

    
        
        //return view('user.checkout',compact(['user','carts','total','address']));
    }


    protected function otpverification(Request $request)
    {
        Visitors::_save_visitors();
        $user = Auth::user();


        
        if ($request->isMethod('post')) 
        {
    
            $rules = array(
                                     
                'otp'                 => 'required|numeric|digits:4',             
                                
            );
    
    
    
    
            $validator = validator()->make(request()->all(), $rules);
    
            // check if the validator failed -----------------------
            if ($validator->fails()) 
            {
        
                // get the error messages from the validator
               
              return back()->withErrors($validator)->withInput();

              
            } 
            else 
            
            {


               $count= DB::table('otp')->where('token',$request->otp)->where('user_id',$user->id)->count();
               if($count==1)
               {
                User::where('id',$user->id)->update(['phone_verified_at'=>now()]);
                return redirect()->back();
               }

               else
               {
                return back()->withErrors("Invalid OTP"); 
               }
                  
        
            } 
        
            }
       
       
    }

    protected function resendotp(Request $request)
    {
        Visitors::_save_visitors();
        $user = Auth::user();


        
        if ($request->isMethod('post')) 
        {
            $token=mt_rand(1000,9999);
               $insert= DB::table('otp')->insert(
                    ['user_id' =>$user->id,'token' =>$token,
                    'created_at' =>now()
                    ]
                );

                if($insert) //Sending OTP
                {
                    $message="Ladiez Kitchen :: Hi, ".$user->name.",\n OTP : ".$token.". Don't share your OTP with anyone.";

                    $sms_api = new SmsController;
        
                    $sms_api->sms($message,$user->phone);
                }
        }

    }


    protected function history()
    {
        Visitors::_save_visitors();
        $user = Auth::user();
       
        return view('user.history',compact(['user']));
    }


    protected function history_list()
    {
        $user = Auth::user();
      
        $datatable = DB::table('login_log')->where('user_id', $user->id)->select('*');
        return datatables()->of($datatable)
        ->editColumn('time', function ($datatable) 
        {
                return date( 'jS M Y- h:i:s a', strtotime($datatable->time));
        })
        
        ->editColumn('device', function ($datatable) 
        {
            $d=$datatable->device;
            $d= str_replace("Mozilla/5.0", "", $d); 
            $d= str_replace("Gecko/20100101", "", $d); 
            $d= str_replace("Gecko", "", $d);
            $d= str_replace("AppleWebKit/", "", $d);
            $d= str_replace("AppleWebKit", "", $d);
            $d= str_replace("537.36", "", $d);
            $d= str_replace("KHTML, like Gecko", "", $d);
            $d= str_replace("KHTML, like ", "", $d);
            $d= str_replace("KHTML", "", $d);
            $d= str_replace("like", "", $d);
            $d= str_replace("(", "", $d);
            $d= str_replace(")", "", $d);
            
             return $d;
        })->escapeColumns([])->make(true);
    } 

}
