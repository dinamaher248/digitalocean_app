@extends('admin.layout.admin_app')

@section('content')
<div class="card mb-4">
  <div class="card-header">Edit Privacy Policy</div>
  <div class="card-body">
       <form action="/save-privacy-policy" method="POST">
        @csrf

      <div class="mb-3">
        {{-- <label for="privacyPolicy" class="form-label">Privacy Policy Content</label> --}}
        <textarea class="form-control" id="privacyPolicy" name="privacy_policy" rows="20" required>
Privacy Policy – Wheely Carpooling App

Effective Date: [Insert Date]

At Wheely, your privacy is important to us. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our carpooling mobile application (“Wheely” or the “App”).

1. Information We Collect

a. Personal Information:
- Full Name
- Email Address
- Phone Number
- Profile Picture
- Payment Information (processed via secure third-party services like Paymob)

b. Ride Information:
- Ride preferences (pickup/drop-off)
- Location data (real-time GPS during rides)
- Ratings and reviews

c. Technical Information:
- Device type and operating system
- App usage logs
- Crash reports

2. How We Use Your Information

We use your information to:
- Match drivers and riders
- Facilitate in-app communication
- Process payments securely
- Improve app performance and user experience
- Send important notifications (ride confirmations, updates)

3. Sharing of Information

Your data may be shared with:
- Other users (basic profile details only)
- Third-party payment gateways (e.g., Paymob)
- Service providers for analytics and performance improvement
- Legal authorities if required by law

4. Location Data

We collect and use your location data to:
- Show nearby rides
- Track ride progress
- Enhance safety for both drivers and passengers

You can manage location permissions through your device settings.

5. Data Security

We implement industry-standard security measures to protect your data. However, no method of transmission over the internet or storage is 100% secure.

6. Your Rights

You can:
- Access or update your personal information
- Request account deletion
- Withdraw consent to data processing

To make a request, please contact us at support@wheelyapp.com.

7. Children’s Privacy

Wheely is not intended for children under 18 years of age. We do not knowingly collect personal information from minors.

8. Updates to This Policy

We may update this Privacy Policy from time to time. We will notify users of significant changes through the app or by email.

9. Contact Us

If you have any questions or concerns about this Privacy Policy, please contact:

Wheely Support Team  
Email: support@wheelyapp.com

By using Wheely, you agree to the terms outlined in this Privacy Policy.

</textarea>
      </div>

      <button type="submit" class="btn btn-success">Save Privacy Policy</button>
    </form>
  </div>
</div>
@endsection

@section('scripts')

@endsection 

@section('styles')
 
@endsection 