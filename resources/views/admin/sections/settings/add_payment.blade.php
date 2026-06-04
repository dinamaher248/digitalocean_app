@extends('admin.layout.admin_app')

@section('content')
<div class="card mb-4">
  <div class="card-header">Paymob Payment Settingsr</div>
  <div class="card-body">
      <form action="/save-paymob-settings" method="POST">
      <div class="mb-3">
        <label for="apiKey" class="form-label">API Key</label>
        <input type="text" class="form-control" id="apiKey" name="api_key" value="ZXlKaGJHY2lPaUpJVXpVeE1pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SmpiR0Z6Y3lJNklrMWxjbU5vWVc1MElpd2ljSEp2Wm1sc1pWOXdheUk2T1RneE1UVTVMQ0p1WVcxbElqb2lhVzVwZEdsaGJDSjkuX04xODBoMlpiNldtTk9CMHJPM1kwdVdrUlVJWVhyeDZuU3B6clFhNXpIaGxEVkJHaGRiMXZQazF5dl9VbUd3X0pQOVhUblR5c3dqZ0hZX2lWQmo0emc=" required />
      </div>

      <div class="mb-3">
        <label for="iframeId" class="form-label">Iframe ID</label>
        <input type="text" class="form-control" id="iframeId" name="iframe_id" value="851711" required />
      </div>

      <div class="mb-3">
        <label for="cardIntegrationId" class="form-label">Card Integration ID</label>
        <input type="text" class="form-control" id="cardIntegrationId" name="card_integration_id" value="4595833" required />
      </div>

      <div class="mb-3">
        <label for="walletIntegrationId" class="form-label">Wallet Integration ID</label>
        <input type="text" class="form-control" id="walletIntegrationId" name="wallet_integration_id" value="4597454" required />
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