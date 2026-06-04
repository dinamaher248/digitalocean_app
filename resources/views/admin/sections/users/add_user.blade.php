@extends('admin.layout.admin_app')

@section('content')
<div class="card mb-4">
  <div class="card-header">Add New User</div>
  <div class="card-body">
      <form action="{{ route('admin.user.store') }}" method="POST">
          @csrf

          <div class="mb-3">
              <label for="name" class="form-label">Name</label>
              <input type="text" name="name" class="form-control" required>
          </div>

          <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required>
          </div>

          <div class="mb-3">
              <label for="phone" class="form-label">Phone</label>
              <input type="text" name="phone" class="form-control" required>
          </div>

          <div class="mb-3">
              <label for="username" class="form-label">Username</label>
              <input type="text" name="username" class="form-control" required>
          </div>

          <div class="mb-3">
              <label for="password" class="form-label">Password</label>
              <input type="text" name="password" class="form-control" required>
          </div>

          <button type="submit" class="btn btn-primary">Add User</button>
      </form>
  </div>
</div>
@endsection

@section('scripts')

@endsection 

@section('styles')
 
@endsection 