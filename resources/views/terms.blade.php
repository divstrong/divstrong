<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms & Conditions | divStrong</title>
    <meta name="description" content="divStrong's terms and conditions for digital services.">

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
            <p class="text-brand font-semibold text-sm tracking-wide uppercase mb-3">Our Contract</p>
            <h1 class="text-4xl lg:text-5xl font-extrabold tracking-tight">Terms & Conditions</h1>
        </div>
    </section>

    {{-- Content --}}
    <section class="py-16 lg:py-24">
        <div class="max-w-3xl mx-auto px-6 lg:px-8 text-gray-600 leading-relaxed text-lg">
            <ol class="list-decimal list-outside pl-6 space-y-8">
                <li><strong class="text-brand">ENGAGEMENT.</strong> An initial deposit is required to engage, with progress payments due upon completion of established milestones in the Agreement. The final balance will become due upon successful demonstration of the contracted digital services in accordance with the Project Scope. Any deliverables due will be transferred to Client upon receipt of final payment to settle the outstanding balance.</li>

                <li><strong class="text-brand">ACCURACY.</strong> divStrong shall not be held liable for the accuracy of any information supplied by the Client.</li>

                <li><strong class="text-brand">COPYRIGHT.</strong> divStrong will only use materials that are in accordance with federal copyright laws and the Client agrees to only provide material to divStrong for which they have approval, license and/or permission.</li>

                <li><strong class="text-brand">APPROVALS.</strong> divStrong requires approval of features throughout the life of the project to ensure digital services are being rendered to Client satisfaction. Modifications or revisions to the Project Scope following initial approval will be deemed Change Requests which are subject to further billing at the established hourly rate. Client will be provided written notice of any Change Requests which must be approved prior to those additional services being rendered.</li>

                <li><strong class="text-brand">SATISFACTION.</strong> If at any point, Client becomes unsatisfied with the level of service or quality of work received, Client may request to terminate a project for which a pro-rated refund may be issued for any Incomplete Work. Incomplete Work are tasks specified in the Project Scope which have not been substantially completed or delivered to Client in accordance with the terms of the Agreement.</li>

                <li><strong class="text-brand">PAYMENT.</strong> In the event that a submitted invoice becomes overdue for thirty (30) days or more, divStrong reserves the right to stop providing service without providing further notice.</li>

                <li><strong class="text-brand">CONFIDENTIALITY.</strong> divStrong will not provide Client's confidential information, including but not limited to names, addresses, account credentials and trade secrets, to any third-parties without prior authorization from Client. divStrong agrees to take reasonable precautions to prevent unauthorized disclosure of any confidential information provided by Client.</li>

                <li><strong class="text-brand">EFFECTIVE.</strong> The Agreement is effective on the stated Effective Date and shall continue unless terminated. Both Client and divStrong may terminate the Agreement by providing written notice explaining the reasons for termination, at which point the opposite party will have five (5) days to accept the termination or agree to a way of curing issue that will remedy the breach. Failure to respond within the five (5) day cure period may result in termination of the Agreement.</li>

                <li><strong class="text-brand">TALENT.</strong> Client agrees not to circumvent divStrong in an attempt to independently hire divStrong employees, partners, vendors or sub-contractors which are only known to Client by virtue of working with divStrong, except where explicit permission has been granted in writing by divStrong to waive this stipulation.</li>

                <li><strong class="text-brand">JURISDICTION.</strong> Agreement is subject to the laws of the State of Virginia, and each party hereto hereby submits to the jurisdiction of the state and federal courts of and within the State of Virginia. Signing the Agreement represents a formal contract for which the amount listed as 'Total' is to be paid to DivStrong Productions, LLC in return for performing the services outlined in Project Scope.</li>
            </ol>
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
