<?php

namespace App\Http\Controllers;
Use Auth;
use DB;
use App\Visitor;
use App\Job;
use App\Employer;
use App\Candidate; 
use Session;
use Illuminate\Support\Str;

use Illuminate\Http\Request;

class SubadminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:subadmin');  
    }

    




    protected function dashboard(Request $request)
    
    {
 
        $user = Auth::guard('subadmin')->user();
        $subadmin_url=DB::table('admin_settings')->value('subadmin_url');
      
        Visitor::_save();

   
        $totaljobs = Job::where('subadmin_id',$user->id)->count();
        $jobs = Job::where('subadmin_id',$user->id)->where('status', 1)->orderBy('id', 'desc')->paginate(14);

        return view('subadmin.subadmin_dashboard',compact(['user','totaljobs','jobs','subadmin_url']) );
 
    }

    protected function addjob(Request $request)
    
{

    $user = Auth::guard('subadmin')->user();
    $subadmin_url=DB::table('admin_settings')->value('subadmin_url');
  
    Visitor::_save();


    if ($request->isMethod('post')) 
    
    {

        $rules = array(

            'jobtittle'          => 'required',
            'type'               => 'required|string', 
            'category'           => 'required|string', 
            'salary'             => 'required', 
            'experience'         => 'required', 
            'experience_months'  => 'required', 
            'qualification_type' => 'required', 
            'gender'             => 'required', 
            'vacancies'          => 'required', 
            'lastdate'           => 'required|date', 
            'email'              => 'required', 
            'description'        => 'required',
            'file'               => 'mimes:jpeg,jpg,png|max:1024',        
             
           
        );



        $validator = validator()->make(request()->all(), $rules);

        // check if the validator failed -----------------------
        if ($validator->fails()) {
    
            // get the error messages from the validator
           
            return redirect()->back()->withErrors($validator)->withInput();
          
        }

    else
    {


        $preferred_nationalities=$suitable=$qualification=NULL;
        
        if(!empty($request->suitable)) 
        {
            $ptr = $request->suitable;
         
          $suitable=implode(", ",$ptr);
          
        }


        if(!empty($request->qualification)) 
        {
            $ptr = $request->qualification;
         
          $qualification=implode(", ",$ptr);
          
        }
             
        
        
          
        if(!empty($request->preferred_nationalities)) 
        {
            $ptr = $request->preferred_nationalities;
         
          $preferred_nationalities=implode(", ",$ptr);
          
        }
         
          
          
          
        
          
        if(substr($preferred_nationalities,0,3)=="ALL")
         {
          
          $preferred_nationalities=NULL;
         
         }
          
       
         
         $job=new Job;

         $job->employer_id=1;
         $job->subadmin_id=$user->id;
         $job->company=1;
         $job->jobtittle=$request->jobtittle;
         $job->type=$request->type;
         $job->category=$request->category;
         $job->suitable=$suitable;
         $job->salary=$request->salary;
         $job->vacancies=$request->vacancies;
         $job->email=$request->email;
         $job->qualification_type=$request->qualification_type;
         $job->qualification=$qualification;
         $job->experience=$request->experience;
         $job->experience_months=$request->experience_months;
         $job->preferred_nationalities=$preferred_nationalities;
         $job->city=$request->city;
         $job->gender=$request->gender;
         $job->lastdate=$request->lastdate;
         $job->description=$request->description;
         $job->hr_name=$request->hr_name;
         $job->hr_designation=$request->hr_designation;
         $job->hr_email=$request->hr_email;
         $job->hr_phone=$request->hr_phone;
         $job->address=$request->address;
         $job->external_url=$request->external_url;

         $job->save();

         $id=$job->id;

         if($id)
         {
            if ($request->file)
            {

                $folderPath = public_path('assets/images/jobImages/');    
                         
                $request->file->move($folderPath, $id.'.png');
              
                 
                  $file = $folderPath . $id.'.png';
                     
                 imagejpeg(imagecreatefromstring(file_get_contents($file)), $folderPath . $id.'.jpg');
                 unlink($file);                                  
           
            }

            return redirect($subadmin_url.'job/'.$id);  
         }

         

             
    }

}

    $job_designations= DB::table('job_designation')->orderBy('name', 'asc')->get();
    
    $job_categories= DB::table('job_category')->orderBy('name', 'asc')->get();

    $qualifications= DB::table('qualification_list')->orderBy('name', 'asc')->get();

    return view('subadmin.subadmin_addjob',compact(['user','job_designations','job_categories','qualifications','subadmin_url']) );

}


protected function editjob(Request $request,$id)
    
