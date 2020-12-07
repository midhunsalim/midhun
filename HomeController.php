<?php

namespace App\Http\Controllers;
use DB;
Use Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Visitor;
use App\Job;
use App\Employer;
use App\Candidate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Session;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    
    
    protected function index(Request $request)
    {
       
       /* if (Auth::guard('candidate')->check()) {
            $user = Auth::guard('candidate')->user();
            echo $user_id = $user->id;
        }*/
      //  $user = Auth::guard('candidate')->user();
      //echo $user_id = $user->id;
      



    Visitor::_save();

        $designation = DB::table('job_designation')->orderBy('name', 'asc')->get();
        
        
      
        
        $categories = DB::table('job_category')->orderBy('name', 'asc')->get();


        $active_categories= $items = array();
              
          foreach ($categories as $category) 
          {
              $jobCount = Job::where('category', $category->name)->count();

       
        if($jobCount>0)

        {
            $jobCount=" (".$jobCount.")";
            $active_categories[] = $category->name.$jobCount;
        }

        else
        {
            $jobCount="" ; 
         }

            $items[] = $category->name.$jobCount;
}
shuffle($active_categories);
$active_categories=array_slice($active_categories,1,15);

        
$totaljobs = Job::count();
$todaysjobs = Job::whereDate('created_at', DB::raw('CURDATE()'))->count();
if($todaysjobs>0)
{
    $todaysjobs=" - ".$todaysjobs." added today";
}
 
else{
    $todaysjobs='';
}
  
$categories= $items;

$Dubaijobs = Job::where('city','Dubai')->orderBy('status', 'desc')->orderBy('id', 'desc')->paginate(5);
$Sharjahjobs = Job::where('city','Sharjah')->orderBy('status', 'desc')->orderBy('id', 'desc')->paginate(5);
$jobs = Job::orderBy('status', 'desc')->orderBy('id', 'desc')->paginate(30);
$topemployers= Employer::where('id','>',1)->where('status',1)->where('verified',1) ->orderBy(DB::raw('RAND()'))->paginate(20);

$user="";
if (Auth::guard('employer')->check())
$user = Auth::guard('employer')->user();

if (Auth::guard('candidate')->check())
$user = Auth::guard('candidate')->user();

return view('home', compact(['user','categories','active_categories' ,'designation', 'totaljobs','todaysjobs', 'Dubaijobs', 'Sharjahjobs','topemployers','jobs']));
  
}



