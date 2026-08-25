<?php

namespace App\Filament\App\Pages;

use App\Actions\SubmitRender;
use App\Enums\Orientation;
use App\Enums\SubtitlePosition;
use App\Models\AyahAudio;
use App\Models\Background;
use App\Models\Reciter;
use App\Models\Surah;
use App\Models\TranslationSource;
use App\Models\User;
use App\Support\RenderQuota;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class CreateVideo extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;

    protected static ?string $navigationLabel = 'Create';

    protected static ?string $title = 'Create a video';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    public static function getRoutePath(Panel $panel): string
    {
        return '/';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Create a video';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Pick a reciter, verse range, and background. Bayan stitches the audio and burns the text.';
    }

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'orientation' => Orientation::Vertical->value,
            'show_arabic' => true,
            'show_translation' => true,
            'include_bismillah' => false,
            'loop_background' => true,
            'arabic_style' => config('bayan.default_styles.arabic'),
            'translation_style' => config('bayan.default_styles.translation'),
            'translation_source_id' => TranslationSource::query()->value('id'),
            'reciter_id' => Reciter::query()->value('id'),
            'background_id' => Background::query()->value('id'),
            'surah' => 1,
            'ayah_start' => 1,
            'ayah_end' => 7,
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Recitation')
                    ->description('Who recites, and which verses.')
                    ->columns(2)
                    ->schema([
                        Select::make('reciter_id')
                            ->label('Reciter')
                            ->options(fn () => Reciter::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->columnSpanFull(),
                        Select::make('surah')
                            ->label('Surah')
                            ->options(fn () => Surah::query()->orderBy('number')->get()->mapWithKeys(
                                fn (Surah $surah) => [$surah->number => $surah->number.'. '.$surah->name_en]
                            ))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (mixed $state, Set $set): void {
                                $count = (int) Surah::query()->where('number', $state)->value('ayah_count') ?: 1;
                                $user = Auth::user();
                                $budget = RenderQuota::ayahsPerRender();

                                if ($user instanceof User && $user->remainingRenders() > 0) {
                                    $budget = max($budget, RenderQuota::maxAyahs($user->remainingRenders()));
                                }

                                $set('ayah_start', 1);
                                $set('ayah_end', min($count, $budget));
                            })
                            ->columnSpanFull(),
                        Select::make('ayah_start')
                            ->label('From ayah')
                            ->options(fn (Get $get) => $this->ayahOptions((int) $get('surah')))
                            ->required()
                            ->live(),
                        Select::make('ayah_end')
                            ->label('To ayah')
                            ->options(fn (Get $get) => $this->ayahOptions((int) $get('surah')))
                            ->helperText(fn (Get $get): string => $this->rangeCostPreview($get))
                            ->required()
                            ->live(),
                        Toggle::make('include_bismillah')
                            ->label('Include Bismillah')
                            ->live()
                            ->columnSpanFull(),
                    ]),
                Section::make('Picture')
                    ->description('Background clip and frame.')
                    ->columns(2)
                    ->schema([
                        Radio::make('background_id')
                            ->label('Background')
                            ->options(fn () => $this->backgroundOptions())
                            ->required()
                            ->columns(2)
                            ->live()
                            ->afterStateUpdated(function (mixed $state, Set $set): void {
                                if (! is_numeric($state)) {
                                    return;
                                }

                                $background = Background::query()->find((int) $state);
                                if ($background instanceof Background) {
                                    $set('loop_background', $background->loop_default);
                                }
                            })
                            ->columnSpanFull(),
                        ToggleButtons::make('orientation')
                            ->options([
                                Orientation::Vertical->value => 'Vertical 9:16',
                                Orientation::Horizontal->value => 'Horizontal 16:9',
                            ])
                            ->inline()
                            ->required()
                            ->columnSpanFull(),
                        Checkbox::make('loop_background')
                            ->label('Loop background')
                            ->columnSpanFull(),
                    ]),
                Section::make('Text')
                    ->description('At least one layer must be on.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('show_arabic')->label('Arabic text')->live(),
                        Toggle::make('show_translation')->label('Translation')->live(),
                        Select::make('translation_source_id')
                            ->label('Translation')
                            ->options(fn () => TranslationSource::query()->pluck('name', 'id'))
                            ->visible(fn (Get $get) => (bool) $get('show_translation'))
                            ->required(fn (Get $get) => (bool) $get('show_translation'))
                            ->columnSpanFull(),
                    ]),
                Section::make('Arabic style')
                    ->columns(2)
                    ->collapsed()
                    ->visible(fn (Get $get) => (bool) $get('show_arabic'))
                    ->schema($this->styleFields('arabic_style')),
                Section::make('Translation style')
                    ->columns(2)
                    ->collapsed()
                    ->visible(fn (Get $get) => (bool) $get('show_translation'))
                    ->schema($this->styleFields('translation_style')),
                Section::make()
                    ->columns(2)
                    ->schema([
                        Placeholder::make('quota')
                            ->label('Daily quota')
                            ->content(fn (Get $get): string => $this->quotaPreview($get)),
                        Placeholder::make('duration')
                            ->label('Estimated duration')
                            ->content(fn (Get $get) => $this->durationPreview($get)),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('create')
                    ->footer([
                        Actions::make([
                            Action::make('create')
                                ->label('Generate video')
                                ->submit('create'),
                        ]),
                    ]),
            ]);
    }

    public function create(SubmitRender $submit): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            abort(403);
        }

        $job = $submit->handle($user, $this->form->getState());

        Notification::make()
            ->title($job->cache_hit ? 'Ready instantly' : 'Render queued')
            ->success()
            ->send();

        $this->redirect(route('filament.app.resources.render-jobs.index'));
    }

    /**
     * @return array<int, HtmlString>
     */
    private function backgroundOptions(): array
    {
        $options = [];

        foreach (Background::query()->orderBy('category')->orderBy('label')->get() as $background) {
            $options[$background->id] = $this->backgroundOptionLabel($background);
        }

        return $options;
    }

    private function backgroundOptionLabel(Background $background): HtmlString
    {
        $caption = e($background->label).' · '.e($background->category);
        $url = $background->thumbnailUrl();
        $image = $url === null
            ? ''
            : '<img src="'.e($url).'" alt="'.$caption.'" style="width:100%;height:7rem;object-fit:cover;border-radius:0.5rem;display:block;background:rgba(15,23,42,.35)">';

        return new HtmlString(
            '<span style="display:flex;flex-direction:column;gap:0.5rem">'.$image.'<span>'.$caption.'</span></span>'
        );
    }

    /**
     * @return array<int, int>
     */
    private function ayahOptions(int $surah): array
    {
        $count = (int) Surah::query()->where('number', $surah)->value('ayah_count');

        return array_combine(range(1, max(1, $count)), range(1, max(1, $count)));
    }

    /**
     * @return list<Component>
     */
    private function styleFields(string $prefix): array
    {
        return [
            Select::make("{$prefix}.font")
                ->options(function (): array {
                    $families = config('bayan.fonts.families');

                    if (! is_array($families)) {
                        return [];
                    }

                    $options = [];
                    foreach (array_keys($families) as $name) {
                        if (is_string($name)) {
                            $options[$name] = $name;
                        }
                    }

                    return $options;
                })
                ->required(),
            ColorPicker::make("{$prefix}.color")->required(),
            ColorPicker::make("{$prefix}.outline")->label('Outline')->required(),
            ToggleButtons::make("{$prefix}.position")
                ->options(collect(SubtitlePosition::cases())->mapWithKeys(fn (SubtitlePosition $p) => [$p->value => ucfirst($p->value)]))
                ->inline()
                ->required(),
            Select::make("{$prefix}.size")
                ->options([28 => '28', 32 => '32', 36 => '36', 42 => '42', 48 => '48', 56 => '56', 64 => '64', 72 => '72', 88 => '88'])
                ->required(),
        ];
    }

    private function quotaPreview(Get $get): string
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return '—';
        }

        $remaining = $user->remainingRenders();
        $per = RenderQuota::ayahsPerRender();
        $cost = RenderQuota::creditsFor($this->selectedAyahCount($get));

        return "{$remaining} new renders left today ({$per} ayahs per render). This range uses {$cost}. Cache hits are free.";
    }

    private function rangeCostPreview(Get $get): string
    {
        $ayahs = $this->selectedAyahCount($get);
        $cost = RenderQuota::creditsFor($ayahs);

        if ($cost === 1) {
            return "{$ayahs} ayahs · 1 render";
        }

        return "{$ayahs} ayahs · {$cost} renders";
    }

    private function selectedAyahCount(Get $get): int
    {
        return RenderQuota::ayahCount(
            (int) $get('ayah_start'),
            (int) $get('ayah_end'),
            (bool) $get('include_bismillah'),
            (int) $get('surah'),
        );
    }

    private function durationPreview(Get $get): string
    {
        $reciterId = $get('reciter_id');
        $surah = (int) $get('surah');
        $from = (int) $get('ayah_start');
        $to = (int) $get('ayah_end');

        if (! $reciterId || $from < 1 || $to < $from) {
            return '—';
        }

        $ms = (int) AyahAudio::query()
            ->where('reciter_id', $reciterId)
            ->where('surah', $surah)
            ->whereBetween('ayah', [$from, $to])
            ->sum('duration_ms');

        if ($get('include_bismillah') && $surah !== 1) {
            $ms += (int) AyahAudio::query()
                ->where('reciter_id', $reciterId)
                ->where('surah', 1)
                ->where('ayah', 1)
                ->value('duration_ms');
        }

        if ($ms === 0) {
            return 'Audio not imported for this range.';
        }

        $seconds = intdiv($ms, 1000);

        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}
