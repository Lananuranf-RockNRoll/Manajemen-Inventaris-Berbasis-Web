<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * GET /api/employees
     */
    public function index(Request $request): JsonResponse
    {
        $employees = Employee::with('warehouse')
            ->when($request->search,       fn ($q) => $q->where('name', 'LIKE', "%{$request->search}%")
                ->orWhere('email', 'LIKE', "%{$request->search}%"))
            ->when($request->warehouse_id, fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->job_title,    fn ($q) => $q->where('job_title', 'LIKE', "%{$request->job_title}%"))
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return EmployeeResource::collection($employees)->response();
    }

    /**
     * POST /api/employees
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'email'        => 'required|email|max:100|unique:employees,email',
            'phone'        => 'nullable|string|max:20',
            'job_title'    => 'nullable|string|max:100',
            'department'   => 'nullable|string|max:50',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'hire_date'    => 'nullable|date',
            'is_active'    => 'boolean',
        ]);

        $employee = Employee::create($validated);

        return (new EmployeeResource($employee->load('warehouse')))->response()->setStatusCode(201);
    }

    /**
     * GET /api/employees/{id}
     */
    public function show(Employee $employee): JsonResponse
    {
        return (new EmployeeResource($employee->load('warehouse')))->response();
    }

    /**
     * PUT /api/employees/{id}
     */
    public function update(Request $request, Employee $employee): JsonResponse
    {
        $validated = $request->validate([
            'name'         => 'sometimes|string|max:100',
            'email'        => 'sometimes|email|max:100|unique:employees,email,' . $employee->id,
            'phone'        => 'nullable|string|max:20',
            'job_title'    => 'nullable|string|max:100',
            'department'   => 'nullable|string|max:50',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'hire_date'    => 'nullable|date',
            'is_active'    => 'boolean',
        ]);

        $employee->update($validated);

        return (new EmployeeResource($employee->load('warehouse')))->response();
    }

    /**
     * DELETE /api/employees/{id}
     */
    public function destroy(Employee $employee): JsonResponse
    {
        $employee->delete();

        return response()->json(['message' => 'Karyawan berhasil dihapus.']);
    }
}
