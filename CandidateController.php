<?php

namespace App\Http\Controllers;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
Use Auth;
use App\Visitor;
use App\Job;
use App\Candidate; 
use Session;
use Carbon\Carbon;

class CandidateController extends Controller
        
{   
    
    public function __construct()
    {
        $this->middleware('auth:candidate');  
    }


    public function checkprofile()
    {
        $user = Auth::guard('candidate')->user();

       
        

        if ($user->phone==NULL or $user->dob=="" or $user->gender==NULL or $user->nationality==NULL or $user->nationality==NULL)
        {
            Candidate::where('id', $user->id)->update(['Completed' => 0]);
            return 'candidate/personal-details';  
        }

        if(!$educations = DB::table('candidate_educational_details')->where('candidate_id',$user->id)->count())
        {
            Candidate::where('id', $user->id)->update(['Completed' => 0]);
            return 'candidate/educational-details'; 
        }
      

        
       
        else
        {
            Candidate::where('id', $user->id)->update(['Completed' => 1]);
        
            return 0;

        }


    }
    
    protected function myprofile()
    {
        $user = Auth::guard('candidate')->user();
        
        if($this->checkprofile())
        {
            return redirect($this->checkprofile());
        }

       
        
        Visitor::_save();
        $unreadmessages = DB::table('candidate_messages')->where('candidate_id',$user->id)->where('box',1)->where('open',0)->count();



        $educations = DB::table('candidate_educational_details')->where('candidate_id',$user->id)->orderBy('start_date', 'desc')->get();
        $experiences = DB::table('candidate_experience_details')->where('candidate_id',$user->id)->orderBy('start_date', 'desc')->get();
        $working= DB::table('candidate_experience_details')->where('candidate_id',$user->id)->where('end_date','Present')->orderBy('end_date', 'desc')->get();
        $job_designation= DB::table('job_designation')->orderBy('name', 'asc')->get();
        $qualification_list= DB::table('qualification_list')->orderBy('name', 'asc')->get();



        return view('candidate.myprofile',compact(['user','unreadmessages','educations','experiences','working','job_designation','qualification_list']) );
 
    }


    
    protected function dashboard(Request $request)
    {
      
      
        $user = Auth::guard('candidate')->user();
       
        if($this->checkprofile())
        {
            return redirect($this->checkprofile());
        }

        

        if($user->Completed!=1)
        {
           // return redirect('candidate/personal-details');
        }
        

        Candidate::where('id', $user->id)->update(['last_active' => DB::raw('now()')]);
        Visitor::_save();

       $qualification = DB::table('candidate_educational_details')->where('candidate_id',$user->id)->whereBetween('qualification', array(1, 7))->orderBy('qualification', 'desc')->take(1)->get('qualification');
   
    foreach ( $qualification as $object) {
        $qualification=$object->qualification;
    }


   $educations = DB::table('candidate_educational_details')->where('candidate_id',$user->id)->orderBy('qualification', 'desc')->get('stream');

    $education='';
    foreach ($educations as $object) {
        $education=$education.' '.$object->stream;
    }

    Candidate::where('id', $user->id)->update(['search_key' =>$education,'qualification_type' =>$qualification]);

           $numberofapplications = DB::table('job_log')->where('candidateid',$user->id)->count();
           $shortlistedjobs=DB::table('job_log')->where('candidateid',$user->id)
           ->where(function($query){
               $query->where('status', 'Shortlisted');
               $query->orWhere('status', 'Selected');
               
           })->count();

           $unreadmessages = DB::table('candidate_messages')->where('candidate_id',$user->id)->where('box',1)->where('open',0)->count();

  



 $jobs = Job::where('status', 1)->orderBy('id', 'desc')->paginate(5);


return view('candidate.dashboard',compact('user'), ['numberofapplications' => $numberofapplications, 'shortlistedjobs' => $shortlistedjobs,'unreadmessages' => $unreadmessages,'jobs' => $jobs]);


}

 

