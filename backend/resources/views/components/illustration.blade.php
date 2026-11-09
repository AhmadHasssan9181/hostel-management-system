@props(['name' => 'room', 'class' => 'h-36 w-auto'])

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 240 160" fill="none"
     xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
    @switch($name)
        @case('room')
            <ellipse cx="120" cy="141" rx="88" ry="10" class="fill-line/70" />
            <rect x="24" y="24" width="58" height="50" rx="5" class="fill-brand-50 stroke-ink/25" stroke-width="1.5" />
            <path d="M53 24v50M24 49h58" class="stroke-ink/20" stroke-width="1.2" />
            <circle cx="40" cy="38" r="5" class="fill-accent-300/70" />
            <path d="M31 60c4-4 8-4 12 0s8 4 12 0 8-4 12 0" class="stroke-brand-500" stroke-width="1.4" />

            <path d="M88 62v46M92 62h8a6 6 0 0 1 6 6v34H88" class="fill-brand-100 stroke-ink/25" stroke-width="1.5" />
            <rect x="98" y="78" width="92" height="24" rx="7" class="fill-brand-200/70 stroke-ink/25" stroke-width="1.5" />
            <rect x="104" y="70" width="28" height="13" rx="5" class="fill-surface stroke-ink/25" stroke-width="1.3" />
            <path d="M138 84c8-4 18-4 26 0s16 3 22 0v14c-7 3-15 3-22 0s-17-4-26 0V84Z" class="fill-brand-400/60 stroke-brand-600/60" stroke-width="1.2" />
            <path d="M110 102v15M184 102v15M96 117h98" class="stroke-ink/30" stroke-width="1.6" />

            <rect x="196" y="98" width="26" height="22" rx="3" class="fill-surface stroke-ink/25" stroke-width="1.4" />
            <path d="M209 98V86" class="stroke-ink/30" stroke-width="1.4" />
            <path d="m199 86 10-12 10 12h-20Z" class="fill-accent-200 stroke-accent-500/70" stroke-width="1.2" />

            <path d="M66 132v-14h16v14" class="fill-surface stroke-ink/25" stroke-width="1.3" />
            <path d="M74 118c-1-8-6-11-6-11s7 0 9 6c1-7 7-9 7-9s-1 8-4 14" class="stroke-brand-500" stroke-width="1.4" />

            <path d="m34 108 1.6 4.9 4.9 1.6-4.9 1.6L34 121l-1.6-4.9-4.9-1.6 4.9-1.6L34 108Z" class="fill-accent-300" />
            <circle cx="208" cy="40" r="2" class="fill-brand-400" />
            <circle cx="24" cy="88" r="1.6" class="fill-brand-400" />
            @break

        @case('ticket')
            <ellipse cx="120" cy="141" rx="82" ry="10" class="fill-line/70" />
            <rect x="72" y="22" width="96" height="112" rx="9" class="fill-surface stroke-ink/25" stroke-width="1.6" />
            <rect x="100" y="14" width="40" height="16" rx="5" class="fill-brand-100 stroke-ink/25" stroke-width="1.4" />
            <circle cx="106" cy="48" r="6" class="fill-brand-100 stroke-brand-500/60" stroke-width="1.2" />
            <path d="m103.5 48 2 2 3.5-4" class="stroke-brand-700" stroke-width="1.4" />
            <path d="M120 46h34M120 52h22" class="stroke-ink/20" stroke-width="1.6" />
            <circle cx="106" cy="72" r="6" class="fill-brand-100 stroke-brand-500/60" stroke-width="1.2" />
            <path d="m103.5 72 2 2 3.5-4" class="stroke-brand-700" stroke-width="1.4" />
            <path d="M120 70h34M120 76h26" class="stroke-ink/20" stroke-width="1.6" />
            <circle cx="106" cy="96" r="6" class="fill-surface stroke-ink/25" stroke-width="1.2" />
            <path d="M120 94h34M120 100h18" class="stroke-ink/20" stroke-width="1.6" />

            <path d="M176 70a10 10 0 0 1 3-19 12 12 0 0 1 23 3 9 9 0 0 1-2 17" class="fill-brand-200/80 stroke-brand-600/70" stroke-width="1.4" />
            <path d="M182 74 170 96l-11-3 4-13" class="fill-brand-300/70 stroke-brand-700/60" stroke-width="1.3" />
            <path d="m163 92 4-13" class="stroke-brand-800/60" stroke-width="1.2" />

            <path d="m52 40 1.8 5.4 5.4 1.8-5.4 1.8L52 54l-1.8-5-5.4-1.8 5.4-1.8L52 40Z" class="fill-accent-300" />
            <circle cx="62" cy="108" r="2" class="fill-brand-400" />
            @break

        @case('wallet')
            <ellipse cx="120" cy="141" rx="84" ry="10" class="fill-line/70" />
            <path d="M54 68h112a8 8 0 0 1 8 8v40a8 8 0 0 1-8 8H54a8 8 0 0 1-8-8V76a8 8 0 0 1 8-8Z" class="fill-brand-100 stroke-ink/25" stroke-width="1.6" />
            <path d="M46 84h126v20H46z" class="fill-brand-200/70" />
            <path d="M172 92h16a5 5 0 0 1 0 10h-16a5 5 0 0 1 0-10Z" class="fill-surface stroke-ink/25" stroke-width="1.3" />
            <path d="M158 68V58a8 8 0 0 0-8-8H62" class="stroke-ink/30" stroke-width="1.4" />

            <g transform="rotate(-9 108 58)">
                <rect x="78" y="34" width="62" height="38" rx="6" class="fill-surface stroke-ink/25" stroke-width="1.4" />
                <path d="M78 44h62" class="stroke-ink/20" stroke-width="1.2" />
                <rect x="86" y="52" width="16" height="10" rx="2" class="fill-accent-200" />
                <path d="M110 54h20M110 60h14" class="stroke-ink/25" stroke-width="1.3" />
            </g>

            <path d="M40 112c6-5 14-5 20 0" class="stroke-brand-500" stroke-width="1.4" />
            <circle cx="70" cy="126" r="8" class="fill-accent-300/80 stroke-accent-600/60" stroke-width="1.2" />
            <circle cx="86" cy="130" r="6" class="fill-accent-200 stroke-accent-500/60" stroke-width="1.1" />
            <path d="m206 44 1.7 5.1 5.1 1.7-5.1 1.7-1.7 5.1-1.7-5.1-5.1-1.7 5.1-1.7 1.7-5.1Z" class="fill-accent-300" />
            @break

        @case('bell')
            <ellipse cx="120" cy="141" rx="76" ry="9" class="fill-line/70" />
            <path d="M168 84c0 18 6 26 12 31 2 2 1 5-2 5H62c-3 0-4-3-2-5 6-5 12-13 12-31a48 48 0 0 1 96 0Z" class="fill-brand-100 stroke-ink/25" stroke-width="1.6" />
            <path d="M102 120a18 18 0 0 0 36 0" class="fill-brand-300/80 stroke-brand-600/60" stroke-width="1.4" />
            <path d="M120 22v8" class="stroke-ink/30" stroke-width="1.6" />
            <circle cx="120" cy="18" r="4" class="fill-brand-400 stroke-brand-700/60" stroke-width="1.2" />
            <path d="M52 44 40 36M188 44l12-8M44 76H28M196 76h16" class="stroke-brand-500/70" stroke-width="1.6" />
            <circle cx="58" cy="86" r="2" class="fill-accent-400" />
            <circle cx="184" cy="98" r="2" class="fill-brand-400" />
            <path d="m76 106 1.5 4.5 4.5 1.5-4.5 1.5L76 118l-1.5-4.5L70 112l4.5-1.5L76 106Z" class="fill-accent-300" />
            @break

        @case('building')
            <ellipse cx="120" cy="142" rx="92" ry="9" class="fill-line/70" />
            <rect x="62" y="30" width="116" height="106" rx="6" class="fill-brand-50 stroke-ink/25" stroke-width="1.6" />
            <path d="M56 30h128" class="stroke-ink/30" stroke-width="2" />
            <path d="M76 44h26v22H76zM112 44h26v22h-26zM148 44h16v22h-16zM76 78h26v22H76zM112 78h26v22h-26zM148 78h16v22h-16z" class="fill-surface stroke-ink/20" stroke-width="1.2" />
            <path d="M76 44h26v11H76zM112 44h26v11h-26zM76 78h26v11H76zM112 78h26v11h-26z" class="fill-brand-200/50" />
            <path d="M106 136v-20a6 6 0 0 1 6-6h16a6 6 0 0 1 6 6v20" class="fill-brand-100 stroke-ink/25" stroke-width="1.4" />
            <circle cx="128" cy="123" r="1.4" class="fill-ink/40" />
            <path d="M62 136h116" class="stroke-ink/30" stroke-width="1.8" />
            <path d="M194 136v-22" class="stroke-ink/30" stroke-width="1.6" />
            <path d="M194 114c-8-2-12-8-12-14 7 0 11 4 12 14Zm0 0c8-2 12-8 12-14-7 0-11 4-12 14Z" class="fill-brand-300/80 stroke-brand-600/60" stroke-width="1.2" />
            <circle cx="46" cy="42" r="8" class="fill-accent-300/70" />
            <path d="m205 50 1.6 4.9 4.9 1.6-4.9 1.6-1.6 4.9-1.6-4.9-4.9-1.6 4.9-1.6 1.6-4.9Z" class="fill-brand-300" />
            @break

        @case('box')
            <ellipse cx="120" cy="141" rx="80" ry="9" class="fill-line/70" />
            <path d="m60 84 60-24 60 24v42l-60 22-60-22V84Z" class="fill-brand-100 stroke-ink/25" stroke-width="1.6" />
            <path d="m60 84 60 22 60-22M120 106v42" class="stroke-ink/20" stroke-width="1.3" />
            <path d="m60 84-12-16 52-20 20 12" class="fill-brand-200/80 stroke-ink/25" stroke-width="1.4" />
            <path d="m180 84 12-16-52-20-20 12" class="fill-brand-200/80 stroke-ink/25" stroke-width="1.4" />
            <path d="m120 60 12-9-52-20-12 9 52 20Z" class="fill-brand-300/70 stroke-brand-600/50" stroke-width="1.2" />
            <rect x="94" y="34" width="22" height="26" rx="3" class="fill-surface stroke-ink/25" stroke-width="1.2" transform="rotate(-12 105 47)" />
            <circle cx="142" cy="54" r="10" class="fill-accent-200 stroke-accent-600/60" stroke-width="1.2" />
            <path d="M142 44v20" class="stroke-accent-600/50" stroke-width="1.2" />
            <path d="m186 48 1.6 4.9 4.9 1.6-4.9 1.6-1.6 4.9-1.6-4.9-4.9-1.6 4.9-1.6 1.6-4.9Z" class="fill-accent-300" />
            @break

        @case('users')
            <ellipse cx="120" cy="141" rx="82" ry="9" class="fill-line/70" />
            <circle cx="96" cy="56" r="20" class="fill-brand-100 stroke-ink/25" stroke-width="1.6" />
            <path d="M62 122c0-18 15-30 34-30s34 12 34 30" class="fill-brand-100 stroke-ink/25" stroke-width="1.6" />
            <circle cx="146" cy="62" r="16" class="fill-brand-200/80 stroke-ink/25" stroke-width="1.5" />
            <path d="M120 122c2-15 12-26 26-26s26 11 28 26" class="fill-brand-200/70 stroke-ink/25" stroke-width="1.5" />
            <circle cx="150" cy="100" r="12" class="fill-surface stroke-ink/25" stroke-width="1.3" />
            <path d="M144 100h12M150 94v12" class="stroke-brand-600" stroke-width="1.6" />
            <path d="m54 40 1.7 5.1 5.1 1.7-5.1 1.7L54 53.6l-1.7-5.1-5.1-1.7 5.1-1.7L54 40Z" class="fill-accent-300" />
            @break

        @default
            <ellipse cx="120" cy="141" rx="82" ry="9" class="fill-line/70" />
            <circle cx="120" cy="78" r="42" class="fill-brand-100 stroke-ink/25" stroke-width="1.5" />
            <path d="M104 78h32M120 62v32" class="stroke-brand-600" stroke-width="2" />
    @endswitch
</svg>