protected function aboutus()
{
   
  

Visitor::_save();



    
$totaljobs = Job::count()+200;

$totalemployers= Employer::count()+100;

$totalcandidates= Candidate::count()+300;

$topemployers= Employer::where('id','>',1)->where('status',1)->where('verified',1) ->orderBy(DB::raw('RAND()'))->paginate(20);


return view('aboutus', compact(['totalemployers', 'totalcandidates', 'totaljobs','topemployers']));

}



 //For displaying job image


 protected  function viewjobphoto(Request $request, $title,$jid)

    {

        $file= 'assets/images/jobs.jpg';
        $jid= (int)$jid;
      


      
        $designation= DB::table('job_designation')->where('name', $title)->get();

        if($designation->count()==1)
        {

            foreach ($designation as $title) {
                $file= 'assets/images/designation_images/'.$title->id.'.jpg';
            }
          
           
        }
        

        if (file_exists('assets/images/jobImages/'.$jid.'.jpg')) {
            $file= 'assets/images/jobImages/'.$jid.'.jpg';

        }
        if (!file_exists($file)) {
            $file= 'assets/images/jobs.jpg';
          } 
          
      
        $img = file_get_contents($file);
        return response($img)->header('Content-type','image/jpg');
      //return $file;

      //return view('viewjob', ['file' => $file]);



    }




 //For displaying top employer image

 protected function viewtopemployers(Request $request, $id,$w,$h)

    {

        $file= 'assets/images/jow.png';
      
      


         $id=(int) filter_var($id, FILTER_SANITIZE_NUMBER_INT)/7/9895030048;
        $file= 'app/public/employer_data/images/'.$id.'.png';
        $file= storage_path('app/public/employer_data/images/'.$id.'.png');


        if (!file_exists($file)) {
          $file= 'assets/images/jow.png';
        }
        $img = file_get_contents($file);
      return response($img)->header('Content-type','image/png');



    }

    protected function top_employers_refresh(Request $request)

    {

        $topemployers= Employer::where('id','>',1)->where('status',1)->where('verified',1) ->orderBy(DB::raw('RAND()'))->paginate(5);

        return view('top_employers',compact('topemployers'));
          



    }


 //View Single Job details

    protected function viewjob(Request $request, $id)

    {
         Visitor::_save();

         $user= $cantApply=$qualification= $educations=$job_log=$monthy_applications=NULL;
       
        
      
    $id=(int) filter_var($id, FILTER_SANITIZE_NUMBER_INT);

    $job = Job::where('id', $id)->first();
        if(!$job)
        {
            abort(404);
            abort(403, 'Unauthorized action.');
            
        }

   

   
    



    if (Auth::guard('employer')->check() )
    {
       

        
        $user = Auth::guard('employer')->user();
       
        if($job->employer_id==$user->id)
        {

            $similar_jobs=Job::where('employer_id',$user->id)->where('id','!=',$id)->inRandomOrder()->paginate(10);
            $total_applications = DB::table('job_log')->where('jobid',$id)->count();

            $delete_able=NULL;
            if($job->views<10 AND $total_applications==0)
            $delete_able=1;

        return view('employer.employer-view-job', compact(['job','user','similar_jobs','total_applications','delete_able']));

        }

    }




    if (Auth::guard('candidate')->check())
    {
        $user = Auth::guard('candidate')->user();
       
        if($user->Completed==0)
        {
            $cantApply=1;
            return redirect('candidate/personal-details');
        
        }
      
    
    

    $qualification = DB::table('candidate_educational_details')->where('candidate_id',$user->id)->whereBetween('qualification', array(1, 7))->orderBy('qualification', 'desc')->take(1)->get('qualification');
   
        foreach ( $qualification as $object) {
            $qualification=$object->qualification;
        }
    
    
       $educations = DB::table('candidate_educational_details')->where('candidate_id',$user->id)->orderBy('qualification', 'desc')->get();
       
    
        $education='';
        foreach ($educations as $object) {
            $education=$education.' '.$object->stream;
        }
    
        //Candidate::where('id', $user->id)->update(['search_key' =>$education,'qualification_type' =>$qualification]);

        $monthy_applications = DB::table('job_log')->where('candidateid',$user->id)->whereMonth('applieddate', '=', date('n'))->whereYear('applieddate', '=', date('Y'))->count();

        $job_log = DB::table('job_log')->where('candidateid',$user->id)->where('jobid',$id)->get();
        
        if($job_log->count() >1)
        {
            DB::table('job_log')->where('candidateid',$user->id)->where('jobid',$id)->orderBy('id', 'desc')->take($job_log->count()-1)->delete();
         }
          $job_log = DB::table('job_log')->where('candidateid',$user->id)->where('jobid',$id)->first();

          DB::table('job_log')->where('jobid',$id)->where('candidateid', $user->id)->update(['candidate_Notification' => 1]);
    }

        session(['link' => url()->current()]);
        Job::where('id', $id)->increment('views', 1);
        $recent_jobs=Job::where('id','!=',$id)->latest()->paginate(10);
        $similar_jobs=Job::where('id','!=',$id)->inRandomOrder()->paginate(10);

        $categories = DB::table('job_category')->orderBy('name', 'asc')->get();


        $active_categories= $items = array();
              
          foreach ($categories as $category) 
          {
              $jobCount = Job::where('category', $category->name)->count();

       
        if($jobCount>0)

        {
            $jobCount=" (".$jobCount.")";
            $active_categories[] = $category->name.$jobCount;
        }

        else
        {
            $jobCount="" ; 
         }

            $items[] = $category->name.$jobCount;
}
   
       
return view('viewjob', compact(['job','user','recent_jobs','similar_jobs','educations','cantApply','job_log','monthy_applications','active_categories']));

    }



     //viewemployer

     protected function viewemployer($id)

     {
          Visitor::_save();

    
                
     $employer =Employer::where('id',$id)->where('status',1)->where('verified',1) ->first();
     
     if(!$employer)
     {
         abort(404);
     }

     Employer::where('id',$id)->increment('views',1);
     
       
         
       
        
 return view('viewemployer', compact('employer'));
 

 
 
     }




     //View All Job details

     protected function viewalljobs(Request $request)

     {
          Visitor::_save();
          $searchkey=$city=$category=$type=NULL;

          $searchkey = $request->query('searchkey');
          $city = $request->query('city');
          $category = $request->query('category');
          $type = $request->query('type');
        
          
 
   $jobs = Job::query();

if (!empty($searchkey)) {
    $jobs = $jobs->where('jobtittle', 'like', '%'.$searchkey.'%');
}

if (!empty($category)) {
    
    $jobs = $jobs->where('category', $category);
}


if (!empty($city)) {
    $jobs = $jobs->where('city', 'like', '%'.$city.'%');
}

if (!empty($type)) {
    $jobs = $jobs->where('type', 'like', '%'.$type.'%');
}

   $jobs = $jobs->latest()->paginate(32);



         if(!$jobs)
         {
             abort(404);
             
         }
   


         $designation = DB::table('job_designation')->orderBy('name', 'asc')->get();
         
         
         $categories = DB::table('job_category')->orderBy('name', 'asc')->get();


         $active_categories= $items = array();
               
           foreach ($categories as $category) 
           {
               $jobCount = Job::where('category', $category->name)->count();
 
        
         if($jobCount>0)
 
         {
             $jobCount=" (".$jobCount.")";
             $active_categories[] = $category->name.$jobCount;
         }
 
         else
         {
             $jobCount="" ; 
          }
 
             $items[] = $category->name.$jobCount;
 }
 //shuffle($active_categories);
 //$active_categories=array_slice($active_categories,1,15);


   
        
 return view('jobs', compact('jobs'),['categories' => $items, 'designation' =>$designation,'active_categories'=>$active_categories]);
 

 
 
     }



     //Contact form saving


     
    protected function contactus(Request $request)

    {      Visitor::_save();
        if (request()->getMethod() == 'POST') {
            $rules = ['captcha' => 'required|captcha'];
            $validator = validator()->make(request()->all(), $rules);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->with('error', '* Wrong Captcha')->withInput();
            } else {
                $name = substr(testInput($request->input('name')),0,100);
                $phone = substr(testInput($request->input('phone')),0,100);
                $email = substr(testInput($request->input('email')),0,100);
                $message = substr(testInput($request->input('message')),0,1000);
                $ip = substr(testInput($_SERVER['REMOTE_ADDR']),0,1000);
                

               $insert= DB::table('contact_messages')->insert(
                    ['name' =>$name,'email' =>$email,'phone' =>$phone,'message' => $message, 'ip' => $ip]
                );
                if($insert){
                 return redirect()->back()->with('Success', 'Success');
                }
            }
        }

        return view('contactus');
    }





}
