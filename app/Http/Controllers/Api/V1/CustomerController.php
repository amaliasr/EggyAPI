<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Services\CustomerService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(private readonly CustomerService $service) {}

    /** ?search=budi — dipakai frontend untuk autocomplete di form Penjualan/Retur. */
    public function index(Request $request)
    {
        return CustomerResource::collection($this->service->search($request->string('search')->toString() ?: null));
    }

    public function store(StoreCustomerRequest $request)
    {
        $customer = $this->service->create($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Pelanggan baru berhasil ditambahkan.',
            'data' => new CustomerResource($customer),
        ], 201);
    }

    public function show(int $customer)
    {
        return response()->json(['success' => true, 'data' => new CustomerResource($this->service->find($customer))]);
    }

    public function update(UpdateCustomerRequest $request, int $customer)
    {
        $updated = $this->service->update($customer, $request->validated(), $request->user());

        return response()->json(['success' => true, 'data' => new CustomerResource($updated)]);
    }
}
