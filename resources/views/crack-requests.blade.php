@extends('master')

@section('content')
    <div class="container">
        <div class="page-header">
            <h1>Crack requests</h1>
        </div>

        <div class="row">
            <div class="col-md-12">
                @if (empty($crackRequests))
                    There are no crack requests.
                @else
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
                                            <button type="submit" class="btn btn-danger"
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
                @endif
            </div>
        </div>
        <div class="row">
            <form>
                <fieldset class="form-group">
                    <label for="exampleInputEmail1">Email address</label>
                    <input type="email" class="form-control" id="exampleInputEmail1" placeholder="Enter email">
                    <small class="text-muted">We'll never share your email with anyone else.</small>
                </fieldset>
                <fieldset class="form-group">
                    <label for="exampleInputPassword1">Password</label>
                    <input type="password" class="form-control" id="exampleInputPassword1" placeholder="Password">
                </fieldset>
                <fieldset class="form-group">
                    <label for="exampleSelect1">Example select</label>
                    <select class="form-control" id="exampleSelect1">
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                    </select>
                </fieldset>
                <fieldset class="form-group">
                    <label for="exampleSelect2">Example multiple select</label>
                    <select multiple class="form-control" id="exampleSelect2">
                        <option>1</option>
                        <option>2</option>
                        <option>3</option>
                        <option>4</option>
                        <option>5</option>
                    </select>
                </fieldset>
                <fieldset class="form-group">
                    <label for="exampleTextarea">Example textarea</label>
                    <textarea class="form-control" id="exampleTextarea" rows="3"></textarea>
                </fieldset>
                <fieldset class="form-group">
                    <label for="exampleInputFile">File input</label>
                    <input type="file" class="form-control-file" id="exampleInputFile">
                    <small class="text-muted">This is some placeholder block-level help text for the above input. It's a
                        bit lighter and easily wraps to a new line.
                    </small>
                </fieldset>
                <div class="radio">
                    <label>
                        <input type="radio" name="optionsRadios" id="optionsRadios1" value="option1" checked>
                        Option one is this and that&mdash;be sure to include why it's great
                    </label>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="optionsRadios" id="optionsRadios2" value="option2">
                        Option two can be something else and selecting it will deselect option one
                    </label>
                </div>
                <div class="radio disabled">
                    <label>
                        <input type="radio" name="optionsRadios" id="optionsRadios3" value="option3" disabled>
                        Option three is disabled
                    </label>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox"> Check me out
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>
    </div>
@endsection
