@extends('admin.layout.admin_app')

@section('content')
<form method="POST" action="{{ route('admin.ride.post') }}" enctype="multipart/form-data">
    @csrf

    {{-- Section: Your Cars --}}
    <div class="mb-4">
        <label for="car_id" class="form-label fw-bold">Your Cars</label>
        <select name="car_id" id="car_id" class="form-select @error('car_id') is-invalid @enderror">
            <option value="">Select your car</option>
            @foreach($cars as $car)
                <option value="{{ $car->id }}" {{ old('car_id') == $car->id ? 'selected' : '' }}>
                    {{ $car->brand }}
                </option>
            @endforeach
        </select>
        @error('car_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Section: Departure Details --}}
    <div class="mb-4">
        <label for="departure_date" class="form-label fw-bold">Departure Date</label>
        <input type="date" name="departure_date" id="departure_date" class="form-control @error('departure_date') is-invalid @enderror" value="{{ old('departure_date') }}">
        @error('departure_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="departure_time" class="form-label fw-bold">Departure Time</label>
        <input type="time" name="departure_time" id="departure_time" class="form-control @error('departure_time') is-invalid @enderror" value="{{ old('departure_time') }}">
        @error('departure_time')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Section: Route Information --}}
    <div class="mb-4">
        <label for="start_point" class="form-label fw-bold">Start Point</label>
        <input type="text" name="start_point" id="start_point" class="form-control @error('start_point') is-invalid @enderror" value="{{ old('start_point', $startPlaceName ?? '') }}" readonly onclick="selectPoint(true)">
        <input type="hidden" name="start_point_coords" id="start_point_coords">
        <input type="hidden" name="start_lat" id="start_lat">
        <input type="hidden" name="start_lng" id="start_lng">
        <input type="hidden" name="start_name" id="start_name">
        @error('start_point')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="end_point" class="form-label fw-bold">Destination Point</label>
        <input type="text" name="end_point" id="end_point" class="form-control @error('end_point') is-invalid @enderror" value="{{ old('end_point', $endPlaceName ?? '') }}" readonly onclick="selectPoint(false)">
        <input type="hidden" name="end_point_coords" id="end_point_coords">
        <input type="hidden" name="end_lat" id="end_lat">
        <input type="hidden" name="end_lng" id="end_lng">
        <input type="hidden" name="end_name" id="end_name">
        @error('end_point')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Section: Image Upload --}}
    <div class="mb-4">
        <label for="ride_image" class="form-label fw-bold">Upload Ride Image</label>
        <input type="file" name="ride_image" id="ride_image" class="form-control @error('ride_image') is-invalid @enderror" accept="image/*">
        @error('ride_image')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- Section: Ride Details --}}
    <div class="mb-4">
        <label for="seats" class="form-label fw-bold">Available Seats</label>
        <input type="number" name="seats" id="seats" class="form-control @error('seats') is-invalid @enderror" value="{{ old('seats') }}" min="1">
        @error('seats')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="price" class="form-label fw-bold">Price</label>
        <input type="number" name="price" id="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}" min="0" step="0.01">
        @error('price')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="description" class="form-label fw-bold">Car Description</label>
        <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
        @error('description')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="d-grid">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-car-front-fill me-1"></i> Apply
        </button>
    </div>
</form>

<!-- Location Picker Modal -->
<div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="locationModalLabel">Choose Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="overflow: hidden;">
                <div id="map" style="height: 400px; width: 100%;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" id="confirmLocation" class="btn btn-success">Confirm</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<!-- Ensure Mapbox CSS is loaded -->
<link href="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.css" rel="stylesheet" integrity="sha384-4bFe0oXfh8vW5Dw8bNOtXhT7p7c0F4rSNwt/tXs6bZVyA7uYv1P4l=x" crossorigin="anonymous" />
<style>
    #map {
        height: 400px; /* Fixed height */
        width: 100%; /* Full width of modal body */
        z-index: 1; /* Ensure map stays within modal */
        position: relative; /* Contain child elements */
        visibility: hidden; /* Hide until initialized */
    }
    .modal-body {
        padding: 0; /* Remove padding for snug fit */
        overflow: hidden; /* Contain map */
        height: 400px; /* Match map height */
        position: relative; /* Ensure child positioning */
    }
    .modal-dialog {
        max-width: 800px; /* Consistent modal size */
    }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="https://api.mapbox.com/mapbox-gl-js/v2.15.0/mapbox-gl.js"></script>
