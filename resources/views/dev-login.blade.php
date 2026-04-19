<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dev Login — Sinbad</title>
    <link rel="stylesheet" href="{{ asset('css/accounting.css') }}">
</head>
<body class="ac-layout">
<main class="ac-dev-login">

    <div class="ac-card">
        <div class="ac-card__body">
            <h1 class="ac-page-header__title">Dev Login</h1>
            <p class="ac-dev-login__note">Local environment only.</p>

            @if($errors->any())
                <div class="ac-alert ac-alert--error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="/dev/login">
                @csrf
                <div class="ac-form-group">
                    <label class="ac-label" for="email">Email</label>
                    <input id="email" name="email" type="email" class="ac-control"
                           value="admin@sinbad.test" autofocus>
                </div>
                <div class="ac-form-group">
                    <label class="ac-label" for="password">Password</label>
                    <input id="password" name="password" type="password" class="ac-control"
                           value="password">
                </div>
                <button type="submit" class="ac-btn ac-btn--primary ac-btn--full">Login</button>
            </form>
        </div>
    </div>

</main>
</body>
</html>
