{{-- Five stars for a 0–5 rating, with half stars. Expects $rating. --}}
@php $rating = max(0, min(5, (float) ($rating ?? 0))); @endphp
@for($i = 1; $i <= 5; $i++)
    @if($rating >= $i)
        <i class="bi bi-star-fill"></i>
    @elseif($rating >= $i - 0.5)
        <i class="bi bi-star-half"></i>
    @else
        <i class="bi bi-star text-slate-300"></i>
    @endif
@endfor
