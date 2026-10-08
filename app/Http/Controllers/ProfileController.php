<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\NigeriaState;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'states' => NigeriaState::query()
                ->ordered()
                ->get(),
        ]);
    }

    public function showApplicant(Request $request, User $user): View
    {
        abort_unless($user->isApplicant(), 404);

        if ($request->user()->isEmployer()) {
            abort_unless($user->isActive(), 403);
        }

        return view('profile.show', [
            'user' => $user,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->except('profile_image', 'email');

        if ($request->hasFile('profile_image')) {
            $uploadedImagePath = $request->file('profile_image')->store('profile-images', 'public');

            if ($user->profile_image_path) {
                Storage::disk('public')->delete($user->profile_image_path);
            }

            $data['profile_image_path'] = $uploadedImagePath;
        }

        $user->fill($data);
        $user->save();

        return Redirect::route('profile.edit')->with('success', 'Profile updated successfully.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/')->with('success', 'Your account has been deleted.');
    }
}
