<?php

namespace App\Http\Controllers;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Visitors;  
use App\Admin;
use App\Product;
use Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Auth;
use App\Order;
use App\User;



class AdminController extends Controller
{

    private $admin_url='admin/ladiezkitchen/superuser/404';
    public function __construct()
    {
       
        $this->middleware('auth:admin'); 
    }

    
   
    protected function dashboard()
    {
        $admin = Auth::guard('admin')->user();
        $admin_url=$this->admin_url;
        $product=1;

        $products=Product::where('status', 1)->latest()->paginate(7); 
        $orders = Order::latest()->paginate(7); 
        return view('admin.dashboard',compact(['admin','products','orders','admin_url']));
    }

    protected function orders()
    {
        
        $admin_url=$this->admin_url;
        $admin = Auth::guard('admin')->user();
        return view('admin.orders',compact(['admin','admin_url']));
    }


    protected function orders_list()
    {
        $admin = Auth::guard('admin')->user();
        $admin_url=$this->admin_url;
        $order = Order::select('*');
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
         
           $link=url($this->admin_url.'/vieworder/'.$order->id);
           
            return "<a target=_blank  href=$link>". $order->id.'</a>';
        })->escapeColumns([])->make(true);

        
    }
    
    
    protected function vieworder($id)
    {
        $admin = Auth::guard('admin')->user();
        $admin_url=$this->admin_url;
        $order = Order::where('id',$id)->first();
        $order_items=DB::table('order_items')->where('order_id',$id)->get();
       
              
        return view('admin.vieworder',compact(['admin','admin_url','order_items','order']));

    }



    protected function change_order_status($type,$id)
    {
       $order = Order::where('id',$id)->first();
       $order_items=DB::table('order_items')->where('order_id',$id)->get();
       
       
       if( $order->status !="Delivered" && $type=="cancel")
        {
            Order::where('id',$id)->update(['status'=>'Cancelled','updated_at'=>NULL]);
            foreach($order_items as $order_item)
            { 

            Product::where('id',$order_item->product_id)->increment('count',$order_item->quantity); //incrementing stock
            }

            $message="Order Cancelled.\nOrder_ID:".$order->id.".\nAmount: INR ".$order->total."\n-Ladiez Kitchen";

            $sms_api = new SmsController;

            $sms_api->sms($message,$order->phone);
    
        }
        
        if( $order->status =="Processing" && $type=="makeconfirmed")
        {
           
            Order::where('id',$id)->update(['status'=>'Confirmed','updated_at'=>NULL]);
            $message="Order Confirmed.\nOrder_ID:".$order->id.".\nAmount: INR ".$order->total."\n-Ladiez Kitchen";

            $sms_api = new SmsController;

            $sms_api->sms($message,$order->phone);
    
        }


        if( $order->status=='Confirmed' && $type=="makedelivered")
        {
            Order::where('id',$id)->update(['status'=>'Delivered','updated_at'=>now()]);

            foreach($order_items as $order_item)
            { 

            Product::where('id',$order_item->product_id)->increment('sold',$order_item->quantity); // incrementing sold


            }
            //Sms API

            $message="Order Delivered.\nOrder_ID:".$order->id.".\nAmount: INR ".$order->total."\n-Ladiez Kitchen";

            $sms_api = new SmsController;

            $sms_api->sms($message,$order->phone);
    
        }

        return back();
    }
 
    protected function addproduct(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $admin_url=$this->admin_url;

        if ($request->isMethod('post')) {
    
            $rules = array(
                   
                'title'          => 'required',
                'type'           => 'required',
                'price'          => 'required',
                'count'          => 'required',
                'description'    => 'required|min:6',
                'image'          => 'required|image|mimes:jpeg,jpg|max:4048',
                
            );
    
    
    
            $validator = validator()->make(request()->all(), $rules);
    
            // check if the validator failed -----------------------
            if ($validator->fails()) {
        
                // get the error messages from the validator
               
                return redirect()->back()->withErrors($validator)->withInput();
              
            } else 
            
            {
                     
                    $data = new Product;
                    $data->title = $request->input('title');
                    $data->price = $request->input('price');
                    $data->type = $request->input('type');
                    $data->count = $request->input('count');
                    $data->description = $request->input('description');
                    $data->created_at = date('Y-m-d H:i:s');
                    $data->added_by = $admin->id;

                   if( $data->save())
                   {

                    $name = $data->id.'.jpg';

                    $image = $request->file('image');

                    $destinationPath = 'assets/images/products/';
                    $image->move($destinationPath, $name);

                    return redirect($admin_url.'/viewproduct/'.$data->id);
                   
                   }

                   
               
                   
        
            } 
        
            }
        
          

            return view('admin.addproduct',compact(['admin','admin_url']));


    }


    protected function editproduct(Request $request,$id)
    {
        $admin = Auth::guard('admin')->user();
        $admin_url=$this->admin_url;

        if ($request->isMethod('post')) {
    
            $rules = array(
                   
                
                'price'          => 'required',
                'count'          => 'required',
                'description'    => 'required|min:6',
                
                
            );
    
    
    
            $validator = validator()->make(request()->all(), $rules);
    
            // check if the validator failed -----------------------
            if ($validator->fails()) {
        
                // get the error messages from the validator
               
                return redirect()->back()->withErrors($validator)->withInput();
              
            } else 
            
            {
                     
                  
                    $status = $request->input('status');
                    $price = $request->input('price');                    
                    $count = $request->input('count');
                    $description = $request->input('description');
                    

                    Product::where('id', $id)->update(['status' =>$status,'price' =>$price,
                    'count' =>$count,'description' =>$description,'updated_at' =>now()]);

                   if( $image = $request->file('image'))
                   {

                    $name = $id.'.jpg';

                   

                    $destinationPath = 'assets/images/products/';
                    $image->move($destinationPath, $name);

                    
                   
                   }

                   
               
                   
        
            } 
            return redirect($admin_url.'/viewproduct/'.$id);
            }
        
          

            $product=Product::where('id',$id)->first();
        
        return view('admin.editproduct',compact(['admin','product','admin_url']));


    }
    protected function viewproduct(Request $request, $id) 
    {  $admin_url=$this->admin_url;
        
       
        $admin = Auth::guard('admin')->user();
        $product=Product::where('id',$id)->first();
        
        return view('admin.viewproduct',compact(['admin','product','admin_url']));


    }


    protected function viewphoto(Request $request, $w,$h) //Show User photo
    {
        $admin_url=$this->admin_url;
       
        $admin = Auth::guard('admin')->user();
      
        $file= storage_path('app/public/admin/images/'.$admin->id.'.jpg');
        if (!file_exists($file)) {
            $file= storage_path('app/public/admin/images/admin.jpg');
          }

        $img = file_get_contents($file);
      return response($img)->header('Content-type','image/png');


      

      /*  $required_width=$w;
        $required_height=$h;

       

       list($width, $height) = getimagesize($file);

        $image = imagecreatefromjpeg($file);
        $thumbImage = imagecreatetruecolor($required_width, $required_height);
        imagecopyresized($thumbImage, $image, 0, 0, 0, 0, $required_width,$required_height, $width, $height);
        imagedestroy($image);
        //imagedestroy($thumbImage); do not destroy before display :)
        ob_end_clean();  // clean the output buffer ... if turned on.
        header('Content-Type: image/jpeg');  
        imagejpeg($thumbImage); //you does not want to save.. just display
        imagedestroy($thumbImage); //but not needed, cause the script exit in next line and free the used memory
        exit;*/

      // $img = file_get_contents(public_path($file));
     //return response($img)->header('Content-type','image/jpeg');

    }
    

    protected function products()
    {
        $admin = Auth::guard('admin')->user();
        $admin_url=$this->admin_url;
        return view('admin.products',compact(['admin','admin_url']));
    } 

    protected function products_list()
    {
        $admin = Auth::guard('admin')->user();
        $admin_url=$this->admin_url;
        $product = Product::select('*');
        return datatables()->of($product)
        ->editColumn('created_at', function ($product) 
        {
           
            return date( 'jS M Y- h:i:s a', strtotime($product->created_at));
        })->editColumn('updated_at', function ($product) 
        {
           
            return date( 'jS M Y- h:i:s a', strtotime($product->updated_at));
        })->editColumn('title', function ($product) 
        {
           $url=asset('assets/images/products/'.$product->id.'.jpg');
           $link=url($this->admin_url.'/viewproduct/'.$product->id);
            return "<a target=_blank  href=$link>". $product->title.'<br><img width=70 height=70 src='.$url.' ></a>';
        })->escapeColumns([])->make(true);
    } 


    protected function users()
    {
        $admin_url=$this->admin_url;
        $admin = Auth::guard('admin')->user();
        return view('admin.users',compact(['admin','admin_url']));
    } 

    protected function users_list()
    {
        $admin = Auth::guard('admin')->user();
        $admin_url=$this->admin_url;
        $users = User::select('*');
        return datatables()->of($users)
        ->editColumn('created_at', function ($users) 
        {
            //change over here
            //return date('d-m-Y ,hh:mm', strtotime($user->created_at) );
            return date( 'jS M Y- h:i:s a', strtotime($users->created_at));
        })
        ->editColumn('phone_verified_at', function ($users) 
        {
            if($users->phone_verified_at==NULL)
            return "No";
            return date( 'jS M Y- h:i:s a', strtotime($users->phone_verified_at));
        })->make(true);
    } 


    protected function userlogins()
    {
        $admin_url=$this->admin_url;
        $admin = Auth::guard('admin')->user();
        return view('admin.user_logins',compact(['admin','admin_url']));
    } 

    protected function user_logins_list()
    {
        $admin = Auth::guard('admin')->user();
        $admin_url=$this->admin_url;
        $users = DB::table('login_log')->where('user_id','!=',NULL)->select('*');
        return datatables()->of($users)
        ->editColumn('time', function ($users) 
        {
            //change over here
            //return date('d-m-Y ,hh:mm', strtotime($user->created_at) );
            return date( 'jS M Y- h:i:s a', strtotime($users->time));
        })->editColumn('user_id', function ($users) 
        {
           
           $link=url($this->admin_url.'/viewuser/'.$users->user_id);
            return "<a target=_blank  href=$link>ID: ". $users->user_id.'<br>Name: '.User::where('id',$users->user_id)->value('name').'</a>';
        })->escapeColumns([])->make(true);
    } 



    
    protected function adminlogins()
    {
        $admin_url=$this->admin_url;
        $admin = Auth::guard('admin')->user();
        return view('admin.admin_logins',compact(['admin','admin_url']));
    } 

    protected function admin_logins_list()
    {
        $admin = Auth::guard('admin')->user();
        $admin_url=$this->admin_url;
        $users = DB::table('login_log')->where('admin_id','!=',NULL)->select('*');
        return datatables()->of($users)
        ->editColumn('time', function ($users) 
        {
            //change over here
            //return date('d-m-Y ,hh:mm', strtotime($user->created_at) );
            return date( 'jS M Y- h:i:s a', strtotime($users->time));
        })->editColumn('user_id', function ($users) 
        {
           
           $link=url($this->admin_url.'/viewuser/'.$users->user_id);
            return "<a target=_blank >ID: ". $users->admin_id.'<br>Name: '.Admin::where('id',$users->admin_id)->value('name').'</a>';
        })->escapeColumns([])->make(true);
    } 



    protected function visitors()
    {
        $admin_url=$this->admin_url;
        $admin = Auth::guard('admin')->user();
        return view('admin.visitors',compact(['admin','admin_url']));
    } 

    protected function visitors_list()
    {
        $admin = Auth::guard('admin')->user();
        $admin_url=$this->admin_url;
        $visitors = Visitors::select('*');
        return datatables()->of($visitors)
        ->editColumn('created_at', function ($visitors) 
        {
            //change over here
            //return date('d-m-Y ,hh:mm', strtotime($user->created_at) );
            return date( 'jS M Y- h:i:s a', strtotime($visitors->created_at));
        })->make(true);
    } 

    protected function videos(Request $request,$id=NULL)
    {
        $admin_url=$this->admin_url;
        if ($request->isMethod('post')) {
    
            $rules = array(
                   
                'url'          => 'required'
                               
            );
        
            $validator = validator()->make(request()->all(), $rules);
    
            // check if the validator failed -----------------------
            if ($validator->fails()) {
        
                // get the error messages from the validator
               
                return redirect()->back()->withErrors($validator)->withInput();
              
            } else 
            
            {

                DB::table('videos')->insert(['url'=> $request->input('url')]);
            }

        }
        
        if($id!=NULL)
        {
            $videos=DB::table('videos')->where('id',$id)->delete(); 
            return redirect()->back();
        }
        
        $admin = Auth::guard('admin')->user();
        $videos=DB::table('videos')->orderby('id','desc')->get();
        return view('admin.videos',compact(['admin','videos','admin_url']));
    } 

    protected function sliders(Request $request,$id=NULL)
    {
        $admin_url=$this->admin_url;
        if ($request->isMethod('post')) {
            $messages = [
               
                'image.mimes' => 'Jpg files only',
              ];
            $rules = array(
                   
                'title1'          => 'required',
                'title2'           => 'required',
                'image'          => 'required|image|mimes:jpeg,jpg|max:4048',
                
            );
        
            $validator = validator()->make(request()->all(), $rules, $messages);
    
            // check if the validator failed -----------------------
            if ($validator->fails()) {
        
                // get the error messages from the validator
               
                return redirect()->back()->withErrors($validator)->withInput();
              
            } else 
            
            {

                $id=DB::table('sliders')->insertGetId(['title1'=> $request->title1,'title2'=> $request->title2,'link_url'=> $request->link_url]);
                


                $name = $id.'.jpg';

                
                $image = $request->file('image');

                $destinationPath = 'assets/images/slider/';
                $image->move($destinationPath, $name);

                $id=NULL;
                return redirect()->back(); 
            }

        }
        
        if($id!=NULL)
        {
            DB::table('sliders')->where('id',$id)->delete(); 
            unlink('assets/images/slider/'.$id.'.jpg');
        }
        
        $admin = Auth::guard('admin')->user();
        $sliders=DB::table('sliders')->orderby('id','desc')->get();
        return view('admin.sliders',compact(['admin','sliders','admin_url']));
    } 


    protected function editslider(Request $request,$id)
    {
        $admin = Auth::guard('admin')->user();
        $admin_url=$this->admin_url;

        if ($request->isMethod('post')) {
    
            $rules = array(
                                   
                'title1'          => 'required',
                'title2'          => 'required'
               
            );
    
    
    
            $validator = validator()->make(request()->all(), $rules);
    
            // check if the validator failed -----------------------
            if ($validator->fails()) {
        
                // get the error messages from the validator
               
                return redirect()->back()->withErrors($validator)->withInput();
              
            } else 
            
            {
                     
               DB::table('sliders')->where('id',$id)->update(['title1'=> $request->title1,'title2'=> $request->title2,'link_url'=> $request->link_url]);
                

                 

                   if( $image = $request->file('image'))
                   {

                    $name = $id.'.jpg';

                   

                    $destinationPath = 'assets/images/slider/';
                    $image->move($destinationPath, $name);

                    
                   
                   }

                   
               
                   return redirect()->back();   
        
            } 
            
            }


    }



    protected function changepassword(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $admin_url=$this->admin_url;

        if ($request->isMethod('post')) 
    {
        
        $rules = array(
              
            'currentpassword'       => 'required',
            'password'              => 'min:7|required_with:password_confirmation|same:password_confirmation',
            'password_confirmation' => 'min:7'
            
        );



        $validator = validator()->make(request()->all(), $rules);
        
        // check if the validator failed -----------------------
        if ($validator->fails()) {
    
            // get the error messages from the validator
           
            return back()->withErrors($validator);
          
        } 
       
        else 
        
        {
           

            if($request->password_confirmation==$request->password && $request->password==$request->currentpassword && Hash::check($request->currentpassword, $admin->password) )
           {
            return redirect()->back()->withErrors("Current Password and New Passwords entered by you are same ");
           }

           if($request->password_confirmation==$request->password && Hash::check($request->currentpassword, $admin->password) )
           {
            
            Admin::where('id', $admin->id)->update(['password'=> Hash::make($request->password)]);
           
            return back()->with('success', " Your password has been changed successfully! ");
            
           }

           else
           {
            return back()->withErrors("Current Password entered is wrong !");
           }

            

        }

    }
        
                    
            return view('admin.change-password',compact(['admin','admin_url']));
    }


}
