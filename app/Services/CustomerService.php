<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * Master data pelanggan, dipakai bersama oleh Penjualan & Retur (komplain
 * pembeli). Pencarian (search()) dipakai frontend untuk autocomplete; kalau
 * tidak ketemu, frontend biasanya memanggil create() untuk menambah pelanggan
 * baru saat itu juga, lalu memakai id-nya. resolveOrCreate() ada sebagai
 * jalan pintas: SaleService/EggReturnService bisa terima `customer_id` YANG
 * SUDAH ADA, atau `new_customer` (object nama+telepon) untuk dibuatkan
 * sekalian dalam satu request — dua-duanya didukung, frontend pilih mana yang
 * lebih cocok dengan alur formnya.
 */
class CustomerService
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    public function search(?string $query, int $perPage = 20): LengthAwarePaginator
    {
        $builder = Customer::query();

        if (! empty($query)) {
            $builder->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            });
        }

        return $builder->orderBy('name')->paginate($perPage);
    }

    public function find(int $id): Customer
    {
        return Customer::findOrFail($id);
    }

    public function create(array $data, User $actor): Customer
    {
        $customer = Customer::create([
            'uuid' => (string) Str::uuid(),
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $actor->id,
        ]);

        $this->auditLog->record($actor, 'create', $customer, null, $customer->only(['name', 'phone']));

        return $customer;
    }

    public function update(int $id, array $data, User $actor): Customer
    {
        $customer = $this->find($id);
        $before = $customer->only(['name', 'phone', 'address']);

        $customer->fill(array_intersect_key($data, array_flip(['name', 'phone', 'address', 'notes'])))
            ->forceFill(['updated_by' => $actor->id])
            ->save();

        $this->auditLog->record($actor, 'update', $customer, $before, $customer->only(['name', 'phone', 'address']));

        return $customer;
    }

    /**
     * Dipakai internal oleh SaleService/EggReturnService: kalau $customerId
     * diisi, pakai pelanggan itu; kalau $newCustomerData diisi (minimal
     * `name`), buat pelanggan baru sekalian; kalau dua-duanya kosong,
     * kembalikan null (transaksi tetap boleh tanpa pelanggan tercatat/walk-in).
     */
    public function resolveOrCreate(?int $customerId, ?array $newCustomerData, User $actor): ?Customer
    {
        if ($customerId) {
            return $this->find($customerId);
        }

        if (! empty($newCustomerData['name'])) {
            return $this->create($newCustomerData, $actor);
        }

        return null;
    }
}
