@extends('layouts.master')

@section('title') Chat new @endsection

@section('headerCss')
<style>
.rep-menu a {
    font-size: 14px;
    line-height: 30px;
    color: black;
    display: block;
    margin: 10px 0;
    padding: 5px 0 5px 20px;

}
.rep-menu a:hover {
    background: #7a6fbe;
    color: #ffffff !important;
}
.rep-menu i {
    font-size: 16px;
    margin: 0px 7px;
}

</style> 
@endsection

@section('content')

<div class="d-lg-flex">
                            <div class="chat-leftsidebar card">
                                <div class="card-body" style="flex: 0 1 auto;">
                                    
                                   <div class="text-center bg-light rounded px-4 py-3">                                            
                                            <div class="chat-user-status">
                                                <img src="https://cp.a2brx.com/images/avatar-chat-min.jpg" class="avatar-md rounded-circle" alt="">
                                                <div class="">
                                                    <div class="status"></div>
                                                </div>
                                            </div>
                                            <h5 class="font-size-16 mb-1 mt-3"><a href="#" class="text-dark">James Williams </a></h5>
                                            <p class="text-muted mb-0">Customer Support</p>                                            
                                            <p class="text-muted mb-0">Available</p>
                                   </div>
                                </div>        
                                <div class="px-3">
                                    <div class="search-box position-relative">
                                        <input type="text" class="form-control rounded border" placeholder="Search...">
                                        <i class="mdi mdi-magnify search-icon"></i>
                                    </div>
                                </div>
        
                                <div class="chat-leftsidebar-nav">
                                    <ul class="nav nav-pills nav-justified bg-light m-3 rounded">
                                        <li class="nav-item">
                                            <a href="#chat" data-bs-toggle="tab" aria-expanded="true" class="nav-link active">
                                                <i class="bx bx-chat font-size-20 d-sm-none"></i>
                                                <span class="d-none d-sm-block">Chat</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#groups" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                                <i class="bx bx-group font-size-20 d-sm-none"></i>
                                                <span class="d-none d-sm-block">Groups</span>
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a href="#contacts" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                                <i class="bx bx-book-content font-size-20 d-sm-none"></i>
                                                <span class="d-none d-sm-block">Contacts</span>
                                            </a>
                                        </li>
                                    </ul>
                                    <div class="tab-content">
                                        <div class="tab-pane show active" id="chat">
                                            <div class="chat-message-list" data-simplebar>
                                                <div class="pt-0">                                                  
                                                    <ul class="list-unstyled chat-list p-3">
                                                        <li class="active">
                                                            <a href="#">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="flex-shrink-0 user-img online align-self-center mr-3">
                                                                        <div class="avatar-sm  online align-self-center">
                                                                            <span class="avatar-title rounded-circle bg-soft-primary text-white font-size-16 font-size-18">
                                                                            <img src="https://cp.a2brx.com/images/avatar-cha-usert-min.jpg" class="avatar-sm rounded-circle" alt="">
                                                                            </span>
                                                                        </div>
                                                                        <span class="user-status"></span>
                                                                        </div>
                                                                    <div class="flex-grow-1 overflow-hidden">
                                                                        <h5 class="text-truncate font-size-15 mb-0">Mila Wilson</h5>
                                                                        <p class="text-white mb-0 mt-1 text-truncate">Athenas Pharmacy</p>                                                                   
                                                                    </div>
                                                                    <div class="flex-grow-2 ms-3 text-center">
                                                                        <span class="badge bg-light rounded-pill">5</span></br>
                                                                        <span class="badge bg-light rounded-pill">SMS</span>
                                                                    </div>                                                                
                                                                </div>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="#">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="flex-shrink-0 user-img online align-self-center mr-3">
                                                                        <div class="avatar-sm align-self-center">
                                                                            <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-16 font-size-18">
                                                                                P
                                                                            </span>
                                                                        </div>
                                                                        <span class="user-status"></span>
                                                                    </div>
                                                                    
                                                                    <div class="flex-grow-1 overflow-hidden">
                                                                        <h5 class="text-truncate font-size-15 mb-0">Mina Elafandy</h5>
                                                                        <p class="text-muted mb-0 mt-1 text-truncate">Athenas Pharmacy</p>
                                                                    </div>
                                                                    <div class="flex-shrink-0">
                                                                    </div>
                                                                </div>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="#">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="flex-shrink-0 user-img online align-self-center mr-3">
                                                                        <div class="avatar-sm align-self-center">
                                                                            <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-16 font-size-18">
                                                                                D
                                                                            </span>
                                                                        </div>    
                                                                    </div>                                                                    
                                                                    <div class="flex-grow-1 overflow-hidden">
                                                                        <h5 class="text-truncate font-size-15 mb-0">Farkhod Kodirov</h5>
                                                                        <p class="text-muted mb-0 mt-1 text-truncate">Driver</p>
                                                                    </div>
                                                                    <div class="flex-grow-2 ms-3 text-center">
                                                                        <span class="badge bg-primary rounded-pill text-white">4</span></br>
                                                                        <span class="badge bg-primary rounded-pill text-white">APP</span>
                                                                    </div>                                                                   
                                                                </div>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="#">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="flex-shrink-0 user-img online align-self-center mr-3">
                                                                        <div class="avatar-sm align-self-center">
                                                                            <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-16 font-size-18">
                                                                                D
                                                                            </span>
                                                                        </div>    
                                                                    </div>                                                                    
                                                                    <div class="flex-grow-1 overflow-hidden">
                                                                        <h5 class="text-truncate font-size-15 mb-0">Djurakul Akobirov</h5>
                                                                        <p class="text-muted mb-0 mt-1 text-truncate">Driver</p>
                                                                    </div>
                                                                    <div class="flex-grow-2 ms-3 text-center">
                                                                        <span class="badge bg-primary rounded-pill text-white">8</span></br>
                                                                        <span class="badge bg-primary rounded-pill text-white">APP</span>
                                                                    </div>                                                                   
                                                                </div>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="#">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="flex-shrink-0 user-img align-self-center mr-3">
                                                                        <img src="https://cp.a2brx.com/images/avatar-cha-usert2-min.jpg" class="rounded-circle avatar-sm" alt="">
                                                                        <span class="user-status"></span>
                                                                    </div>
                                                                    <div class="flex-grow-1 overflow-hidden">
                                                                        <h5 class="text-truncate font-size-15 mb-0">Marshall Wilson</h5>
                                                                        <p class="text-muted mb-0 mt-1 text-truncate">PHARMACY TOWN</p>
                                                                    </div>
                                                                    <div class="flex-grow-2 ms-3 text-center">
                                                                        <span class="badge bg-primary rounded-pill text-white">1</span></br>
                                                                        <span class="badge bg-primary rounded-pill text-white">APP</span>
                                                                    </div> 
                                                                </div>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="#">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="flex-shrink-0 user-img online align-self-center mr-3">
                                                                        <div class="avatar-sm align-self-center">
                                                                            <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-16 font-size-18">
                                                                                D
                                                                            </span>
                                                                        </div>    
                                                                    </div>                                                                    
                                                                    <div class="flex-grow-1 overflow-hidden">
                                                                        <h5 class="text-truncate font-size-15 mb-0">Unab Khaydarov</h5>
                                                                        <p class="text-muted mb-0 mt-1 text-truncate">Driver</p>
                                                                    </div>
                                                                    <div class="flex-grow-2 ms-3 text-center">
                                                                        <span class="badge bg-primary rounded-pill text-white">8</span></br>
                                                                        <span class="badge bg-primary rounded-pill text-white">APP</span>
                                                                    </div>                                                                   
                                                                </div>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="#">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="flex-shrink-0 user-img online align-self-center mr-3">
                                                                        <div class="avatar-sm align-self-center">
                                                                            <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-16 font-size-18">
                                                                                P
                                                                            </span>
                                                                        </div>
                                                                        <span class="user-status"></span>
                                                                    </div>
                                                                    
                                                                    <div class="flex-grow-1 overflow-hidden">
                                                                        <h5 class="text-truncate font-size-15 mb-0">Mayank Parikh</h5>
                                                                        <p class="text-muted mb-0 mt-1 text-truncate">Super Health Pharmacy</p>
                                                                    </div>
                                                                    <div class="flex-shrink-0">
                                                                    </div>
                                                                </div>
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
        
                                        <div class="tab-pane" id="groups">
                                            <div class="chat-message-list" data-simplebar>
                                                <div class="pt-3">
                                                    <div class="px-3">
                                                        <h5 class="font-size-14 mb-3">Groups</h5>
                                                    </div>
                                                    <ul class="list-unstyled chat-list p-3 pt-0">
                                                        <li>
                                                            <a href="#">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="flex-shrink-0 avatar-sm mr-3">
                                                                        <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-16">
                                                                            G
                                                                        </span>
                                                                    </div>
                                                                    
                                                                    <div class="flex-grow-1">
                                                                        <h5 class="font-size-13 mb-0">General</h5>
                                                                    </div>
                                                                </div>
                                                            </a>
                                                        </li>
        
                                                        <li>
                                                            <a href="#">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="flex-shrink-0 avatar-sm mr-3">
                                                                        <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-16">
                                                                            D
                                                                        </span>
                                                                    </div>
                                                                    
                                                                    <div class="flex-grow-1">
                                                                        <h5 class="font-size-13 mb-0">Drivers</h5>
                                                                    </div>
                                                                </div>
                                                            </a>
                                                        </li>        
                                                        <li>
                                                            <a href="#">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="flex-shrink-0 avatar-sm mr-3">
                                                                        <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-16">
                                                                            P
                                                                        </span>
                                                                    </div>
                                                                    
                                                                    <div class="flex-grow-1">
                                                                        <h5 class="font-size-13 mb-0">Pharmacys</h5>
                                                                    </div>
                                                                </div>
                                                            </a>
                                                        </li>        
                                                        <li>
                                                            <a href="#">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="flex-shrink-0 avatar-sm mr-3">
                                                                        <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-16">
                                                                            C
                                                                        </span>
                                                                    </div>
                                                                    
                                                                    <div class="flex-grow-1">
                                                                        <h5 class="font-size-13 mb-0">Customers</h5>
                                                                    </div>
                                                                </div>
                                                            </a>
                                                        </li>        
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
        
                                        <div class="tab-pane" id="contacts">
                                            <div class="chat-message-list" data-simplebar>
                                                <div class="pt-3">
                                                    <div class="px-3">
                                                        <h5 class="font-size-14 mb-3">Contacts</h5>
                                                    </div>
        
                                                    <div class="p-3 pt-0">
                                                        <div>
                                                            <div class="px-3 contact-list">A</div>
        
                                                            <ul class="list-unstyled chat-list">
                                                                <li>
                                                                    <a href="#">
                                                                        <h5 class="font-size-13 mb-0">Adam Miller</h5>
                                                                    </a>
                                                                </li>
            
                                                                <li>
                                                                    <a href="#">
                                                                        <h5 class="font-size-13 mb-0">Alfonso Fisher</h5>
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
        
                                                        <div class="mt-4">
                                                            <div class="px-3 contact-list">B</div>
        
                                                            <ul class="list-unstyled chat-list">
                                                                <li>
                                                                    <a href="#">
                                                                        <h5 class="font-size-13 mb-0">Bonnie Harney</h5>
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
        
                                                        <div class="mt-4">
                                                            <div class="px-3 contact-list">C</div>
        
                                                            <ul class="list-unstyled chat-list">
                                                                <li>
                                                                    <a href="#">
                                                                        <h5 class="font-size-13 mb-0">Charles Brown</h5>
                                                                    </a>
                                                                    <a href="#">
                                                                        <h5 class="font-size-13 mb-0">Carmella Jones</h5>
                                                                    </a>
                                                                    <a href="#">
                                                                        <h5 class="font-size-13 mb-0">Carrie Williams</h5>
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
        
                                                        <div class="mt-4">
                                                            <div class="px-3 contact-list">D</div>
        
                                                            <ul class="list-unstyled chat-list">
                                                                <li>
                                                                    <a href="#">
                                                                        <h5 class="font-size-13 mb-0">Dolores Minter</h5>
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
        
                            </div>
                            <!-- end chat-leftsidebar -->
        
                            <div class="w-100 user-chat mt-4 mt-sm-0 ms-lg-3 ml-3">
                                <div class="card">
                                    <div class="p-3 px-lg-4 border-bottom">
                                        <div class="row">
                                            <div class="col-xl-4 col-7">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0 avatar-lg mr-3 d-sm-block d-none">
                                                        <img src="https://cp.a2brx.com/images/avatar-cha-usert-min.jpg" alt="user" class="img-fluid d-block avatar rounded-circle">
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h5 class="font-size-16 mb-1 text-truncate"><a href="#" class="text-dark">Mila Wilson</a></h5>
                                                        <p class="text-muted text-truncate mb-0">Online </p>
                                                        <h6 class="font-size-16 mb-1 text-truncate">Communication: SMS</h5>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xl-8 col-5 text-right">                                                    
                                                <h5>Pharmacy customer: <span class="badge bg-light">Athenas Pharmacy</span> <span style="cursor:pointer;" class="badge badge-pill badge-info" onclick="">History orders</span></h5>  
                                                <h5>Phone: <span class="badge bg-light">(929) 123 4567</span></h5> 
                                                <h5>App: <span class="badge bg-light">no app</span> <span style="cursor:pointer;" class="badge badge-pill badge-info" onclick="">Resend</span></h5> 
                                                <h5>Address: <span class="badge bg-light">19 Lawrence St 1 e, Yonkers, NY 10705, USA</span></h5>
                                                <h5>Current driver: <span class="badge bg-light">Farkhod Kodirov</span></h5>
                                            </div>
                                        </div>
                                    </div>
        
                                    <div class="chat-conversation p-4" data-simplebar>
                                        <ul class="list-unstyled mb-0">
                                            <li class="chat-day-title"> 
                                                <span class="title">Current order #96753 - <span style="font-size: 11px;padding: 4px 5px;border-radius: 3px;" class="badge badge-pill badge-success">Delivered 03.22.2023</span></span>
                                            </li>
                                            <li>
                                                <div class="conversation-list">
                                                    <div class="d-flex">
                                                        <img src="https://cp.a2brx.com/images/avatar-cha-usert-min.jpg" class="rounded-circle avatar-md" alt="">
                                                        <div class="flex-1 ms-3">
                                                            <div class="d-flex justify-content-between">
                                                                <h5 class="font-size-16 conversation-name align-middle"> Mila Wilson </h5>
                                                                <span class="time fw-normal text-muted me-0 me-md-4">Thursday 10:02 AM</span>
                                                            </div>
                                                            <div class="ctext-wrap">
                                                                <div class="ctext-wrap-content">
                                                                    <p class="mb-0">Hi, I ordered a package a few days ago and it still hasn't arrived. Can you help me track it?</p>
                                                                    
                                                                </div>
                                                                <div class="dropdown align-self-start">
                                                                    <a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                        <i class="bx bx-dots-vertical-rounded"></i>
                                                                    </a>
                                                                    <div class="dropdown-menu">
                                                                        <a class="dropdown-item" href="#">Copy</a>
                                                                        <a class="dropdown-item" href="#">Save</a>
                                                                        <a class="dropdown-item" href="#">Forward</a>
                                                                        <a class="dropdown-item" href="#">Delete</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
        
                                            <li class="right">
                                                <div class="conversation-list">
                                                    <div class="d-flex">
                                                        <div class="flex-1 mr-3">
                                                            <div class="d-flex justify-content-between">
                                                                <span class="time fw-normal text-muted ms-0 ms-md-4">Thursday 10:02 AM</span>
                                                                <h5 class="font-size-16 conversation-name align-middle"> James Williams </h5>
                                                            </div>
                                                            <div class="ctext-wrap">
                                                                <div class="ctext-wrap-content">
                                                                    <p class="mb-0 text-start">Hi! Sorry to hear that. Do you have your order number?
                                                                    </p>
                                                                   
                                                                </div>
                                                                <div class="dropdown align-self-start">
                                                                    <a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                        <i class="bx bx-dots-vertical-rounded"></i>
                                                                    </a>
                                                                    <div class="dropdown-menu">
                                                                        <a class="dropdown-item" href="#">Copy</a>
                                                                        <a class="dropdown-item" href="#">Save</a>
                                                                        <a class="dropdown-item" href="#">Forward</a>
                                                                        <a class="dropdown-item" href="#">Delete</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <img src="https://cp.a2brx.com/images/avatar-chat-min.jpg" class="rounded-circle avatar-md" alt="">
                                                    </div>
                                                    
                                                </div>
                                                
                                            </li>
        
                                            <li>
                                                <div class="conversation-list">
                                                    <div class="d-flex">
                                                        <img src="https://cp.a2brx.com/images/avatar-cha-usert-min.jpg" class="rounded-circle avatar-md" alt="">
                                                        <div class="flex-1 ms-3">
                                                            <div class="d-flex justify-content-between">
                                                                <h5 class="font-size-16 conversation-name align-middle"> Mila Wilson </h5>
                                                                <span class="time fw-normal text-muted me-0 me-md-4">Thursday 10:04 AM</span>
                                                            </div>
                                                            <div class="ctext-wrap">
                                                                <div class="ctext-wrap-content">
                                                                   <p class="mb-0">
                                                                        Yes, I do. It's 96753.
                                                                    </p>
                                                                </div>
                                                                <div class="dropdown align-self-start">
                                                                    <a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                        <i class="bx bx-dots-vertical-rounded"></i>
                                                                    </a>
                                                                    <div class="dropdown-menu">
                                                                        <a class="dropdown-item" href="#">Copy</a>
                                                                        <a class="dropdown-item" href="#">Save</a>
                                                                        <a class="dropdown-item" href="#">Forward</a>
                                                                        <a class="dropdown-item" href="#">Delete</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                </div>
                                            </li>
        
                                            <li class="chat-day-title"> 
                                                <span class="title">Today</span>
                                            </li>
        
                                            <li class="right">
                                                <div class="conversation-list">
                                                    <div class="d-flex">
                                                        <div class="flex-1 mr-3">
                                                            <div class="d-flex justify-content-between">
                                                                <span class="time fw-normal text-muted ms-0 ms-md-4">Today 10:08 AM</span>
                                                                <h5 class="font-size-16 conversation-name align-middle"> James Williams </h5>
                                                            </div>
                                                            <div class="ctext-wrap">
                                                                <div class="ctext-wrap-content">
                                                                    <p class="mb-0 text-start">
                                                                    Thank you. Let me check. It looks like your package was delivered yesterday at 2 pm. Did you check your mailbox or with your neighbors?
                                                                    </p>       
        
                                                                </div>
                                                                
                                                                <div class="dropdown align-self-start">
                                                                    <a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                        <i class="bx bx-dots-vertical-rounded"></i>
                                                                    </a>
                                                                    <div class="dropdown-menu">
                                                                        <a class="dropdown-item" href="#">Copy</a>
                                                                        <a class="dropdown-item" href="#">Save</a>
                                                                        <a class="dropdown-item" href="#">Forward</a>
                                                                        <a class="dropdown-item" href="#">Delete</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                           
                                                        </div>
                                                        <img src="https://cp.a2brx.com/images/avatar-chat-min.jpg" class="rounded-circle avatar-md" alt="">
                                                    </div>
                                                </div>
                                            </li>
        
                                            <li>
                                                <div class="conversation-list">
                                                    <div class="d-flex">
                                                        <img src="https://cp.a2brx.com/images/avatar-cha-usert-min.jpg" class="rounded-circle avatar-md" alt="">
                                                        <div class="flex-1 ms-3">
                                                            <div class="d-flex justify-content-between">
                                                                <h5 class="font-size-16 conversation-name align-middle"> Mila Wilson </h5>
                                                                <span class="time fw-normal text-muted me-0 me-md-4">Today 10:04 AM</span>
                                                            </div>
                                                            <div class="ctext-wrap">
                                                                <div class="ctext-wrap-content">
                                                                    <p class="mb-0">
                                                                    Oh, I didn't realize that. No, I haven't checked yet. I'll go do that now. Thanks for your help!
                                                                    </p>
                                                                </div>
                                                                <div class="dropdown align-self-start">
                                                                    <a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                        <i class="bx bx-dots-vertical-rounded"></i>
                                                                    </a>
                                                                    <div class="dropdown-menu">
                                                                        <a class="dropdown-item" href="#">Copy</a>
                                                                        <a class="dropdown-item" href="#">Save</a>
                                                                        <a class="dropdown-item" href="#">Forward</a>
                                                                        <a class="dropdown-item" href="#">Delete</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
        
                                            <li class="right">
                                                <div class="conversation-list">
                                                    <div class="d-flex">
                                                        <div class="flex-1 mr-3">
                                                            <div class="d-flex justify-content-between">
                                                                <span class="time fw-normal text-muted ms-0 ms-md-4">Today 10:08 AM</span>
                                                                <h5 class="font-size-16 conversation-name align-middle"> James Williams </h5>
                                                            </div>
                                                            <div class="ctext-wrap">
                                                                <div class="ctext-wrap-content">
                                                                    <p class="mb-0 text-start">
                                                                    You're welcome! Let us know if you need anything else.
                                                                    </p>
                                                                </div>
                                                                
                                                                <div class="dropdown align-self-start">
                                                                    <a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                        <i class="bx bx-dots-vertical-rounded"></i>
                                                                    </a>
                                                                    <div class="dropdown-menu">
                                                                        <a class="dropdown-item" href="#">Copy</a>
                                                                        <a class="dropdown-item" href="#">Save</a>
                                                                        <a class="dropdown-item" href="#">Forward</a>
                                                                        <a class="dropdown-item" href="#">Delete</a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                           
                                                        </div>
                                                        <img src="https://cp.a2brx.com/images/avatar-chat-min.jpg" class="rounded-circle avatar-md" alt="">
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
        
                                    <div class="p-3 border-top">
                                        <div class="row">
                                            <div class="col">
                                                <div class="position-relative">
                                                    <input type="text" class="form-control border chat-input" placeholder="Enter Message...">
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <button type="submit" class="btn btn-primary chat-send w-md waves-effect waves-light"><span class="d-none d-sm-inline-block me-2">Send</span> <i class="mdi mdi-send float-end"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- end user chat -->      
                            </div>                 
@endsection

@section('footerScript')
<script src="{{ URL::asset('/js/bootstrap.bundle.min.js')}}"></script>
<script src="{{ URL::asset('/libs/simplebar/simplebar.min.js')}}"></script>

@endsection
