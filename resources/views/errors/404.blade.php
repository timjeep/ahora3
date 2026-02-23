<!doctype html>
<title>Page Not Found</title>
<style>
  body { text-align: center; padding: 150px; }
  h1 { font-size: 50px; }
  body { font: 20px Helvetica, sans-serif; color: #333; }
  article { display: block; text-align: left; width: 650px; margin: 0 auto; }
  a { color: #dc8100; text-decoration: none; }
  a:hover { color: #333; text-decoration: none; }
</style>

<article>
    <img src="{{url('/logo.webp')}}" alt="Logo" style="max-width:200px;max-height:200px;background-color:rgb(83, 88, 97);padding: 1rem;border-radius: 0.5rem;">
    <h1>What are you doing here?</h1>
    <div>
        <p>The page you are looking for does not exist.</p>
        @if(config('app.env') == 'local')
            <p style="font-size: 14px; color: #666; margin-top: 0.5rem;"><code>{{ request()->method() }} {{ request()->fullUrl() }}</code></p>
        @endif
        <p>&mdash; {{ config('app.name') }} Team</p>
    </div>
</article>