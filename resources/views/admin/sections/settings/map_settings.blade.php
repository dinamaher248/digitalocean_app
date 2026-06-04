@extends('admin.layout.admin_app')

@section('content')
<div class="card mb-4">
  <div class="card-header">Map Settings</div>
  <div class="card-body">
      <form action="/save-paymob-settings" method="POST">
      <div class="mb-3">
        <label for="apiKey" class="form-label">API Key</label>
        <input type="text" class="form-control" id="apiKey" name="api_key" required />
      </div>

      <div class="mb-3">
        <label for="nearestPoint" class="form-label">Nearest Point Url</label>
        <input type="text" class="form-control" id="nearestPoint" name="nearest_point" required />
      </div>

      <div class="mb-3">
        <label for="route" class="form-label">Route</label>
        <input type="text" class="form-control" id="route" name="route" required />
      </div>

      <div class="mb-3">
        <label for="tileLayer" class="form-label">Tile Layer</label>
        <input type="text" class="form-control" id="tileLayer" name="tile_layer" required />
      </div>

      <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
  </div>
</div>
@endsection

@section('scripts')

@endsection 

@section('styles')
 
@endsection 