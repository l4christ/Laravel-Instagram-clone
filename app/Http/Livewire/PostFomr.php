<?php

namespace App\Http\Livewire;

use App\Models\Post;
use Livewire\Component;
use App\Jobs\SendNewPostEmail;

class PostFomr extends Component
{

    public $title;
    public $body;

    protected $rules = [
        'title' => 'required',
        'body' => ['required', 'min:10']
    ];

    public function updated($propertyName)

    {

        $this->validateOnly($propertyName);

    }

    public function submitForm() {
        $incomingFields = $this->validate();
        
        $incomingFields['title'] = $this->title;
        $incomingFields['body'] = $this->body;

        $incomingFields['title'] = strip_tags($incomingFields['title']);
        $incomingFields['body'] = strip_tags($incomingFields['body']);
        $incomingFields['user_id'] = auth()->id();
        
        $newPost = Post::create($incomingFields);

        dispatch(new SendNewPostEmail(['sendTo' => auth()->user()->email, 'name' => auth()->user()->username, 'title' => $newPost->title]));
        
        // $this->resetForm();

        // session()->flash('message', 'Post successfully updated.');

        return redirect("/post/{$newPost->id}")->with('success', 'New post successfully created');
    }
    // private function resetForm(){
    //     $this->title='';
    //     $this->body='';
    //}
    public function render()
    {
        return view('livewire.post-fomr');
    }
}