{

    $user = Auth::guard('subadmin')->user();
    $subadmin_url=DB::table('admin_settings')->value('subadmin_url');
  
  
    Visitor::_save();
    

    $job=Job::find($id);

    if($job->subadmin_id!=$user->id)
    {
        abort(403, 'Unauthorized action.');
        exit(0);
    }


    if ($request->isMethod('post')) 
    
    {

        $rules = array(

            'id'                 => 'required|exists:App\Job,id',
            'jobtittle'          => 'required',
            'type'               => 'required|string', 
            'category'           => 'required|string', 
            'salary'             => 'required', 
            'experience'         => 'required', 
            'experience_months'  => 'required', 
            'qualification_type' => 'required', 
            'gender'             => 'required', 
            'vacancies'          => 'required', 
            'lastdate'           => 'required|date', 
            'email'              => 'required', 
            'description'        => 'required',
            'file'               => 'mimes:jpeg,jpg,png|max:1024',        
             
           
        );



        $validator = validator()->make(request()->all(), $rules);

        // check if the validator failed -----------------------
        if ($validator->fails()) {
    
            // get the error messages from the validator
           
            return redirect()->back()->withErrors($validator)->withInput();
          
        }

    else
    {


        $preferred_nationalities=$suitable=$qualification=NULL;
        
        if(!empty($request->suitable)) 
        {
            $ptr = $request->suitable;
         
          $suitable=implode(", ",$ptr);
          
        }


        if(!empty($request->qualification)) 
        {
            $ptr = $request->qualification;
         
          $qualification=implode(", ",$ptr);
          
        }
             
        
        
          
        if(!empty($request->preferred_nationalities)) 
        {
            $ptr = $request->preferred_nationalities;
         
          $preferred_nationalities=implode(", ",$ptr);
          
        }
         
          
          
          
        
          
        if(substr($preferred_nationalities,0,3)=="ALL")
         {
          
          $preferred_nationalities=NULL;
         
         }
          
       
         
         $job=Job::find($id);

         $job->employer_id=1;
         $job->subadmin_id=$user->id;
         $job->company=1;
         $job->jobtittle=$request->jobtittle;
         $job->type=$request->type;
         $job->category=$request->category;
         $job->suitable=$suitable;
         $job->salary=$request->salary;
         $job->vacancies=$request->vacancies;
         $job->email=$request->email;
         $job->qualification_type=$request->qualification_type;
         $job->qualification=$qualification;
         $job->experience=$request->experience;
         $job->experience_months=$request->experience_months;
         $job->preferred_nationalities=$preferred_nationalities;
         $job->city=$request->city;
         $job->gender=$request->gender;
         $job->lastdate=$request->lastdate;
         $job->description=$request->description;
         $job->hr_name=$request->hr_name;
         $job->hr_designation=$request->hr_designation;
         $job->hr_email=$request->hr_email;
         $job->hr_phone=$request->hr_phone;
         $job->address=$request->address;
         $job->external_url=$request->external_url;

         $job->save();

         

         if($id)
         {
            if ($request->file)
            {

                $folderPath = 'assets/images/jobImages/';    
                         
                $request->file->move($folderPath, $id.'.png');
              
                 
                  $file = $folderPath . $id.'.png';
                     
                 imagejpeg(imagecreatefromstring(file_get_contents($file)), $folderPath . $id.'.jpg');
                 unlink($file);                                  
           
            }

            return redirect($subadmin_url.'job/'.$id);  
         }

         

             
    }

}
    $job_designations= DB::table('job_designation')->orderBy('name', 'asc')->get();
    
    $job_categories= DB::table('job_category')->orderBy('name', 'asc')->get();

    $qualifications= DB::table('qualification_list')->orderBy('name', 'asc')->get();

    return view('subadmin.subadmin_editjob',compact(['user','job','job_designations','job_categories','qualifications','subadmin_url']) );

}



protected function viewjob(Request $request, $id)

{
    $user = Auth::guard('subadmin')->user();
    $subadmin_url=DB::table('admin_settings')->value('subadmin_url');
    Visitor::_save();
   
    
  
$id=(int) filter_var($id, FILTER_SANITIZE_NUMBER_INT);


$job=Job::find($id);

if($job->subadmin_id!=$user->id)
{
    abort(403, 'Unauthorized action.');
    exit(0);
}


if (Auth::guard('subadmin')->check() )
{
   

        $similar_jobs=Job::where('subadmin_id',$user->id)->where('id','!=',$id)->inRandomOrder()->paginate(10);
        $total_applications = DB::table('job_log')->where('jobid',$id)->count();
       

        $delete_able=NULL;
        if($job->views<10 AND $total_applications==0)
        $delete_able=1;

    return view('subadmin.subadmin-view-job', compact(['job','user','similar_jobs','delete_able','subadmin_url']));


}


}

