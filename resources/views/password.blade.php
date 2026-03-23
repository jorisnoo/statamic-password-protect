<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
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
            background: #f3f4f6;
            color: #1f2937;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 4px 24px rgba(0, 0, 0, 0.04);
            padding: 40px;
            width: 100%;
            max-width: 380px;
        }

        h1 {
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 24px;
            letter-spacing: -0.01em;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        input[type="password"] {
            width: 100%;
            padding: 10px 14px;
            font-size: 15px;
            font-family: inherit;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            color: #1f2937;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        input[type="password"]:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        input[type="password"].has-error {
            border-color: #ef4444;
        }

        input[type="password"].has-error:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
        }

        .error {
            font-size: 13px;
            color: #ef4444;
        }

        button {
            width: 100%;
            margin-top: 16px;
            padding: 10px 14px;
            font-size: 15px;
            font-weight: 500;
            font-family: inherit;
            color: #fff;
            background: #4f46e5;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s;
        }

        button:hover {
            background: #4338ca;
        }

        button:focus-visible {
            outline: 2px solid #4f46e5;
            outline-offset: 2px;
        }

        @media (prefers-color-scheme: dark) {
            body {
                background: #111827;
                color: #e5e7eb;
            }

            .card {
                background: #1f2937;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3), 0 4px 24px rgba(0, 0, 0, 0.2);
            }

            input[type="password"] {
                background: #111827;
                border-color: #374151;
                color: #e5e7eb;
            }

            input[type="password"]:focus {
                border-color: #818cf8;
                box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.2);
            }

            input[type="password"].has-error {
                border-color: #f87171;
            }

            button {
                background: #6366f1;
            }

            button:hover {
                background: #818cf8;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ config('app.name') }}</h1>

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
