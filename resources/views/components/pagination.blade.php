@if ($paginator->hasPages())
    <nav class="smart-pagination" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin:0; padding:0;">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span class="page-pill disabled">« Prev</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="page-pill">« Prev</a>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <span class="page-pill dots">{{ $element }}</span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="page-pill active">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="page-pill">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="page-pill">Next »</a>
        @else
            <span class="page-pill disabled">Next »</span>
        @endif
    </nav>

    <style>
        .smart-pagination .page-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 999px; /* Pill shape */
            text-decoration: none;
            transition: all 0.2s;
        }
        .smart-pagination a.page-pill:hover {
            background: var(--hijau, #16a34a);
            color: white;
            border-color: var(--hijau, #16a34a);
        }
        .smart-pagination .page-pill.active {
            background: var(--hijau, #16a34a);
            color: white;
            border-color: var(--hijau, #16a34a);
            cursor: default;
        }
        .smart-pagination .page-pill.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f8fafc;
            color: #94a3b8;
        }
        .smart-pagination .page-pill.dots {
            border: none;
            background: transparent;
            padding: 6px 8px;
        }
    </style>
@endif
