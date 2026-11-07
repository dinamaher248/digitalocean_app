@php
    $selectedKeys = collect(old('vehicle_type_keys', $selectedKeys ?? []))
        ->map(fn ($key) => (string) $key)
        ->all();
    $selectedHubs = collect(old('vendor_hub_sections', $selectedHubs ?? []))
        ->map(fn ($key) => (string) $key)
        ->all();
    $currentUserType = old('user_type', $document->user_type ?? 'both');
    $showVehicleTypes = in_array($currentUserType, ['driver', 'both'], true);
    $showVendorHubs = in_array($currentUserType, ['vendor', 'both'], true);
    $audienceEnabled = \Illuminate\Support\Facades\Schema::hasColumn('registration_documents', 'user_type');
    $hubLabels = [
        'orders' => 'طلبات',
        'bookings' => 'حجوزات',
        'services' => 'خدمات أخرى',
    ];
@endphp
@if($audienceEnabled)
<div class="mb-3">
    <label class="form-label">مطلوب لـ <span class="text-danger">*</span></label>
    <select name="user_type" id="user_type" class="form-select" required onchange="regDocToggleTargets()">
        <option value="both" @selected($currentUserType === 'both')>الكل (سائق + صاحب نشاط)</option>
        <option value="vendor" @selected($currentUserType === 'vendor')>صاحب نشاط</option>
        <option value="driver" @selected($currentUserType === 'driver')>سائق</option>
    </select>
</div>

<div class="alert alert-info py-2 small" id="user-type-hint">
    حدّد الفئة ثم اختر وسائل النقل و/أو نوع النشاط.
</div>

<div class="mb-3" id="vehicle-types-box" @if(!$showVehicleTypes) style="display:none;" @endif>
    <div class="border rounded p-3 audience-target-box">
        <label class="form-label mb-1">وسائل النقل التي يُطلب منها المستند <span class="text-danger">*</span></label>
        <small class="d-block mb-3 audience-target-hint">
            مثال: رخصة القيادة للسيارة فقط — لا تضع علامة على التوكتوك.
        </small>
        @if(($vehicleTypes ?? collect())->isEmpty())
            <div class="alert alert-warning mb-0">
                لا توجد وسائل نقل مفعّلة. أضفها من «وسائل النقل والنطاق» أولاً.
            </div>
        @else
            <div class="row">
                @foreach($vehicleTypes as $vehicle)
                    <div class="col-md-4 col-sm-6">
                        <div class="form-check mb-2">
                            <input
                                class="form-check-input vehicle-type-checkbox"
                                type="checkbox"
                                name="vehicle_type_keys[]"
                                value="{{ $vehicle->key }}"
                                id="vehicle_type_{{ $vehicle->id }}"
                                {{ in_array($vehicle->key, $selectedKeys, true) ? 'checked' : '' }}
                                @if(!$showVehicleTypes) disabled @endif
                            >
                            <label class="form-check-label" for="vehicle_type_{{ $vehicle->id }}">
                                {{ $vehicle->displayName() }}
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    @error('vehicle_type_keys')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3" id="vendor-hubs-box" @if(!$showVendorHubs) style="display:none;" @endif>
    <div class="border rounded p-3 audience-target-box">
        <label class="form-label mb-1">نوع نشاط صاحب النشاط <span class="text-danger">*</span></label>
        <small class="d-block mb-3 audience-target-hint">
            مثال: لو الخدمات مش محتاجة مستندات، سيب «خدمات أخرى» من غير علامة.
        </small>
        @foreach($hubLabels as $hubKey => $hubLabel)
            <div class="form-check mb-2">
                <input
                    class="form-check-input vendor-hub-checkbox"
                    type="checkbox"
                    name="vendor_hub_sections[]"
                    value="{{ $hubKey }}"
                    id="vendor_hub_{{ $hubKey }}"
                    {{ in_array($hubKey, $selectedHubs, true) ? 'checked' : '' }}
                    @if(!$showVendorHubs) disabled @endif
                >
                <label class="form-check-label" for="vendor_hub_{{ $hubKey }}">{{ $hubLabel }}</label>
            </div>
        @endforeach
    </div>
    @error('vendor_hub_sections')
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<style>
.audience-target-box {
    background: #1b2434;
    border-color: #3a4660 !important;
    color: #f4f7fb;
}
.audience-target-box .form-label,
.audience-target-box .form-check-label {
    color: #f4f7fb !important;
}
.audience-target-hint {
    color: #c5d0e0;
}
</style>
<script>
function regDocToggleTargets() {
    const select = document.getElementById('user_type');
    const vehicleBox = document.getElementById('vehicle-types-box');
    const hubBox = document.getElementById('vendor-hubs-box');
    const hint = document.getElementById('user-type-hint');
    if (!select) return;

    const type = select.value;
    const showVehicles = type === 'driver' || type === 'both';
    const showHubs = type === 'vendor' || type === 'both';

    if (vehicleBox) {
        vehicleBox.style.display = showVehicles ? 'block' : 'none';
        vehicleBox.querySelectorAll('.vehicle-type-checkbox').forEach(function (input) {
            input.disabled = !showVehicles;
        });
    }
    if (hubBox) {
        hubBox.style.display = showHubs ? 'block' : 'none';
        hubBox.querySelectorAll('.vendor-hub-checkbox').forEach(function (input) {
            input.disabled = !showHubs;
        });
    }

    if (hint) {
        if (type === 'driver') {
            hint.innerHTML = '<strong>سائق:</strong> اختر وسائل النقل المطلوبة فقط. التوكتوك مثلاً ممكن يتشال من الرخصة.';
        } else if (type === 'vendor') {
            hint.innerHTML = '<strong>صاحب نشاط:</strong> اختر الطلبات و/أو الحجوزات و/أو الخدمات.';
        } else {
            hint.innerHTML = '<strong>الكل:</strong> حدّد وسائل السائقين وأنواع النشاط كلٌ على حدة.';
        }
    }
}

document.addEventListener('DOMContentLoaded', regDocToggleTargets);
</script>
@endif
