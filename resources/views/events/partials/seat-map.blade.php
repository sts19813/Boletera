{{-- MAPA --}}
<div class="text-center">
    <div style="position: relative; display: inline-block; width:100%;">
        <div class="seat-map-wrapper">
            <div id="seatMap" class="seat-map">
                {{-- PNG --}}
                @if ($lot->png_image)
                    <img src="{{ asset('/' . $lot->png_image) }}" alt="Mapa" />
                @endif

                {{-- SVG --}}
                @if ($lot->svg_image)
                    <div class="seat-svg">
                        {!! file_get_contents(public_path($lot->svg_image)) !!}
                    </div>
                @endif
            </div>

            {{-- CONTROLES MOBILE --}}
            <div class="seat-zoom-controls">
                <button id="zoomIn">+</button>
                <button id="zoomOut">−</button>
            </div>

            {{-- HINT --}}
            <div class="seat-zoom-hint d-lg-none">
                Usa dos dedos para acercar o alejar
            </div>
        </div>
    </div>
</div>