<div class="container py-md-5 container--narrow">
    <form wire:submit.prevent="submitForm" action="/create-post" method="POST">
        @csrf
      <div class="form-group">
        <label for="post-title" class="text-muted mb-1"><small>Title</small></label>
        <input wire:model="title" name="title" value="{{ old('title') }}" id="post-title" class="form-control form-control-lg form-control-title" type="text" placeholder="" autocomplete="off" />
        @error('title')
            <p class="m-0 small alert alert-danger shadow-sm">{{ $message }}</p>
        @enderror
    </div>

      <div class="form-group">
        <label for="post-body" class="text-muted mb-1"><small>Body Content</small></label>
        <textarea wire:model="body" name="body" id="post-body" class="body-content tall-textarea form-control" type="text">{{ old('body') }}</textarea>
        @error('body')
            <p class="m-0 small alert alert-danger shadow-sm">{{ $message }}</p>
        @enderror
    </div>

      <button class="btn btn-primary" type="submit">Save New Post</button>
    </form>
  </div>