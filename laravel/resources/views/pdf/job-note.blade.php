<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Job Note {{ $job->formatted_no }}</title>
    <style>
        @page { margin: 28px 34px; }
        body { margin: 0; }
    </style>
</head>
<body>
    @include('jobs._document')
</body>
</html>
