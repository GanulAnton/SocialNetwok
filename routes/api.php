<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('register',[AuthController::class, 'register']);
Route::post('login',[AuthController::class, 'login']);

Route::middleware(['auth:api','log.route'])->group(function () {
    Route::get('logout',[AuthController::class, 'logout']);

    Route::put('users/{user}/profiles/{profile}/avatar',[ProfileController::class,'updateAvatar']);
    Route::apiResource('users.profiles',ProfileController::class);

    Route::apiResource('users.posts', PostController::class);

    Route::get('users/{user}/followers', [FollowController::class, 'getFollowsInfo']); // need to choose "relation = followers|followings"
    Route::post('users/{user}/followers',[FollowController::class,'follow']);
    Route::put('users/{user}/followers', [FollowController::class, 'approveFollow']);
    Route::delete('users/{user}/followers', [FollowController::class, 'denyFollow']);

    Route::post('posts/{post}/like',[LikeController::class, 'like']);
    Route::get('posts/{post}/like',[LikeController::class, 'whoLiked']);
    Route::get('posts/{post}/like/count',[LikeController::class, 'whoLiked']);
});

