@extends('layouts.app')

@section('page-title')
    <h4><i class="bi bi-speedometer2"></i> Dashboard</h4>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title text-primary">Welcome, {{ auth()->user()->name }}!</h5>
                <p class="card-text">You are successfully logged into the YLDA Cooperative Management System.</p>
                <hr>
                <p class="text-muted small">You will be automatically redirected to your role-based dashboard. If you are not redirected, please contact the administrator.</p>
            </div>
        </div>
    </div>
</div>
@endsection
