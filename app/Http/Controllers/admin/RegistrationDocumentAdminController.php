<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\RegistrationDocument;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrationDocumentAdminController extends Controller
{
    public function index()
    {
        $documents = RegistrationDocument::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.sections.documents.index', compact('documents'));
    }

    public function create()
    {
        $document = new RegistrationDocument(['user_type' => 'both']);
        $vehicleTypes = $this->activeVehicleTypes();
        $selectedHubs = [];

        return view('admin.sections.documents.create', compact('document', 'vehicleTypes', 'selectedHubs'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string|max:255',
            'key' => 'nullable|string|max:100|unique:registration_documents,key',
            'field_type' => 'required|in:image,text',
            'user_type' => 'required|in:driver,vendor,both',
            'vehicle_type_keys' => 'nullable|array',
            'vehicle_type_keys.*' => 'string|max:100',
            'vendor_hub_sections' => 'nullable|array',
            'vendor_hub_sections.*' => 'in:orders,bookings,services',
            'sort_order' => 'nullable|integer|min:0',
            'is_required' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $data['key'] = $data['key'] ?? Str::slug($data['label'], '_');
        $data['is_required'] = $request->boolean('is_required', true);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $this->applyAudienceTargets($request, $data);

        RegistrationDocument::create($data);

        return redirect()->route('admin.documents.index')->with('success', 'تم إضافة المستند');
    }

    public function edit(RegistrationDocument $document)
    {
        $vehicleTypes = $this->activeVehicleTypes();
        $selectedKeys = $document->vehicleTypeKeys();
        $selectedHubs = $document->vendorHubSections();

        return view('admin.sections.documents.edit', compact('document', 'vehicleTypes', 'selectedKeys', 'selectedHubs'));
    }

    public function update(Request $request, RegistrationDocument $document)
    {
        $data = $request->validate([
            'label' => 'required|string|max:255',
            'key' => 'required|string|max:100|unique:registration_documents,key,' . $document->id,
            'field_type' => 'required|in:image,text',
            'user_type' => 'required|in:driver,vendor,both',
            'vehicle_type_keys' => 'nullable|array',
            'vehicle_type_keys.*' => 'string|max:100',
            'vendor_hub_sections' => 'nullable|array',
            'vendor_hub_sections.*' => 'in:orders,bookings,services',
            'sort_order' => 'nullable|integer|min:0',
            'is_required' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_required'] = $request->boolean('is_required');
        $data['is_active'] = $request->boolean('is_active');

        $this->applyAudienceTargets($request, $data);

        $document->update($data);

        return redirect()->route('admin.documents.index')->with('success', 'تم تحديث المستند');
    }

    public function destroy(RegistrationDocument $document)
    {
        $document->delete();

        return back()->with('success', 'تم حذف المستند');
    }

    public function toggle(RegistrationDocument $document)
    {
        $document->update(['is_active' => !$document->is_active]);

        return back()->with('success', 'تم تحديث حالة المستند');
    }

    private function activeVehicleTypes()
    {
        if (!Schema::hasTable('vehicle_types')) {
            return collect();
        }

        $query = VehicleType::query();
        if (Schema::hasColumn('vehicle_types', 'is_active')) {
            $query->where('is_active', true);
        }
        if (Schema::hasColumn('vehicle_types', 'sort_order')) {
            $query->orderBy('sort_order');
        }

        return $query->orderBy('id')->get();
    }

    private function validatedVehicleTypeKeys(Request $request): array
    {
        $keys = collect($request->input('vehicle_type_keys', []))
            ->map(fn ($key) => trim((string) $key))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($keys === []) {
            throw ValidationException::withMessages([
                'vehicle_type_keys' => 'اختر وسيلة نقل واحدة على الأقل.',
            ]);
        }

        if (Schema::hasTable('vehicle_types')) {
            $validKeys = VehicleType::query()->pluck('key')->all();
            foreach ($keys as $key) {
                if (!in_array($key, $validKeys, true)) {
                    throw ValidationException::withMessages([
                        'vehicle_type_keys' => 'وسيلة النقل المختارة غير موجودة.',
                    ]);
                }
            }
        }

        return $keys;
    }

    private function applyAudienceTargets(Request $request, array &$data): void
    {
        $type = $data['user_type'] ?? 'both';

        if (in_array($type, ['driver', 'both'], true)
            && Schema::hasColumn('registration_documents', 'vehicle_type_keys')) {
            $data['vehicle_type_keys'] = $this->validatedVehicleTypeKeys($request);
        } elseif (Schema::hasColumn('registration_documents', 'vehicle_type_keys')) {
            $data['vehicle_type_keys'] = null;
        }

        if (in_array($type, ['vendor', 'both'], true)
            && Schema::hasColumn('registration_documents', 'vendor_hub_sections')) {
            $hubs = collect($request->input('vendor_hub_sections', []))
                ->map(fn ($key) => strtolower(trim((string) $key)))
                ->intersect(['orders', 'bookings', 'services'])
                ->unique()
                ->values()
                ->all();
            if ($hubs === []) {
                throw ValidationException::withMessages([
                    'vendor_hub_sections' => 'حدد نوع النشاط الذي يُطلب منه هذا المستند.',
                ]);
            }
            $data['vendor_hub_sections'] = $hubs;
        } elseif (Schema::hasColumn('registration_documents', 'vendor_hub_sections')) {
            $data['vendor_hub_sections'] = null;
        }
    }
}
