{{--
    Meta Pixel. Every event carries an eventID; the server sends the same ID through the Conversions API
    so Meta counts each action once. window.fbTrack({name, data, id}) fires events returned by fetch() calls.
--}}
@php($fbTracking = app(\App\Services\FacebookTracking::class))
@if($fbTracking->pixelEnabled())
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
        document,'script','https://connect.facebook.net/en_US/fbevents.js');

        fbq('init', @js($fbTracking->pixelId()), @js((object) $fbTracking->advancedMatching()));
        fbq('track', 'PageView', {}, { eventID: @js($fbTracking->eventId('pv')) });

        window.fbTrack = function (event) {
            if (window.fbq && event && event.name) fbq('track', event.name, event.data || {}, { eventID: event.id });
        };
        @foreach($fbTracking->browserEvents() as $fbEvent)
            window.fbTrack(@js($fbEvent));
        @endforeach
    </script>
@endif
