<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(User $user)
    {
        // SELECT * FROM profiles WHERE profiles.user_id = $user->id AND profiles.user_id IS NOT NULL;
        // SELECT * FROM users WHERE users.id IN ($user->id)
        return ProfileResource::collection($user->profile()->with('user')->get());

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ProfileRequest $request, $userId)
    {
        //SELECT * FROM users WHERE users.id = current_user.id AND users.id = usersId AND users.id IS NOT NULL;
        User::where('id', $request->user()->id)->findOrFail($userId);
        //INSERT INTO profiles (user_id,avatar,full_name,nick_name,date_of_birthday,interests,is_private) VALUES ('...');
        $created_profile = new Profile();
        $created_profile->user_id = $userId;
        $created_profile->avatar = $request->avatar ? $request->avatar->store('avatars','public') : null;
        $created_profile->full_name = $request->full_name;
        $created_profile->nick_name = $request->nick_name;
        $created_profile->date_of_birthday = $request->date_of_birthday;
        $created_profile->interests = $request->interests;
        $created_profile->is_private = $request->is_private ? true : false;
        $created_profile->save();
        Log::channel('profile')->info('New profile was created',[
            'user_id' => $created_profile->user_id,
            'profile_id' => $created_profile->id]);
        return new ProfileResource($created_profile);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user, $profileId)
    {
        //SELECT * FROM profiles WHERE profiles.user_id = $user->id
        //->AND profiles.user_id IS NOT NULL AND profiles.id = $profileId LIMIT 1;
        //SELECT * FROM users WHERE users.id IN ($user->id);
        return ProfileResource::make($user->profile()->with('user')->findOrFail($profileId));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ProfileRequest $request, $userId, $profileId)
    {
        //SELECT * FROM users WHERE id = $request->user()->id AND users.id = $userId LIMIT 1;
        $user = User::where('id', $request->user()->id)->findOrFail($userId);
        //SELECT * FROM profiles WHERE profiles.user_id = user.id AND profiles.id = '$profileId' AND profiles.user_id IS NOT NULL;
        $profile = $user->profile()->findOrFail($profileId);
        //UPDATE profiles SET full_name = '', nick_name = '',..,is_private = '' WHERE profiles.user_id = user.id AND profiles.id = '$profileId' AND profiles.id IS NOT NULL;
        $profile->update([
            'full_name' => $request->full_name,
            'nick_name' => $request->nick_name,
            'date_of_birthday' => $request->date_of_birthday,
            'interests' => $request->interests,
            'is_private' => $request->is_private ? true : false,
            ]);
        Log::channel('profile')->info('Profile was updated',[
            'user_id' => $profile->user_id,
            'profile_id' => $profile->id,
            'request_data' => $request->all(),
        ]);
        return ProfileResource::collection($profile->get());
    }

    public function updateAvatar(Request $request, $userId, $profileId)
    {

        //SELECT * FROM users WHERE id = current_user.id AND id = usersId AND id IS NOT NULL;
        $user = User::where('id', $request->user()->id)->findOrFail($userId);
        //SELECT * FROM profiles WHERE profiles.user_id = user.id AND profiles.id = '$profileId' AND profiles.id IS NOT NULL;
        $profile = $user->profile()->findOrFail($profileId);
        //SELECT avatar FROM profiles WHERE profiles.user_id = user.id AND profiles.id = '$profileId' AND profiles.id IS NOT NULL;
        $avatar = $profile->avatar;
        if ($avatar){
            Storage::disk('public')->delete($avatar);
            $path = $request->avatar->store('avatars','public');
            //UPDATE profiles SET avatar = '$path' WHERE profiles.user_id = user.id AND profiles.id = '$profileId' AND profiles.id IS NOT NULL;
            $profile->update(['avatar' => $path]);
        }
        Log::channel('profile')->info('Avatar of profile was updated',[
            'user_id' => $profile->user_id,
            'profile_id' => $profile->id,
            'data' => Storage::disk('public')->url($path),
        ]);
        return ProfileResource::make($profile);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $userId, $profileId)
    {
        //SELECT * FROM users WHERE id = $request->user()->id AND id = $userId;
        //SELECT * FROM profiles WHERE user_id IN ($userId);
        $user = User::where('id', $request->user()->id)->with('profile')->findOrFail($userId);

        //SELECT * FROM profiles WHERE profiles.id = $profileId LIMIT 1;
        $profile = $user->profile->findOrFail($profileId);

        //DELETE FROM profiles WHERE id = $profile->id;
        $profile->delete();

        Log::channel('profile')->info('Profile was deleted',[
            'user_id' => $profile->user_id,
            'profile_id' => $profile->id,
        ]);
        return [ProfileResource::make($profile),'message' => 'Successfuly delete'];
    }
}