<script>
    let map = null;
    let marker = null;
    let currentInput = null;
    let selectedLatLng = null;

    // Your provided Mapbox access token
    const mapboxToken = '{{ env('MAPBOX_TOKEN') }}';

    function initMap() {
        console.log('Initializing map at', new Date().toLocaleTimeString('en-US', { timeZone: 'Europe/Athens' }));
        if (!mapboxToken || mapboxToken === 'your-mapbox-access-token') {
            console.error('Mapbox access token is missing or invalid. Please obtain a token from https://www.mapbox.com/');
            return;
        }

        if (!map) {
            mapboxgl.accessToken = mapboxToken;
            map = new mapboxgl.Map({
                container: 'map',
                style: 'mapbox://styles/mapbox/streets-v11',
                center: [31.2357, 30.0444], // Cairo, Egypt
                zoom: 6,
                maxZoom: 18
            });

            map.on('load', () => {
                console.log('Map loaded at', new Date().toLocaleTimeString('en-US', { timeZone: 'Europe/Athens' }));
                document.getElementById('map').style.visibility = 'visible';
            });

            map.on('error', (error) => console.error('Map error at', new Date().toLocaleTimeString('en-US', { timeZone: 'Europe/Athens' }), error));

            map.on('click', (e) => {
                console.log('Map clicked at:', e.lngLat, 'at', new Date().toLocaleTimeString('en-US', { timeZone: 'Europe/Athens' }));
                selectedLatLng = e.lngLat;
                if (marker) {
                    marker.setLngLat(e.lngLat);
                } else {
                    marker = new mapboxgl.Marker().setLngLat(e.lngLat).addTo(map);
                }
                reverseGeocode(selectedLatLng.lat, selectedLatLng.lng);
            });

            map.resize();
            console.log('Map initialized successfully at', new Date().toLocaleTimeString('en-US', { timeZone: 'Europe/Athens' }));
        }
    }

    function reverseGeocode(lat, lng) {
        const url = `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&addressdetails=1`;
        fetch(url)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data && data.display_name) {
                    const locationName = data.display_name || 'نقطة غير معروفة';
                    if (currentInput) {
                        currentInput.value = locationName;
                        const coordInput = document.getElementById(currentInput.id + '_coords');
                        const latInput = document.getElementById(currentInput.id.replace('_point', '_lat'));
                        const lngInput = document.getElementById(currentInput.id.replace('_point', '_lng'));
                        const nameInput = document.getElementById(currentInput.id.replace('_point', '_name'));
                        if (coordInput) coordInput.value = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
                        if (latInput) latInput.value = lat.toFixed(5);
                        if (lngInput) lngInput.value = lng.toFixed(5);
                        if (nameInput) nameInput.value = locationName;
                    }
                    console.log('Location name retrieved:', locationName, 'at', new Date().toLocaleTimeString('en-US', { timeZone: 'Europe/Athens' }));
                } else {
                    console.log('No location name found, using coordinates at', new Date().toLocaleTimeString('en-US', { timeZone: 'Europe/Athens' }));
                    if (currentInput) currentInput.value = 'نقطة غير معروفة';
                }
            })
            .catch(error => {
                console.error('Reverse geocoding error at', new Date().toLocaleTimeString('en-US', { timeZone: 'Europe/Athens' }), error);
                if (currentInput) currentInput.value = 'غير قادر على جلب اسم المكان';
            });
    }

    function selectPoint(isStart) {
        console.log('Opening modal for', isStart ? 'start_point' : 'end_point', 'at', new Date().toLocaleTimeString('en-US', { timeZone: 'Europe/Athens' }));
        currentInput = isStart ? document.getElementById('start_point') : document.getElementById('end_point');
        const modalElement = document.getElementById('locationModal');
        if (!modalElement) {
            console.error('Modal element not found at', new Date().toLocaleTimeString('en-US', { timeZone: 'Europe/Athens' }));
            return;
        }
        const modal = new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: true
        });
        modal.show();
        console.log('Modal shown at', new Date().toLocaleTimeString('en-US', { timeZone: 'Europe/Athens' }));

        const observer = new MutationObserver((mutations) => {
            console.log('Modal size changed at', new Date().toLocaleTimeString('en-US', { timeZone: 'Europe/Athens' }));
            if (!map) {
                initMap();
            } else {
                setTimeout(() => {
                    map.resize();
                    map.panTo([31.2357, 30.0444]); // Force reload to Cairo
                    console.log('Map resized and panned at', new Date().toLocaleTimeString('en-US', { timeZone: 'Europe/Athens' }));
                }, 600);
            }
            observer.disconnect();
        });

        observer.observe(modalElement, { attributes: true, childList: true, subtree: true });
    }

    document.getElementById('confirmLocation').addEventListener('click', () => {
        if (selectedLatLng && currentInput) {
            const latInput = document.getElementById(currentInput.id.replace('_point', '_lat'));
            const lngInput = document.getElementById(currentInput.id.replace('_point', '_lng'));
            const nameInput = document.getElementById(currentInput.id.replace('_point', '_name'));
            if (latInput) latInput.value = selectedLatLng.lat.toFixed(5);
            if (lngInput) lngInput.value = selectedLatLng.lng.toFixed(5);
            if (nameInput) nameInput.value = currentInput.value || 'نقطة غير معروفة';
            console.log('Location confirmed:', currentInput.value, 'at', new Date().toLocaleTimeString('en-US', { timeZone: 'Europe/Athens' }));
        } else {
            console.log('No location selected at', new Date().toLocaleTimeString('en-US', { timeZone: 'Europe/Athens' }));
        }
        const modal = bootstrap.Modal.getInstance(document.getElementById('locationModal'));
        if (modal) modal.hide();
    });

    document.getElementById('locationModal').addEventListener('hidden.bs.modal', () => {
        if (map) {
            map.resize();
            console.log('Map resized on modal hide at', new Date().toLocaleTimeString('en-US', { timeZone: 'Europe/Athens' }));
        }
    });
</script>
@endsection