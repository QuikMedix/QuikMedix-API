<div class="table-responsive mb-0" data-pattern="priority-columns">
    <table id="tech-companies-1" class="table table-striped table-bordered text-center">
        <thead>
            <tr>
                <th>#</th>
                <th style="width: 85px;" data-priority="2">Ended At</th>
                <th data-priority="1">Waypoint</th>
                <th data-priority="3">Orders</th>
                <th style="width: 75px;" data-priority="4">Count Orders</th>
                <th style="width: 120px;" data-priority="4">Cash Sum<br>Co-pay (${{number_format($copay_sum,2)}})</th>
            </tr>
        </thead>
        <tbody>
            @foreach($routes_logs_groups as $routes_logs_group)
            <tr>
                <td>{{$routes_logs_group->priority}}</td>
                <td>{{date('g:i A', strtotime($routes_logs_group->created))}}</td>
                <td>{{ucfirst($routes_logs_group->type)}} #{{$routes_logs_group->type_id}}<br>
                    @if(!empty($routes_logs_group->area_name))
                    <span title="Area" class="badge bg-primary" style="color: #fff;">{{$routes_logs_group->area_name}}</span>
                    @endif
                </td>
                <td class="text-left p-0">
                    <div class="p-2" style="height: auto;max-height: 80px;overflow: auto;display: flex;flex-direction: row;flex-wrap: wrap;gap: 5px;">
                        @foreach($routes_logs_group->orders as $order)
                        <a target="_blank" href="/orders/{{$order->pharmacy_id}}/show/{{$order->id}}"><button class="btn btn-outline-dark waves-effect p-1">#{{$order->id}} <span title="Status" style="font-size: 11px;padding: 4px 5px;border-radius: 3px;box-shadow: 0 -3px 31px 0 rgb(64 59 59 / 5%), 0 6px 20px 0 rgb(58 57 57 / 20%);" class="badge badge-pill badge-{{$order->statusecolor}}">{{$order->statusename}}</span>
                            @if($order->copay==0)
                            <span title="Co-pay" style="font-size: 11px;color: black;padding: 4px 5px;border-radius: 3px;box-shadow: 0 -3px 31px 0 rgb(64 59 59 / 5%), 0 6px 20px 0 rgb(58 57 57 / 20%);" class="badge badge-pill badge-success mt-1">$0 Not required</span>
                            @else
                            <span title="Co-pay" style="font-size: 11px;color: black;padding: 4px 5px;border-radius: 3px;box-shadow: 0 -3px 31px 0 rgb(64 59 59 / 5%), 0 6px 20px 0 rgb(58 57 57 / 20%);" class="badge badge-pill badge-{{$order->statuse_copay_color}} mt-1">${{round($order->copay,2)}} {{$order->statuse_copay_name}}</span>
                            @endif
                        </button></a> 
                        @endforeach
                    </div>
                </td>
                <td>{{count($routes_logs_group->order_id)}}</td>
                <td>${{number_format(array_sum($routes_logs_group->copay),2)}}</td>                                                                
            </tr>
            @endforeach
        </tbody>
    </table>
</div>