protected function updatejob(Request $request)
    
{

    $user = Auth::guard('subadmin')->user();
    $subadmin_url=DB::table('admin_settings')->value('subadmin_url');
    Visitor::_save();


    if ($request->isMethod('post')) 
    
    {

        $rules = array(

            'id'                 => 'required|numeric',
            'status'             => 'required|numeric', 
            'lastdate'           => 'required|date', 
        );



        $validator = validator()->make(request()->all(), $rules);

        // check if the validator failed -----------------------
        if ($validator->fails()) {
    
            // get the error messages from the validator
           
            return redirect()->back()->withErrors($validator)->withInput();
          
        }

    else
    {



        $update=Job::where('subadmin_id',$user->id)->where('id',$request->id)->update(

            ['status' =>$request->status,'lastdate' =>$request->lastdate ]
        );

        

         if($update)

         {
            return redirect($subadmin_url.'job/'.$request->id);  
         }

         

             
    }

}


}



protected function jobs(Request $request)

{
    $user = Auth::guard('subadmin')->user();
    Visitor::_save();
    $subadmin_url=DB::table('admin_settings')->value('subadmin_url');     
    
    return view('subadmin.subadmin_jobs', compact(['user','subadmin_url']));

}

protected function job_list()

{
    $user = Auth::guard('subadmin')->user();
    $subadmin_url=DB::table('admin_settings')->value('subadmin_url');    

    $datatable = Job::where('subadmin_id',$user->id)->latest()->select('*');
    return datatables()->of($datatable)
    ->editColumn('id', function ($datatable) 
    {
        $subadmin_url=DB::table('admin_settings')->value('subadmin_url');    
    return  '<a href="'.url($subadmin_url.'job/'.$datatable->id).'">'.$datatable->id.'</a>';
      
    })

    ->editColumn('jobtittle', function ($datatable) 
    {
        $subadmin_url=DB::table('admin_settings')->value('subadmin_url');   
    return  '<a href="'.url($subadmin_url.'job/'.$datatable->id).'">'.Str::limit($datatable->jobtittle,30).'</a>';
      
    })

    ->editColumn('created_at', function ($datatable) 
    {
            return date( 'jS M Y- h:i:s a', strtotime($datatable->created_at));
    })
    
    ->editColumn('edit', function ($datatable) 

    {
        $subadmin_url=DB::table('admin_settings')->value('subadmin_url');
        return '<a href="'.url($subadmin_url.'editjob/'.$datatable->id).'" class="btn btn-success btn-sm text-white" data-toggle="tooltip" data-original-title="Edit"><i class="fa fa-pencil"></i></a>';
       

    })->escapeColumns([])->make(true);
}




protected function designations(Request $request)

{
    $user = Auth::guard('subadmin')->user();
    Visitor::_save();
    $subadmin_url=DB::table('admin_settings')->value('subadmin_url'); 
    
    if ($request->isMethod('post')) 
    
    {

        $rules = array(
            
            'designation'   => 'required|string',           
            'file'          => 'max:1000',        
                        
        );



        $validator = validator()->make(request()->all(), $rules);

        // check if the validator failed -----------------------
        if ($validator->fails()) {
    
            // get the error messages from the validator
           
            return redirect()->back()->withErrors($validator)->withInput();
          
        }

    else
    {
         
        
        $id=DB::table('job_designation')->insertGetId(['name'=> $request->designation]);
                

         if($id)
         {
            if ($request->file)
            {

                $folderPath = 'assets/images/designation_images/';    
                         
                $request->file->move($folderPath, $id.'.png');
              
                 
                  $file = $folderPath . $id.'.png';
                     
                 imagejpeg(imagecreatefromstring(file_get_contents($file)), $folderPath . $id.'.jpg');
                 unlink($file);  
                 
                
           
            }
            return redirect()->back();
             
         }

        }

             
    }

   
    return view('subadmin.subadmin_designations', compact(['user','subadmin_url']));

}

protected function designations_lists()

{
    $user = Auth::guard('subadmin')->user();
    $subadmin_url=DB::table('admin_settings')->value('subadmin_url');    

    $datatable = DB::table('job_designation')->orderby('name','asc')->select('*');
    return datatables()->of($datatable)
    ->editColumn('image', function ($datatable) 
    {
        
            return '<img src="'.asset('assets/images/designation_images/'.$datatable->id).'.jpg" width="100" height="100" alt="No Image">';
    })
    
    ->editColumn('delete', function ($datatable) 

    {
        $subadmin_url=DB::table('admin_settings')->value('subadmin_url');
        return '<a href="'.url($subadmin_url.'designation/delete/'.$datatable->id).'"><i class="fa fa-trash"></i></a>';

    })->escapeColumns([])->make(true);
}


