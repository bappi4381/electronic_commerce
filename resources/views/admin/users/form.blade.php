@php($isEdit = isset($user))
<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <x-admin.input name="name" label="Name" required :value="$user->name ?? null" autocomplete="off" />
    <x-admin.input name="email" type="email" label="Email" required :value="$user->email ?? null" autocomplete="off" />
    <x-admin.password name="password" :label="$isEdit ? 'New password' : 'Password'" :required="!$isEdit" autocomplete="new-password" minlength="8"
                      :hint="$isEdit ? 'Leave blank to keep the current password.' : 'At least 8 characters.'" />
    <x-admin.password name="password_confirmation" label="Confirm password" :required="!$isEdit" autocomplete="new-password" />
    <x-admin.input name="phone" label="Phone" optional :value="$user->phone ?? null" />
    <div class="hidden md:block" aria-hidden="true"></div>
    <div class="md:col-span-2">
        <x-admin.input name="address" label="Address" optional :value="$user->address ?? null" />
    </div>
    <x-admin.input name="city" label="City" optional :value="$user->city ?? null" />
    <x-admin.input name="state" label="State / province" optional :value="$user->state ?? null" />
    <x-admin.input name="postal_code" label="Postal code" optional :value="$user->postal_code ?? null" />
    <x-admin.input name="country" label="Country" optional :value="$user->country ?? null" />
</div>
