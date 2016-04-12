@extends('master')

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>Crack requests</h1>
        </div>

        <div class="row">
            <div class="col-md-12">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th class="text-right">ID</th>
                        <th>BSSID</th>
                        <th>Password</th>
                        <th>Finished</th>
                        <th class="text-right">Pending parts</th>
                        <th>Created at</th>
                        <th>Latest work assigned at</th>
                        <th>Actions</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($crackRequests as $crackRequest)
                        <tr>
                            <td class="align-middle text-right">{{ $crackRequest->id }}</td>
                            <td class="align-middle">{{ $crackRequest->bssid }}</td>
                            <td class="align-middle">
                                <span class="password">{{ $crackRequest->password }}</span>
                            </td>
                            <td class="align-middle">{{ $crackRequest->finished ? 'Yes' : 'No'}}</td>
                            <td class="align-middle text-right">{{ $crackRequest->pending_parts }}</td>
                            <td class="align-middle">{{ $crackRequest->created_at }}</td>
                            <td class="align-middle">{{ $crackRequest->latest_work_assigned_at }}</td>
                            <td class="align-middle">
                                <form action="{{ url('/' . $crackRequest->id) }}" method="POST">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="btn btn-danger">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