protected function deletedesignation($id)

{
    $user = Auth::guard('subadmin')->user();
    Visitor::_save();
    $subadmin_url=DB::table('admin_settings')->value('subadmin_url');    

    if($id)
         {
            DB::table('job_designation')->where('id', $id)->delete();
            $folderPath = 'assets/images/designation_images/';                            
            $file = $folderPath . $id.'.jpg'; 
            if (file_exists($file))
            {
                unlink($file);     
           
            }
            return back();
             
         }

}


protected function categories(Request $request)

{
    $user = Auth::guard('subadmin')->user();
    Visitor::_save();
    $subadmin_url=DB::table('admin_settings')->value('subadmin_url'); 
    
    if ($request->isMethod('post')) 
    
    {

        $rules = array(
            
            'category'   => 'required|string',           
                                   
        );



        $validator = validator()->make(request()->all(), $rules);

        // check if the validator failed -----------------------
        if ($validator->fails()) {
    
            // get the error messages from the validator
           
            return redirect()->back()->withErrors($validator)->withInput();
          
        }

    else
    {        
        
        $id=DB::table('job_category')->insertGetId(['name'=> $request->category]);    

        
            return redirect()->back();
             
    }

             
    }

   
    return view('subadmin.subadmin_categories', compact(['user','subadmin_url']));

}

protected function categories_lists()

{
    $user = Auth::guard('subadmin')->user();
    $subadmin_url=DB::table('admin_settings')->value('subadmin_url');    

    $datatable = DB::table('job_category')->orderby('name','asc')->select('*');
    return datatables()->of($datatable)
        
    ->editColumn('delete', function ($datatable) 

    {
        $subadmin_url=DB::table('admin_settings')->value('subadmin_url');
        return '<a href="'.url($subadmin_url.'category/delete/'.$datatable->id).'"><i class="fa fa-trash"></i></a>';

    })->escapeColumns([])->make(true);
}

protected function deletecategory($id)

{
    $user = Auth::guard('subadmin')->user();
    Visitor::_save();
   

    if($id)
         {
            DB::table('job_category')->where('id', $id)->delete();
            return back();             
         }

}


protected function qualifications(Request $request)

{
    $user = Auth::guard('subadmin')->user();
    Visitor::_save();
    $subadmin_url=DB::table('admin_settings')->value('subadmin_url'); 
    
    if ($request->isMethod('post')) 
    
    {

        $rules = array(
            
            'qualification'   => 'required|string',           
                                   
        );



        $validator = validator()->make(request()->all(), $rules);

        // check if the validator failed -----------------------
        if ($validator->fails()) {
    
            // get the error messages from the validator
           
            return redirect()->back()->withErrors($validator)->withInput();
          
        }

    else
    {        
        
        $id=DB::table('qualification_list')->insertGetId(['name'=> $request->qualification]);    

        
            return redirect()->back();
             
    }

             
    }

   
    return view('subadmin.subadmin_qualifications', compact(['user','subadmin_url']));

}

protected function qualifications_lists()

{
    $user = Auth::guard('subadmin')->user();
    $subadmin_url=DB::table('admin_settings')->value('subadmin_url');    

    $datatable = DB::table('qualification_list')->orderby('name','asc')->select('*');
    return datatables()->of($datatable)
        
    ->editColumn('delete', function ($datatable) 

    {
        $subadmin_url=DB::table('admin_settings')->value('subadmin_url');
        return '<a href="'.url($subadmin_url.'qualification/delete/'.$datatable->id).'"><i class="fa fa-trash"></i></a>';

    })->escapeColumns([])->make(true);
}

protected function deletequalification($id)

{
    $user = Auth::guard('subadmin')->user();
    Visitor::_save();
   

    if($id)
         {
            DB::table('qualification_list')->where('id', $id)->delete();
            return back();             
         }

}




protected function userlogins()

{
    $user = Auth::guard('subadmin')->user();
    Visitor::_save();
    $subadmin_url=DB::table('admin_settings')->value('subadmin_url');    

   
    return view('subadmin.subadmin_login_history', compact(['user','subadmin_url']));

}

protected function userlogins_lists()

{
    $user = Auth::guard('subadmin')->user();
    $datatable = DB::table('login_log')->where('subadmin_id', $user->id)->select('*');
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
