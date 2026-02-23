<script setup>
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { AtSign, Building2, Globe, Link, Mail, Phone } from 'lucide-vue-next';
</script>

<template>
    <AppLayout>
        <Head title="Cookies" />
        <div class="font-sans text-gray-900 antialiased">
            <Card class="mx-auto mt-2 w-full p-4">
                <CardTitle class="text-center">
                    <div
                        class="mx-auto flex aspect-square size-32 items-center justify-center"
                    >
                        <AppLogoIcon class="fill-current text-white" />
                    </div>
                    <div>Cookie Policy for MDC</div>
                </CardTitle>
                <CardContent>
                    <p class="mb-4">Cookie Policy (Maintenance Drone Co.)</p>
                    <p class="mb-4">
                        <strong>Effective date:</strong> 13 February 2026<br />
                        <strong>Company:</strong> Maintenance Drone Co. ("MDC",
                        "we", "us", "our")<br />
                        <strong>Websites / App:</strong>
                    </p>
                    <ul class="mb-4 list-disc pl-6">
                        <li>maintenancedrone.ai (the "Site")</li>
                        <li>
                            roofingreimagined.com (the "App" / "Roofing
                            Reimagined")
                        </li>
                    </ul>
                    <p class="mb-6">
                        This Cookie Policy explains how MDC uses cookies and
                        similar technologies (such as pixels, SDKs, and local
                        storage) on our Site and App, what they do, and the
                        choices you have.
                    </p>

                    <h2 class="mb-2 text-lg font-semibold">
                        1) What are cookies?
                    </h2>
                    <p class="mb-4">
                        Cookies are small text files placed on your device when
                        you visit a website or use a web application. Cookies
                        help websites and apps work properly, keep them secure,
                        remember preferences, and understand how the service is
                        used.
                    </p>
                    <p class="mb-2">
                        We may also use similar technologies, such as:
                    </p>
                    <ul class="mb-4 list-disc pl-6">
                        <li>
                            Local storage (browser storage used to store small
                            pieces of data)
                        </li>
                        <li>
                            Pixels / tags (small code snippets used for
                            analytics or measuring performance)
                        </li>
                        <li>
                            SDKs (software development kits, typically used for
                            analytics or error reporting)
                        </li>
                    </ul>
                    <p class="mb-6">
                        For simplicity, we refer to all of these as "cookies" in
                        this policy.
                    </p>

                    <h2 class="mb-2 text-lg font-semibold">
                        2) Why we use cookies
                    </h2>
                    <p class="mb-2">We use cookies to:</p>
                    <ul class="mb-6 list-disc pl-6">
                        <li>
                            Run and secure the App (login, session management,
                            fraud prevention, and security)
                        </li>
                        <li>
                            Remember your settings (language, preferences, and
                            other configuration)
                        </li>
                        <li>
                            Measure performance and usage (analytics, feature
                            adoption, and reliability)
                        </li>
                        <li>
                            Fix bugs and improve the experience (error
                            monitoring and debugging)
                        </li>
                    </ul>

                    <h2 class="mb-2 text-lg font-semibold">
                        3) Types of cookies we use
                    </h2>
                    <p class="mb-4">
                        We typically use the following categories:
                    </p>

                    <h3 class="mb-2 font-semibold">
                        A. Strictly Necessary Cookies (Always On)
                    </h3>
                    <p class="mb-2">
                        These cookies are required for the Site/App to function
                        and cannot be switched off in our systems. They are
                        usually set in response to actions you take, such as
                        logging in, setting privacy preferences, or filling out
                        forms.
                    </p>
                    <p class="mb-2">Examples:</p>
                    <ul class="mb-4 list-disc pl-6">
                        <li>
                            Authentication/session cookies (keep you logged in)
                        </li>
                        <li>
                            Security cookies (help detect abuse and protect
                            accounts)
                        </li>
                        <li>Load-balancing cookies (keep services stable)</li>
                    </ul>

                    <h3 class="mb-2 font-semibold">B. Functional Cookies</h3>
                    <p class="mb-2">
                        These cookies allow the Site/App to remember choices you
                        make and provide enhanced features.
                    </p>
                    <p class="mb-2">Examples:</p>
                    <ul class="mb-4 list-disc pl-6">
                        <li>Remembering UI preferences</li>
                        <li>
                            Keeping selections like filters, sort order, or
                            layout choices
                        </li>
                    </ul>

                    <h3 class="mb-2 font-semibold">
                        C. Analytics / Performance Cookies
                    </h3>
                    <p class="mb-4">
                        These cookies help us understand how people use the
                        Site/App (e.g., pages visited, features used, time on
                        page) so we can improve performance and usability. These
                        cookies may be set by us or by third-party analytics
                        providers.
                    </p>
                    <p class="mb-4">
                        If you use a cookie banner/consent tool, analytics
                        cookies should be optional where required by law.
                    </p>

                    <h3 class="mb-2 font-semibold">
                        D. Advertising / Marketing Cookies (If Used)
                    </h3>
                    <p class="mb-6">
                        If we run marketing campaigns, these cookies may help
                        measure ad performance or show relevant content. If MDC
                        does not currently use advertising cookies, you can
                        remove this section.
                    </p>

                    <h2 class="mb-2 text-lg font-semibold">
                        4) First-party vs third-party cookies
                    </h2>
                    <p class="mb-2">
                        First-party cookies are set by MDC (maintenancedrone.ai
                        / roofingreimagined.com).
                    </p>
                    <p class="mb-6">
                        Third-party cookies are set by service providers we use
                        (for example, analytics, error monitoring, or embedded
                        services). Third parties may collect information about
                        your online activities over time and across different
                        sites, depending on their policies.
                    </p>

                    <h2 class="mb-2 text-lg font-semibold">
                        5) What information cookies may collect
                    </h2>
                    <p class="mb-2">
                        Depending on the cookie type, cookies may collect:
                    </p>
                    <ul class="mb-4 list-disc pl-6">
                        <li>
                            Device and browser information (e.g., browser type,
                            OS)
                        </li>
                        <li>
                            IP address (often used for security, fraud
                            prevention, and approximate location)
                        </li>
                        <li>
                            App usage data (events, page views, feature usage)
                        </li>
                        <li>Unique identifiers tied to your browser/session</li>
                    </ul>
                    <p class="mb-6">
                        We do not use cookies to store sensitive information
                        like passwords.
                    </p>

                    <h2 class="mb-2 text-lg font-semibold">
                        6) Your choices and how to manage cookies
                    </h2>
                    <p class="mb-4">You can control cookies in several ways:</p>

                    <h3 class="mb-2 font-semibold">
                        A. Cookie consent tool (if available)
                    </h3>
                    <p class="mb-4">
                        You may use our cookie banner to accept or reject non-essential cookies (like analytics or marketing).
                    </p>

                    <h3 class="mb-2 font-semibold">B. Browser settings</h3>
                    <p class="mb-2">Most browsers let you:</p>
                    <ul class="mb-2 list-disc pl-6">
                        <li>See what cookies you have and delete them</li>
                        <li>Block third-party cookies</li>
                        <li>
                            Block all cookies (note: this may break login and
                            key features)
                        </li>
                    </ul>
                    <p class="mb-2">Helpful links (browser help pages):</p>
                    <ul class="mb-4 list-disc pl-6">
                        <li>Chrome: cookie controls</li>
                        <li>Safari: cookie settings</li>
                        <li>Firefox: cookie settings</li>
                        <li>Edge: cookie settings</li>
                    </ul>
                    <p class="mb-4">
                          (You can also search: "manage cookies in [your
                        browser name]".)
                    </p>

                    <h3 class="mb-2 font-semibold">
                        C. Opt-out signals (where applicable)
                    </h3>
                    <p class="mb-6">
                        Some regions recognize browser-based opt-out signals
                        (examples: Global Privacy Control, Do Not Sell My Personal Information). If applicable, we will
                        honor such signals to the extent required by law.
                    </p>

                    <h2 class="mb-2 text-lg font-semibold">
                        7) Retention (how long cookies last)
                    </h2>
                    <p class="mb-2">Cookies can be:</p>
                    <ul class="mb-4 list-disc pl-6">
                        <li>
                            Session cookies: deleted when you close your browser
                        </li>
                        <li>
                            Persistent cookies: remain until they expire or you
                            delete them
                        </li>
                    </ul>
                    <p class="mb-6">
                        We keep cookies only as long as needed for their purpose
                        (or as required by law).
                    </p>

                    <h2 class="mb-2 text-lg font-semibold">
                        8) Changes to this Cookie Policy
                    </h2>
                    <p class="mb-6">
                        We may update this policy from time to time to reflect
                        changes in technology, legal requirements, or our
                        practices. We will post the updated version on our
                        Site/App and update the "Effective date" above.
                    </p>

                    <h2 class="mb-2 text-lg font-semibold">9) Contact us</h2>
                    <p class="mb-4">
                        If you have questions about our use of cookies, contact
                        us at:
                    </p>
                    <address class="mb-6 not-italic space-y-1">
                        <div class="flex items-center gap-1">
                            <span class="font-bold text-lg">Maintenance Drone Co.</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <Globe class="shrink-0" />
                            <span>Website: <a target="_blank" href="https://maintenancedrone.ai">maintenancedrone.ai</a></span>
                        </div>
                        <div class="flex items-center gap-1">
                            <Link class="shrink-0" />
                            <span>App: <a target="_blank" href="https://roofingreimagined.com">roofingreimagined.com</a></span>
                        </div>
                        <div class="flex items-center gap-1">
                            <AtSign class="shrink-0" />
                            <span><a target="_blank" href="mailto:info@maintenancedrone.ai">info@maintenancedrone.ai</a></span>
                        </div>
                        <div class="flex items-center gap-1">
                            <Mail class="shrink-0" />
                            <span><a target="_blank" href="https://www.google.com/maps/place/3220+Albion+Rd+S,+Ottawa,+ON+K1V+8Z1/">3220 Albion Rd S., Ottawa, ON K1V 9S6, Canada</a></span>
                        </div>
                        <div class="flex items-center gap-1">
                            <Phone class="shrink-0" />
                            <span><a href="tel:6138317663">613-831-7663 (ROOF)</a></span>
                        </div>
                        <div class="flex items-center gap-1">
                            <Phone class="shrink-0" />
                            <span><a href="tel:+18009200539">1-800-920-0539</a></span>
                        </div>
                    </address>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
