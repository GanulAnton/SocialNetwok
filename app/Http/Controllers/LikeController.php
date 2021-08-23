<?php

namespace App\Http\Controllers;


use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LikeController extends Controller
{
    /**
     * @param \Illuminate\Http\Request $request
     * */

    public function like(Request $request, Post $post)
    {
        //SELECT * FROM users WHERE users.id = 'request.user.id';
        $user = $request->user();
        //SELECT EXISTS(SELECT posts.*, (SELECT COUNT(*) FROM users INNER JOIN likes ON users.id = likes.user_id WHERE posts.id = likes.post_id)
        //->AS likes_count FROM posts INNER JOIN likes ON posts.id = likes.post_id WHERE likes.user_id = $request->user()->id AND post_id = '$post->id') AS exists;
        if ($user->likes()->where('post_id',$post->id)->exists()){
            //DELETE FROM likes WHERE post_id = 'post.id' AND user_id IN ('user.id');
            $post->likes()->detach($user->id);
            Log::channel('post')->info('User was like post',['user_id' => $user->id, 'post_id' => $post->id]);
            return ['message' => 'dislike'];
        }
        //INSERT INTO likes ('user_id', 'post_id') VALUES ('user.id','post.id');
        $post->likes()->attach($user->id);
        Log::channel('post')->info('User was dislike post',['user_id' => $user->id, 'post_id' => $post->id]);
        return ['message' => 'like'];
    }

    public function whoLiked(Post $post)
    {
        //SELECT users.*, likes.post_id AS pivot_post_id, likes.user_id AS pivot_user_id FROM users
        //->INNER JOIN likes ON users.id = likes.user_id WHERE likes.post_id = $post->id LIMIT 1 OFFSET 0;
         $post->likes()->paginate();
    }
}
