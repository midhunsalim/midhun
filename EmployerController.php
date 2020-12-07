<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
Use Auth;
use DB;
use App\Visitor;
use App\Job;
use App\Employer;
use App\Candidate; 
use Session;
use Carbon\Carbon;
use Illuminate\Support\Str;

class EmployerController extends Controller
        
{   
    
    public function __construct()
    {
        $this->middleware('auth:employer');  
    }




    protected function dashboard(Request $request)
    
    {
 
        $user = Auth::guard('employer')->user();
      
        Visitor::_save();

   
        $totaljobs = Job::where('employer_id',$user->id)->count();
        $jobs = Job::where('employer_id',$user->id)->where('status', 1)->orderBy('id', 'desc')->paginate(7);

        return view('employer.dashboard',compact(['user','totaljobs','jobs']) );
 
    }



    protected function myprofile()
    {
        $user = Auth::guard('employer')->user();      
       
        
        Visitor::_save();

        $jobs = Job::where('employer_id',$user->id)->orderBy('id', 'desc')->paginate(17);


        return view('employer.myprofile',compact(['user','jobs']) );
 
    }

    protected function tradelicense()
    {
        $user = Auth::guard('employer')->user();      
       
        
        Visitor::_save();

        if(file_exists($file= storage_path('app/public/employer_data/trade_license/'.$user->id.'.pdf'))){
            return response()->download($file, $user->name.'_'.$user->id.'.pdf', ['Content-Type' => 'application/pdf'], 'inline');
            }
    
            else{
                abort(404);
            }
 
    }


    public function editprofile(Request $request) {

        $user = Auth::guard('employer')->user();
    
       
        Visitor::_save();
    
        if ($request->isMethod('post')) 
        
        { 
          
    
            $rules = array(
                'cname'                      => 'required',
                'company_type'               => 'required',
                'dialcode'                   => 'required',
                'phone'                      => 'unique:App\Candidate,phone,'.$user->id,
                'yearly_recruitment'         => 'required',
                'total_employees'            => 'required',
                'contact_person_name'        => 'required',
                'contact_person_designation' => 'required',
                'contact_person_eid'         => 'required',                     
                'trade_license_expiry'       => 'required|date',
                'activity'                   => 'required',
                'city'                       => 'required',
                'address'                    => 'required',
                'description'                => 'required',
                
            );
    
    
    
            $validator = validator()->make(request()->all(), $rules);
    
            // check if the validator failed -----------------------
            if ($validator->fails()) {
        
                // get the error messages from the validator
    
    
               
                return redirect()->back()->withErrors($validator)->withInput();
              
            } 
            
            else 
            {

                $landline=$dialcode_landline=$activity=$city=NULL;

                if(!empty($request->activity)) 
                {
                   $ptr = $request->activity;
                 
                   $activity=implode(", ",$ptr);
                  
                }
                  
                    
                if(!empty($request->city)) 
                {
                   $ptr = $request->city;
                 
                   $city=implode(", ",$ptr);
                  
                } 
    
        $phone=(int) filter_var(substr($request->input('phone'),0,30), FILTER_SANITIZE_NUMBER_INT);
        $dialcode="+".(int) filter_var(substr($request->input('dialcode'),0,10), FILTER_SANITIZE_NUMBER_INT);
        if(!empty($request->landline)) 
        {
        $landline=(int) filter_var(substr($request->input('landline'),0,30), FILTER_SANITIZE_NUMBER_INT);
        }

        if(!empty($request->dialcode_landline)) 
        {
        $dialcode_landline="+".(int) filter_var(substr($request->input('dialcode_landline'),0,10), FILTER_SANITIZE_NUMBER_INT);
        }
                       
          $update= Employer::where('id', $user->id)->
                        update(
                            ['cname' =>$request->cname,'company_type' =>$request->company_type,'dialcode' =>$dialcode,'phone' => $phone,

                            'dialcode_landline' =>$dialcode_landline,'landline' =>$landline,'trade_license_expiry' =>$request->trade_license_expiry,

                            'yearly_recruitment' =>$request->yearly_recruitment,'total_employees' =>$request->total_employees,'contact_person_name' =>$request->contact_person_name,

                            'contact_person_eid' => $request->contact_person_eid,'contact_person_designation' =>$request->contact_person_designation,'address' =>$request->address,'description' =>$request->description,
                            
                            'website' => $request->website,'activity'=>$activity,'city'=>$city

                            ]
                        );
                        if($update){
    
    
    
            
                            Session::flash('message', "Profile updated Successfully ");
                            return redirect()->back();
                        }
                
          
        
            }
        
    
    
            if ($request->file)
            {
    
            
            $rules = array(
                'file' => 'required|mimes:pdf|max:7048',
               
            );
    
    
    
            $validator = validator()->make(request()->all(), $rules);
    
            // check if the validator failed -----------------------
            if ($validator->fails()) {
        
                // get the error messages from the validator
               
                return redirect()->back()->withErrors($validator)->withInput();
              
            } else {
                $folderPath = storage_path('app/public/employer_data/trade_license/');
    
                $request->file->move($folderPath, $user->id.'.pdf');
    
       
    
                return back()->with('success','You have successfully upload Resume');
                    
               
            }
        
            }
        
        } 
        
        
        $categories = DB::table('job_category')->orderBy('name', 'asc')->get();
    
        return view('employer.edit-profile-employer', compact(['user','categories']));
    
    }



