@extends('admin.layout.admin_app')

@section('content')
<div class="container">
  <h2>Rides</h2>
  <table id="casesTable" class="display table table-bordered">
      <thead>
          <tr>
              <th>ID</th>
              <th>driver</th>
              <th>start place</th>
              <th>Destination place</th>
              <th>Created At</th>
              <th>Actions</th>
          </tr>
      </thead>
      <tbody>
          @foreach ($rides as $ride)
              <tr>
                  <td>{{ $ride->id }}</td>
                  <td>{{ $ride->user->name }}</td>
                  <td>{{ $ride->start_name }}</td>
                  <td>{{ $ride->dest_name }}</td>
                  {{-- <td>{{ Str::limit($ride->notes, 50) }}</td> --}}
                  <td>{{ $ride->created_at->format('Y-m-d') }}</td>
                  <td><a href="#" class="btn btn-info btn-sm">View</a></td>
              </tr>
          @endforeach
      </tbody>
  </table>
</div>
@endsection

@section('scripts')
<script>
  $(document).ready(function () {
        $('#casesTable').DataTable();
    });
</script>
@endsection

@section('styles')
<style>

</style> 
@endsection 