<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @if($title)<title>{{ $title }}</title>@endif
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #fff;
            color: #000;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            padding: 40px;
            width: 100%;
            max-width: 340px;
        }

        h1 {
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 28px;
            letter-spacing: -0.02em;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        input[type="password"] {
            width: 100%;
            padding: 10px 0;
            font-size: 15px;
            font-family: inherit;
            border: none;
            border-bottom: 1.5px solid #000;
            background: transparent;
            color: #000;
            outline: none;
            border-radius: 0;
            transition: border-color 0.15s;
        }

        input[type="password"]::placeholder {
            color: #999;
        }

        input[type="password"]:focus {
            border-bottom-color: #555;
        }

        input[type="password"].has-error {
            border-bottom-color: #000;
        }

        .error {
            font-size: 13px;
            color: #666;
        }

        button {
            width: 100%;
            margin-top: 20px;
            padding: 10px 14px;
            font-size: 15px;
            font-weight: 500;
            font-family: inherit;
            color: #fff;
            background: #000;
            border: none;
            border-radius: 0;
            cursor: pointer;
            transition: background 0.15s;
        }

        button:hover {
            background: #333;
        }

        button:focus-visible {
            outline: 2px solid #000;
            outline-offset: 2px;
        }

        @media (prefers-color-scheme: dark) {
            body {
                background: #000;
                color: #fff;
            }

            input[type="password"] {
                border-bottom-color: #fff;
                color: #fff;
            }

            input[type="password"]::placeholder {
                color: #666;
            }

            input[type="password"]:focus {
                border-bottom-color: #aaa;
            }

            input[type="password"].has-error {
                border-bottom-color: #fff;
            }

            .error {
                color: #999;
            }

            button {
                background: #fff;
                color: #000;
            }

            button:hover {
                background: #ddd;
            }

            button:focus-visible {
                outline-color: #fff;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        @if($title)<h1>{{ $title }}</h1>@endif

        <form method="POST" action="{{ route('statamic.password-protect.verify') }}">
            @csrf

            <div class="field">
                <input
                    type="password"
                    name="password"
                    placeholder="Password"
                    autofocus
                    required
                    @class(['has-error' => $errors->has('password')])
                >

                @error('password')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit">Enter</button>
        </form>
    </div>
</body>
</html>
