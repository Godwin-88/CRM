<?php

namespace App\Http\Controllers;

use App\Models\DepartmentHeadcount;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Employee::class);

        $employees = Employee::query()
            ->with(['user', 'reportingManager'])
            ->when($request->filled('search'), function ($query, $search) {
                $query->where('employee_number', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            })
            ->orderBy('user.name')
            ->paginate(25)
            ->appends($request->query());

        $departments = DepartmentHeadcount::all();

        $headcountSummary = Employee::query()
            ->selectRaw('department, count(*) as current_count')
            ->groupBy('department')
            ->pluck('current_count', 'department');

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'departments' => $departments,
            'headcountSummary' => $headcountSummary,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(Employee $employee): Response
    {
        $this->authorize('view', $employee);

        $employee->load(['user', 'reportingManager']);

        return Inertia::render('Employees/Show', [
            'employee' => $employee,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Employee::class);

        $users = User::whereDoesntHave('employee')->select(['id', 'name', 'email'])->orderBy('name')->get();
        $managers = User::select(['id', 'name'])->orderBy('name')->get();

        return Inertia::render('Employees/Create', [
            'users' => $users,
            'managers' => $managers,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Employee::class);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', 'unique:employees,user_id'],
            'department' => ['required', 'string'],
            'job_title' => ['required', 'string'],
            'employment_type' => ['required', 'in:full_time,part_time,contract,intern'],
            'start_date' => ['required', 'date'],
            'reporting_manager_id' => ['nullable', 'exists:users,id'],
        ]);

        $employee = Employee::create(array_merge($validated, [
            'employee_number' => Employee::generateEmployeeNumber(),
        ]));

        \activity()
            ->performedOn($employee)
            ->withProperties(['employee_number' => $employee->employee_number])
            ->log('employee_created');

        return redirect()->route('employees.show', $employee)->with('success', 'Employee created.');
    }

    public function edit(Employee $employee): Response
    {
        $this->authorize('update', $employee);

        $managers = User::select(['id', 'name'])->orderBy('name')->get();

        return Inertia::render('Employees/Edit', [
            'employee' => $employee->load(['user']),
            'managers' => $managers,
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $this->authorize('update', $employee);

        $validated = $request->validate([
            'department' => ['sometimes', 'string'],
            'job_title' => ['sometimes', 'string'],
            'employment_type' => ['sometimes', 'in:full_time,part_time,contract,intern'],
            'reporting_manager_id' => ['nullable', 'exists:users,id'],
        ]);

        $employee->update($validated);

        \activity()
            ->performedOn($employee)
            ->log('employee_updated');

        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated.');
    }
}
