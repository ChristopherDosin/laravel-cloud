@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <project-list :projects="projects"></project-list>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <passport-personal-access-tokens></passport-personal-access-tokens>
        </div>
    </div>
</div>
@endsection
