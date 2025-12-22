<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Medical Profile</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Patient Medical Profile</h1>
        <div class="row">
            <div class="col-xs-4">
                <img src="{{ 'https://xpertnurse.xpertbotacademy.online/storage/'.$patient->photo }}" alt="Patient Photo" class="img-fluid rounded-circle" style="width: 100px;">
            </div>
            <div class="col-xs-8">
                <ul class="list-group">
                    <li class="list-group-item"><strong>Name:</strong> {{ $patient->first_name }} {{ $patient->last_name }}</li>
                    <li class="list-group-item"><strong>Date of Birth:</strong> {{ $patient->dob->format('d/m/Y') }}</li>
                    <li class="list-group-item"><strong>Phone Number:</strong> {{ $patient->phone }}</li>
                </ul>
            </div>            
        </div>

        <div class="row">
            <div class="col-xs-12">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">Medical Information</h2>
                        <div class="row">
                            <div class="col-xs-12">
                                <h3>Medications</h3>
                                <ul class="list-group">
                                    @foreach($patient->drugs as $drug)
                                    <li class="list-group-item">
                                        <div class="row">
                                            <div class="col-xs-4">
                                                <img src="{{'https://xpertnurse.xpertbotacademy.online/storage/'.$drug->path}}" class="img" style="width:50px" alt="">
                                            </div>
                                            <div class="col-xs-8">
                                                {{$drug->name}} - Dosage: {{$drug->dose}}, Frequency: {{$drug->pivot->duration}}
                                            </div>
                                        </div>
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <h3>Vital Signs</h3>
                                <ul class="list-group">

                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Heart Rate</th>
                                            <th>Oxygen</th>
                                            <th>Temperature</th>
                                            <th>Glucose</th>
                                            <th>Blood Pressure</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($patient->vitals()->orderByDesc('created_at')->take(30)->get() as $vital)
                                        <tr>
                                            <td>{{ $vital->created_at }}</td>
                                            <td>{{ $vital->heartbeat }}</td>
                                            <td>{{ $vital->oxygen }}</td>
                                            <td>{{ $vital->temperature }}</td>
                                            <td>{{ $vital->glucose }}</td>
                                            <td>{{ $vital->pressure }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>


                                </ul>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <h3>Tests</h3>


                                @if(!empty($params))
                                    <table class="table table-bordered table-striped">
                                        <tr>
                                            <th>Group</th>
                                            <th>Parameter</th>
                                        </tr>
                                        @foreach($params as $key=>$group)
                                            <tr>
                                                <td>
                                                    {{$key}}
                                                </td>
                                                <td>
                                                    <table>
                                                        <tr>
                                                            <th>
                                                                Paramter
                                                            </th>
                                                            <td>
                                                                Result
                                                            </td>
                                                        </tr>
                                                        @foreach($group as $param=>$values)
                                                        <tr>
                                                            <td>
                                                                {{$param}}
                                                            </td>
                                                            <td>
                                                                @foreach($values as $value)
                                                                    <div>
                                                                        {{$value->created_at->format('d/m/Y')}} : {{$value->value}}
                                                                    </div>
                                                                @endforeach
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </table>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                @else
                                    <p>No results available.</p>
                                @endif






                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-12">
                                <h3>Medical Photos</h3>
                                @foreach($patient->photos()->orderByDesc('created_at')->take(6)->get() as $photo)
                                <img src="{{ 'https://xpertnurse.xpertbotacademy.online/storage/'.$photo->path }}" alt="Medical Photo 1" class="img-fluid mb-2">
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
