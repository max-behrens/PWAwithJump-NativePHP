<!DOCTYPE html>
<html>
<head>
    <title>Show Task</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-lg-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Show Task</h2>
                    <a class="btn btn-primary" href="{{ route('tasks.index') }}"> Back</a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group mb-3">
                    <strong>Title:</strong>
                    {{ $task->title }}
                </div>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group mb-3">
                    <strong>Description:</strong>
                    {{ $task->description }}
                </div>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group mb-3">
                    <strong>Status:</strong>
                    @if($task->completed)
                        <span class="badge bg-success">Completed</span>
                    @else
                        <span class="badge bg-warning text-dark">Pending</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>