@extends('admin.layout.admin_app')

@section('content')
  <div class="container">
    <h2 class="my-4">Push Notification</h2>

    <!-- Form to send push notification -->
    <form method="POST" action="{{ route('admin.send_notification_to_topic_post') }}" class="row g-3">
      @csrf

      <!-- Notification Title -->
      <div class="col-12">
        <label for="notificationTitle" class="form-label">Notification Title</label>
        <input type="text" name="head" id="notificationTitle" class="form-control" placeholder="Enter notification title" required>
      </div>

      <!-- Notification Body -->
      <div class="col-12">
        <label for="notificationBody" class="form-label">Notification Body</label>
        <textarea name="desc" id="notificationBody" class="form-control" placeholder="Enter notification body" rows="4" required></textarea>
      </div>

      <!-- Select Topic -->
      {{-- <div class="col-12">
        <label for="topicSelect" class="form-label">Select Topic</label>
        <select name="type" id="topicSelect" class="form-select" required>
          <option value="newcase">New Case</option>
          <option value="non">Non</option>
          <option value="news">News</option>
          <option value="new_branch">New Branch</option>
        </select>
      </div> --}}

      <!-- Send Button -->
      <div class="col-12 text-center">
        <button type="submit" class="btn btn-primary">Send Notification</button>
      </div>
    </form>
  </div>
@endsection

@section('scripts')
<script>
  // Optional: You can add custom JS to handle UI interactivity or validation if needed.
</script>
@endsection

@section('styles')
<style>
  .container {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
  }

  .form-label {
    font-weight: bold;
  }

  .btn-primary {
    width: 100%;
  }
</style>
@endsection
