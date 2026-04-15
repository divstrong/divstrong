<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.gtag')
    <title>Accessibility | divStrong</title>
    <meta name="description" content="divStrong's commitment to website accessibility.">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-900 antialiased">

    {{-- Navigation --}}
    <nav class="bg-white border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 lg:h-20">
                <a href="/" class="flex items-center">
                    <img src="{{ asset('images/logo.png') }}" alt="divStrong" class="h-8">
                </a>
                <a href="/" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">&larr; Back to Home</a>
            </div>
        </div>
    </nav>

    {{-- Header --}}
    <section class="bg-gray-50 py-16 lg:py-24 border-b border-gray-100">
        <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
            <p class="text-brand font-semibold text-sm tracking-wide uppercase mb-3">Our Commitment</p>
            <h1 class="text-4xl lg:text-5xl font-extrabold tracking-tight">Accessibility On This Website</h1>
        </div>
    </section>

    {{-- Content --}}
    <section class="py-16 lg:py-24">
        <div class="max-w-3xl mx-auto px-6 lg:px-8 space-y-6 text-gray-600 leading-relaxed text-lg">
            <p>Our website provides several methods, features, and policies that can help with access to our website and/or to products or services provided or referred to on our website or by our business. There are also various aids available by third parties and which are provided by most browsers.</p>

            <p>If you are having difficulty with access to our website even after utilizing any access features within this website and/or any third party or browser features, we invite you to contact us for further assistance. <a href="/#contact" class="text-brand hover:underline">Contact information</a> can be found below.</p>

            <p>This website contains a third-party plugin, also known as a Widget, which is powered by this third party's dedicated accessibility server. Use of this Widget can improve website access for some users, particularly related to certain type of disabilities. This Widget has stated that its application makes efforts to follow to some degree the Web Content Accessibility Guidelines (WCAG 2.1).</p>

            <p>The accessibility menu can be enabled by clicking the accessibility menu icon that appears after clicking on the Widget described above. After triggering the accessibility menu, please wait a moment for the accessibility menu to load in its entirety.</p>

            <h2 class="text-2xl font-bold text-gray-900 pt-6">Disclaimer</h2>

            <p>We anticipate that from time to time, within our resources, we will be making modifications to parts of our website, and possibly modifications to accessibility of our website. Reasonable efforts toward improving seamless, accessible, and unhindered use of websites by customers and potential customers is generally a worthwhile goal. Moving closer to this goal will often depend on the knowledge the company has regarding any particular difficulties those using the website might encounter, as well as available resources and improvements with technology.</p>

            <p>Despite the efforts we may have made regarding accessibility, consistent with normal business practices for a company of our size and resources, some content, features, processes, or policies, may be improved, so we welcome your suggestions.</p>

            <p>Our website may use third-party add-ons or "plugins", such as Google Maps and similar services. These may not work the same for every user and/or every type of disability. We do not have control over the structure of these plugins, and are unable to modify them at all or to the extent that would accommodate every user of our website, and are not responsible for those elements which we cannot control.</p>

            <h2 class="text-2xl font-bold text-gray-900 pt-6">Contact Information</h2>

            <p>If you are experiencing difficulties with any content on our website because of a disability, or if you require assistance with any part of our site because of your particular disability, please contact us and let us know. We will be happy to assist.</p>

            <p>Please reach us by telephone <a href="tel:8043159609" class="text-brand hover:underline">(804) 315-9609</a>, via email by writing to <a href="mailto:support@divstrong.com" class="text-brand hover:underline">support@divstrong.com</a>, or send a letter to 14321 Winter Breeze Drive, Suite #55, Midlothian, VA 23119.</p>

            <p>One of our representatives will follow-up with you in a timely manner and do our best to accommodate.</p>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="bg-neutral-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 text-center">
            <a href="/" class="inline-block mb-4">
                <img src="{{ asset('images/logo.png') }}" alt="divStrong" class="h-8 brightness-0 invert">
            </a>
            <p class="text-sm text-gray-400">&copy; 2009-{{ date('Y') }} divStrong</p>
        </div>
    </footer>

</body>
</html>