    protected function viewphoto(Request $request, $w,$h) //Show Candidate photo
    {
        
       
        $user = Auth::guard('candidate')->user();
        $file= 'candidates_data/photos/'.$user->id.'.jpg';
        $file= storage_path('app/public/candidates_data/photos/'.$user->id.'.jpg');
      
      

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
    
    
    

    
    protected function downloadresume(Request $request)
    
    {

        $user = Auth::guard('candidate')->user();
        Visitor::_save();

        
       
        if(file_exists($file= storage_path('app/public/candidates_data/resumes/'.$user->id.'.pdf'))){
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



protected function editprofile(Request $request) 
{

    $user = Auth::guard('candidate')->user();

   
    Visitor::_save();

    if ($request->isMethod('post')) {





        $name = substr(testInput($request->input('name')),0,100);
        $designation = substr(testInput($request->input('designation')),0,100);
        $gender = substr(testInput($request->input('gender')),0,50);
        $dob = substr(testInput($request->input('dob')),0,100);
        $phone=(int) filter_var(substr($request->input('phone'),0,30), FILTER_SANITIZE_NUMBER_INT);
        $dialcode_phone="+".(int) filter_var(substr($request->input('dialcode_phone'),0,10), FILTER_SANITIZE_NUMBER_INT);
             
        $nationality = ucfirst(substr(testInput($request->input('nationality')),0,100));
        $qualification_type = substr(testInput($request->input('qualification_type')),0,10);
        $experience=(int) filter_var(substr($request->input('experience'),0,10), FILTER_SANITIZE_NUMBER_INT);
        $experience_months=(int) filter_var(substr($request->input('experience_months'),0,10), FILTER_SANITIZE_NUMBER_INT);
        $notice_period = substr(testInput($request->input('notice_period')),0,100);
        $currentsalary=(int) filter_var(substr($request->input('currentsalary'),0,10), FILTER_SANITIZE_NUMBER_INT);
        $expectedsalary=(int) filter_var(substr($request->input('expectedsalary'),0,10), FILTER_SANITIZE_NUMBER_INT);
        $visa_type = substr(testInput($request->input('visa_type')),0,100);
        $visa_expiry = substr(testInput($request->input('visa_expiry')),0,100);
        $license_type = substr(testInput($request->input('license_type')),0,100);
        $license_expiry = substr(testInput($request->input('license_expiry')),0,100);
        $skills = substr(testInput($request->input('skills')),0,2000);
        $aboutme = substr(testInput($request->input('aboutme')),0,2000);

        $sphone=$dialcode_sphone=NULL;

        if(!empty($request->sphone)) 
        {
            $sphone=(int) filter_var(substr($request->input('sphone'),0,30), FILTER_SANITIZE_NUMBER_INT);
        }

        if(!empty($request->dialcode_sphone)) 
        {
            $dialcode_sphone="+".(int) filter_var(substr($request->input('dialcode_sphone'),0,10), FILTER_SANITIZE_NUMBER_INT);
        }

       
     
      

        $rules = array(
            'name'        => 'required',
            'phone'       => 'unique:App\Candidate,phone,'.$user->id,     
            'dob'         => 'required|date|before:-18 years',
            'designation' => 'required',
            'gender'      => 'required',
            
        );



        $validator = validator()->make(request()->all(), $rules);

        // check if the validator failed -----------------------
        if ($validator->fails()) {
    
            // get the error messages from the validator


           
            return redirect()->back()->withErrors($validator)->withInput();
          
        } 
        
        else 
        {

         

                   
      $update= Candidate::where('id', $user->id)->
                    update(
                        ['name' =>$name,'designation' =>$designation,'gender' =>$gender,'dob' =>$dob,'phone' => $phone,
                        'dialcode_phone' =>$dialcode_phone,'sphone' => $sphone,'dialcode_sphone' =>$dialcode_sphone,'qualification_type' =>$qualification_type,
                        'nationality' =>$nationality,'notice_period' =>$notice_period,'experience'=>$experience,'experience_months'=>$experience_months,
                        'currentsalary' =>$currentsalary, 'expectedsalary' =>$expectedsalary,'visa_type' =>$visa_type,'visa_expiry' =>$visa_expiry,
                        'license_type' => $license_type, 'license_expiry' =>$license_expiry,'skills' =>$skills,'aboutme' =>$aboutme
                        ]
                    );
                    if($update){



        
                        Session::flash('message', "Personal Details updated Successfully ");
                        //return redirect()->back();
                    }
            
      
    
        }
    


        if ($request->file)
        {

        
        $rules = array(
            'file' => 'required|mimes:pdf|max:2048',
           
        );



        $validator = validator()->make(request()->all(), $rules);

        // check if the validator failed -----------------------
        if ($validator->fails()) {
    
            // get the error messages from the validator
           
            return redirect()->back()->withErrors($validator)->withInput();
          
        } else {
            $folderPath = storage_path('app/public/candidates_data/resumes/');

            $request->file->move($folderPath, $user->id.'.pdf');

   

            return back()->with('success','You have successfully upload Resume');
                
           
        }
    
        }
    
    }

    $unreadmessages = DB::table('candidate_messages')->where('candidate_id',$user->id)->where('box',1)->where('open',0)->count();
    $job_designations= DB::table('job_designation')->orderBy('name', 'asc')->get();
    $resume=$photo="";

    if(!file_exists($file= storage_path('app/public/candidates_data/resumes/'.$user->id.'.pdf')))
    {
        Candidate::where('id', $user->id)->update(['Completed' => 0]);
      $resume="required";
    }
    if(!file_exists($file= storage_path('app/public/candidates_data/photos/'.$user->id.'.jpg')))
    {
        Candidate::where('id', $user->id)->update(['Completed' => 0]);
        $photo="required"; 
    }

 

    return view('candidate.personal-details', compact(['unreadmessages','user','job_designations','resume','photo']));



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




public function savephoto(Request $request) 

{


    $user = Auth::guard('candidate')->user();

    Candidate::where('id', $user->id)->update(['last_active' => DB::raw('now()')]);
    Visitor::_save();


   


    $folderPath = storage_path('app/public/candidates_data/photos/');
  //  $folderPath = public_path('images/');


    $image_parts = explode(";base64,", $request->image);

    $image_type_aux = explode("image/", $image_parts[0]);

    $image_type = $image_type_aux[1];

    $image_base64 = base64_decode($image_parts[1]);

   
    $file = $folderPath . $user->id. uniqid() . '.png';
   
  if (file_put_contents($file, $image_base64)) {
         
        imagejpeg(imagecreatefromstring(file_get_contents($file)), $folderPath . $user->id.'.jpg');
        unlink($file);

        return response()->json(['success'=>'success']);
        
   }


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


    protected function settings()

    {
        $user = Auth::guard('candidate')->user();

        Visitor::_save();


 return view('candidate.settings',compact('user'));



    }

    protected function userlogins()

    {
        $user = Auth::guard('candidate')->user();
        Visitor::_save();
               
        $unreadmessages = DB::table('candidate_messages')->where('candidate_id',$user->id)->where('box',1)->where('open',0)->count(); 
       
        return view('candidate.login_history', compact(['user','unreadmessages']));

    }

    protected function userlogins_lists()

    {
        $user = Auth::guard('candidate')->user();
        $datatable = DB::table('login_log')->where('candidate_id', $user->id)->select('*');
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



    protected function changepassword(Request $request)

    {
       
        $user = Auth::guard('candidate')->user();
        Visitor::_save();
               
        $unreadmessages = DB::table('candidate_messages')->where('candidate_id',$user->id)->where('box',1)->where('open',0)->count(); 
       

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
                
                Candidate::where('id', $user->id)->update(['password'=> Hash::make($request->password)]);
               
                return redirect()->back()->with('success', "Your password has been changed successfully!");
               }
    
               else
               {
                return redirect()->back()->withErrors("Current Password entered is wrong !");
               }
    
                
    
            }
    
        }
    


        return view('candidate.changepassword', compact(['user','unreadmessages']));

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
            Visitor::_save();

           
           if(Hash::check($request->password, $user->password) )
           {
            
            Candidate::where('id', $user->id)->update(['block'=> 1]);
            Auth::guard('candidate')->logout();
            return redirect('/');

           echo "Sheri";
           }

           else
           {
            return redirect()->back()->withErrors(" Password entered is wrong !");
           }

            

        }

    }}



    
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
           
          
           return redirect('candidate/');

           echo "Sheri";
           }

           else
           {
            return redirect()->back()->withErrors(" Password entered is wrong !");
           }

            

        }

    }}


}