    protected function updatephoto(Request $request) 

{


    $user = Auth::guard('employer')->user();

  
    Visitor::_save();


   


    $folderPath = storage_path('app/public/employer_data/images/');
  //  $folderPath = public_path('images/');


    $image_parts = explode(";base64,", $request->image);

    $image_type_aux = explode("image/", $image_parts[0]);

    $image_type = $image_type_aux[1];

    $image_base64 = base64_decode($image_parts[1]);

   
    $file = $folderPath . $user->id. uniqid() . '.png';
   
  if (file_put_contents($file, $image_base64)) {
         
        imagepng(imagecreatefromstring(file_get_contents($file)), $folderPath . $user->id.'.png');
        unlink($file);

        return response()->json(['success'=>'success']);
        
   }


}



    
protected function addjob(Request $request)
    
{

    $user = Auth::guard('employer')->user();
  
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
            'terms'              => 'required'
           
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

         $job->employer_id=$user->id;
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

         $job->save();

         $id=$job->id;

         if($id)

         {
            return redirect('job/'.$id);  
         }

         

             
    }

}

    $job_designations= DB::table('job_designation')->orderBy('name', 'asc')->get();
    
    $job_categories= DB::table('job_category')->orderBy('name', 'asc')->get();

    $qualifications= DB::table('qualification_list')->orderBy('name', 'asc')->get();

    return view('employer.add-job',compact(['user','job_designations','job_categories','qualifications']) );

}


protected function editjob(Request $request)
    
{

    $user = Auth::guard('employer')->user();
  
    Visitor::_save();


    if ($request->isMethod('post')) 
    
    {

        $rules = array(

            'id'                 => 'required|numeric',
            'type'               => 'required|string',               
            'salary'             => 'required',            
            'vacancies'          => 'required', 
            'lastdate'           => 'required|date',             
            'description'        => 'required' 
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


        $update=Job::where('employer_id',$user->id)->where('id',$request->id)->update(

            ['type' =>$request->type,'salary' =>$request->salary,'vacancies' =>$request->vacancies,
             'lastdate' =>$request->lastdate,'description' =>$request->description,'hr_name' =>$request->hr_name,
             'hr_email' =>$request->hr_email,'hr_phone' =>$request->hr_phone,'hr_designation' =>$request->hr_designation,
             'address' =>$request->address
            ]
        );

        

         if($update)

         {
            return redirect('job/'.$request->id);  
         }

         

             
    }

}


}


protected function updatejob(Request $request)
    
{

    $user = Auth::guard('employer')->user();
  
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



        $update=Job::where('employer_id',$user->id)->where('id',$request->id)->update(

            ['status' =>$request->status,'lastdate' =>$request->lastdate ]
        );

        

         if($update)

         {
            return redirect('job/'.$request->id);  
         }

         

             
    }

}


}


protected function deletejob(Request $request)
    
