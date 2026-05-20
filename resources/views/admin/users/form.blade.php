<div class="mb-3">
    <label>Name</label>
    <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" class="form-control" required>
</div>

<div class="mb-3">
    <label>Email</label>
    <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="form-control" required>
</div>

@if(!isset($user))
<div class="mb-3">
    <label>Password</label>
    <input type="password" name="password" class="form-control" required>
</div>

<div class="mb-3">
    <label>Confirm Password</label>
    <input type="password" name="password_confirmation" class="form-control" required>
</div>
@endif

<div class="mb-3">
    <label>Phone</label>
    <input type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}" class="form-control">
</div>

<div class="mb-3">
    <label>Address</label>
    <input type="text" name="address" value="{{ old('address', $user->address ?? '') }}" class="form-control">
</div>

<div class="mb-3">
    <label>City</label>
    <input type="text" name="city" value="{{ old('city', $user->city ?? '') }}" class="form-control">
</div>

<div class="mb-3">
    <label>State</label>
    <input type="text" name="state" value="{{ old('state', $user->state ?? '') }}" class="form-control">
</div>

<div class="mb-3">
    <label>Postal Code</label>
    <input type="text" name="postal_code" value="{{ old('postal_code', $user->postal_code ?? '') }}" class="form-control">
</div>

<div class="mb-3">
    <label>Country</label>
    <input type="text" name="country" value="{{ old('country', $user->country ?? '') }}" class="form-control">
</div>
