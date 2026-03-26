<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Toernooigenerator</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

        <style>
            :root {
                --font-heading: 'Space Grotesk', sans-serif;
                --font-body: 'Inter', sans-serif;

                --background: 0 0% 100%;
                --foreground: 220 20% 10%;
                --card: 0 0% 100%;
                --card-foreground: 220 20% 10%;
                --primary: 196 99% 45%;
                --primary-foreground: 0 0% 100%;
                --secondary: 220 20% 96%;
                --muted: 220 14% 96%;
                --muted-foreground: 220 10% 46%;
                --accent: 152 60% 94%;
                --accent-foreground: 152 70% 25%;
                --border: 220 13% 91%;
                --input: 220 13% 91%;
                --ring: 152 70% 42%;
                --destructive: 0 84% 60%;
            }

            body {
                font-family: var(--font-body);
                background: hsl(var(--background));
                color: hsl(var(--foreground));
            }

            .font-heading {
                font-family: var(--font-heading);
            }
        </style>
    </head>
    <body>
        <div class="min-h-screen bg-[hsl(var(--background))]">
            <nav class="fixed top-0 left-0 right-0 z-50 border-b bg-white/80 backdrop-blur-xl" style="border-color: hsl(var(--border) / 0.6)">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between h-16">
                        <a href="#" class="flex items-center gap-2.5">
                            <img src="{{ asset('tg_logo_dark.png') }}" alt="Toernooigenerator" class="h-11 w-auto object-contain">
                        </a>

                        <div class="hidden md:flex items-center gap-8 text-sm font-medium">
                            <a href="#functies" class="hover:opacity-100 opacity-70">Functies</a>
                            <a href="#hoe-het-werkt" class="hover:opacity-100 opacity-70">Hoe het werkt</a>
                            <a href="#prijzen" class="hover:opacity-100 opacity-70">Prijzen</a>
                        </div>

                        <div class="hidden md:flex items-center gap-3">
                            <a href="{{ route('login') }}" class="px-3 h-8 inline-flex items-center rounded-md text-xs font-medium">Inloggen</a>
                            <a href="{{ route('register') }}" class="px-3 h-8 inline-flex items-center rounded-md text-xs font-heading font-semibold text-white" style="background: hsl(var(--primary));">Gratis starten</a>
                        </div>
                    </div>
                </div>
            </nav>

            <section class="relative pt-32 pb-20 lg:pt-40 lg:pb-32 overflow-hidden">
                <div class="absolute inset-0 -z-10">
                    <div class="absolute top-20 left-1/2 -translate-x-1/2 w-[800px] h-[800px] rounded-full blur-3xl" style="background: hsl(var(--primary) / 0.08);"></div>
                    <div class="absolute top-40 right-0 w-[400px] h-[400px] rounded-full blur-3xl" style="background: hsl(var(--primary) / 0.08);"></div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center max-w-4xl mx-auto">
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-sm font-medium mb-6 border" style="background: hsl(var(--accent)); color: hsl(var(--accent-foreground)); border-color: hsl(var(--primary) / 0.25);">
                            ⚡ Gratis je eerste toernooi aanmaken
                        </div>

                        <h1 class="font-heading font-bold text-4xl sm:text-5xl lg:text-7xl tracking-tight leading-[1.08]">
                            Maak je toernooi
                            <br>
                            <span style="color: hsl(var(--primary));">in minuten.</span>
                        </h1>

                        <p class="mt-6 text-lg sm:text-xl max-w-2xl mx-auto leading-relaxed" style="color: hsl(var(--muted-foreground));">
                            Genereer automatisch speelschema's, volg live scores en deel resultaten. Alles wat je nodig hebt voor een perfect toernooi.
                        </p>

                        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-md font-heading font-semibold text-base px-8 h-12 text-white" style="background: hsl(var(--primary));">Gratis starten →</a>
                            <a href="#hoe-het-werkt" class="inline-flex items-center justify-center rounded-md font-medium text-base px-8 h-12 border" style="border-color: hsl(var(--input));">Bekijk demo</a>
                        </div>
                    </div>

                    <div class="mt-20 grid grid-cols-1 sm:grid-cols-3 gap-6 max-w-2xl mx-auto">
                        <div class="text-center p-4">
                            <div class="text-2xl font-heading font-bold">&lt; 2 min</div>
                            <div class="text-sm mt-1" style="color: hsl(var(--muted-foreground));">Toernooi aanmaken</div>
                        </div>
                        <div class="text-center p-4">
                            <div class="text-2xl font-heading font-bold">10.000+</div>
                            <div class="text-sm mt-1" style="color: hsl(var(--muted-foreground));">Toernooien gemaakt</div>
                        </div>
                        <div class="text-center p-4">
                            <div class="text-2xl font-heading font-bold">Live</div>
                            <div class="text-sm mt-1" style="color: hsl(var(--muted-foreground));">Scoreborden</div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="functies" class="py-24 lg:py-32" style="background: hsl(var(--muted) / 0.5);">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center max-w-2xl mx-auto mb-16">
                        <p class="text-sm font-semibold tracking-wide uppercase font-heading mb-3" style="color: hsl(var(--primary));">Functies</p>
                        <h2 class="font-heading font-bold text-3xl sm:text-4xl tracking-tight">Alles voor jouw toernooi</h2>
                        <p class="mt-4 text-lg" style="color: hsl(var(--muted-foreground));">Van het aanmaken tot de finale — wij regelen het hele toernooi.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        @foreach ([
                            ['🏟️', 'Meerdere formaten', 'Knockout, round-robin, competitie en meer.'],
                            ['📺', 'Live scoreborden', 'Toon scores, standen en schema\'s in real-time.'],
                            ['⚙️', 'Automatisch schema', 'Voer teams in en genereer direct je schema.'],
                            ['📊', 'Statistieken', 'Volg resultaten en standen automatisch.'],
                            ['📤', 'Exporteren', 'Download schema\'s en resultaten in Excel.'],
                            ['🎨', 'Eigen branding', 'Gebruik je logo, kleuren en sponsoren.'],
                        ] as [$icon, $title, $desc])
                            <div class="rounded-2xl border bg-white p-6 transition-all" style="border-color: hsl(var(--border));">
                                <div class="w-11 h-11 rounded-xl flex items-center justify-center mb-4" style="background: hsl(var(--accent));">{{ $icon }}</div>
                                <h3 class="font-heading font-semibold text-base mb-2">{{ $title }}</h3>
                                <p class="text-sm leading-relaxed" style="color: hsl(var(--muted-foreground));">{{ $desc }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="hoe-het-werkt" class="py-24 lg:py-32">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center max-w-2xl mx-auto mb-16">
                        <p class="text-sm font-semibold tracking-wide uppercase font-heading mb-3" style="color: hsl(var(--primary));">Hoe het werkt</p>
                        <h2 class="font-heading font-bold text-3xl sm:text-4xl tracking-tight">In 3 stappen klaar</h2>
                        <p class="mt-4 text-lg" style="color: hsl(var(--muted-foreground));">Geen gedoe. Geen handmatig plannen. Gewoon snel en simpel.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 lg:gap-12">
                        @foreach ([
                            ['STAP 01', 'Teams invoeren', 'Voeg teams of spelers toe. Individueel of via import.'],
                            ['STAP 02', 'Format kiezen', 'Kies knockout, round-robin of competitie.'],
                            ['STAP 03', 'Starten & volgen', 'Voer scores in en deel live resultaten.'],
                        ] as [$step, $title, $desc])
                            <div class="text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl mb-6 border-2" style="background: hsl(var(--accent)); border-color: hsl(var(--primary) / 0.2);">✅</div>
                                <div class="text-xs font-heading font-bold mb-2 tracking-widest" style="color: hsl(var(--primary) / 0.6);">{{ $step }}</div>
                                <h3 class="font-heading font-bold text-xl mb-3">{{ $title }}</h3>
                                <p class="leading-relaxed max-w-xs mx-auto" style="color: hsl(var(--muted-foreground));">{{ $desc }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="py-24 lg:py-32" style="background: hsl(var(--muted) / 0.5);">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center max-w-2xl mx-auto mb-16">
                        <p class="text-sm font-semibold tracking-wide uppercase font-heading mb-3" style="color: hsl(var(--primary));">Voor iedereen</p>
                        <h2 class="font-heading font-bold text-3xl sm:text-4xl tracking-tight">Wie gebruiken het?</h2>
                        <p class="mt-4 text-lg" style="color: hsl(var(--muted-foreground));">Van lokale voetbalclubs tot grote bedrijfsevenementen.</p>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        @foreach ([
                            ['⚽', 'Sportclubs'],
                            ['🏢', 'Bedrijven'],
                            ['🎮', 'Gaming'],
                            ['🏫', 'Scholen'],
                            ['🏐', 'Evenementen'],
                            ['🏆', 'Federaties'],
                        ] as [$icon, $title])
                            <div class="rounded-2xl border bg-white p-5 sm:p-6 text-center" style="border-color: hsl(var(--border));">
                                <div class="text-3xl sm:text-4xl mb-3">{{ $icon }}</div>
                                <h3 class="font-heading font-semibold text-sm sm:text-base mb-1.5">{{ $title }}</h3>
                                <p class="text-xs sm:text-sm" style="color: hsl(var(--muted-foreground));">Geschikt voor kleine en grote toernooien.</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="prijzen" class="py-24 lg:py-32">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center max-w-2xl mx-auto mb-16">
                        <p class="text-sm font-semibold tracking-wide uppercase font-heading mb-3" style="color: hsl(var(--primary));">Prijzen</p>
                        <h2 class="font-heading font-bold text-3xl sm:text-4xl tracking-tight">Simpel & transparant</h2>
                        <p class="mt-4 text-lg" style="color: hsl(var(--muted-foreground));">Begin gratis. Upgrade wanneer je wilt.</p>
                    </div>

                    @php($plans = config('billing.plans', []))
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8 max-w-6xl mx-auto">
                        @foreach ($plans as $code => $plan)
                            <div @class([
                                'relative rounded-2xl border p-6 lg:p-8 flex flex-col',
                                'shadow-xl' => $code === 'pro',
                            ]) style="{{ $code === 'pro' ? 'border-color: #16a34a; box-shadow: 0 10px 30px rgb(22 163 74 / 0.2);' : 'border-color: hsl(var(--border));' }}">
                                @if ($code === 'pro')
                                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full text-xs font-heading font-semibold text-white" style="background: #16a34a;">
                                        Populairst
                                    </div>
                                @endif

                                <h3 class="font-heading font-bold text-lg">{{ $plan['name'] }}</h3>

                                <div class="mt-3 flex items-baseline gap-1">
                                    <span class="font-heading font-bold text-4xl">€{{ $plan['price_eur'] }}</span>
                                    <span class="text-sm" style="color: hsl(var(--muted-foreground));">
                                        {{ ($plan['price_eur'] ?? 0) > 0 ? '/maand' : 'voor altijd' }}
                                    </span>
                                </div>

                                <ul class="space-y-3 my-8 text-sm">
                                    <li>
                                        {{ is_int($plan['limits']['tournaments'] ?? null) ? 'Tot '.$plan['limits']['tournaments'].' toernooi(en)' : 'Onbeperkt toernooien' }}
                                    </li>
                                    <li>
                                        {{ is_int($plan['limits']['teams'] ?? null) ? 'Tot '.$plan['limits']['teams'].' teams' : 'Onbeperkt teams' }}
                                    </li>
                                    @foreach (($plan['features'] ?? []) as $feature)
                                        <li>{{ \Illuminate\Support\Str::headline((string) $feature) }}</li>
                                    @endforeach
                                </ul>

                                @if ($code === 'enterprise')
                                    <a href="#" class="mt-auto h-10 inline-flex items-center justify-center rounded-md border text-sm font-heading font-semibold" style="border-color: hsl(var(--input));">Neem contact op</a>
                                @elseif (($plan['price_eur'] ?? 0) === 0)
                                    <a href="{{ route('register') }}" class="mt-auto h-10 inline-flex items-center justify-center rounded-md border text-sm font-heading font-semibold" style="border-color: hsl(var(--input));">Gratis beginnen</a>
                                @else
                                    <a href="{{ route('register') }}" class="mt-auto h-10 inline-flex items-center justify-center rounded-md text-sm font-heading font-semibold text-white" style="{{ $code === 'pro' ? 'background: #16a34a;' : 'background: hsl(var(--primary));' }}">Start met {{ $plan['name'] }}</a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <footer>
                <section class="py-20 lg:py-28">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="relative rounded-3xl overflow-hidden p-10 sm:p-14 lg:p-20 text-center" style="background: hsl(var(--foreground)); color: hsl(var(--background));">
                            <h2 class="font-heading font-bold text-3xl sm:text-4xl lg:text-5xl tracking-tight">Klaar om te beginnen?</h2>
                            <p class="mt-4 text-base sm:text-lg opacity-70 max-w-lg mx-auto">Maak gratis je eerste toernooi aan en ontdek hoe makkelijk het is.</p>
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-md mt-8 font-heading font-semibold text-base px-8 h-12 text-white" style="background: hsl(var(--primary));">Gratis starten →</a>
                        </div>
                    </div>
                </section>

                <div class="border-t" style="border-color: hsl(var(--border));">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                            <div class="flex items-center gap-2.5">
                                <img src="{{ asset('tg_logo_dark.png') }}" alt="Toernooigenerator" class="h-10 w-auto object-contain">
                                <span class="font-heading font-bold text-sm">Toernooi<span style="color: hsl(var(--primary));">generator</span></span>
                            </div>

                            <div class="flex items-center gap-6 text-sm" style="color: hsl(var(--muted-foreground));">
                                <a href="#">Privacy</a>
                                <a href="#">Voorwaarden</a>
                                <a href="#">Contact</a>
                            </div>

                            <p class="text-xs" style="color: hsl(var(--muted-foreground));">© 2026 Toernooigenerator. Alle rechten voorbehouden.</p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