{

    $user = Auth::guard('employer')->user();
  
    Visitor::_save();


    if ($request->isMethod('post')) 
    
    {

        $rules = array(
            'id'                 => 'required|numeric'             
        );

        $validator = validator()->make(request()->all(), $rules);

        // check if the validator failed -----------------------
        if ($validator->fails()) {
    
            // get the error messages from the validator
           
            return redirect()->back()->withErrors($validator)->withInput();
          
        }

    else
    {
        $job = Job::where('id', $request->id)->first();
        $total_applications = DB::table('job_log')->where('jobid',$request->id)->count();

        $delete_able=NULL;
        if($job->views<10 AND $total_applications==0)
        {
            $delete_able=1;

        $delete=Job::where('employer_id',$user->id)->where('id',$request->id)->delete();
      
         if($delete)

         {
            return redirect('employer/my-jobs');  
         }
        }
         

             
    }

}


}


    protected function myjobs(Request $request)
    
    {
 
        $user = Auth::guard('employer')->user();
      
        Visitor::_save();

        $matchingprofiles=Candidate::where('block',0)->orderBy('id', 'desc')->count();
        
        $jobs = Job::where('employer_id',$user->id)->orderBy('id', 'desc')->paginate(32);
        
       

        return view('employer.my-jobs',compact(['user','jobs','matchingprofiles']) );
 
    }


    
    protected function jobapplications(Request $request,$id)
    
    {
 
        $user = Auth::guard('employer')->user();
      
        Visitor::_save();

        $job = Job::where('id', $id)->first();
        if(!$job)
        {
            abort(404);
            
        }

        if($job->employer_id!=$user->id)
        {
            abort(404);

            return redirect('employer/'); 
            
        }


        $job_logs = DB::table('job_log')->where('jobid',$id)->paginate(100);
        
        $total_applications = $job_logs->total();
        
        


        return view('employer.employer_job_applications',compact(['user','job','total_applications','job_logs']) );
 
    }




   
    protected function candidates()
    {
        $user = Auth::guard('employer')->user();
                
        Visitor::_save();


        $candidates=Candidate::where('block',0)->where('Completed',1)->orderBy('premium', 'desc')->orderBy('last_active', 'desc')->paginate(10);



        return view('employer.candidates',compact(['user','candidates']) );
 
    }



    protected function viewcandidate($id)
    {
        $user = Auth::guard('employer')->user();
                
        Visitor::_save();


        $candidate=Candidate::where('block',0)->where('id',$id)->first();
        if(!$candidate)
        {
            return redirect('employer/candidates');  
        }

        $educations = DB::table('candidate_educational_details')->where('candidate_id',$id)->orderBy('start_date', 'desc')->get();
        $experiences = DB::table('candidate_experience_details')->where('candidate_id',$id)->orderBy('start_date', 'desc')->get();
        $working= DB::table('candidate_experience_details')->where('candidate_id',$id)->where('end_date','Present')->orderBy('end_date', 'desc')->get();
        $job_designation= DB::table('job_designation')->orderBy('name', 'asc')->get();
        $qualification_list= DB::table('qualification_list')->orderBy('name', 'asc')->get();

        Candidate::where('block',0)->where('id',$id)->increment('views');



        return view('employer.employer_view_candidate',compact(['user','candidate','educations','experiences','working','job_designation','qualification_list']) );
 
    }


    

     
    protected function viewcandidatephoto(Request $request,$id,$candidate_id,$w,$h)
    {
        $user = Auth::guard('employer')->user();
        
        
       
        $file= 'candidates_data/photos/'.$candidate_id.'.jpg';
        $file= storage_path('app/public/candidates_data/photos/'.$candidate_id.'.jpg');
      
      

        $required_width=$w;
        $required_height=$h;

       if (!file_exists($file)) {
         $file= 'assets/images/no.jpg';
       }

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
        exit;

      // $img = file_get_contents(public_path($file));
     //return response($img)->header('Content-type','image/jpeg');
 
    }



    
    protected function userlogins()

    {
        $user = Auth::guard('employer')->user();
        Visitor::_save();
       
        return view('employer.login_history', compact(['user']));

    }

    protected function userlogins_lists()

    {
        $user = Auth::guard('employer')->user();
        $datatable = DB::table('login_log')->where('employer_id', $user->id)->select('*');
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
 

    protected function viewphoto(Request $request, $w,$h) //Show Candidate photo
    {
        
       
        $user = Auth::guard('candidate')->user();
        $file= 'candidates_data/photos/'.$user->id.'.jpg';
        $file= storage_path('app/public/candidates_data/photos/'.$user->id.'.jpg');
      
      

        $required_width=$w;
        $required_height=$h;

       if (!file_exists($file)) {
         $file= 'images/no.jpg';
       }

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
        exit;

      // $img = file_get_contents(public_path($file));
     //return response($img)->header('Content-type','image/jpeg');

    }
    
    protected function downloadresume(Request $request,$id)
    
    {

        $user = Auth::guard('employer')->user();
        Visitor::_save();

        $id=($id-7)/7/17;
       
        if(file_exists($file= storage_path('app/public/candidates_data/resumes/'.$id.'.pdf'))){
        return response()->download($file, $user->name.'_'.$user->id.'.pdf', ['Content-Type' => 'application/pdf'], 'inline');
        }

        else{
            abort(404);
        }
    


    }

    
    

    
    protected function addeducation(Request $request)


{


    $user = Auth::guard('candidate')->user();

        
        Visitor::_save();
    
    if ($request->isMethod('post')) {

        $rules = array(
            'qualification'    => 'required',                        
            'start_date'       =>'required|date',
            'end_date'         =>'required|date',
            'institute'        =>'required'
           
        );



        $validator = validator()->make(request()->all(), $rules);

        // check if the validator failed -----------------------
        if ($validator->fails()) {
    
            // get the error messages from the validator
           
            return redirect()->back()->withErrors($validator)->withInput();
          
        } else {

                $qualification = substr(testInput($request->input('qualification')),0,100);
                $university = substr(testInput($request->input('university')),0,200);
                $start_date = substr(testInput($request->input('start_date')),0,100);
                $end_date = substr(testInput($request->input('end_date')),0,100);
                $stream = substr(testInput($request->input('stream')),0,150);
                $subject = substr(testInput($request->input('subject')),0,200);
                $institute = substr(testInput($request->input('institute')),0,400);
                $description = substr(testInput($request->input('description')),0,2000);

            
               
            $insert= DB::table('candidate_educational_details')->insert(
                ['candidate_id' =>$user->id,'qualification' =>$qualification,'university' =>$university,'start_date' =>$start_date,'end_date' => $end_date,
                'stream' =>$stream,'subject' =>$subject,'institute' =>$institute,'description' =>$description
                ]
            );
            if($insert){

                Session::flash('message', "Education details added Successfully !");
                return redirect()->back();
            }
    
      
    
        }
    
    
    }

}     

protected function updateeducation(Request $request)


{


    $user = Auth::guard('candidate')->user();

        
        Visitor::_save();
    
    if ($request->isMethod('post')) {

        $rules = array(
            'qualification'    => 'required',                        
            'start_date'       =>'required|date',
            'end_date'         =>'required|date',
            'institute'        =>'required',
            'edit_id'          =>'required'
           
        );



        $validator = validator()->make(request()->all(), $rules);

        // check if the validator failed -----------------------
        if ($validator->fails()) {
    
            // get the error messages from the validator
           
            return redirect()->back()->withErrors($validator)->withInput();
          
        } else {

                $qualification = substr(testInput($request->input('qualification')),0,100);
                $university = substr(testInput($request->input('university')),0,200);
                $start_date = substr(testInput($request->input('start_date')),0,100);
                $end_date = substr(testInput($request->input('end_date')),0,100);
                $stream = substr(testInput($request->input('stream')),0,150);
               // $subject = substr(testInput($request->input('subject')),0,200);
                $institute = substr(testInput($request->input('institute')),0,400);
                $description = substr(testInput($request->input('description')),0,2000);
                $edit_id = substr(testInput($request->input('edit_id')),0,2000);

                
               
           $update= DB::table('candidate_educational_details')->where('id',$edit_id)->where('candidate_id',$user->id)->update(
                ['qualification' =>$qualification,'university' =>$university,'start_date' =>$start_date,'end_date' => $end_date,
                'stream' =>$stream,'institute' =>$institute,'description' =>$description,'updated_at'=>now()
                ]
            );
            if($update){

                Session::flash('message', "Education details updated Successfully !");
                return redirect()->back();
            }
    
      
    
        }
    
    
    }

}     




public function addexperience(Request $request)
{


    $user = Auth::guard('candidate')->user();

        
    Visitor::_save();
    
    if ($request->isMethod('post')) {

        $rules = array(
            'designation'      => 'required',                        
            'start_date'       =>'required|date',   
            'company'          => 'required',
            'city'             => 'required'
            
        );



        $validator = validator()->make(request()->all(), $rules);

        // check if the validator failed -----------------------
        if ($validator->fails()) {
    
            // get the error messages from the validator
           
            return redirect()->back()->withErrors($validator)->withInput();
          
        } else {

                $designation = substr(testInput($request->input('designation')),0,100);
                $salary = substr(testInput($request->input('salary')),0,50);
                $start_date = substr(testInput($request->input('start_date')),0,100);
                $end_date = substr(testInput($request->input('end_date')),0,100);
               
                $company = substr(testInput($request->input('company')),0,200);
                $city = substr(testInput($request->input('city')),0,100);
                
                $roles = substr(testInput($request->input('roles')),0,2000);

               
                    if($end_date=="Present")
                    $end_date=NULL;

            
               
                    $insert= DB::table('candidate_experience_details')->insert(
                        ['candidate_id' =>$user->id,'designation' =>$designation,'salary' =>$salary,'start_date' =>$start_date,
                        'end_date' => $end_date,'company' =>$company,'city' =>$city,'roles' =>$roles
                        ]
                    );
                    if($insert){
        
                        Session::flash('message', "Experience added Successfully ");
                        return redirect()->back();
                    }
            
      
    
        }
    
    
    }

}  

public function updateexperience(Request $request)
{


    $user = Auth::guard('candidate')->user();

        
    Visitor::_save();
    
    if ($request->isMethod('post')) {

        $rules = array(
            'designation'      => 'required',                        
            'start_date'       =>'required|date',   
            'company'          => 'required',
            'city'             => 'required',           
            'edit_id'          => 'required'
        );



        $validator = validator()->make(request()->all(), $rules);

        // check if the validator failed -----------------------
        if ($validator->fails()) {
    
            // get the error messages from the validator
           
            return redirect()->back()->withErrors($validator)->withInput();
          
        } else {

                $designation = substr(testInput($request->input('designation')),0,100);
                $salary = substr(testInput($request->input('salary')),0,50);
                $start_date = substr(testInput($request->input('start_date')),0,100);
                $end_date = substr(testInput($request->input('end_date')),0,100);
                $company = substr(testInput($request->input('company')),0,200);
                $city = substr(testInput($request->input('city')),0,500);
                //$country = substr(testInput($request->input('country')),0,200);
                $roles = substr(testInput($request->input('roles')),0,2000);
                $edit_id = substr(testInput($request->input('edit_id')),0,2000);

                if($end_date=="Present")
                $end_date=NULL;

            
               
                    $update= DB::table('candidate_experience_details')->where('id',$edit_id)->where('candidate_id',$user->id)->
                    update(
                        ['designation' =>$designation,'salary' =>$salary,'start_date' =>$start_date,
                        'end_date' => $end_date,'company' =>$company,'city' =>$city,'roles' =>$roles,'updated_at'=>now()
                        ]
                    );
                    if($update){
        
                        Session::flash('message', "Experience updated Successfully ");
                       // return redirect('Candidate/ViewProfile');
                        return redirect()->back();

                    }
            
      
    
        }
    
    
    }

}  


public function edit_delete(Request $request, $p1,$id) 
{
    
   
    $user = Auth::guard('candidate')->user();
    Visitor::_save();



   if($p1=='Education')
   {
    DB::table('candidate_educational_details')->where('id',$id)->where('candidate_id',$user->id)->delete();
    Session::flash('message', "Education deleted Successfully  ");
    return redirect()->back();
   }

   if($p1=='Experience')
   {
    DB::table('candidate_experience_details')->where('id',$id)->where('candidate_id',$user->id)->delete();
    Session::flash('message', "Experience deleted Successfully  ");
    return redirect()->back();
   }
  

} 






public function editeducationaldetails(Request $request) {

    $user = Auth::guard('candidate')->user();

    Candidate::where('id', $user->id)->update(['last_active' => DB::raw('now()')]);
    Visitor::_save();



    $unreadmessages = DB::table('candidate_messages')->where('candidate_id',$user->id)->where('box',1)->where('open',0)->count();
   

    $educations = DB::table('candidate_educational_details')->where('candidate_id',$user->id)->orderBy('end_date', 'desc')->get();
    $qualification_list= DB::table('qualification_list')->orderBy('name', 'asc')->get();
  



    return view('candidate.educational-details', compact(['unreadmessages','user','qualification_list','educations']));



}


protected function workexperience(Request $request) {

    $user = Auth::guard('candidate')->user();

    
    Visitor::_save();



    $unreadmessages = DB::table('candidate_messages')->where('candidate_id',$user->id)->where('box',1)->where('open',0)->count();
   

    $experiences = DB::table('candidate_experience_details')->where('candidate_id',$user->id)->orderByRaw('LENGTH(end_date) asc')->orderBy('end_date', 'desc')->get();
    $job_designation= DB::table('job_designation')->orderBy('name', 'asc')->get();
  



    return view('candidate.work-experience', compact(['unreadmessages','user','job_designation','experiences']));



}












    public function applyjob(Request $request)

    {
        $user = Auth::guard('candidate')->user();
        Visitor::_save();


        if ($request->isMethod('post')) {

            
           $id = substr(testInput($request->input('job_id')),0,10);
         
            $Qualifications = $request->input('Qualifications');
            $Qualifications=implode(", ",$Qualifications);
                      

           

            $job = Job::where('id', $id)->first();
                if(!$job)
                {
                    abort(404);
                    
                }
          
                if($job->status==0 )
                {
                    Session::flash('message', "Job Closed ");
                    return redirect()->back();
                }

                if(date('Y-m-d H:i:s')> $job->lastdate)
                {

                    Session::flash('message', "Monthly Quota Exhausted ");
                    return redirect()->back();                    
                    
                }
        $monthy_applications = DB::table('job_log')->where('candidateid',$user->id)->whereMonth('applieddate', '=', date('n'))->whereYear('applieddate', '=', date('Y'))->count();

        if($monthy_applications>=10 && $user->premium!=1)
        {
            Session::flash('message', "Monthly Quota Exhausted ");
            return redirect()->back();
        }

        $job_log = DB::table('job_log')->where('candidateid',$user->id)->where('jobid',$id)->get();
        
        if($job_log->count() >1)
        {
            DB::table('job_log')->where('candidateid',$user->id)->where('jobid',$id)->orderBy('id', 'desc')->take($job_log->count()-1)->delete();
            Session::flash('message', "Already Applied ");
            return redirect()->back();
         }

         else
         {
            $insert= DB::table('job_log')->insert(
                ['jobid' =>$job->id,'candidateid' =>$user->id,'employer_id' =>$job->employer_id,
                'Qualifications' =>$Qualifications, 'applieddate' =>now()
                ]
            );
            if($insert){

                Session::flash('message', "Job Applied Successfully ");
                return redirect()->back();
            }

         }
         
        
        }
   

    }

/*
    public function advanced_job_search(Request $request)

    {
        $user = Auth::guard('candidate')->user();
        Visitor::_save();
        Candidate::where('id', $user->id)->update(['last_active' => now()]);        
        $unreadmessages = DB::table('candidate_messages')->where('candidate_id',$user->id)->where('box',1)->where('open',0)->count(); 
        $categories = DB::table('job_category')->orderBy('name', 'asc')->get();
        $qualification_list= DB::table('qualification_list')->orderBy('name', 'asc')->get();

      
        if ($request->isMethod('post')) {
       
         $keyword = $request->keyword;
         $category = $request->category;
         $qualification = $request->qualification;
         $city = $request->city;
         $experience_from = $request->experience_from;
         $experience_to = $request->experience_to;
        
       
        }
        
        if(empty($keyword)) { $keyword=' ';}
        if(empty($category)) { $category='';}
        if(empty($qualification)) { $qualification='';}
        if(empty($city)) { $city='';}
        if(empty($experience_from)) { $experience_from=0;}
        if(empty($experience_to)) { $experience_to=100;}
        $input = [];
        $input=[
            'keyword' => $keyword,
            'category' => $category,
            'qualification' => $qualification,
            'city' => $city,
            'experience_from' => $experience_from,
            'experience_to' => $experience_to
        ];


    
   

    $jobs=DB::table('jobs')->whereBetween('experience', [$experience_from, $experience_to])
    ->where(function($query) use ($input){
        $query->orWhere('jobtittle', 'LIKE', '%'.$input['keyword'].'%');
        $query->orWhere('qualification', 'LIKE', '%'.$input['keyword'].'%');
        $query->orWhere('category', 'LIKE', '%'.$input['keyword'].'%');
        $query->orWhere('suitable', 'LIKE', '%'.$input['keyword'].'%');      
    })->where('category', 'LIKE', '%'.$input['category'].'%')
    ->where('qualification', 'LIKE', '%'.$input['qualification'].'%')
    ->where('city', 'LIKE', '%'.$input['city'].'%')
     ->orderBy('id', 'desc')->paginate(21);



       return view('Candidate.advanced_job_search', compact(['user','unreadmessages','jobs','categories','qualification_list','input']));

    }
*/

    public function viewalljobs()

    {
        $user = Auth::guard('candidate')->user();
        Visitor::_save();
        Candidate::where('id', $user->id)->update(['last_active' => now()]);        
        $unreadmessages = DB::table('candidate_messages')->where('candidate_id',$user->id)->where('box',1)->where('open',0)->count(); 
       
        return view('Candidate.viewalljobs', compact(['user','unreadmessages']));

    }

    public function viewalljobs_lists()

    {
        $datatable = DB::table('jobs')->select('*');
        return datatables()->of($datatable)
        ->editColumn('lastdate', function ($datatable) 
        {
            
             if (date("Y-m-d")>date( 'Y-m-d', strtotime($datatable->lastdate))){
             
                return '<span class="badge bg-danger" >Closed</span><br><small>'. getTimeago(strtotime($datatable->lastdate)).'</small>';
                
              }
            else  if($datatable->status==0)
            {
                return '<span class="badge bg-danger" >Closed</span>';

            }
              else
            return date( 'jS M Y', strtotime($datatable->lastdate));
        })
        ->editColumn('id', function ($datatable) 
        {
            return " <a target=_blank href=ViewJob/$datatable->id>$datatable->id</a>";
        }) 
        ->editColumn('jobtittle', function ($datatable) 
        {
            return " <a target=_blank href=ViewJob/$datatable->id>$datatable->jobtittle</a>";
        })
         ->editColumn('experience', function ($datatable) 
        {
            return ($datatable->experience-1)." - ".$datatable->experience." Years";
        })->escapeColumns([])->make(true);
    }

    public function appliedjobs()

    {
        $user = Auth::guard('candidate')->user();
        Visitor::_save();
        Candidate::where('id', $user->id)->update(['last_active' => now()]);        
        $unreadmessages = DB::table('candidate_messages')->where('candidate_id',$user->id)->where('box',1)->where('open',0)->count(); 
       $appliedjobs = DB::table('job_log')->where('candidateid', $user->id)->orderby('id','desc')->paginate(32);
        return view('candidate.appliedjobs', compact(['user','unreadmessages','appliedjobs']));

    }

   

    public function shortlistedjobs()

    {
        $user = Auth::guard('candidate')->user();
        Visitor::_save();
        Candidate::where('id', $user->id)->update(['last_active' => now()]);        
        $unreadmessages = DB::table('candidate_messages')->where('candidate_id',$user->id)->where('box',1)->where('open',0)->count(); 
       
        return view('Candidate.shortlistedjobs', compact(['user','unreadmessages']));

    }
    public function shortlistedjobs_lists()

    {
        $user = Auth::guard('candidate')->user();
        $datatable = DB::table('job_log')->where('candidateid',$user->id)
        ->where(function($query){
            $query->where('status', 'Shortlisted');
            $query->orWhere('status', 'Selected');
            
        })->select('*');
        return datatables()->of($datatable)
        ->editColumn('applieddate', function ($datatable) 
        {
           
                return date( 'jS M Y, h:m a', strtotime($datatable->applieddate));
        })
        ->editColumn('jobid', function ($datatable) 
        {
           
            $job=Job::where('id', $datatable->jobid)->first(); 
            return " <a target=_blank href=ViewJob/$datatable->jobid>$job->jobtittle</a>";
        }) 
        ->editColumn('status', function ($datatable) 
        { if(empty($datatable->shortlistdate))
            $temp='';
            else
            $temp='<br>  <span>on  <br> '.date("d M Y",strtotime($datatable->shortlistdate)).'</span><br />';
            if($datatable->status=='Selected' )
                 return '  <button type="button" class="btn btn-success">Selected</button>'.$temp;
            
              if($datatable->status=='Shortlisted' )
                 return '  <button type="button" class="btn btn-info">Shortlisted</button>'.$temp;
            
            if($datatable->status=='Rejected' )
                return '<button type="button" class="btn btn-danger">Rejected</button>'.$temp;
                 
             if($datatable->status==NULL )
                 return '<button type="button" class="btn btn-default">Processing</button>';
        })->escapeColumns([])->make(true);
    }


 
    protected function changepassword(Request $request)

    {
       
        $user = Auth::guard('employer')->user();
        Visitor::_save();
       

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
               
                return redirect()->back()->withErrors($validator);
              
            } 
           
            else 
            
            {
              
    
                if($request->password_confirmation==$request->password && $request->password==$request->currentpassword && Hash::check($request->currentpassword, $user->password) )
               {
                return redirect()->back()->withErrors("Current Password and New Passwords entered by you are same ");
               }
    
               if($request->password_confirmation==$request->password && Hash::check($request->currentpassword, $user->password) )
               {
                
                Employer::where('id', $user->id)->update(['password'=> Hash::make($request->password)]);
               
                return redirect()->back()->with('success', "Your password has been changed successfully!");
               }
    
               else
               {
                return redirect()->back()->withErrors("Current Password entered is wrong !");
               }
    
                
    
            }
    
        }
    


        return view('employer.employer_change_password', compact(['user']));

    }
   

    public function inbox()

    {
        $user = Auth::guard('candidate')->user();
        Visitor::_save();
        Candidate::where('id', $user->id)->update(['last_active' => now()]);        
        $unreadmessages = DB::table('candidate_messages')->where('candidate_id',$user->id)->where('box',1)->where('open',0)->count(); 
       
        return view('Candidate.inbox', compact(['user','unreadmessages']));

    }

    public function inbox_lists()

    {
        $user = Auth::guard('candidate')->user();
        $datatable = DB::table('candidate_messages')->where('candidate_id', $user->id)->where('box',1)->select('*');
        return datatables()->of($datatable)
        ->editColumn('time', function ($datatable) 
        {
                return getTimeAgo(strtotime($datatable->time));
        })
        
        ->editColumn('subject', function ($datatable) 
        {
            return "<div style='white-space: nowrap; 
  width: 100px; 
  overflow: hidden;
  text-overflow: ellipsis; '>".$datatable->subject."</div>";
        })->editColumn('id', function ($datatable) 
        {
            $read='';
           if( $datatable->open==0) 
                {
         $read="<a class='label label-default' href=".url('/Candidate/ViewMessage')."/$datatable->id><b style='color:blue'><i>(New)</i></b></a>";
                 }
                 if( $datatable->sender_id==0) 
                 {
          return "<a class='label label-success' href=".url('/Candidate/ViewMessage')."/$datatable->id>Admin</a>".$read;
                  }
         else
         {
            $employer=DB::table('employers')->where('id',$datatable->sender_id)->first();
            return " <a class='label label-info' href=".url('/Candidate/ViewMessage')."/$datatable->id>". $employer->cname."</a>".$read;
         }
           
        })
        
        ->escapeColumns([])->make(true);
    }

    public function trash()

    {
        $user = Auth::guard('candidate')->user();
        Visitor::_save();
        Candidate::where('id', $user->id)->update(['last_active' => now()]);        
        $unreadmessages = DB::table('candidate_messages')->where('candidate_id',$user->id)->where('box',1)->where('open',0)->count(); 
       
        return view('Candidate.trash', compact(['user','unreadmessages']));

    }

    public function trash_lists()

    {
        $user = Auth::guard('candidate')->user();
        $datatable = DB::table('candidate_messages')->where('candidate_id', $user->id)->where('box',3)->select('*');
        return datatables()->of($datatable)
        ->editColumn('time', function ($datatable) 
        {
                return getTimeAgo(strtotime($datatable->time));
        })
        
        ->editColumn('subject', function ($datatable) 
        {
            return "<div style='white-space: nowrap; 
  width: 100px; 
  overflow: hidden;
  text-overflow: ellipsis; '>".$datatable->subject."</div>";
        })->editColumn('id', function ($datatable) 
        {
            $read='';
           if( $datatable->open==0) 
                {
         $read="<a class='label label-default' href=".url('/Candidate/ViewMessage')."/$datatable->id><b style='color:blue'><i>(New)</i></b></a>";
                 }
                 if( $datatable->sender_id==0) 
                 {
          return "<a class='label label-success' href=".url('/Candidate/ViewMessage')."/$datatable->id>Admin</a>".$read;
                  }
         else
         {
            $employer=DB::table('employers')->where('id',$datatable->sender_id)->first();
            return " <a class='label label-info' href=".url('/Candidate/ViewMessage')."/$datatable->id>". $employer->cname."</a>".$read;
         }
           
        })
        
        ->escapeColumns([])->make(true);
    }


    public function viewmessage(Request $request,$id)

    {
        $user = Auth::guard('candidate')->user();
        Visitor::_save();
        Candidate::where('id', $user->id)->update(['last_active' => now()]);        
       


        $message = DB::table('candidate_messages')->where('id',$id)->where('candidate_id',$user->id)->first();
        if(!$message)
        {
            return redirect()->back();  
        }
        $sender='';
        if($message->sender_id==0)
        {
             
             
            $sender=" <a class='label label-success' >Admin</a>";
   
     
         }
         else{
            $employer=DB::table('employers')->where('id',$message->sender_id)->first();
             $sender=$message->sender_name.", "." <a class='label label-info'>".$employer->cname."</a>";
        }

        DB::table('candidate_messages')->where('id',$id)->where('candidate_id',$user->id)->update(['open' => 1]);    
        $unreadmessages = DB::table('candidate_messages')->where('candidate_id',$user->id)->where('box',1)->where('open',0)->count();
       
        return view('Candidate.viewmessage', compact(['message','sender','user','unreadmessages']));

            
   

    }




    public function deletemessage(Request $request,$id)

    {
        $user = Auth::guard('candidate')->user();
        Visitor::_save();
        Candidate::where('id', $user->id)->update(['last_active' => now()]);        
       

       
        $message = DB::table('candidate_messages')->where('id',$id)->where('candidate_id',$user->id)->first();
        if(!$message)
        {
            return redirect()->back();  
        }
        
        if($message->box==1)
        {
       DB::table('candidate_messages')->where('id',$id)->where('open',1)->where('candidate_id',$user->id)->update(['box' => 3]); 
        
         }

         if($message->box==3)
         {
        DB::table('candidate_messages')->where('id',$id)->where('box',3)->where('open',1)->where('candidate_id',$user->id)->delete(); 
         
          }

        
          return redirect('Candidate/Inbox');

            
   

    }


  

    
    public function disable_account_view()

    {
       
        $user = Auth::guard('candidate')->user();
        Visitor::_save();
        Candidate::where('id', $user->id)->update(['last_active' => now()]);        
        $unreadmessages = DB::table('candidate_messages')->where('candidate_id',$user->id)->where('box',1)->where('open',0)->count(); 
       
        return view('Candidate.disableaccount', compact(['user','unreadmessages']));

    }
    
    public function disable_account(Request $request)

    {

    if ($request->isMethod('post')) {
        
        $rules = array(
              
            'password'       => 'required'
                   
            
        );



        $validator = validator()->make(request()->all(), $rules);
        
        // check if the validator failed -----------------------
        if ($validator->fails()) {
    
            // get the error messages from the validator
           
            return redirect()->back()->withErrors($validator);
          
        } 
       
        else 
        
        {
            $user = Auth::guard('candidate')->user();

           
           if(Hash::check($request->password, $user->password) )
           {
            
            Candidate::where('id', $user->id)->update(['block'=> 1]);
            //Session::flash('message', "Your password has been changed successfully!");
            return redirect('Candidate/Logout');

           echo "Sheri";
           }

           else
           {
            return redirect()->back()->withErrors(" Password entered is wrong !");
           }

            

        }

    }}


    public function delete_account_view()

    {
       
        $user = Auth::guard('candidate')->user();
        Visitor::_save();
        Candidate::where('id', $user->id)->update(['last_active' => now()]);        
        $unreadmessages = DB::table('candidate_messages')->where('candidate_id',$user->id)->where('box',1)->where('open',0)->count(); 
       
        return view('Candidate.deleteaccount', compact(['user','unreadmessages']));

    }


    
    public function delete_account(Request $request)

    {

    if ($request->isMethod('post')) {
        
        $rules = array(
              
            'password'       => 'required'
                   
            
        );



        $validator = validator()->make(request()->all(), $rules);
        
        // check if the validator failed -----------------------
        if ($validator->fails()) {
    
            // get the error messages from the validator
           
            return redirect()->back()->withErrors($validator);
          
        } 
       
        else 
        
        {
            $user = Auth::guard('candidate')->user();

           
           if(Hash::check($request->password, $user->password) )
           {
            
            
            DB::table('candidate_educational_details')->where('candidate_id',$user->id)->delete();
            DB::table('candidate_experience_details')->where('candidate_id',$user->id)->delete();
            DB::table('job_log')->where('candidateid',$user->id)->delete();


            Candidate::where('id', $user->id)->delete();


            if(file_exists($file= storage_path('app/public/candidates_data/resumes/'.$user->id.'.pdf')))
            {
                unlink($file);
            }
            if(file_exists($file= storage_path('app/public/candidates_data/photos/'.$user->id.'.jpg')))
            {
                unlink($file);
            }
            $file= storage_path('app/public/candidates_data/photos/'.$user->id.'.jpg');
           
          
           return redirect('Candidate/');

           echo "Sheri";
           }

           else
           {
            return redirect()->back()->withErrors(" Password entered is wrong !");
           }

            

        }

    }}



}
