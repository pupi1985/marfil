@extends('master')

@section('content')
    <div class="container">
        <div class="page-header">
            <h1><a href="{{ url('/') }}">Crack requests</a></h1>
        </div>

        <div class="row">
            <div class="col-xs-12">
                @if (empty($crackRequests))
                    <div class="alert alert-info">
                        There are no crack requests.
                    </div>
                @else
                    <table class="table table-striped table-condensed">
                        <thead>
                        <tr>
                            <th class="text-right">ID</th>
                            <th>BSSID</th>
                            <th>Password</th>
                            <th>Finished</th>
                            <th class="text-right">Pending parts</th>
                            <th>Created at</th>
                            <th>Latest work unit assigned at</th>
                            <th>Actions</th>
                        </tr>
                        </thead>

                        <tbody>
                        @foreach($crackRequests as $crackRequest)
                            <tr{{ $crackRequest->rowClass ? ' class=' . $crackRequest->rowClass : '' }}>
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
                                    <form action="{{ url('/crack-request/' . $crackRequest->id) }}" method="POST"
                                          class="form-inline">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                    onclick="return show_confirm();">
                                                Delete
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
        <div class="row">
            <div class="col-xs-9">
                <form class="form-inline">
                    <div class="checkbox">
                        <label class="checkbox-inline">
                            <input type="checkbox" id="autorefreshCheckbox" onclick="return autorefreshChange();">
                            Auto-refresh page
                            <div class="form-group">
                                <input type="text" class="form-control" id="secondsInput"
                                       oninput="return autorefreshChange();" size="5" value="60">
                            </div>
                            Next refresh in: <span id="secondsDisplay">60</span> seconds.
                        </label>
                        <p class="text-muted">
                            <small>Input amount of seconds to auto-refresh. At least 60.</small>
                        </p>
                    </div>
                </form>
            </div>
            <div class="col-xs-3">
                <form action="{{ url('/crack-request/all') }}" method="POST" class="form-inline text-right">
                    <input type="hidden" name="_method" value="DELETE">
                    <div class="form-group">
                        <button type="submit" class="btn btn-md btn-warning" onclick="return show_confirm();">
                            Remove all crack requests
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12">
                <div class="row">
                    <div class="col-xs-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title">Add crack request</h3>
                            </div>
                            <div class="panel-body">
                                <form action="{{ url('/') }}" method="POST" enctype="multipart/form-data">
                                    <fieldset class="form-group{{ $errors->has('bssid') ? ' has-error' : '' }}">
                                        <label for="bssid">BSSID</label>
                                        <input type="text" class="form-control" id="bssid" name="bssid"
                                               {{ isset($bssid) && !empty($bssid) ? 'value=' . $bssid : '' }}
                                               placeholder="01:23:45:67:89:AB">
                                        <small class="text-muted">
                                            The BSSID to crack. The .cap file must contain a handshake for this BSSID.
                                        </small>
                                        @if ($errors->has('bssid'))
                                            <span class="help-block">
                                               <strong>{{ $errors->first('bssid') }}</strong>
                                            </span>
                                        @endif
                                    </fieldset>
                                    <fieldset class="form-group{{ $errors->has('file') ? ' has-error' : '' }}">
                                        <label for="file">.cap file</label>
                                        <input type="file" class="form-control-file" id="file" name="file">
                                        <small class="text-muted">
                                            The .cap file that contains the handshake. If the file is too large try
                                            compacting
                                            it with the `wpaclean` utility.
                                        </small>
                                        @if ($errors->has('file'))
                                            <span class="help-block">
                                               <strong>{{ $errors->first('file') }}</strong>
                                            </span>
                                        @endif
                                    </fieldset>
                                    <button type="submit" class="btn btn-primary">Submit</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                @if (isset($operationResult) && isset($operationMessage) && !is_null($operationResult))
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="alert {{ $operationResult == App\Models\MessageResults::SUCCESS ? 'alert-success' : 'alert-danger' }}">
                                {{ $operationMessage }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
