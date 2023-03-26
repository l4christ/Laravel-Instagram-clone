<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Jobs\SendNewPostEmail;
use Illuminate\Support\Facades\Mail;

class PostController extends Controller
{
    //
    public function search($term) {
        $posts = Post::search($term)->get();
        $posts->load('user:id,username,avatar');
        return $posts;
    }

    public function actuallyUpdate(Post $post, Request $request) {
        $incomingFields = $request->validate([
            'title' => 'required',
            'body' => 'required'
        ]);

        $incomingFields['title'] = strip_tags($incomingFields['title']);
        $incomingFields['body'] = strip_tags($incomingFields['body']);

        $post->update($incomingFields);

        // back() is to redirect user to the previous page they are coming from
        return back()->with('success', 'Post Succesfully Updated');
    }

    public function showEditForm(Post $post) {
        return view('edit-post', ['post' => $post]);
    }

    public function delete(Post $post) {
        // if(auth()->user()->cannot('delete', $post)) {
        //     return 'You cannot do that';
        // }
        
        $post->delete();

        return redirect('/profile/' . auth()->user()->username)->with('success', 'Post Successfully deleted');
    }

    public function deleteApi(Post $post) {
        // if(auth()->user()->cannot('delete', $post)) {
        //     return 'You cannot do that';
        // }
        
        $post->delete();

        return 'true';
    }

    public function viewSinglePost(Post $post) {
        // the Post $post above is called type hinting. it means the $ post must be an instance og the Post class
        // this is powerful in performind model query

        $post['body'] = strip_tags(Str::markdown($post->body), '<p><ul><li><strong><em><h3><br>');
        return view('single-post', ['post' => $post]);
    }

    public function storeNewPost(Request $request) {
        
    }

    public function storeNewPostApi(Request $request) {
        $incomingFields = $request->validate([
            'title' => 'required',
            'body' => 'required'
        ]);
        
        $incomingFields['title'] = strip_tags($incomingFields['title']);
        $incomingFields['body'] = strip_tags($incomingFields['body']);
        $incomingFields['user_id'] = auth()->id();
        
        $newPost = Post::create($incomingFields);

        dispatch(new SendNewPostEmail(['sendTo' => auth()->user()->email, 'name' => auth()->user()->username, 'title' => $newPost->title]));
        

        return $newPost->id;
    }

    public function showCreateForm() {
        return view('create-post');
    }
}
