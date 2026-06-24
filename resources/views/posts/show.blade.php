<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $post->post_title }}</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Inter, Arial, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.8;
        }

        .container {
            max-width: 900px;
            margin: auto;
            padding: 40px 20px 80px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #2563eb;
            font-weight: 600;
            margin-bottom: 40px;
        }

        .back-link:hover {
            opacity: .8;
        }

        .hero-image {
            width: 100%;
            height: 450px;
            object-fit: cover;
            border-radius: 20px;
            margin-bottom: 35px;
        }

        .post-header {
            margin-bottom: 30px;
        }

        .post-title {
            font-size: 48px;
            line-height: 1.2;
            margin-bottom: 20px;
            color: #0f172a;
        }

        .post-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
            color: #64748b;
            font-size: 14px;
        }

        .author {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .author-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #2563eb;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .content-wrapper {
            background: #fff;
            border-radius: 20px;
            padding: 40px;
            margin-top: 40px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .06);
        }

        .content-wrapper img {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            margin: 25px 0;
        }

        .content-wrapper h2 {
            margin: 35px 0 15px;
            font-size: 32px;
            color: #0f172a;
        }

        .content-wrapper h3 {
            margin: 25px 0 12px;
            font-size: 24px;
            color: #0f172a;
        }

        .content-wrapper p {
            margin-bottom: 18px;
            color: #334155;
        }

        .content-wrapper ul,
        .content-wrapper ol {
            margin: 20px 0 20px 25px;
        }

        .content-wrapper li {
            margin-bottom: 10px;
        }

        .content-wrapper a {
            color: #2563eb;
            text-decoration: none;
        }

        .content-wrapper a:hover {
            text-decoration: underline;
        }

        .content-wrapper blockquote {
            border-left: 4px solid #2563eb;
            padding-left: 20px;
            margin: 25px 0;
            color: #475569;
            font-style: italic;
        }

        @media(max-width:768px) {

            .post-title {
                font-size: 34px;
            }

            .hero-image {
                height: 280px;
            }

            .content-wrapper {
                padding: 25px;
            }
        }
    </style>
</head>

<body>

    <div class="container">

        <a href="/" class="back-link">
            ← Back to Posts
        </a>

        @if($post->first_image)
            <img src="{{ $post->first_image }}" alt="{{ $post->post_title }}" class="hero-image">
        @endif

        <div class="post-header">

            <h1 class="post-title">
                {{ $post->post_title }}
            </h1>

            <div class="post-meta">

                <div class="author">

                    <div class="author-avatar">
                        {{ strtoupper(substr(optional($post->author)->display_name ?? 'A', 0, 1)) }}
                    </div>

                    <span>
                        {{ optional($post->author)->display_name }}
                    </span>

                </div>

                <span>
                    📅 {{ \Carbon\Carbon::parse($post->post_date)->format('F d, Y') }}
                </span>

                <span>
                    📖 {{ $post->reading_time }} min read
                </span>

            </div>

        </div>

        <div class="content-wrapper">
            {!! $post->post_content !!}
        </div>

    </div>

</body>

</html>