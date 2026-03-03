<!DOCTYPE html>
<html>
<head>
    <title>Laravel Task App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Tasks</h1>
            <a href="{{ route('tasks.create') }}" class="btn btn-primary">Add Task</a>
        </div>

        @if ($message = Session::get('success'))
            <div class="alert alert-success">
                <p>{{ $message }}</p>
            </div>
        @endif

        <table class="table table-bordered">
            <tr>
                <th>No</th>
                <th>Title</th>
                <th>Status</th>
                <th width="280px">Action</th>
            </tr>
            @foreach ($tasks as $task)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $task->title }}</td>
                <td>
                    @if($task->completed)
                        <span class="badge bg-success">Completed</span>
                    @else
                        <span class="badge bg-warning text-dark">Pending</span>
                    @endif
                </td>
                <td>
                    <form action="{{ route('tasks.destroy',$task->id) }}" method="POST">
                        <a class="btn btn-info" href="{{ route('tasks.show',$task->id) }}">Show</a>
                        <a class="btn btn-primary" href="{{ route('tasks.edit',$task->id) }}">Edit</a>
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </table>
    </div>
</body>
</html>