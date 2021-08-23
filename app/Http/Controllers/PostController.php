<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Http\Resources\PostResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $userId)
    {
        //SELECT * FROM users WHERE users.id = 'request.user.id';
        $current_user = $request->user();
        //SELECT * FROM users WHERE exists (SELECT * FROM profiles WHERE users.id = profiles.user_id) AND users.id = $current_user->id LIMIT 1;
        //SELECT * FROM profiles WHERE profiles.user_id IN ($current_user->id);
        //SELECT posts.*, (SELECT COUNT(*) FROM users INNER JOIN likes ON users.id = likes.user_id
        //->WHERE posts.id = likes.post_id`) AS likes_count FROM posts WHERE posts.user_id IN ($current_user->id);
        //SELECT users.*, followers.user_id AS pivot_user_id, followers.follower_id AS pivot_follower_id, followers.is_approve AS pivot_is_approve
        //->FROM users INNER JOIN followers ON users.id = followers.follower_id WHERE followers.user_id IN ($current_user->id);
        $user = User::has('profile')->with(['profile', 'posts.likes', 'followers'])->findOrFail($userId);
        //User::wih
        $profile_status = $user->profile->is_private;                                  //SELECT users.*, followers.user_id AS pivot_user_id, followers.follower_id as pivot_follower_id, followers.is_approve AS pivot_is_approve FROM users
                                                                                       //->INNER JOIN followers ON users.id = followers.follower_id WHERE followers.user_id = $request->user->id AND followers.is_approve = 'true' AND users.id = $userId limit 1
        if ($user->id == $current_user->id or !$profile_status or ($profile_status and $user->followers->where('pivot.is_approve', true)->find($current_user->id))) {
            return PostResource::collection($user->posts()->paginate());

        }
        Log::channel('post')->info('User tried to check private posts',[
            'user_id' => $current_user->id,
            'profile_id' => $user->profile->id,
        ]);
        return response(['Dont have permissions to private profile'],403);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PostRequest $request, $userId)
    {
        //SELECT * FROM users WHERE id = 'request.user.id' AND id = '$userId';
        $user = User::where('id', $request->user()->id)->findOrFail($userId);

        Log::channel('post')->info('New post created', ['data' => $request->text,'user_id' => $user->id]);
        //INSERT INTO posts(text,user_id) VALUES ('request.text','user.id');
        $post = $user->posts()->create([
            'text' => $request->text,
        ]);
        $files = $request->attaches;
        if ($files){
            foreach ($files as $file){
                $path = $file->store('attaches','public');
                //INSERT INTO attaches(post_id,link) VALUES ('post.id','path');
                $post->attaches()->createMany();
            }
            Log::channel('post')->info('Detected attaches to new post');
        }
        return PostResource::make($post);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $userId, $postId)
    {
        //SELECT * FROM users WHERE users.id = 'request.user.id';
        \Log::info('ollololo');
        $user = $request->user();

        //if ($user->id != $userId) {
            //SELECT * FROM users JOIN followers ON users.id = followers.user_id where followers.follower_id = '$user.id' AND EXISTS (SELECT * FROM profiles WHERE users.id = profiles.user_id) AND followers.is_approve = 'true';
            //SELECT users.*, followers.follower_id as pivot_follower_id, followers.user_id AS pivot_user_id, followers.is_approve AS pivot_is_approve
            //->FROM users INNER JOIN followers ON users.id = followers.user_id
            //->WHERE followers.follower_id = $user->id AND EXISTS(SELECT * FROM profiles WHERE users.id = profiles.user_id) AND followers.is_approve = 'true' AND users.id = $userId LIMIT 1
            $user = $user->followings()->has('profile')->wherePivot('is_approve', true)->findOrFail($userId);

       // }
        return PostResource::make($user->posts()->findOrFail($postId));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(PostRequest $request, $userId, $postId)
    {
        //SELECT * FROM users WHERE users.id = 'request.user.id' AND users.id = 'usersId';
        $user = User::where('id', $request->user()->id)->findOrFail($userId);

        //SELECT posts.*, (SELECT COUNT(*) FROM users INNER JOIN likes ON users.id = likes.user_id WHERE posts.id = likes.post_id) AS likes_count
        //->FROM posts WHERE posts.user_id = $user->id AND posts.user_id IS NOT NULL AND posts.id = $postId LIMIT 1;
        $post = $user->posts()->findOrFail($postId);
        //UPDATE posts SET 'text' = $request->text WHERE id = $postId;
        $post->update(array('text' => $request->text));
        Log::channel('post')->info('User was update post',['user_id' => $user->id, 'post_id' => $postId, 'request_data' => $request->all()]);
        $files = $request->attaches;
        if ($files){
            Log::channel('post')->info('User was tried to add some attaches',['user_id' => $request->user()->id, 'post_id' => $postId]);
            foreach ($files as $file){
                $path = $file->store('avatars','public');
                //INSERT INTO attaches ('link','post_id') VALUES ('$path','$post->id');
                $post->attaches()->create([
                    'link' => $path
                ]);
            }
        }
        return PostResource::make($post);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(PostRequest $request, $userId, $postId)
    {
        //SELECT * FROM users WHERE users.id = 'request.user.id' AND users.id = 'usersId' AND users.id NOT NULL LIMIT 1;
        $user = User::where('id', $request->user()->id)->findOrFail($userId);
        //SELECT * FROM posts WHERE posts.user_id IN (SELECT id FROM users WHERE users.id = 'userId') AND posts.id = '$postId';
        //SELECT posts.*, (SELECT COUNT(*) FROM users INNER JOIN likes ON users.id = likes.user_id WHERE posts.id = likes.post_id) AS likes_count
        //->FROM posts WHERE posts.user_id = $user->id AND posts.user_id IS NOT NULL AND posts.id = $postId LIMIT 1;
        $post = $user->posts()->with('attaches')->findOrFail($postId);
        //DELETE FROM posts WHERE id = $postId;
        $post->delete();

        Log::channel('post')->info('Post was deleted',['user_id' => $request->user()->id, 'post_id' => $postId]);
        return [PostResource::make($post),'message' => 'Successfuly delete'];
    }
}
