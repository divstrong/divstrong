<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.gtag')
    <title>Privacy Policy | divStrong</title>
    <meta name="description" content="divStrong's privacy policy.">

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
            <p class="text-brand font-semibold text-sm tracking-wide uppercase mb-3">Our Promise</p>
            <h1 class="text-4xl lg:text-5xl font-extrabold tracking-tight">Privacy Policy</h1>
        </div>
    </section>

    {{-- Content --}}
    <section class="py-16 lg:py-24">
        <div class="max-w-3xl mx-auto px-6 lg:px-8 space-y-6 text-gray-600 leading-relaxed text-lg">
            <p>We may ask you to provide us with certain personally identifiable information, including but not limited to Name, Email, Phone, Company, and Title. The information that we request will be retained by us and used as described in this privacy policy. Our website does not use third-party services that collect information used to identify you.</p>

            <p>We want to inform you that whenever you use our website we collect metadata, analytics, and error reporting through both third-party services and server logs. This data may include information such as your device Internet Protocol ("IP") address, device name, operating system version, browser information, the time and date of when you accessed the website, and other related statistics.</p>

            <h2 class="text-2xl font-bold text-gray-900 pt-6">Cookies</h2>

            <p>Cookies are files with a small amount of data that are commonly used as anonymous unique identifiers. These are sent to your browser from the websites that you visit and are stored on your device's internal memory.</p>

            <p>This website does not use cookies explicitly. However, the app may use third-party code and libraries that use cookies to collect information and improve their services. You have the option to either accept or refuse these cookies and know when a cookie is being sent to your device. If you choose to refuse our cookies, you may not be able to use some portions of this website.</p>

            <h2 class="text-2xl font-bold text-gray-900 pt-6">Security</h2>

            <p>We value your trust in providing us your Personal Information, thus we are striving to use commercially acceptable means of protecting it. But remember that no method of transmission over the internet, or method of electronic storage is 100% secure and reliable, and we cannot guarantee its absolute security.</p>

            <h2 class="text-2xl font-bold text-gray-900 pt-6">Links to Other Sites</h2>

            <p>This website may contain links to other sites. If you click on a third-party link, you will be directed to that site. Note that these external sites are not operated by us. Therefore, we strongly advise you to review the privacy policies of these websites. We have no control over and assume no responsibility for the content, privacy policies, or practices of any third-party sites or services.</p>

            <h2 class="text-2xl font-bold text-gray-900 pt-6">Children's Privacy</h2>

            <p>This website is not intended for anyone under the age of 13. We do not knowingly collect personally identifiable information from children under 13 years of age. In the case we discover that a child under 13 has provided us with personal information, we immediately delete this data from our servers. If you are a parent or guardian and you are aware that your child has provided us with personal information, please <a href="/#contact" class="text-brand hover:underline">contact us</a> so that we will be able to take the necessary steps to purge that data.</p>

            <h2 class="text-2xl font-bold text-gray-900 pt-6">Changes to the Privacy Policy</h2>

            <p>We may update our Privacy Policy from time to time. Thus, you are advised to review this page periodically for any changes.</p>

            <p>This policy is effective as of January 1, 2024.</p>

            <h2 class="text-2xl font-bold text-gray-900 pt-6">Contact Us</h2>

            <p>If you have any questions or suggestions about our Privacy Policy, do not hesitate to contact us at <a href="mailto:support@divstrong.com" class="text-brand hover:underline">support@divstrong.com</a>.</p>
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
