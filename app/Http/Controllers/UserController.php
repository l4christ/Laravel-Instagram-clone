<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\Follow;
use Illuminate\Http\Request;
use App\Events\OurExampleEvent;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    //
    public function storeAvatar(Request $request) {
        $request->validate([
            'avatar' => 'required|image|max:2000'
        ]);

        // $request->file('avatar')->store('public/avatars');

        $user = auth()->user();
        $filename = $user->id . '_' . uniqid() . '.jpg';
        //the Image is the intervention image package installed for resizing
        $imgData = Image::make($request->file('avatar'))->fit(120)->encode('jpg');
        Storage::put('public/avatars/' . $filename, $imgData);

        $oldAvatar = $user->avatar;
        $user->avatar = $filename;
        $user->save();

        //delete old profile pics whenever the user uploads another
        if($oldAvatar != "/fallback-avatar.jpg") {
            Storage::delete(str_replace("/storage", "public", $oldAvatar));
        }

        return back()->with('success', 'Congrats on the Avatar');
    }
    
    public function showAvatarForm() {
        return view('avatar-form');
    }

    private function getSharedData($user) {
        $currentlyFollowing = 0;

        if(auth()->check()) {

            $currentlyFollowing = Follow::where([['user_id', '=', auth()->user()->id], ['followeduser', '=', $user->id]])->count();

        }

        // We can share a variable and it will be available in our blade template
        View::share('sharedData', ['avatar' => $user->avatar, 'currentlyFollowing' => $currentlyFollowing, 'username' => $user->username, 'postCount' => $user->posts()->count(), 'followerCount' => $user->followers()->count(), 'followingCount' => $user->followingTheseUsers()->count()]);
    }

    public function profile(User $user) {
        $this->getSharedData($user);
        
        // get the posts of the user order in desc
        $thePosts = $user->posts()->latest()->get();
        return view('profile-posts', ['posts' => $thePosts]);
    }

    public function profileRaw(User $user) {
        return response()->json(['theHTML' => view('profile-posts-only', ['posts' => $user->posts()->latest()->get()])->render(), 'docTitle' => $user->username . "'s Profile"]);
    }

    public function profileFollowers(User $user) {
        $this->getSharedData($user);

        $followers = $user->followers()->latest()->get();
        return view('profile-followers', ['followers' => $followers]);
    }

    public function profileFollowersRaw(User $user) {
        return response()->json(['theHTML' => view('profile-followers-only', ['followers' => $user->followers()->latest()->get()])->render(), 'docTitle' => $user->username . "'s Followers"]);

    }

    public function profileFollowing(User $user) {
        $this->getSharedData($user);

        $followingTheseUsers = $user->followingTheseUsers()->latest()->get();
        return view('profile-following', ['following' => $followingTheseUsers]);
    }

    public function profileFollowingRaw(User $user) {
        return response()->json(['theHTML' => view('profile-following-only', ['following' => $user->followingTheseUsers()->latest()->get()])->render(), 'docTitle' => 'Who '. $user->username . " Follows"]);

    }

    public function logout() {
        event(new OurExampleEvent(['username' => auth()->user()->username, 'action' => 'logout']));
        auth()->logout();
        return redirect('/')->with('success', 'You are now logged out');
    }

    public function showCorrectHomepage() {
        //check if a user is logged in or not
        if (auth()->check()) {
            return view('homepage-feed', ['posts' => auth()->user()->feedPosts()->latest()->paginate(4)]);

        } else {
            $postCount = Cache::remember('postCount', 20, function () {
                //sleep(5); // this was just introduced to confirm that the cache is working
                return Post::count();
            });
            return view('homepage', ['postCount' => $postCount]);
        }
    }

    public function loginApi(Request $request) {
        $incomingFields = $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        if (auth()->attempt( $incomingFields)) {
            $user = User::where('username', $incomingFields['username'])->first();
            $token = $user->createToken('ourapptoken')->plainTextToken;
            return $token;
;        }
        return 'SOrry';
    }

    public function login(Request $request) {
        $incomingFields = $request->validate([
            'loginusername' => 'required',
            'loginpassword' => 'required'
        ]);

        if (auth()->attempt(['username' => $incomingFields['loginusername'], 'password' => $incomingFields['loginpassword']])) {
            //generate a cookie
            $request->session()->regenerate();
            event(new OurExampleEvent(['username' => auth()->user()->username, 'action' => 'login']));
            return redirect('/')->with('success', 'You have Successfully logged in');
        } else{
            return redirect('/')->with('failure', 'Invalid login.');
        }
    }

    public function register(Request $request) {
        $incomingFields = $request->validate([
            'username' => ['required', 'min:3', 'max:20', Rule::unique('users', 'username')],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'min:8', 'confirmed']
        ]);

        $incomingFields['password'] = bcrypt($incomingFields['password']);

        //register the user
        $user = User::create($incomingFields);

        //log the user in
        auth()->login($user);

        return redirect('/')->with('success', 'Thank you for creating an account');
    }
}
