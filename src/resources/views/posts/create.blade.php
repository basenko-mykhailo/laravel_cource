<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Posts</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<h1>Create Post</h1>

<!--      //       'title',
        //        'body',
        //        "author_id",
        //        "category_id",
        //        "status" -->


<form action="{{ route('posts.store') }}" method="POST">
    <div class="mb-3">
        <label for="exampleFormControlInput1" class="form-label">Title</label>
        <input type="text" class="form-control" id="exampleFormControlInput1" name="title">
    </div>
    <div class="mb-3">
        <label for="exampleFormControlTextarea1" class="form-label">Body</label>
        <textarea class="form-control" id="exampleFormControlTextarea1" rows="3" name="body"></textarea>
    </div>
    <div class="mb-3">
        <label for="textid" class="form-label">Author ID</label>
        <input type="text" class="form-control" id="textid" name="author_id">
    </div>
    <div class="mb-3">
        <select name="category_id">
            <option value="">— без Category —</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    {{ @csrf_field() }}
    <button type="submit">Create</button>
</form>




<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

