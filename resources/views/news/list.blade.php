@extends('layouts.master')

@section('title') News @endsection

@section('headerCss')

    <!-- Responsive Table css -->
    <link href="{{ URL::asset('/libs/rwd-table/rwd-table.min.css')}}" rel="stylesheet" type="text/css" /> <!-- Bootstrap Css -->
@endsection
@php
function time_elapsed_string0($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
@endphp
@section('content')
 <!-- start page title -->

                    
                    <!-- end row -->
            @if(Auth::user()->role=="admin")
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#push"><i class="mdi mdi-cellphone-information"></i> Push Notification</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#notice"><i class="mdi mdi-message-alert-outline"></i> Notice to pharmacies</a>
                </li>
				<li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#sms"><i class="mdi mdi-message-processing-outline"></i> Mass SMS</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#history"><i class="mdi mdi-history"></i> History</a>
                </li>				
            </ul>
	
			<div class="tab-content">
				<div class="tab-pane fade show active" id="push">
					<div class="card col-12">
                        <div class="card">
							<div class="card-body">
                                <div class="row">
						                <div class="col-6">
                                                <h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">Push Notification</h5> 	
                                                
                                                
                                                <form method="post" enctype="multipart/form-data">
                                                    @csrf
                                                    <input type="hidden" name="save" value="1">
                                                    <div class="row mt-5">
                                                        <div class="col-12"> 
                                                            <span class="badge bg-primary" style="color: white;font-size: 16px;position: absolute;right: 20px;">Total users: 956 <i class="mdi mdi-spin mdi-star"></i></span>
                                                            <div class="mb-3">
                                                            <span style="float: left;margin: 2px 10px 0 0;">Send during time zone business hours?</span>
                                                            <input type="checkbox" id="switch6" switch="primary" checked />
                                                            <label for="switch6" data-on-label="Yes" data-off-label="No"></label>
                                                            </div>
                                                        </div>
						                                <div class="col-6">                                                            
                                                            <div class="mb-3">
                                                                <label class="control-label">Choose a state</label>
                                                                <select class="form-control select2">
                                                                    <option>All states</option>
                                                                    <optgroup label="Alaskan/Hawaiian Time Zone">
                                                                        <option value="AK">Alaska</option>
                                                                        <option value="HI">Hawaii</option>
                                                                    </optgroup>
                                                                    <optgroup label="Pacific Time Zone">
                                                                        <option value="CA">California</option>
                                                                        <option value="NV">Nevada</option>
                                                                        <option value="OR">Oregon</option>
                                                                        <option value="WA">Washington</option>
                                                                    </optgroup>
                                                                    <optgroup label="Mountain Time Zone">
                                                                        <option value="AZ">Arizona</option>
                                                                        <option value="CO">Colorado</option>
                                                                        <option value="ID">Idaho</option>
                                                                        <option value="MT">Montana</option>
                                                                        <option value="NE">Nebraska</option>
                                                                        <option value="NM">New Mexico</option>
                                                                        <option value="ND">North Dakota</option>
                                                                        <option value="UT">Utah</option>
                                                                        <option value="WY">Wyoming</option>
                                                                    </optgroup>
                                                                    <optgroup label="Central Time Zone">
                                                                        <option value="AL">Alabama</option>
                                                                        <option value="AR">Arkansas</option>
                                                                        <option value="IL">Illinois</option>
                                                                        <option value="IA">Iowa</option>
                                                                        <option value="KS">Kansas</option>
                                                                        <option value="KY">Kentucky</option>
                                                                        <option value="LA">Louisiana</option>
                                                                        <option value="MN">Minnesota</option>
                                                                        <option value="MS">Mississippi</option>
                                                                        <option value="MO">Missouri</option>
                                                                        <option value="OK">Oklahoma</option>
                                                                        <option value="SD">South Dakota</option>
                                                                        <option value="TX">Texas</option>
                                                                        <option value="TN">Tennessee</option>
                                                                        <option value="WI">Wisconsin</option>
                                                                    </optgroup>
                                                                    <optgroup label="Eastern Time Zone">
                                                                        <option value="CT">Connecticut</option>
                                                                        <option value="DE">Delaware</option>
                                                                        <option value="FL">Florida</option>
                                                                        <option value="GA">Georgia</option>
                                                                        <option value="IN">Indiana</option>
                                                                        <option value="ME">Maine</option>
                                                                        <option value="MD">Maryland</option>
                                                                        <option value="MA">Massachusetts</option>
                                                                        <option value="MI">Michigan</option>
                                                                        <option value="NH">New Hampshire</option>
                                                                        <option value="NJ">New Jersey</option>
                                                                        <option value="NY">New York</option>
                                                                        <option value="NC">North Carolina</option>
                                                                        <option value="OH">Ohio</option>
                                                                        <option value="PA">Pennsylvania</option>
                                                                        <option value="RI">Rhode Island</option>
                                                                        <option value="SC">South Carolina</option>
                                                                        <option value="VT">Vermont</option>
                                                                        <option value="VA">Virginia</option>
                                                                        <option value="WV">West Virginia</option>
                                                                    </optgroup>
                                                                </select>
                                                            </div> 

                                                            
                                                            <div class="mb-3">
                                                                    <label for="example-text-input">Notification Subject</label>                                                                    
                                                                    <input class="form-control" maxlength="50"  id="defaultconfig" required type="text" name="title" value="">   
                                                            </div>
                                                            <div class="alert alert-info" role="alert">Notification will be delivered immediately  <strong>to all users</strong></div> 
                                                            
                                                          
                                                            
                                                        </div>


                                                        <div class="col-6">


                                                            
                                                                    <label for="example-text-input">Body</label>                                                      
                                                                     <textarea name="text" id="text" cols="30" rows="8" class="form-control" required></textarea>
                                                                
                                                            
                                                        </div>
                                                   
                                                        <div class="col-12 mt-5">
                                                <button type="button" class="btn btn-secondary btn-sm waves-effect" style="width: 100%;">Send</button>
                                            </div>
                                                    </div> 
                                                </form>  
                                        </div>								 
                                        
						
                                        <div class="col-6">                                             
                                            <div class="preview_ph">
                                            <div id="review-title" style="position: absolute;top: 300px;margin-left: 70px;color: black;font-weight: bold;text-transform: capitalize;max-width: 325px;text-align: left;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;"></div>
                                            <div id="review-text" style="position: absolute;text-transform: capitalize;top: 320px;margin-left: 70px;color: black;font-weight: 300;max-width: 325px;text-align: left;overflow: hidden;text-overflow: ellipsis;display: -moz-box;-moz-box-orient: vertical;display: -webkit-box;-webkit-line-clamp: 3;-webkit-box-orient: vertical;line-clamp: 3;box-orient: vertical;"></div>
                                            </div>                                            
                                        </div>
                                </div>
                            </div>
                        </div>
					</div>
                </div>

                <div class="tab-pane fade show" id="notice">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
								<div class="card-body">
                                <h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">Notice to pharmacies</h5> 
                                    <div class="row">	                                                
                                        <div class="col-6">                                                             
                                            <div class="mb-3">
                                                <label for="example-text-input">Notification Subject</label>                                                                    
                                                <input class="form-control" maxlength="50"  id="defaultconfig" required type="text" name="title-notice" value="">   
                                            </div>
                                            <div class="mb-3">
                                                <label for="example-text-input">Photo</label>                                                                    
                                                <input type="file" class="filestyle" data-buttonname="btn-secondary">
                                            </div>
                                            <div class="alert alert-info" role="alert">Notification will be delivered immediately  <strong>to all pharmacies</strong></div>
                                        </div>						
                                        <div class="col-6">  
                                            <label for="example-text-input">Body</label>                                                      
                                            <textarea name="text-notice" id="text-notice" cols="30" rows="8" class="form-control" required></textarea> 
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-12">
                            <div class="card">
								<div class="card-body">                                
                                    <div class="row">	                                                
                                        <div class="col-2">  
                                            <img class="img-fluid" src="https://cp.a2brx.com/images/faq.jpg" alt="FAQ"> 
                                        </div>  			
                                        <div class="col-10">  
                                        <h4><span class="badge bg-light">19.03.2023</span></h4>
                                        
                                        <h3>Subject</h3>                                                                                                 
                                         <p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</p>
                                         <a class="btn btn-primary waves-effect waves-light" href="#" role="button">Details</a> 
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
                
                <div class="tab-pane fade show" id="sms">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
								<div class="card-body">
                                <div class="row">
						                <div class="col-6">
                                                <h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;">SMS Notification</h5> 	
                                                
                                                
                                                <form method="post" enctype="multipart/form-data">
                                                    @csrf
                                                    <input type="hidden" name="save" value="1">
                                                    <div class="row mt-5">
                                                        <div class="col-12"> 
                                                            <span class="badge bg-primary" style="color: white;font-size: 16px;position: absolute;right: 20px;">Total users: 14233 <i class="mdi mdi-spin mdi-star"></i></span>
                                                            <div class="mb-3">
                                                            <span style="float: left;margin: 2px 10px 0 0;">Send during time zone business hours?</span>
                                                            <input type="checkbox" id="switch6" switch="primary" checked />
                                                            <label for="switch6" data-on-label="Yes" data-off-label="No"></label>
                                                            </div>
                                                        </div>
						                                <div class="col-6">                                                            
                                                            <div class="mb-3">
                                                                <label class="control-label">Choose a state</label>
                                                                <select class="form-control select2">
                                                                    <option>All states</option>
                                                                    <optgroup label="Alaskan/Hawaiian Time Zone">
                                                                        <option value="AK">Alaska</option>
                                                                        <option value="HI">Hawaii</option>
                                                                    </optgroup>
                                                                    <optgroup label="Pacific Time Zone">
                                                                        <option value="CA">California</option>
                                                                        <option value="NV">Nevada</option>
                                                                        <option value="OR">Oregon</option>
                                                                        <option value="WA">Washington</option>
                                                                    </optgroup>
                                                                    <optgroup label="Mountain Time Zone">
                                                                        <option value="AZ">Arizona</option>
                                                                        <option value="CO">Colorado</option>
                                                                        <option value="ID">Idaho</option>
                                                                        <option value="MT">Montana</option>
                                                                        <option value="NE">Nebraska</option>
                                                                        <option value="NM">New Mexico</option>
                                                                        <option value="ND">North Dakota</option>
                                                                        <option value="UT">Utah</option>
                                                                        <option value="WY">Wyoming</option>
                                                                    </optgroup>
                                                                    <optgroup label="Central Time Zone">
                                                                        <option value="AL">Alabama</option>
                                                                        <option value="AR">Arkansas</option>
                                                                        <option value="IL">Illinois</option>
                                                                        <option value="IA">Iowa</option>
                                                                        <option value="KS">Kansas</option>
                                                                        <option value="KY">Kentucky</option>
                                                                        <option value="LA">Louisiana</option>
                                                                        <option value="MN">Minnesota</option>
                                                                        <option value="MS">Mississippi</option>
                                                                        <option value="MO">Missouri</option>
                                                                        <option value="OK">Oklahoma</option>
                                                                        <option value="SD">South Dakota</option>
                                                                        <option value="TX">Texas</option>
                                                                        <option value="TN">Tennessee</option>
                                                                        <option value="WI">Wisconsin</option>
                                                                    </optgroup>
                                                                    <optgroup label="Eastern Time Zone">
                                                                        <option value="CT">Connecticut</option>
                                                                        <option value="DE">Delaware</option>
                                                                        <option value="FL">Florida</option>
                                                                        <option value="GA">Georgia</option>
                                                                        <option value="IN">Indiana</option>
                                                                        <option value="ME">Maine</option>
                                                                        <option value="MD">Maryland</option>
                                                                        <option value="MA">Massachusetts</option>
                                                                        <option value="MI">Michigan</option>
                                                                        <option value="NH">New Hampshire</option>
                                                                        <option value="NJ">New Jersey</option>
                                                                        <option value="NY">New York</option>
                                                                        <option value="NC">North Carolina</option>
                                                                        <option value="OH">Ohio</option>
                                                                        <option value="PA">Pennsylvania</option>
                                                                        <option value="RI">Rhode Island</option>
                                                                        <option value="SC">South Carolina</option>
                                                                        <option value="VT">Vermont</option>
                                                                        <option value="VA">Virginia</option>
                                                                        <option value="WV">West Virginia</option>
                                                                    </optgroup>
                                                                </select>
                                                            </div> 

                                                            
                                                            <div class="mb-3">
                                                                    <label for="example-text-input">SMS Subject</label>                                                                    
                                                                    <input class="form-control" maxlength="50"  id="defaultconfig" required type="text" name="title-sms" value="">   
                                                            </div>
                                                            <div class="alert alert-info" role="alert">SMS will be delivered immediately  <strong>to all users</strong></div> 
                                                            
                                                          
                                                            
                                                        </div>


                                                        <div class="col-6">


                                                            
                                                                    <label for="example-text-input">Body</label>                                                      
                                                                     <textarea name="text-sms" id="text-sms" cols="30" rows="8" class="form-control" required></textarea>
                                                                
                                                            
                                                        </div>
                                                   
                                                        <div class="col-12 mt-5">
                                                <button type="button" class="btn btn-secondary btn-sm waves-effect" style="width: 100%;">Send</button>
                                            </div>
                                                    </div> 
                                                </form>  
                                        </div>								 
                                        
						
                                        <div class="col-6">                                             
                                            <div class="preview_sms">
                                            <div id="review-title-sms" style="position: absolute;top: 300px;margin-left: 70px;color: black;font-weight: bold;text-transform: capitalize;max-width: 325px;text-align: left;overflow: hidden;text-overflow: ellipsis;white-space: nowrap;"></div>
                                            <div id="review-text-sms" style="position: absolute;text-transform: capitalize;top: 320px;margin-left: 70px;color: black;font-weight: 300;max-width: 325px;text-align: left;overflow: hidden;text-overflow: ellipsis;display: -moz-box;-moz-box-orient: vertical;display: -webkit-box;-webkit-line-clamp: 3;-webkit-box-orient: vertical;line-clamp: 3;box-orient: vertical;"></div>
                                            </div>                                            
                                        </div>
                                </div>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade show" id="history">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
								<div class="card-body">
                                <div class="row">
						                <div class="col-12">
											<h5 style="background: #7a6fbe;color: #ffffff;padding: 5px;text-align: center;" class="mb-5">History</h5>
											
											<div class="alert alert-success mt-2" role="alert">
                                             <i class="mdi mdi-cellphone-message"></i>   <strong>05.10.2020</strong> Push from pharmacy <strong>Sinai Rx Pharmacy Inc</strong> Status - Successful
                                            </div>
											<div class="alert alert-success mt-2" role="alert">
                                             <i class="mdi mdi-cellphone-message"></i>   <strong>05.09.2020</strong> Push from pharmacy <strong>MEDICOS PHARMACY</strong> Status - Successful
                                            </div>
											
											<div class="alert alert-info mt-2" role="alert">
                                             <i class="mdi mdi-email-open-multiple-outline"></i>   <strong>05.07.2020</strong> Notice from <strong>administrator</strong> Status - Successful
                                            </div>
											
										
										</div>
								</div>
                        		</div>											
								
								
								
								
								
								
								
								
								
								
								<!-- 
                                @if(Auth::user()->role=="admin")
                                <a href="/news/add" class="addorder" style="position:absolute;margin-top: 10px;margin-left: 10px;"><button class="btn btn-primary">Add News</button></a>
                                @endif
                                <div style="display: inline-block;text-align: center;margin-top: 10px;">Pages: 
                                    @foreach ($pages as $page)
                                        <form class="filter-form" style="display: inline-block;">
                                            <input type="hidden" name="page" value="{{ $page['id'] }}">
                                            <button class="btn {{$page['class']}}">{{ $page['id'] }}</button>
                                        </form> 
                                    @endforeach
                                    ...
                                </div>
                                <div class="card-body">
                                    @foreach ($news as $post)
                                        @if(!empty($post->viewed))
                                        <div class="alert alert-{{$post->type}} alert-block viewed" role="alert">
                                        @else 
                                        <div class="alert alert-{{$post->type}} alert-block" role="alert">
                                        @endif
                                            @if($post->link!='')
                                                <a href="{{$post->link}}">
                                            @else 
                                                <a href="#">
                                            @endif
                                            <div class="icon-block">
                                            @if($post->type=='danger' || $post->type=='warning')
                                                <i class="fas fa-info-circle"></i>
                                            @else
                                                <i class="fas fa-check-circle"></i>
                                            @endif
                                            </div>
                                            <div class="info-block">
                                                <div class="text"><b>{{$post->title}}</b></div>
                                                <div class="text">{{$post->text}}</div>
                                                @if(round((strtotime(date('now')) - strtotime($post->created))/3600, 1)<48)
                                                    <div class="datetime">{{time_elapsed_string0($post->created)}}</div>
                                                @else
                                                    <div class="datetime">{{date('m/d/Y g:i A', strtotime($post->created))}}</div>
                                                @endif
                                            </div>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                                <div style="display: inline-block;text-align: center;margin-top: 10px;">Pages: 
                                    @foreach ($pages as $page)
                                        <form class="filter-form" style="display: inline-block;">
                                            <input type="hidden" name="page" value="{{ $page['id'] }}">
                                            <button class="btn {{$page['class']}}">{{ $page['id'] }}</button>
                                        </form> 
                                    @endforeach
                                    ...
                                </div> -->
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
				</div>	
			</div>
            @endif

            @if(Auth::user()->role=="medic")

                    <div class="row">
                        <div class="col-12" id="05012023-2">
                            <div class="card">
								<div class="card-body">                                
                                    <div class="row">	                                                
                                        <div class="col-4">  
                                            <img class="img-fluid" src="https://cp.a2brx.com/images/news/05012023-2.jpg" alt="Delivery planning"> 
                                        </div>  			
                                        <div class="col-8">  
                                        <h4><span class="badge bg-light">MAY 01, 2023</span></h4>
                                        
                                        <h3>Delivery planning</h3>                                                                                                 
                                         <p>New order feature. </br>
                                            Select your desired delivery date when placing an order.</br>
                                            Medicines will be in our storage.</br>
                                            We will deliver on the specified date.</br>
                                            You and your customer will receive notification of successful delivery
                                         </p>
                                         
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                     <div class="row">
                        <div class="col-12" id="05012023-1">
                            <div class="card">
								<div class="card-body">                                
                                    <div class="row">	                                                
                                        <div class="col-4">  
                                            <img class="img-fluid" src="https://cp.a2brx.com/images/news/05012023-1.jpg" alt="New customer profile"> 
                                        </div>  			
                                        <div class="col-8">  
                                        <h4><span class="badge bg-light">MAY 01, 2023</span></h4>
                                        
                                        <h3>New customer profile</h3>                                                                                                 
                                         <p>More information about the customer. Order history, quick statistics and profile change history and more.
                                         </p>
                                         
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">                    
                        <div class="col-12" id="03262023">
                            <div class="card">
								<div class="card-body">                                
                                    <div class="row">	                                                
                                        <div class="col-4">  
                                            <img class="img-fluid" src="https://cp.a2brx.com/images/news/03262023.jpg" alt="FAQ"> 
                                        </div>  			
                                        <div class="col-8">  
                                        <h4><span class="badge bg-light">MAR 26, 2023</span></h4>
                                        
                                        <h3>New Feature: Customer category - Facility</h3>                                                                                                 
                                         <p>Multidelivery to one address. Select Facility under Clients in the main menu. Create a new one and fill in the required information. Add more recipients to your new Facility. Create a new Facility order. Note that it is next to "Create a single order" in the dropdown list. 
                                         Fill in your shipping details. Select additional recipients on the right side or upload PDF (micromerchantsystems only) - recipients will be created automatically.
                                         Click Submit. 
                                         </p>
                                         
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12" id="03202023">
                            <div class="card">
								<div class="card-body">                                
                                    <div class="row">	                                                
                                        <div class="col-4">  
                                            <img class="img-fluid" src="https://cp.a2brx.com/images/news/03202023.jpg" alt="FAQ"> 
                                        </div>  			
                                        <div class="col-8">  
                                        <h4><span class="badge bg-light">MAR 20, 2023</span></h4>
                                        
                                        <h3>New Feature: Repeat order</h3>                                                                                                 
                                         <p>After successful delivery, find your order through the search. In the "Order Status" section, you will see the "Repeat Order" button. Just click and a similar order will be created. Make the necessary changes and click Submit</p>
                                         
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

            @endif
@endsection

@section('footerScript')
<script>
$('#text').keyup(function(e){
    $("#review-text").text($("#text").val());
});
$('input[name="title"]').keyup(function(e){
    $("#review-title").text($('input[name="title"]').val());
});
$('#text-sms').keyup(function(e){
    $("#review-text-sms").text($("#text-sms").val());
});
$('input[name="title-sms"]').keyup(function(e){
    $("#review-title-sms").text($('input[name="title-sms"]').val());
});
$('#text-notice').keyup(function(e){
    $("#review-text-notice").text($("#text-notice").val());
});
$('input[name="title-notice"]').keyup(function(e){
    $("#review-title-notice").text($('input[name="title-notice"]').val());
});
</script>
@endsection