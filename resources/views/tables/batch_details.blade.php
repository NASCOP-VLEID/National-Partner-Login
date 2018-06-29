@extends('layouts.master')

    @component('/tables/css')
    @endcomponent

@section('content')

<div class="content">
    <div class="row">
        <div class="col-lg-12">
            <div class="hpanel">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Batch:</strong> {{ $data->batch->id  ?? '' }}</p>
                        </div>
                        <div class="col-md-8">
                            <p><strong>Facility:</strong> {{-- ($data->batch->view_facility->facilitycode . ' - ' . $data->batch->view_facility->name . ' (' . $data->batch->view_facility->county . ')') ?? '' --}}</p>
                        </div>
                        <div class="col-md-4">
                            <p>
                                <strong>Entry Type: </strong>
                                @switch($data->batch->site_entry)
                                    @case(0)
                                        {{ 'Lab Entry' }}
                                        @break
                                    @case(1)
                                        {{ 'Site Entry' }}
                                        @break
                                    @case(2)
                                        {{ 'POC Entry' }}
                                        @break
                                    @default
                                        @break
                                @endswitch
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Date Entered:</strong> {{ $data->batch->created_at ?? '' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Entered By:</strong> 
                                @if($data->batch->creator)
                                    @if($data->batch->creator->full_name != ' ')
                                        {{ $data->batch->creator->full_name }}
                                    @else
                                        {{ $data->batch->creator->facility->name ?? '' }}
                                    @endif
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Date Received:</strong> {{ date('d-m-Y', $data->batch->datereceived)  ?? '' }}</p>
                        </div>
                        <div class="col-md-8">
                            <p><strong>Received By:</strong> {{ $data->batch->receiver->full_name ?? '' }}</p>
                        </div>                       
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover" >
                            <thead>
                                <tr>
                                    <th colspan="17"><center> Sample Log</center></th>
                                </tr>
                                <tr>
                                    <th colspan="6">Patient Information</th>
                                    <th colspan="3">Sample Information</th>
                                    <th colspan="8">Mother Information</th>
                                </tr>
                                <tr> 
                                    <th>No</th>
                                    <th>Patient ID</th>
                                    <th>Sex</th>
                                    <th>DOB</th>
                                    <th>Age (Months)</th>
                                    <th>Infant Prophylaxis</th>

                                    <th>Date Collected</th>
                                    <th>Status</th>
                                    <th>Spots</th>

                                    <th>CCC #</th>
                                    <th>Age</th>
                                    <th>Last Vl</th>
                                    <th>PMTCT Intervention</th>
                                    <th>Feeding Type</th>
                                    <th>Entry Point</th>
                                    <th>Result</th>
                                    <th>Task</th>
                                </tr>
                            </thead>
                            <tbody> 
                                @foreach($data->samples as $key => $sample)
                                    <tr>
                                        <td> {{ $key+1 }} </td>
                                        <td> {{ $sample->patient }} </td>
                                        <td> {{ $sample->sex }} </td>
                                        <td> {{ $sample->dob }} </td>
                                        <td> {{ $sample->age ?? '' }} </td>
                                        <td>
                                            @php/*
                                            @foreach($iprophylaxis as $iproph)
                                                @if($sample->regimen == $iproph->id)
                                                    {{ $iproph->name }}
                                                @endif
                                            @endforeach*/
                                            @endphp
                                            N/A
                                        </td>

                                        <td> {{ $sample->datecollected }} </td>
                                        <td>
                                            @php/*
                                            @foreach($received_statuses as $received_status)
                                                @if($sample->receivedstatus == $received_status->id)
                                                    {{ $received_status->name }}
                                                @endif
                                            @endforeach*/
                                            @endphp
                                            N/A
                                        </td>
                                        <td> {{ $sample->spots }} </td>

                                        <td> {{ $sample->patient->mother->ccc_no ?? '' }} </td>
                                        <td> {{ $sample->mother_age ?? '' }} </td>
                                        <td> {{ $sample->mother_last_result ?? '' }} </td>
                                        <td>
                                            @php/*
                                            @foreach($interventions as $intervention)
                                                @if($sample->mother_prophylaxis == $intervention->id)
                                                    {{ $intervention->name }}
                                                @endif
                                            @endforeach*/
                                            @endphp
                                            N/A
                                        </td>
                                        <td>
                                            @php/*
                                            @foreach($feedings as $feeding)
                                                @if($sample->feeding == $feeding->id)
                                                    {{ $feeding->feeding }}
                                                @endif
                                            @endforeach*/
                                            @endphp
                                            N/A
                                        </td>
                                        <td>
                                            @php/*
                                            @foreach($entry_points as $entry_point)
                                                @if($sample->patient->entry_point == $entry_point->id)
                                                    {{ $entry_point->name }}
                                                @endif
                                            @endforeach*/
                                            @endphp
                                            N/A
                                        </td>
                                        <td>
                                            @php/*
                                            @foreach($results as $result)
                                                @if($sample->result == $result->id)
                                                    {{ $result->name }}
                                                @endif
                                            @endforeach*/
                                            @endphp
                                            N/A
                                        </td>
                                        <td>
                                            N/A
                                        </td>
                                    </tr>
                                @endforeach


                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection

@section('scripts') 

    @component('/tables/scripts')

    @endcomponent

@endsection