<!DOCTYPE html>
<html>

<head>
    <title>Blog POC</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Inter, Arial, sans-serif;
            background: #f8fafc;
            padding: 60px 20px;
            color: #1e293b;
        }

        h1 {
            text-align: center;
            margin-bottom: 50px;
            font-size: 42px;
            font-weight: 700;
        }

        .blog-wrapper {
            max-width: 1280px;
            margin: auto;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 30px;
        }

        .blog-card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .08);
            transition: all .35s ease;
            display: flex;
            flex-direction: column;
        }

        .blog-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 18px 40px rgba(15, 23, 42, .12);
        }

        .blog-image {
            overflow: hidden;
            position: relative;
        }

        .blog-image img {
            width: 100%;
            height: 240px;
            object-fit: cover;
            transition: transform .6s ease;
        }

        .blog-card:hover .blog-image img {
            transform: scale(1.08);
        }

        .blog-content {
            padding: 24px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .category-wrap {
            margin-bottom: 14px;
        }

        .category {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 30px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .blog-content h2 {
            margin-bottom: 15px;
            line-height: 1.4;
            font-size: 22px;
            min-height: 62px;

            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .blog-content h2 a {
            color: #0f172a;
            text-decoration: none;
        }

        .blog-content h2 a:hover {
            color: #2563eb;
        }

        .post-info {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 18px;
            font-size: 14px;
            color: #64748b;
        }

        .post-info span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .post-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 18px;
            border-top: 1px solid #e2e8f0;
            margin-top: auto;
        }

        .author {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .author-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #2563eb;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .author-info {
            display: flex;
            flex-direction: column;
        }

        .author-name {
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
        }

        .post-date {
            font-size: 12px;
            color: #64748b;
        }

        .read-more {
            text-decoration: none;
            color: #2563eb;
            font-weight: 600;
            transition: .3s;
        }

        .read-more:hover {
            transform: translateX(4px);
        }
    </style>
</head>

<body>



    <h1>Latest Posts</h1>

    <div class="blog-wrapper">

        @foreach($posts as $post)

            <div class="blog-card">

                @if($post->first_image)
                    <div class="blog-image">
                        <img src="{{ $post->first_image }}" alt="{{ $post->post_title }}">
                    </div>
                @endif

                <div class="blog-content">

                    <div class="category-wrap">
                        @foreach($post->categories->take(1) as $category)
                            <span class="category">
                                {{ $category->name }}
                            </span>
                        @endforeach
                    </div>

                    <h2>
                        <a href="/{{ $post->post_name }}">
                            {{ $post->post_title }}
                        </a>
                    </h2>

                    <div class="post-info">
                        <span>📖 {{ $post->reading_time }} min read</span>
                    </div>

                    <div class="post-meta">

                        <div class="author">
                            <div class="author-avatar">
                                {{ strtoupper(substr(optional($post->author)->display_name ?? 'A', 0, 1)) }}
                            </div>

                            <div class="author-info">
                                <span class="author-name">
                                    {{ optional($post->author)->display_name }}
                                </span>

                                <span class="post-date">
                                    {{ \Carbon\Carbon::parse($post->post_date)->format('d M Y') }}
                                </span>
                            </div>
                        </div>

                        <a href="/{{ $post->post_name }}" class="read-more">
                            Read More →
                        </a>

                    </div>

                </div>

            </div>

        @endforeach

    </div>
</body>

</html>