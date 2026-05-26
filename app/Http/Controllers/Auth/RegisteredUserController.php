<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\ProfilePhotoService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterRequest $request, ProfilePhotoService $profilePhotos): RedirectResponse
    {
        $validated = $request->validated();

        $generatedUsername = User::generateUniqueUsername(
            $validated['first_name'],
            $validated['last_name']
        );
        
        $ecoUsername = !empty($validated['eco_username']) ? Str::slug($validated['eco_username']) : $generatedUsername;
        
        // Ensure eco_username is unique if provided manually
        if (!empty($validated['eco_username']) && User::where('eco_username', $ecoUsername)->exists()) {
             $ecoUsername = $generatedUsername;
        }

        $photoPath = $profilePhotos->storeFromUploadedFile(
            $request->file('profile_photo'),
            'profiles/'.$ecoUsername,
        );

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'username' => $ecoUsername, // Setting username as eco_username for login compatibility
            'eco_username' => $ecoUsername,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'country' => $validated['country'],
            'profile_photo' => $photoPath,
            'description' => $validated['description'] ?? null,
            'linkedin' => $validated['linkedin'] ?? null,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
